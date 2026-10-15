<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Entity;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StockIn;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockInBulkTest extends TestCase
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

        $this->itemA = $this->makeItem($category, 'BRG-001', 'Kertas A4');
        $this->itemB = $this->makeItem($category, 'BRG-002', 'Pulpen Hitam');
    }

    private function makeItem(Category $category, string $code, string $name): Item
    {
        return Item::forceCreate([
            'category_id' => $category->id,
            'code' => $code,
            'name' => $name,
            'unit' => 'pcs',
            'type' => 'ATK',
            'minimum_stock' => 2,
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

    private function payload(array $items, array $override = []): array
    {
        return array_merge([
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudangA->id,
            'date' => now()->toDateString(),
            'supplier' => 'Toko Maju',
            'items' => $items,
        ], $override);
    }

    private function stockOf(Warehouse $warehouse, Item $item): int
    {
        return (int) ItemStock::where('entity_id', $this->entity->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('item_id', $item->id)
            ->value('current_stock');
    }

    public function test_form_stok_masuk_menyediakan_pencarian_dan_multi_barang(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->get(route('stock-ins.create'))
            ->assertOk()
            ->assertSee('Cari kode / nama barang...', false)
            ->assertSee('+ Tambah Barang');
    }

    public function test_stok_masuk_beberapa_barang_sekaligus(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->post(route('stock-ins.store'), $this->payload([
                ['item_id' => $this->itemA->id, 'quantity' => 10],
                ['item_id' => $this->itemB->id, 'quantity' => 4],
            ]))
            ->assertRedirect(route('stock-ins.index'));

        $this->assertSame(10, $this->stockOf($this->gudangA, $this->itemA));
        $this->assertSame(4, $this->stockOf($this->gudangA, $this->itemB));
        $this->assertSame(10, (int) $this->itemA->fresh()->current_stock);
        $this->assertSame(4, (int) $this->itemB->fresh()->current_stock);
        $this->assertSame(2, StockIn::count());
        $this->assertSame('Toko Maju', StockIn::first()->supplier);
    }

    public function test_stok_masuk_menambah_ke_stok_yang_sudah_ada(): void
    {
        ItemStock::forceCreate([
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudangA->id,
            'item_id' => $this->itemA->id,
            'current_stock' => 5,
            'minimum_stock' => 2,
        ]);

        $this->actingAs($this->user('super_admin'))
            ->post(route('stock-ins.store'), $this->payload([
                ['item_id' => $this->itemA->id, 'quantity' => 3],
            ]));

        $this->assertSame(8, $this->stockOf($this->gudangA, $this->itemA));
        $this->assertSame(8, (int) $this->itemA->fresh()->current_stock);
    }

    public function test_semua_dibatalkan_jika_satu_barang_tidak_valid(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->post(route('stock-ins.store'), $this->payload([
                ['item_id' => $this->itemA->id, 'quantity' => 10],
                ['item_id' => $this->itemB->id, 'quantity' => 0],
            ]))
            ->assertSessionHasErrors('items.1.quantity');

        $this->assertSame(0, StockIn::count());
        $this->assertSame(0, ItemStock::count());
    }

    public function test_barang_yang_sama_dua_kali_ditolak(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->post(route('stock-ins.store'), $this->payload([
                ['item_id' => $this->itemA->id, 'quantity' => 1],
                ['item_id' => $this->itemA->id, 'quantity' => 1],
            ]))
            ->assertSessionHasErrors('items.1.item_id');

        $this->assertSame(0, StockIn::count());
    }

    public function test_barang_nonaktif_ditolak(): void
    {
        $this->itemB->update(['is_active' => false]);

        $this->actingAs($this->user('super_admin'))
            ->post(route('stock-ins.store'), $this->payload([
                ['item_id' => $this->itemB->id, 'quantity' => 1],
            ]))
            ->assertSessionHasErrors('items.0.item_id');

        $this->assertSame(0, StockIn::count());
    }

    public function test_admin_tanpa_akses_gudang_tidak_bisa_menambah_stok(): void
    {
        $this->actingAs($this->user('admin', $this->gudangA))
            ->post(route('stock-ins.store'), $this->payload(
                [['item_id' => $this->itemA->id, 'quantity' => 5]],
                ['warehouse_id' => $this->gudangB->id]
            ))
            ->assertSessionHas('error');

        $this->assertSame(0, StockIn::count());
        $this->assertSame(0, ItemStock::count());
    }

    public function test_minimal_satu_barang(): void
    {
        $this->actingAs($this->user('super_admin'))
            ->post(route('stock-ins.store'), $this->payload([]))
            ->assertSessionHasErrors('items');
    }
}
