<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Request as Pengajuan;
use App\Models\RequestDetail;
use App\Models\StockAudit;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockFlowTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entity;
    private Warehouse $gudangA;
    private Warehouse $gudangB;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = Entity::forceCreate([
            'code' => 'ENT1',
            'name' => 'Entitas Satu',
            'is_active' => true,
        ]);

        $this->gudangA = Warehouse::forceCreate([
            'code' => 'GA',
            'name' => 'Gudang A',
            'is_active' => true,
        ]);

        $this->gudangB = Warehouse::forceCreate([
            'code' => 'GB',
            'name' => 'Gudang B',
            'is_active' => true,
        ]);

        $category = Category::forceCreate(['name' => 'Kertas']);

        $this->item = Item::forceCreate([
            'category_id' => $category->id,
            'code' => 'BRG-001',
            'name' => 'Kertas A4',
            'unit' => 'rim',
            'type' => 'ATK',
            'minimum_stock' => 1,
            'current_stock' => 13,
            'is_active' => true,
        ]);

        $this->setStock($this->gudangA, 10);
        $this->setStock($this->gudangB, 3);
    }

    private function setStock(Warehouse $warehouse, int $stock): ItemStock
    {
        return ItemStock::forceCreate([
            'entity_id' => $this->entity->id,
            'warehouse_id' => $warehouse->id,
            'item_id' => $this->item->id,
            'current_stock' => $stock,
            'minimum_stock' => 1,
        ]);
    }

    private function stockOf(Warehouse $warehouse): int
    {
        return (int) ItemStock::where('entity_id', $this->entity->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $this->item->id)
            ->value('current_stock');
    }

    private function makeUser(string $role, ?Warehouse $warehouse = null): User
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

    private function makePengajuan(int $quantity): array
    {
        $requester = $this->makeUser('staff', $this->gudangA);

        $pengajuan = Pengajuan::forceCreate([
            'user_id' => $requester->id,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudangA->id,
            'request_number' => 'REQ-' . uniqid(),
            'request_date' => now()->toDateString(),
            'request_type' => 'stock',
            'status' => 'pending',
        ]);

        $detail = RequestDetail::forceCreate([
            'request_id' => $pengajuan->id,
            'item_id' => $this->item->id,
            'quantity' => $quantity,
        ]);

        return [$pengajuan, $detail];
    }

    public function test_dashboard_menampilkan_barang_stok_terendah(): void
    {
        $rendah = $this->makeSecondItem(1);

        $nonaktif = Item::forceCreate([
            'category_id' => $this->item->category_id,
            'code' => 'BRG-OFF',
            'name' => 'Barang Nonaktif',
            'unit' => 'pcs',
            'type' => 'ATK',
            'minimum_stock' => 1,
            'current_stock' => 0,
            'is_active' => false,
        ]);

        ItemStock::forceCreate([
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudangA->id,
            'item_id' => $nonaktif->id,
            'current_stock' => 0,
            'minimum_stock' => 1,
        ]);

        $this->actingAs($this->makeUser('super_admin'))
            ->get('/dashboard')
            ->assertOk()
            ->assertViewHas('lowStockItems', function ($stocks) use ($rendah) {
                return $stocks->count() === 3
                    && $stocks->first()->item_id === $rendah->id
                    && $stocks->pluck('current_stock')->all() === [1, 3, 10];
            })
            ->assertSee('Pulpen Hitam');
    }

    private function ajukan(int $quantity): RequestDetail
    {
        $staff = $this->makeUser('staff', $this->gudangA);

        $this->actingAs($staff)
            ->post(route('staff.requests.store'), [
                'entity_id' => $this->entity->id,
                'warehouse_id' => $this->gudangA->id,
                'items' => [['item_id' => $this->item->id, 'quantity' => $quantity]],
            ])
            ->assertRedirect(route('staff.requests.index'));

        return RequestDetail::latest('id')->firstOrFail();
    }

    public function test_pengajuan_mencatat_stok_saat_diajukan_tanpa_kekurangan(): void
    {
        $detail = $this->ajukan(4);

        $this->assertSame(10, (int) $detail->stock_at_request);
        $this->assertSame(0, (int) $detail->shortage_quantity);
    }

    public function test_pengajuan_melebihi_stok_mencatat_kekurangan(): void
    {
        $detail = $this->ajukan(15);

        $this->assertSame(10, (int) $detail->stock_at_request);
        $this->assertSame(5, (int) $detail->shortage_quantity);
    }

    public function test_pengajuan_barang_stok_kosong_mencatat_seluruh_jumlah_sebagai_kekurangan(): void
    {
        ItemStock::where('warehouse_id', $this->gudangA->id)->update(['current_stock' => 0]);

        $detail = $this->ajukan(3);

        $this->assertSame(0, (int) $detail->stock_at_request);
        $this->assertSame(3, (int) $detail->shortage_quantity);
    }

    public function test_pengajuan_barang_tanpa_baris_stok_di_lokasi_tetap_tercatat_sebagai_kekurangan(): void
    {
        $baru = $this->makeSecondItem(0);
        ItemStock::where('item_id', $baru->id)->delete();

        $staff = $this->makeUser('staff', $this->gudangA);

        $this->actingAs($staff)
            ->post(route('staff.requests.store'), [
                'entity_id' => $this->entity->id,
                'warehouse_id' => $this->gudangA->id,
                'items' => [['item_id' => $baru->id, 'quantity' => 6]],
            ])
            ->assertRedirect(route('staff.requests.index'));

        $detail = RequestDetail::where('item_id', $baru->id)->firstOrFail();

        $this->assertSame(0, (int) $detail->stock_at_request);
        $this->assertSame(6, (int) $detail->shortage_quantity);
    }

    public function test_approver_bisa_menyetujui_nol_untuk_barang_tanpa_stok(): void
    {
        $baru = $this->makeSecondItem(0);
        ItemStock::where('item_id', $baru->id)->delete();

        $staff = $this->makeUser('staff', $this->gudangA);

        $this->actingAs($staff)->post(route('staff.requests.store'), [
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudangA->id,
            'items' => [['item_id' => $baru->id, 'quantity' => 6]],
        ]);

        $pengajuan = Pengajuan::latest('id')->firstOrFail();
        $detail = $pengajuan->details()->firstOrFail();

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.approve', $pengajuan), [
                'approved_quantity' => [$detail->id => 0],
            ])
            ->assertRedirect(route('approvals.index'));

        $this->assertSame('approved', $pengajuan->fresh()->status);
        $this->assertSame(0, (int) $detail->fresh()->approved_quantity);
        $this->assertSame(0, ItemStock::where('item_id', $baru->id)->count());
    }

    public function test_approver_tidak_bisa_menyetujui_melebihi_stok_walau_pengajuan_melebihi_stok(): void
    {
        $detail = $this->ajukan(15);
        $pengajuan = $detail->request;

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.approve', $pengajuan), [
                'approved_quantity' => [$detail->id => 12],
            ])
            ->assertSessionHas('error');

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame('pending', $pengajuan->fresh()->status);

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.approve', $pengajuan), [
                'approved_quantity' => [$detail->id => 10],
            ])
            ->assertRedirect(route('approvals.index'));

        $this->assertSame(0, $this->stockOf($this->gudangA));
        $this->assertSame(5, (int) $detail->fresh()->shortage_quantity);
    }

    public function test_form_pengajuan_tidak_membatasi_jumlah_dengan_stok(): void
    {
        $this->actingAs($this->makeUser('staff', $this->gudangA))
            ->get(route('staff.requests.create'))
            ->assertOk()
            ->assertSee('shortage-warning', false)
            ->assertSee('boleh melebihi stok');
    }

    public function test_catatan_stok_tidak_berubah_setelah_stok_berubah(): void
    {
        $detail = $this->ajukan(4);

        ItemStock::where('warehouse_id', $this->gudangA->id)->update(['current_stock' => 1]);

        $this->assertSame(10, (int) $detail->fresh()->stock_at_request);
        $this->assertSame(0, (int) $detail->fresh()->shortage_quantity);
    }

    public function test_form_pengajuan_menyediakan_pencarian_barang(): void
    {
        $this->actingAs($this->makeUser('staff', $this->gudangA))
            ->get(route('staff.requests.create'))
            ->assertOk()
            ->assertSee('item-search', false)
            ->assertSee('Cari kode / nama barang...');
    }

    /* ---------------------------------------------------------------
     | Audit stok
     |---------------------------------------------------------------*/

    public function test_audit_menyesuaikan_stok_gudang_dan_total_barang(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)->post('/stock-audits', [
            'audit_date' => now()->toDateString(),
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudangA->id,
            'item_id' => $this->item->id,
            'physical_stock' => 4,
            'reason' => 'Stok opname',
        ])->assertRedirect(route('stock-audits.index'));

        $this->assertSame(4, $this->stockOf($this->gudangA));
        $this->assertSame(3, $this->stockOf($this->gudangB));
        $this->assertSame(7, (int) $this->item->fresh()->current_stock);

        $audit = StockAudit::firstOrFail();
        $this->assertSame(10, $audit->system_stock);
        $this->assertSame(-6, $audit->difference);
    }

    public function test_audit_menolak_gudang_nonaktif(): void
    {
        $this->gudangA->update(['is_active' => false]);

        $this->actingAs($this->makeUser('super_admin'))
            ->post('/stock-audits', [
                'audit_date' => now()->toDateString(),
                'entity_id' => $this->entity->id,
                'warehouse_id' => $this->gudangA->id,
                'item_id' => $this->item->id,
                'physical_stock' => 4,
                'reason' => 'Stok opname',
            ])
            ->assertSessionHasErrors('warehouse_id');

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame(0, StockAudit::count());
    }

    /* ---------------------------------------------------------------
     | Mutasi stok
     |---------------------------------------------------------------*/

    public function test_mutasi_memindahkan_stok_dan_total_barang_tetap(): void
    {
        $this->actingAs($this->makeUser('super_admin'))
            ->post('/stock-transfers', [
                'entity_id' => $this->entity->id,
                'source_warehouse_id' => $this->gudangA->id,
                'destination_warehouse_id' => $this->gudangB->id,
                'items' => [['item_id' => $this->item->id, 'quantity' => 4]],
                'transfer_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('stock-transfers.index'));

        $this->assertSame(6, $this->stockOf($this->gudangA));
        $this->assertSame(7, $this->stockOf($this->gudangB));
        $this->assertSame(13, (int) $this->item->fresh()->current_stock);
        $this->assertSame(1, StockTransfer::count());
    }

    public function test_mutasi_ditolak_jika_stok_asal_tidak_cukup(): void
    {
        $this->actingAs($this->makeUser('super_admin'))
            ->post('/stock-transfers', [
                'entity_id' => $this->entity->id,
                'source_warehouse_id' => $this->gudangA->id,
                'destination_warehouse_id' => $this->gudangB->id,
                'items' => [['item_id' => $this->item->id, 'quantity' => 11]],
                'transfer_date' => now()->toDateString(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame(3, $this->stockOf($this->gudangB));
        $this->assertSame(0, StockTransfer::count());
    }

    private function makeSecondItem(int $stockA): Item
    {
        $second = Item::forceCreate([
            'category_id' => $this->item->category_id,
            'code' => 'BRG-002',
            'name' => 'Pulpen Hitam',
            'unit' => 'pcs',
            'type' => 'ATK',
            'minimum_stock' => 1,
            'current_stock' => $stockA,
            'is_active' => true,
        ]);

        ItemStock::forceCreate([
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudangA->id,
            'item_id' => $second->id,
            'current_stock' => $stockA,
            'minimum_stock' => 1,
        ]);

        return $second;
    }

    public function test_mutasi_beberapa_barang_sekaligus(): void
    {
        $second = $this->makeSecondItem(8);

        $this->actingAs($this->makeUser('super_admin'))
            ->post('/stock-transfers', [
                'entity_id' => $this->entity->id,
                'source_warehouse_id' => $this->gudangA->id,
                'destination_warehouse_id' => $this->gudangB->id,
                'items' => [
                    ['item_id' => $this->item->id, 'quantity' => 4],
                    ['item_id' => $second->id, 'quantity' => 5],
                ],
                'transfer_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('stock-transfers.index'));

        $this->assertSame(6, $this->stockOf($this->gudangA));
        $this->assertSame(7, $this->stockOf($this->gudangB));
        $this->assertSame(13, (int) $this->item->fresh()->current_stock);
        $this->assertSame(8, (int) $second->fresh()->current_stock);
        $this->assertSame(2, StockTransfer::count());
        $this->assertSame(2, StockTransfer::distinct('transfer_number')->count('transfer_number'));
    }

    public function test_mutasi_beberapa_barang_dibatalkan_semua_jika_satu_gagal(): void
    {
        $second = $this->makeSecondItem(2);

        $this->actingAs($this->makeUser('super_admin'))
            ->post('/stock-transfers', [
                'entity_id' => $this->entity->id,
                'source_warehouse_id' => $this->gudangA->id,
                'destination_warehouse_id' => $this->gudangB->id,
                'items' => [
                    ['item_id' => $this->item->id, 'quantity' => 4],
                    ['item_id' => $second->id, 'quantity' => 5],
                ],
                'transfer_date' => now()->toDateString(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame(3, $this->stockOf($this->gudangB));
        $this->assertSame(0, StockTransfer::count());
    }

    public function test_mutasi_menolak_barang_yang_sama_dua_kali(): void
    {
        $this->actingAs($this->makeUser('super_admin'))
            ->post('/stock-transfers', [
                'entity_id' => $this->entity->id,
                'source_warehouse_id' => $this->gudangA->id,
                'destination_warehouse_id' => $this->gudangB->id,
                'items' => [
                    ['item_id' => $this->item->id, 'quantity' => 1],
                    ['item_id' => $this->item->id, 'quantity' => 1],
                ],
                'transfer_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('items.1.item_id');

        $this->assertSame(10, $this->stockOf($this->gudangA));
    }

    public function test_form_mutasi_menyediakan_pencarian_barang(): void
    {
        $this->actingAs($this->makeUser('super_admin'))
            ->get(route('stock-transfers.create'))
            ->assertOk()
            ->assertSee('Cari kode / nama barang...', false)
            ->assertSee('+ Tambah Barang');
    }

    public function test_admin_tanpa_akses_gudang_tujuan_tidak_bisa_mutasi(): void
    {
        $admin = $this->makeUser('admin', $this->gudangA);

        $this->actingAs($admin)
            ->post('/stock-transfers', [
                'entity_id' => $this->entity->id,
                'source_warehouse_id' => $this->gudangA->id,
                'destination_warehouse_id' => $this->gudangB->id,
                'items' => [['item_id' => $this->item->id, 'quantity' => 2]],
                'transfer_date' => now()->toDateString(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame(0, StockTransfer::count());
    }

    /* ---------------------------------------------------------------
     | Approval pengajuan
     |---------------------------------------------------------------*/

    public function test_approve_mengurangi_stok_lokasi_dan_total_barang(): void
    {
        [$pengajuan, $detail] = $this->makePengajuan(5);

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.approve', $pengajuan), [
                'approved_quantity' => [$detail->id => 4],
            ])
            ->assertRedirect(route('approvals.index'));

        $this->assertSame(6, $this->stockOf($this->gudangA));
        $this->assertSame(3, $this->stockOf($this->gudangB));
        $this->assertSame(9, (int) $this->item->fresh()->current_stock);
        $this->assertSame('approved', $pengajuan->fresh()->status);
        $this->assertSame(4, (int) $detail->fresh()->approved_quantity);
    }

    public function test_approve_gagal_jika_stok_tidak_cukup(): void
    {
        [$pengajuan, $detail] = $this->makePengajuan(50);

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.approve', $pengajuan), [
                'approved_quantity' => [$detail->id => 11],
            ])
            ->assertSessionHas('error');

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame('pending', $pengajuan->fresh()->status);
    }

    public function test_approve_ditolak_jika_jumlah_melebihi_permintaan(): void
    {
        [$pengajuan, $detail] = $this->makePengajuan(2);

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.approve', $pengajuan), [
                'approved_quantity' => [$detail->id => 3],
            ])
            ->assertSessionHas('error');

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame('pending', $pengajuan->fresh()->status);
    }

    public function test_pengajuan_tidak_bisa_diproses_dua_kali(): void
    {
        [$pengajuan, $detail] = $this->makePengajuan(3);
        $approver = $this->makeUser('approver', $this->gudangA);

        $payload = ['approved_quantity' => [$detail->id => 3]];

        $this->actingAs($approver)->post(route('approvals.approve', $pengajuan), $payload);
        $this->actingAs($approver)
            ->post(route('approvals.approve', $pengajuan), $payload)
            ->assertSessionHas('error');

        $this->assertSame(7, $this->stockOf($this->gudangA));
    }

    public function test_reject_tidak_mengubah_stok(): void
    {
        [$pengajuan] = $this->makePengajuan(3);

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.reject', $pengajuan), [
                'rejection_reason' => 'stok_tidak_cukup',
                'approver_note' => 'Tidak perlu',
            ])
            ->assertRedirect(route('approvals.index'));

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame('rejected', $pengajuan->fresh()->status);
        $this->assertSame('stok_tidak_cukup', $pengajuan->fresh()->rejection_reason);
        $this->assertSame('Stok tidak cukup / kosong', $pengajuan->fresh()->rejection_reason_label);
    }

    public function test_reject_wajib_memilih_alasan(): void
    {
        [$pengajuan] = $this->makePengajuan(3);

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.reject', $pengajuan), ['approver_note' => 'Tanpa alasan'])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('pending', $pengajuan->fresh()->status);
    }

    public function test_reject_menolak_alasan_tidak_dikenal(): void
    {
        [$pengajuan] = $this->makePengajuan(3);

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.reject', $pengajuan), ['rejection_reason' => 'asal-asalan'])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('pending', $pengajuan->fresh()->status);
    }

    public function test_staff_melihat_alasan_penolakan_pengajuannya(): void
    {
        [$pengajuan] = $this->makePengajuan(3);

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->post(route('approvals.reject', $pengajuan), ['rejection_reason' => 'duplikat']);

        $this->actingAs($pengajuan->user)
            ->get(route('staff.requests.show', $pengajuan->id))
            ->assertOk()
            ->assertSee('Alasan Penolakan')
            ->assertSee('Pengajuan ganda');
    }

    public function test_form_approval_menyediakan_pilihan_alasan_penolakan(): void
    {
        [$pengajuan] = $this->makePengajuan(3);

        $this->actingAs($this->makeUser('approver', $this->gudangA))
            ->get(route('approvals.show', $pengajuan))
            ->assertOk()
            ->assertSee('name="rejection_reason"', false)
            ->assertSee('Stok tidak cukup / kosong');
    }

    public function test_approver_lokasi_lain_mendapat_403(): void
    {
        [$pengajuan, $detail] = $this->makePengajuan(3);

        $this->actingAs($this->makeUser('approver', $this->gudangB))
            ->post(route('approvals.approve', $pengajuan), [
                'approved_quantity' => [$detail->id => 3],
            ])
            ->assertForbidden();

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame('pending', $pengajuan->fresh()->status);
    }
}
