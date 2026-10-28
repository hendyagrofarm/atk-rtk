<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin biasa tidak boleh melihat, mengubah password, menonaktifkan, atau membuat Super Admin.
 */
class AdminCannotTouchSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $super;
    private User $staff;
    private Entity $entity;
    private Warehouse $gudang;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = Entity::forceCreate(['code' => 'ANR', 'name' => 'PT Agrofarm', 'is_active' => true]);
        $this->gudang = Warehouse::forceCreate(['code' => 'GD', 'name' => 'Gudang Pabrik', 'is_active' => true]);

        $this->admin = $this->makeUser('admin', 'Admin GA');
        $this->super = $this->makeUser('super_admin', 'Bos Super');
        $this->staff = $this->makeUser('staff', 'Dilla');
    }

    private function makeUser(string $role, string $name): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . '@test.local',
            'password' => bcrypt('rahasia-lama'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'entity_id' => $role === 'super_admin' ? null : $this->entity->id,
            'warehouse_id' => $role === 'super_admin' ? null : $this->gudang->id,
        ]);
    }

    public function test_daftar_user_admin_tidak_memuat_super_admin(): void
    {
        $this->actingAs($this->admin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('Dilla')
            ->assertDontSee('Bos Super')
            ->assertDontSee('super@');

        // Mencari atau memfilter role super_admin tetap tidak menampilkannya.
        $this->actingAs($this->admin)->get(route('users.index', ['role' => 'super_admin']))
            ->assertOk()->assertDontSee('Bos Super');
        $this->actingAs($this->admin)->get(route('users.index', ['search' => 'Bos']))
            ->assertOk()->assertDontSee('Bos Super');
    }

    public function test_super_admin_tetap_melihat_semua_user(): void
    {
        $this->actingAs($this->super)->get(route('users.index'))
            ->assertOk()->assertSee('Bos Super')->assertSee('Admin GA');
    }

    public function test_admin_tidak_bisa_membuka_atau_mengubah_akun_super_admin(): void
    {
        $this->actingAs($this->admin)->get(route('users.edit', $this->super))->assertForbidden();

        $this->actingAs($this->admin)
            ->put(route('users.update', $this->super), [
                'name' => 'Bos Super',
                'email' => $this->super->email,
                'role' => 'super_admin',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
                'is_active' => 1,
            ])
            ->assertForbidden();

        $this->assertTrue(\Hash::check('rahasia-lama', $this->super->fresh()->password));

        $this->actingAs($this->admin)->delete(route('users.destroy', $this->super))->assertForbidden();
        $this->actingAs($this->admin)->patch(route('users.activate', $this->super))->assertForbidden();
        $this->assertTrue($this->super->fresh()->is_active);
    }

    public function test_admin_tidak_bisa_membuat_atau_menaikkan_user_menjadi_super_admin(): void
    {
        $this->actingAs($this->admin)->get(route('users.create'))
            ->assertOk()->assertDontSee('value="super_admin"', false);

        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Calon Super',
                'email' => 'calon@test.local',
                'role' => 'super_admin',
                'password' => 'password-123',
                'password_confirmation' => 'password-123',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'calon@test.local']);

        $this->actingAs($this->admin)
            ->put(route('users.update', $this->staff), [
                'name' => 'Dilla',
                'email' => $this->staff->email,
                'role' => 'super_admin',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('staff', $this->staff->fresh()->role);
    }

    public function test_admin_tetap_bisa_mengelola_user_biasa(): void
    {
        $this->actingAs($this->admin)->get(route('users.edit', $this->staff))->assertOk();
    }

    public function test_pilihan_pic_dan_atasan_tidak_memuat_super_admin_untuk_admin(): void
    {
        $this->actingAs($this->admin)->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertSee('Dilla')
            ->assertDontSee('Bos Super');

        $this->actingAs($this->super)->get(route('staff.requests.custom.create'))
            ->assertOk()->assertSee('Admin GA');
    }
}
