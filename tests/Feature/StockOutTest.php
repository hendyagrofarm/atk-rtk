<?php

namespace Tests\Feature;

use App\Exports\StockOutReportExport;
use App\Models\Category;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StockOut;
use App\Models\StockOutItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\RequestKpi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request as HttpRequest;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Barang keluar langsung: admin pemegang stok (Pabrik dan HO) mencatat pengeluaran
 * ke karyawan tanpa pengajuan; stok berkurang saat itu juga.
 */
class StockOutTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entity;
    private Warehouse $pabrik;
    private Warehouse $ho;
    private Item $kertas;
    private Item $pulpen;
    private User $adminPabrik;
    private User $adminHo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = Entity::forceCreate(['code' => 'ANR', 'name' => 'PT Agrofarm Nusa Raya', 'is_active' => true]);
        $this->pabrik = Warehouse::forceCreate(['code' => 'GD-PABRIK', 'name' => 'Gudang Pabrik', 'is_active' => true]);
        $this->ho = Warehouse::forceCreate(['code' => 'GD-HO', 'name' => 'Gudang Head Office', 'is_active' => true]);

        $category = Category::forceCreate(['name' => 'ATK']);
        $this->kertas = $this->makeItem($category, 'ATK-001', 'Kertas A4', 'rim');
        $this->pulpen = $this->makeItem($category, 'ATK-002', 'Pulpen Hitam', 'pcs');

        $this->setStock($this->pabrik, $this->kertas, 10);
        $this->setStock($this->pabrik, $this->pulpen, 3);
        $this->setStock($this->ho, $this->kertas, 5);

        $this->adminPabrik = $this->makeUser('admin', 'Admin Pabrik', $this->pabrik);
        $this->adminHo = $this->makeUser('admin', 'Admin HO', $this->ho);
    }

    private function makeItem(Category $category, string $code, string $name, string $unit): Item
    {
        return Item::forceCreate([
            'category_id' => $category->id,
            'code' => $code,
            'name' => $name,
            'unit' => $unit,
            'type' => 'ATK',
            'minimum_stock' => 1,
            'current_stock' => 0,
            'is_active' => true,
        ]);
    }

    private function setStock(Warehouse $warehouse, Item $item, int $stock): void
    {
        ItemStock::forceCreate([
            'entity_id' => $this->entity->id,
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'current_stock' => $stock,
            'minimum_stock' => 1,
        ]);

        $item->update(['current_stock' => $item->itemStocks()->sum('current_stock')]);
    }

    private function makeUser(string $role, string $name, ?Warehouse $warehouse = null): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'entity_id' => $role === 'super_admin' ? null : $this->entity->id,
            'warehouse_id' => $warehouse?->id,
        ]);
    }

    private function payload(Warehouse $warehouse, array $items, array $override = []): array
    {
        return array_merge([
            'entity_id' => $this->entity->id,
            'warehouse_id' => $warehouse->id,
            'issue_date' => now()->toDateString(),
            'recipient_name' => 'Al Haudhu',
            'recipient_department' => 'Affair & Legal - HSE',
            'note' => 'Kebutuhan harian',
            'items' => $items,
        ], $override);
    }

    private function stockOf(Warehouse $warehouse, Item $item): int
    {
        return (int) ItemStock::where('warehouse_id', $warehouse->id)->where('item_id', $item->id)->value('current_stock');
    }

    private function keluarkan(User $by, Warehouse $warehouse, array $items, array $override = [])
    {
        return $this->actingAs($by)->post(route('stock-outs.store'), $this->payload($warehouse, $items, $override));
    }

    /* ---------------------------------------------------------------
     | Mencatat pengeluaran
     |---------------------------------------------------------------*/

    public function test_admin_mengeluarkan_beberapa_barang_dan_stok_langsung_berkurang(): void
    {
        $response = $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 4],
            ['item_id' => $this->pulpen->id, 'quantity' => 2],
        ]);

        $stockOut = StockOut::firstOrFail();
        $response->assertRedirect(route('stock-outs.show', $stockOut));

        $this->assertSame(6, $this->stockOf($this->pabrik, $this->kertas));
        $this->assertSame(1, $this->stockOf($this->pabrik, $this->pulpen));

        // Stok gudang lain tidak tersentuh; total barang mengikuti jumlah semua gudang.
        $this->assertSame(5, $this->stockOf($this->ho, $this->kertas));
        $this->assertSame(11, (int) $this->kertas->fresh()->current_stock);
        $this->assertSame(1, (int) $this->pulpen->fresh()->current_stock);

        $this->assertSame($this->adminPabrik->id, $stockOut->user_id);
        $this->assertSame('Al Haudhu', $stockOut->recipient_name);
        $this->assertSame('Affair & Legal - HSE', $stockOut->recipient_department);
        $this->assertMatchesRegularExpression('/^SKO-\d{8}-0001$/', $stockOut->issue_number);

        $lines = StockOutItem::where('stock_out_id', $stockOut->id)->get()->keyBy('item_id');
        $this->assertSame(4, $lines[$this->kertas->id]->quantity);
        $this->assertSame(10, $lines[$this->kertas->id]->stock_before);
        $this->assertSame(3, $lines[$this->pulpen->id]->stock_before);
    }

    public function test_stok_bisa_dikeluarkan_sampai_habis(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->pulpen->id, 'quantity' => 3],
        ])->assertRedirect();

        $this->assertSame(0, $this->stockOf($this->pabrik, $this->pulpen));
    }

    public function test_semua_dibatalkan_jika_satu_barang_melebihi_stok(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 4],
            ['item_id' => $this->pulpen->id, 'quantity' => 9],
        ])->assertSessionHas('error');

        $this->assertSame(10, $this->stockOf($this->pabrik, $this->kertas));
        $this->assertSame(3, $this->stockOf($this->pabrik, $this->pulpen));
        $this->assertSame(0, StockOut::count());
        $this->assertSame(0, StockOutItem::count());
    }

    public function test_pesan_error_menyebut_barang_dan_stok_tersedia(): void
    {
        $response = $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->pulpen->id, 'quantity' => 9],
        ]);

        $this->assertStringContainsString('Pulpen Hitam', session('error'));
        $this->assertStringContainsString('Stok tersedia: 3', session('error'));
        $response->assertRedirect();
    }

    public function test_barang_tanpa_baris_stok_di_gudang_tidak_bisa_dikeluarkan(): void
    {
        // Pulpen tidak punya stok di Gudang HO.
        $this->keluarkan($this->adminHo, $this->ho, [
            ['item_id' => $this->pulpen->id, 'quantity' => 1],
        ])->assertSessionHas('error');

        $this->assertSame(0, StockOut::count());
    }

    public function test_nomor_berurutan_pada_hari_yang_sama(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [['item_id' => $this->kertas->id, 'quantity' => 1]]);
        $this->keluarkan($this->adminPabrik, $this->pabrik, [['item_id' => $this->kertas->id, 'quantity' => 1]]);

        $this->assertSame(
            [now()->format('\S\K\O-Ymd') . '-0001', now()->format('\S\K\O-Ymd') . '-0002'],
            StockOut::orderBy('id')->pluck('issue_number')->all()
        );
    }

    public function test_validasi_form(): void
    {
        $ok = [['item_id' => $this->kertas->id, 'quantity' => 1]];

        $this->keluarkan($this->adminPabrik, $this->pabrik, $ok, ['recipient_name' => ''])
            ->assertSessionHasErrors('recipient_name');

        $this->keluarkan($this->adminPabrik, $this->pabrik, $ok, ['issue_date' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('issue_date');

        $this->keluarkan($this->adminPabrik, $this->pabrik, [['item_id' => $this->kertas->id, 'quantity' => 0]])
            ->assertSessionHasErrors('items.0.quantity');

        $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 1],
            ['item_id' => $this->kertas->id, 'quantity' => 1],
        ])->assertSessionHasErrors('items.1.item_id');

        $this->keluarkan($this->adminPabrik, $this->pabrik, [])
            ->assertSessionHasErrors('items');

        $this->kertas->update(['is_active' => false]);
        $this->keluarkan($this->adminPabrik, $this->pabrik, $ok)
            ->assertSessionHasErrors('items.0.item_id');

        $this->assertSame(0, StockOut::count());
        $this->assertSame(10, $this->stockOf($this->pabrik, $this->kertas));
    }

    public function test_tanggal_lampau_diperbolehkan_untuk_pencatatan_terlambat(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 1],
        ], ['issue_date' => now()->subDays(3)->toDateString()])->assertRedirect();

        $this->assertSame(now()->subDays(3)->toDateString(), StockOut::first()->issue_date->toDateString());
    }

    /* ---------------------------------------------------------------
     | Hak akses: admin Pabrik dan admin HO terpisah
     |---------------------------------------------------------------*/

    public function test_admin_tidak_bisa_mengeluarkan_dari_gudang_lain(): void
    {
        $this->keluarkan($this->adminHo, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 1],
        ])->assertSessionHas('error');

        $this->assertSame(10, $this->stockOf($this->pabrik, $this->kertas));
        $this->assertSame(0, StockOut::count());
    }

    public function test_admin_hanya_melihat_transaksi_gudangnya_sendiri(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [['item_id' => $this->kertas->id, 'quantity' => 1]], ['recipient_name' => 'Penerima Pabrik']);
        $this->keluarkan($this->adminHo, $this->ho, [['item_id' => $this->kertas->id, 'quantity' => 1]], ['recipient_name' => 'Penerima HO']);

        $this->actingAs($this->adminPabrik)
            ->get(route('stock-outs.index'))
            ->assertOk()
            ->assertSee('Penerima Pabrik')
            ->assertDontSee('Penerima HO');

        $this->actingAs($this->adminHo)
            ->get(route('stock-outs.index'))
            ->assertOk()
            ->assertSee('Penerima HO')
            ->assertDontSee('Penerima Pabrik');

        $super = $this->makeUser('super_admin', 'Super Admin');

        $this->actingAs($super)
            ->get(route('stock-outs.index'))
            ->assertSee('Penerima Pabrik')
            ->assertSee('Penerima HO');
    }

    public function test_bukti_hanya_bisa_dibuka_oleh_yang_berhak(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [['item_id' => $this->kertas->id, 'quantity' => 1]]);
        $stockOut = StockOut::firstOrFail();

        $this->actingAs($this->adminPabrik)->get(route('stock-outs.show', $stockOut))->assertOk();
        $this->actingAs($this->adminHo)->get(route('stock-outs.show', $stockOut))->assertForbidden();
        $this->actingAs($this->makeUser('super_admin', 'Super'))->get(route('stock-outs.show', $stockOut))->assertOk();
    }

    public function test_staff_dan_approver_tidak_bisa_mengakses_barang_keluar(): void
    {
        $staff = $this->makeUser('staff', 'Staff Biasa', $this->pabrik);
        $approver = $this->makeUser('approver', 'Approver', $this->pabrik);

        foreach ([$staff, $approver] as $user) {
            $this->actingAs($user)->get(route('stock-outs.index'))->assertForbidden();
            $this->actingAs($user)->get(route('stock-outs.create'))->assertForbidden();
            $this->actingAs($user)
                ->post(route('stock-outs.store'), $this->payload($this->pabrik, [['item_id' => $this->kertas->id, 'quantity' => 1]]))
                ->assertForbidden();
        }

        $this->assertSame(10, $this->stockOf($this->pabrik, $this->kertas));
    }

    /* ---------------------------------------------------------------
     | Halaman: form, daftar, bukti cetak
     |---------------------------------------------------------------*/

    public function test_form_menampilkan_barang_bersetok_dan_saran_penerima(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [['item_id' => $this->pulpen->id, 'quantity' => 3]], [
            'recipient_name' => 'Wasi',
            'recipient_department' => 'IT',
        ]);

        $this->actingAs($this->adminPabrik)
            ->get(route('stock-outs.create'))
            ->assertOk()
            ->assertSee('Catat Barang Keluar')
            ->assertSee('Cari kode / nama barang...', false)
            ->assertSee('+ Tambah Barang')
            ->assertSee('Kertas A4')
            ->assertSee('Wasi')
            ->assertSee('"department":"IT"', false)
            ->assertDontSee('Pulpen Hitam | Stok: 0', false);
    }

    public function test_daftar_dapat_dicari_dan_dihitung_totalnya(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 4],
            ['item_id' => $this->pulpen->id, 'quantity' => 2],
        ], ['recipient_name' => 'Budi', 'recipient_department' => 'Finance']);

        $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 1],
        ], ['recipient_name' => 'Citra', 'recipient_department' => 'HRD']);

        $html = $this->actingAs($this->adminPabrik)->get(route('stock-outs.index'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/Total Transaksi<\/p>\s*<p[^>]*>2</', $html);
        $this->assertMatchesRegularExpression('/Total Unit Keluar<\/p>\s*<p[^>]*>7</', $html);

        $this->actingAs($this->adminPabrik)
            ->get(route('stock-outs.index', ['search' => 'Finance']))
            ->assertSee('Budi')
            ->assertDontSee('Citra');

        $this->actingAs($this->adminPabrik)
            ->get(route('stock-outs.index', ['search' => 'Pulpen']))
            ->assertSee('Budi')
            ->assertDontSee('Citra');
    }

    public function test_bukti_cetak_berisi_penerima_barang_dan_kotak_tanda_tangan(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 4],
            ['item_id' => $this->pulpen->id, 'quantity' => 2],
        ]);

        $this->actingAs($this->adminPabrik)
            ->get(route('stock-outs.show', StockOut::firstOrFail()))
            ->assertOk()
            ->assertSee('BUKTI PENGELUARAN BARANG')
            ->assertSee('PT AGROFARM NUSA RAYA')
            ->assertSee('Al Haudhu')
            ->assertSee('Affair &amp; Legal - HSE', false)
            ->assertSee('Kertas A4')
            ->assertSee('Pulpen Hitam')
            ->assertSee('TOTAL BARANG KELUAR')
            ->assertSee('Diserahkan oleh')
            ->assertSee('Diterima oleh')
            ->assertSee('Admin Pabrik')
            ->assertSee('agrofarm-logo.png');
    }

    public function test_menu_barang_keluar_tampil_untuk_admin(): void
    {
        $this->actingAs($this->adminPabrik)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Barang Keluar');
    }

    /* ---------------------------------------------------------------
     | Excel dan laporan
     |---------------------------------------------------------------*/

    public function test_export_excel_barang_keluar_langsung(): void
    {
        Excel::fake();

        $this->keluarkan($this->adminPabrik, $this->pabrik, [['item_id' => $this->kertas->id, 'quantity' => 1]]);

        $this->actingAs($this->adminPabrik)
            ->get(route('stock-outs.export-excel'))
            ->assertOk();

        Excel::assertDownloaded('laporan-barang-keluar-langsung.xlsx');
    }

    public function test_laporan_barang_keluar_memuat_pengeluaran_langsung(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 4],
        ], ['recipient_name' => 'Budi']);

        $this->keluarkan($this->adminHo, $this->ho, [
            ['item_id' => $this->kertas->id, 'quantity' => 2],
        ], ['recipient_name' => 'Penerima HO']);

        $html = $this->actingAs($this->adminPabrik)
            ->get(route('reports.stock-out'))
            ->assertOk()
            ->assertSee('Pengeluaran Langsung oleh Admin')
            ->assertSee('Budi')
            ->assertDontSee('Penerima HO')
            ->getContent();

        $this->assertMatchesRegularExpression('/Keluar Langsung \(unit\)<\/p><p[^>]*>4</', $html);

        // Super admin melihat keduanya, dan filter gudang bekerja.
        $super = $this->makeUser('super_admin', 'Super Admin');

        $this->actingAs($super)->get(route('reports.stock-out'))->assertSee('Budi')->assertSee('Penerima HO');
        $this->actingAs($super)
            ->get(route('reports.stock-out', ['warehouse_id' => $this->ho->id]))
            ->assertSee('Penerima HO')
            ->assertDontSee('Budi');
    }

    public function test_excel_laporan_barang_keluar_memuat_baris_pengeluaran_langsung(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [
            ['item_id' => $this->kertas->id, 'quantity' => 4],
        ], ['recipient_name' => 'Budi', 'recipient_department' => 'Finance']);

        $this->actingAs($this->adminPabrik);

        $rows = (new StockOutReportExport(HttpRequest::create('/', 'GET')))->collection();

        $this->assertCount(1, $rows);
        $this->assertMatchesRegularExpression('/^SKO-/', $rows[0][1]);
        $this->assertSame('Budi (Finance)', $rows[0][6]);
        $this->assertSame('Kertas A4', $rows[0][8]);
        $this->assertSame(4, $rows[0][11]);
        $this->assertStringContainsString('Admin Pabrik', $rows[0][12]);
    }

    public function test_pengeluaran_langsung_tidak_memengaruhi_kpi_pengajuan(): void
    {
        $this->keluarkan($this->adminPabrik, $this->pabrik, [['item_id' => $this->kertas->id, 'quantity' => 4]]);

        $summary = RequestKpi::compute($this->makeUser('super_admin', 'Super Admin'))['summary'];

        $this->assertSame(0, $summary['total_requests']);
        $this->assertNull($summary['fulfillment_rate']);
    }
}
