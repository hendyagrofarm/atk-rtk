<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Position;
use App\Models\Request as Pengajuan;
use App\Models\RequestApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $extra = []): User
    {
        static $n = 0;
        $n++;

        return User::forceCreate(array_merge([
            'name' => ucfirst($role) . " $n",
            'email' => "$role$n@test.local",
            'password' => 'x',
            'role' => $role,
            'is_active' => true,
        ], $extra));
    }

    public function test_staff_melihat_dashboard_personal_tanpa_stok_gudang(): void
    {
        $staff = $this->user('staff');

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total Pengajuan Saya')
            ->assertSee('Pengajuan Saya')
            ->assertDontSee('Barang Aktif')
            ->assertDontSee('5 Barang dengan Stok Terendah')
            ->assertDontSee('Stok Masuk Bulan Ini');
    }

    public function test_approver_melihat_antrian_persetujuan_miliknya_saja(): void
    {
        $entity = Entity::create(['name' => 'PT Uji', 'code' => 'UJI']);
        $pos = Position::create(['name' => 'SPV GA']);
        $approver = $this->user('approver', ['position_id' => $pos->id, 'entity_id' => $entity->id]);
        $pembuat = $this->user('staff', ['entity_id' => $entity->id]);

        $request = Pengajuan::forceCreate([
            'user_id' => $pembuat->id, 'entity_id' => $entity->id,
            'request_number' => 'PGJ-UJI-1', 'request_date' => now()->toDateString(),
            'request_type' => 'custom', 'status' => 'pending',
        ]);
        RequestApproval::forceCreate([
            'request_id' => $request->id, 'step_order' => 1, 'label' => 'Diketahui oleh',
            'position_id' => $pos->id, 'position_name' => 'SPV GA', 'status' => 'pending',
        ]);

        $this->actingAs($approver)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Menunggu persetujuan Anda')
            ->assertSee('PGJ-UJI-1')
            ->assertDontSee('Barang Aktif');

        // Approver lain tanpa jabatan yang sama tidak melihat antrian itu.
        $lain = $this->user('approver', ['entity_id' => $entity->id]);
        $this->actingAs($lain)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('PGJ-UJI-1');
    }

    public function test_admin_tetap_memakai_dashboard_stok(): void
    {
        $this->actingAs($this->user('super_admin'))->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Barang Aktif');
    }
}
