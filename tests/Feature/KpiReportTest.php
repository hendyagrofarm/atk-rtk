<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Item;
use App\Models\Request as Pengajuan;
use App\Models\RequestDetail;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\RequestKpi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class KpiReportTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entity;
    private Warehouse $gudangA;
    private Warehouse $gudangB;
    private Item $itemA;
    private Item $itemB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = Entity::forceCreate(['code' => 'ENT1', 'name' => 'Entitas Satu', 'is_active' => true]);
        $this->gudangA = Warehouse::forceCreate(['code' => 'GA', 'name' => 'Gudang A', 'is_active' => true]);
        $this->gudangB = Warehouse::forceCreate(['code' => 'GB', 'name' => 'Gudang B', 'is_active' => true]);

        $category = Category::forceCreate(['name' => 'Kertas']);
        $this->itemA = $this->makeItem($category, 'BRG-A', 'Kertas A4');
        $this->itemB = $this->makeItem($category, 'BRG-B', 'Pulpen');
    }

    private function makeItem(Category $category, string $code, string $name): Item
    {
        return Item::forceCreate([
            'category_id' => $category->id,
            'code' => $code,
            'name' => $name,
            'unit' => 'pcs',
            'type' => 'ATK',
            'minimum_stock' => 1,
            'current_stock' => 0,
            'is_active' => true,
        ]);
    }

    private function user(string $role, ?Warehouse $warehouse = null): User
    {
        return User::forceCreate([
            'name' => ucfirst($role),
            'email' => $role . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'entity_id' => $role === 'super_admin' ? null : $this->entity->id,
            'warehouse_id' => $warehouse?->id,
        ]);
    }

    /**
     * @param  array<int, array{0: Item, 1: int, 2: int, 3: int|null, 4: int|null}>  $details
     *         [item, diminta, disetujui, stok saat pengajuan, kekurangan]
     */
    private function request(
        string $status,
        array $details,
        array $override = [],
        ?int $processHours = null
    ): Pengajuan {
        $requester = $this->user('staff', $this->gudangA);
        $createdAt = now()->subHours(10);

        $request = Pengajuan::forceCreate(array_merge([
            'user_id' => $requester->id,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudangA->id,
            'request_number' => 'REQ-' . uniqid(),
            'request_date' => now()->toDateString(),
            'request_type' => 'stock',
            'status' => $status,
            'approved_at' => $processHours !== null ? $createdAt->copy()->addHours($processHours) : null,
        ], $override));

        DB::table('requests')->where('id', $request->id)->update(['created_at' => $createdAt]);

        foreach ($details as [$item, $quantity, $approved, $stock, $shortage]) {
            RequestDetail::forceCreate([
                'request_id' => $request->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'approved_quantity' => $approved,
                'stock_at_request' => $stock,
                'shortage_quantity' => $shortage,
            ]);
        }

        return $request->fresh();
    }

    private function isiData(): void
    {
        // R1: disetujui sebagian (B hanya 3 dari 5), diproses 2 jam.
        $this->request('approved', [
            [$this->itemA, 10, 10, 20, 0],
            [$this->itemB, 5, 3, 3, 2],
        ], [], 2);

        // R2: ditolak karena stok kosong, diproses 4 jam.
        $this->request('rejected', [
            [$this->itemA, 8, 0, 0, 8],
        ], ['rejection_reason' => 'stok_tidak_cukup'], 4);

        // R3: masih menunggu, tidak masuk tingkat pemenuhan.
        $this->request('pending', [
            [$this->itemA, 4, 0, 20, 0],
        ]);

        // R4: pengajuan custom, tidak dihitung.
        $this->request('approved', [
            [$this->itemA, 99, 99, null, null],
        ], ['request_type' => 'custom'], 1);

        // R5: data lama tanpa catatan stok, disetujui penuh, waktu proses tidak tercatat.
        $this->request('approved', [
            [$this->itemB, 4, 4, null, null],
        ]);
    }

    public function test_ringkasan_kpi_dihitung_dengan_benar(): void
    {
        $this->isiData();

        $kpi = RequestKpi::compute($this->user('super_admin'));
        $summary = $kpi['summary'];

        $this->assertSame(4, $summary['total_requests']);      // R4 custom tidak dihitung
        $this->assertSame(1, $summary['pending']);
        $this->assertSame(2, $summary['approved']);
        $this->assertSame(1, $summary['rejected']);
        $this->assertSame(3, $summary['processed']);

        // Diminta (diproses): 10 + 5 + 8 + 4 = 27, disetujui: 13 + 4 = 17.
        $this->assertSame(27, $summary['units_requested_processed']);
        $this->assertSame(17, $summary['units_approved']);
        $this->assertSame(63.0, $summary['fulfillment_rate']);

        // Hanya R5 yang disetujui penuh dari 3 pengajuan diproses.
        $this->assertSame(33.3, $summary['full_approval_rate']);

        // Kekurangan hanya dihitung dari baris yang punya catatan stok.
        $this->assertSame(5, $summary['detail_total']);
        $this->assertSame(4, $summary['detail_with_snapshot']);
        $this->assertSame(2, $summary['shortage_lines']);
        $this->assertSame(10, $summary['shortage_units']);
        $this->assertSame(1, $summary['empty_lines']);

        // R1 = 2 jam, R2 = 4 jam, R5 tanpa waktu proses dikecualikan.
        $this->assertSame(3.0, $summary['avg_processing_hours']);
    }

    public function test_barang_diurutkan_dari_kekurangan_terbanyak(): void
    {
        $this->isiData();

        $perItem = RequestKpi::compute($this->user('super_admin'))['per_item'];

        $this->assertSame(['BRG-A', 'BRG-B'], array_column($perItem, 'code'));

        $a = $perItem[0];
        $this->assertSame(3, $a['request_lines']);
        $this->assertSame(22, $a['units_requested']);
        $this->assertSame(10, $a['units_approved']);
        $this->assertSame(1, $a['shortage_lines']);
        $this->assertSame(8, $a['shortage_units']);
        $this->assertSame(1, $a['empty_lines']);

        $b = $perItem[1];
        $this->assertSame(2, $b['request_lines']);
        $this->assertSame(9, $b['units_requested']);
        $this->assertSame(7, $b['units_approved']);
        $this->assertSame(2, $b['shortage_units']);
    }

    public function test_alasan_penolakan_dikelompokkan_termasuk_data_lama(): void
    {
        $this->isiData();

        $this->request('rejected', [[$this->itemA, 1, 0, 5, 0]], [], 1);
        $this->request('rejected', [[$this->itemA, 1, 0, 5, 0]], ['rejection_reason' => 'duplikat'], 1);

        $reasons = collect(RequestKpi::compute($this->user('super_admin'))['rejection_reasons'])
            ->pluck('total', 'label')
            ->all();

        $this->assertSame(1, $reasons['Stok tidak cukup / kosong']);
        $this->assertSame(1, $reasons['Pengajuan ganda']);
        $this->assertSame(1, $reasons['Tanpa alasan (data lama)']);
    }

    public function test_filter_gudang_dan_tanggal(): void
    {
        $this->isiData();

        $this->request('approved', [[$this->itemA, 6, 6, 6, 0]], [
            'warehouse_id' => $this->gudangB->id,
            'request_date' => now()->subDays(40)->toDateString(),
        ], 1);

        $super = $this->user('super_admin');

        $this->assertSame(5, RequestKpi::compute($super)['summary']['total_requests']);

        $this->assertSame(1, RequestKpi::compute($super, [
            'warehouse_id' => $this->gudangB->id,
        ])['summary']['total_requests']);

        $this->assertSame(4, RequestKpi::compute($super, [
            'start_date' => now()->subDays(7)->toDateString(),
        ])['summary']['total_requests']);
    }

    public function test_admin_hanya_melihat_lokasi_yang_diizinkan(): void
    {
        $this->isiData();

        $this->request('approved', [[$this->itemA, 6, 6, 6, 0]], ['warehouse_id' => $this->gudangB->id], 1);

        $admin = $this->user('admin', $this->gudangA);

        $this->assertSame(4, RequestKpi::compute($admin)['summary']['total_requests']);

        // Filter ke gudang yang tidak diizinkan diabaikan, bukan dibuka.
        $this->assertSame(4, RequestKpi::compute($admin, [
            'warehouse_id' => $this->gudangB->id,
        ])['summary']['total_requests']);
    }

    public function test_data_kosong_tidak_menyebabkan_error_pembagian(): void
    {
        $summary = RequestKpi::compute($this->user('super_admin'))['summary'];

        $this->assertSame(0, $summary['total_requests']);
        $this->assertNull($summary['fulfillment_rate']);
        $this->assertNull($summary['full_approval_rate']);
        $this->assertNull($summary['avg_processing_hours']);
    }

    public function test_halaman_kpi_tampil_untuk_admin_dan_super_admin(): void
    {
        $this->isiData();

        $this->actingAs($this->user('super_admin'))
            ->get(route('reports.kpi'))
            ->assertOk()
            ->assertSee('Laporan KPI Pengajuan')
            ->assertSee('63,0%')
            ->assertSee('Barang Paling Sering Kurang')
            ->assertSee('Stok tidak cukup / kosong');

        $this->actingAs($this->user('admin', $this->gudangA))
            ->get(route('reports.kpi'))
            ->assertOk();
    }

    public function test_tabel_barang_kurang_hanya_memuat_barang_yang_pernah_kurang(): void
    {
        $this->isiData();

        $tidakPernahKurang = $this->makeItem($this->itemA->category, 'BRG-OK', 'Barang Aman');
        $this->request('approved', [[$tidakPernahKurang, 2, 2, 50, 0]], [], 1);

        $this->actingAs($this->user('super_admin'))
            ->get(route('reports.kpi'))
            ->assertOk()
            ->assertSee('Kertas A4')
            ->assertSee('Pulpen')
            ->assertDontSee('Barang Aman');

        // Barang tersebut tetap ada di data lengkap untuk Excel.
        $semua = array_column(RequestKpi::compute($this->user('super_admin'))['per_item'], 'code');
        $this->assertContains('BRG-OK', $semua);
    }

    public function test_halaman_kpi_menampilkan_peringatan_cakupan_catatan_stok(): void
    {
        $this->isiData();

        $this->actingAs($this->user('super_admin'))
            ->get(route('reports.kpi'))
            ->assertOk()
            ->assertSee('4 dari 5 barang');
    }

    public function test_halaman_kpi_dilarang_untuk_staff_dan_approver(): void
    {
        $this->actingAs($this->user('staff', $this->gudangA))
            ->get(route('reports.kpi'))
            ->assertForbidden();

        $this->actingAs($this->user('approver', $this->gudangA))
            ->get(route('reports.kpi'))
            ->assertForbidden();
    }

    public function test_tanggal_akhir_sebelum_tanggal_mulai_ditolak(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->get(route('reports.kpi', ['start_date' => '2026-10-10', 'end_date' => '2026-10-01']))
            ->assertSessionHasErrors('end_date');
    }

    public function test_export_excel_kpi(): void
    {
        $this->isiData();
        Excel::fake();

        $this->actingAs($this->user('super_admin'))
            ->get(route('reports.kpi.export-excel'))
            ->assertOk();

        Excel::assertDownloaded('laporan-kpi-pengajuan.xlsx');
    }

    public function test_tab_kpi_ada_di_laporan_lain(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->get(route('reports.requests'))
            ->assertOk()
            ->assertSee('KPI Pengajuan');
    }
}
