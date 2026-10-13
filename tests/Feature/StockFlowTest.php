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
                'item_id' => $this->item->id,
                'quantity' => 4,
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
                'item_id' => $this->item->id,
                'quantity' => 11,
                'transfer_date' => now()->toDateString(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame(3, $this->stockOf($this->gudangB));
        $this->assertSame(0, StockTransfer::count());
    }

    public function test_admin_tanpa_akses_gudang_tujuan_tidak_bisa_mutasi(): void
    {
        $admin = $this->makeUser('admin', $this->gudangA);

        $this->actingAs($admin)
            ->post('/stock-transfers', [
                'entity_id' => $this->entity->id,
                'source_warehouse_id' => $this->gudangA->id,
                'destination_warehouse_id' => $this->gudangB->id,
                'item_id' => $this->item->id,
                'quantity' => 2,
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
            ->post(route('approvals.reject', $pengajuan), ['approver_note' => 'Tidak perlu'])
            ->assertRedirect(route('approvals.index'));

        $this->assertSame(10, $this->stockOf($this->gudangA));
        $this->assertSame('rejected', $pengajuan->fresh()->status);
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
