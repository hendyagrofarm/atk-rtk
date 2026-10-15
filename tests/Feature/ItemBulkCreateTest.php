<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemBulkCreateTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::forceCreate(['name' => 'Alat Tulis']);
    }

    private function admin(): User
    {
        return User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin' . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function row(string $code, array $override = []): array
    {
        return array_merge([
            'code' => $code,
            'name' => 'Barang ' . $code,
            'category_id' => $this->category->id,
            'type' => 'ATK',
            'unit' => 'pcs',
            'minimum_stock' => 5,
            'storage_location' => 'Lemari A',
        ], $override);
    }

    public function test_form_tambah_barang_tidak_punya_isian_stok_saat_ini(): void
    {
        $this->actingAs($this->admin())
            ->get(route('items.create'))
            ->assertOk()
            ->assertSee('+ Tambah Barang')
            ->assertDontSee('current_stock')
            ->assertDontSee('Stok Saat Ini');
    }

    public function test_beberapa_barang_tersimpan_sekaligus_dengan_stok_nol(): void
    {
        $this->actingAs($this->admin())
            ->post(route('items.store'), [
                'items' => [$this->row('A001'), $this->row('A002'), $this->row('R001', ['type' => 'RTK'])],
            ])
            ->assertRedirect(route('items.index'));

        $this->assertSame(3, Item::count());
        $this->assertSame(0, (int) Item::where('code', 'A001')->value('current_stock'));
        $this->assertSame('RTK', Item::where('code', 'R001')->value('type'));
        $this->assertTrue((bool) Item::where('code', 'A002')->value('is_active'));
    }

    public function test_stok_yang_dikirim_manual_diabaikan(): void
    {
        $this->actingAs($this->admin())
            ->post(route('items.store'), [
                'items' => [$this->row('A001', ['current_stock' => 999])],
            ])
            ->assertRedirect(route('items.index'));

        $this->assertSame(0, (int) Item::where('code', 'A001')->value('current_stock'));
    }

    public function test_semua_dibatalkan_jika_satu_barang_tidak_valid(): void
    {
        $this->actingAs($this->admin())
            ->post(route('items.store'), [
                'items' => [$this->row('A001'), $this->row('A002', ['name' => ''])],
            ])
            ->assertSessionHasErrors('items.1.name');

        $this->assertSame(0, Item::count());
    }

    public function test_kode_ganda_dalam_satu_form_ditolak(): void
    {
        $this->actingAs($this->admin())
            ->post(route('items.store'), [
                'items' => [$this->row('A001'), $this->row('a001')],
            ])
            ->assertSessionHasErrors(['items.0.code', 'items.1.code']);

        $this->assertSame(0, Item::count());
    }

    public function test_kode_yang_sudah_ada_di_database_ditolak(): void
    {
        $this->actingAs($this->admin())->post(route('items.store'), ['items' => [$this->row('A001')]]);

        $this->actingAs($this->admin())
            ->post(route('items.store'), ['items' => [$this->row('A001', ['name' => 'Lain']), $this->row('A002')]])
            ->assertSessionHasErrors('items.0.code');

        $this->assertSame(1, Item::count());
    }

    public function test_minimal_satu_barang(): void
    {
        $this->actingAs($this->admin())
            ->post(route('items.store'), ['items' => []])
            ->assertSessionHasErrors('items');
    }

    public function test_form_edit_tidak_menampilkan_stok_saat_ini(): void
    {
        $item = Item::forceCreate([
            'category_id' => $this->category->id,
            'code' => 'E001',
            'name' => 'Barang Edit',
            'unit' => 'pcs',
            'type' => 'ATK',
            'minimum_stock' => 1,
            'current_stock' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('items.edit', $item))
            ->assertOk()
            ->assertDontSee('Stok Saat Ini');
    }
}
