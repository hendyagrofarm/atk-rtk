<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Entity;
use App\Models\Position;
use App\Models\Request as Pengajuan;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pengajuan custom (FPB) terpisah antar departemen: atasan langsung hanya melihat dan
 * memproses pengajuan yang menunjuknya, sedangkan Spv. GA dan Manager HCGA (tahap tetap)
 * melihat semua departemen. Tahap tetap bernama "atasan" tidak boleh membuat approval dobel.
 */
class DepartmentIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entity;
    private Warehouse $gudang;
    private Position $spv;
    private Position $manager;
    private User $admin;
    private User $atasanFinance;
    private User $atasanHrd;
    private User $atasanStaffRole;
    private User $approverLain;
    private User $spvUser;
    private User $managerUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->entity = Entity::forceCreate(['code' => 'ANR', 'name' => 'PT Agrofarm Nusa Raya', 'is_active' => true]);
        $this->gudang = Warehouse::forceCreate(['code' => 'GD', 'name' => 'Gudang Pabrik', 'is_active' => true]);

        $this->spv = Position::create(['name' => 'Spv. GA']);
        $this->manager = Position::create(['name' => 'Manager HCGA']);

        ApprovalStep::create(['step_order' => 1, 'label' => 'Diketahui oleh', 'position_id' => $this->spv->id, 'is_active' => true]);
        ApprovalStep::create(['step_order' => 2, 'label' => 'Disetujui oleh', 'position_id' => $this->manager->id, 'is_active' => true]);

        $this->admin = $this->makeUser('admin', 'Admin GA', null, $this->gudang);

        // Semua atasan berada di entitas dan gudang yang sama: pemisahan harus bukan karena lokasi.
        $this->atasanFinance = $this->makeUser('approver', 'Atasan Finance', null, $this->gudang);
        $this->atasanHrd = $this->makeUser('approver', 'Atasan HRD', null, $this->gudang);
        $this->atasanStaffRole = $this->makeUser('staff', 'Atasan Berakun Staff', null, $this->gudang);
        $this->approverLain = $this->makeUser('approver', 'Approver Lokasi', null, $this->gudang);

        $this->spvUser = $this->makeUser('approver', 'Yudi Ardiyanto', $this->spv);
        $this->managerUser = $this->makeUser('approver', 'Muhammad Rudi', $this->manager);
    }

    private function makeUser(string $role, string $name, ?Position $position = null, ?Warehouse $warehouse = null): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $warehouse?->id,
            'position_id' => $position?->id,
        ]);
    }

    private function buatFpb(User $atasan, string $departemen): Pengajuan
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), [
                'entity_id' => $this->entity->id,
                'department' => $departemen,
                'pic_name' => 'PIC ' . $departemen,
                'superior_user_id' => $atasan->id,
                'items' => [[
                    'custom_item_name' => 'Barang ' . $departemen,
                    'budget_type' => 'OPEX',
                    'quantity' => 1,
                    'unit' => 'pcs',
                ]],
            ])
            ->assertRedirect(route('staff.requests.index'));

        return Pengajuan::latest('id')->firstOrFail();
    }

    private function setujui(User $user, Pengajuan $pengajuan)
    {
        return $this->actingAs($user)->post(route('approvals.approve', $pengajuan));
    }

    /* ---------------------------------------------------------------
     | Atasan hanya melihat pengajuan departemennya
     |---------------------------------------------------------------*/

    public function test_atasan_departemen_lain_tidak_bisa_melihat_atau_memproses(): void
    {
        $finance = $this->buatFpb($this->atasanFinance, 'Finance');

        $this->actingAs($this->atasanHrd)->get(route('approvals.show', $finance))->assertForbidden();
        $this->setujui($this->atasanHrd, $finance)->assertForbidden();
        $this->actingAs($this->atasanHrd)
            ->post(route('approvals.reject', $finance), ['rejection_reason' => 'duplikat'])
            ->assertForbidden();

        $this->assertSame('pending', $finance->fresh()->approvals[0]->status);
    }

    public function test_daftar_approval_atasan_hanya_berisi_departemennya_sendiri(): void
    {
        $finance = $this->buatFpb($this->atasanFinance, 'Finance');
        $hrd = $this->buatFpb($this->atasanHrd, 'HRD');

        $this->actingAs($this->atasanFinance)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee($finance->request_number)
            ->assertDontSee($hrd->request_number);

        $this->actingAs($this->atasanHrd)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee($hrd->request_number)
            ->assertDontSee($finance->request_number);
    }

    public function test_atasan_berakun_staff_juga_terpisah(): void
    {
        $milikStaff = $this->buatFpb($this->atasanStaffRole, 'Logistik');
        $milikFinance = $this->buatFpb($this->atasanFinance, 'Finance');

        $this->actingAs($this->atasanStaffRole)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee($milikStaff->request_number)
            ->assertDontSee($milikFinance->request_number);

        $this->actingAs($this->atasanStaffRole)->get(route('approvals.show', $milikFinance))->assertForbidden();
        $this->actingAs($this->atasanFinance)->get(route('approvals.show', $milikStaff))->assertForbidden();
    }

    public function test_approver_biasa_satu_lokasi_tidak_melihat_fpb_departemen_mana_pun(): void
    {
        $finance = $this->buatFpb($this->atasanFinance, 'Finance');

        $this->actingAs($this->approverLain)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertDontSee($finance->request_number);

        $this->actingAs($this->approverLain)->get(route('approvals.show', $finance))->assertForbidden();
        $this->setujui($this->approverLain, $finance)->assertForbidden();
    }

    public function test_approver_tetap_melihat_pengajuan_stok_di_lokasinya(): void
    {
        $stok = Pengajuan::forceCreate([
            'user_id' => $this->admin->id,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudang->id,
            'request_number' => 'REQ-STOK-LOKASI',
            'request_date' => now()->toDateString(),
            'request_type' => 'stock',
            'status' => 'pending',
        ]);

        $this->buatFpb($this->atasanFinance, 'Finance');

        $this->actingAs($this->approverLain)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee('REQ-STOK-LOKASI');

        $this->actingAs($this->approverLain)->get(route('approvals.show', $stok))->assertOk();
    }

    public function test_admin_pembuat_tetap_melihat_semua_fpb_di_lokasinya(): void
    {
        $finance = $this->buatFpb($this->atasanFinance, 'Finance');
        $hrd = $this->buatFpb($this->atasanHrd, 'HRD');

        $this->actingAs($this->admin)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee($finance->request_number)
            ->assertSee($hrd->request_number);

        $this->actingAs($this->admin)->get(route('approvals.show', $finance))->assertOk();
    }

    public function test_spv_ga_dan_manager_hcga_melihat_pengajuan_semua_departemen(): void
    {
        $finance = $this->buatFpb($this->atasanFinance, 'Finance');
        $hrd = $this->buatFpb($this->atasanHrd, 'HRD');

        foreach ([$this->spvUser, $this->managerUser] as $penyetuju) {
            $this->actingAs($penyetuju)
                ->get(route('approvals.index'))
                ->assertOk()
                ->assertSee($finance->request_number)
                ->assertSee($hrd->request_number);
        }
    }

    public function test_atasan_departemen_lain_tidak_bisa_mencetak_fpb_yang_sudah_selesai(): void
    {
        $finance = $this->buatFpb($this->atasanFinance, 'Finance');

        foreach ([$this->atasanFinance, $this->spvUser, $this->managerUser] as $penyetuju) {
            $this->setujui($penyetuju, $finance)->assertRedirect(route('approvals.index'));
        }

        $this->actingAs($this->atasanFinance)->get(route('fpb.print', $finance))->assertOk();
        $this->actingAs($this->atasanHrd)->get(route('fpb.print', $finance))->assertForbidden();
        $this->actingAs($this->approverLain)->get(route('fpb.print', $finance))->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Tahap tetap bernama "atasan" tidak boleh membuat approval dobel
     |---------------------------------------------------------------*/

    private function buatTahapAtasanTetap(): ApprovalStep
    {
        $posisi = Position::create(['name' => 'Atasan Langsung']);

        // Tahap peninggalan pengaturan lama: dimasukkan langsung ke tabel di urutan pertama.
        // Naikkan dari nomor terbesar agar tidak bentrok dengan aturan unik step_order.
        ApprovalStep::query()->orderByDesc('step_order')->get()
            ->each(fn (ApprovalStep $step) => $step->update(['step_order' => $step->step_order + 1]));

        return ApprovalStep::create([
            'step_order' => 1,
            'label' => 'Diketahui oleh',
            'position_id' => $posisi->id,
            'is_active' => true,
        ]);
    }

    public function test_tahap_tetap_atasan_dilewati_bila_atasan_dipilih_per_pengajuan(): void
    {
        $this->buatTahapAtasanTetap();

        $fpb = $this->buatFpb($this->atasanFinance, 'Finance');

        $this->assertSame(
            ['Atasan Langsung', 'Spv. GA', 'Manager HCGA'],
            $fpb->approvals->pluck('position_name')->all()
        );
        $this->assertSame($this->atasanFinance->id, $fpb->approvals[0]->assigned_user_id);
        $this->assertSame([1, 2, 3], $fpb->approvals->pluck('step_order')->all());
        $this->assertSame(1, $fpb->approvals->where('position_name', 'Atasan Langsung')->count());
    }

    public function test_tidak_bisa_menambah_tahap_tetap_bernama_atasan(): void
    {
        $super = $this->makeUser('super_admin', 'Super Admin');
        $posisi = Position::create(['name' => 'Atasan Langsung']);

        $this->actingAs($super)
            ->post(route('approval-steps.store'), ['label' => 'Diketahui oleh', 'position_id' => $posisi->id])
            ->assertSessionHasErrors('position_id');

        $this->assertSame(0, ApprovalStep::where('position_id', $posisi->id)->count());
    }

    public function test_tahap_lama_bernama_atasan_bisa_dinonaktifkan_dan_dihapus(): void
    {
        $super = $this->makeUser('super_admin', 'Super Admin');
        $tahap = $this->buatTahapAtasanTetap();

        // Menonaktifkan (kotak aktif tidak dicentang) diperbolehkan.
        $this->actingAs($super)
            ->put(route('approval-steps.update', $tahap), ['label' => 'Diketahui oleh', 'position_id' => $tahap->position_id])
            ->assertRedirect(route('approval-steps.index'));

        $this->assertFalse($tahap->fresh()->is_active);

        // Mengaktifkannya kembali ditolak.
        $this->actingAs($super)
            ->put(route('approval-steps.update', $tahap), ['label' => 'Diketahui oleh', 'position_id' => $tahap->position_id, 'is_active' => 1])
            ->assertSessionHasErrors('position_id');

        $this->actingAs($super)
            ->delete(route('approval-steps.destroy', $tahap))
            ->assertRedirect(route('approval-steps.index'));

        $this->assertNull(ApprovalStep::find($tahap->id));
        $this->assertSame([1, 2], ApprovalStep::ordered()->pluck('step_order')->all());
    }

    public function test_halaman_alur_menampilkan_peringatan_untuk_tahap_atasan_aktif(): void
    {
        $super = $this->makeUser('super_admin', 'Super Admin');

        $this->actingAs($super)
            ->get(route('approval-steps.index'))
            ->assertOk()
            ->assertDontSee('Tahap atasan langsung sebaiknya dihapus dari sini');

        $this->buatTahapAtasanTetap();

        $this->actingAs($super)
            ->get(route('approval-steps.index'))
            ->assertOk()
            ->assertSee('Tahap atasan langsung sebaiknya dihapus dari sini');
    }

    public function test_jabatan_atasan_pada_tahap_tetap_membuka_semua_departemen_sehingga_perlu_dibersihkan(): void
    {
        // Dokumentasi risiko: selama tahap tetap berjabatan "atasan" masih ada dan pengajuan
        // dibuat TANPA memilih atasan, pemegang jabatan itu terlibat pada semua pengajuan.
        $this->buatTahapAtasanTetap();
        $posisi = Position::where('name', 'Atasan Langsung')->first();
        $pemegang = $this->makeUser('approver', 'Pemegang Jabatan Atasan', $posisi);

        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), [
                'entity_id' => $this->entity->id,
                'department' => 'Finance',
                'pic_name' => 'PIC',
                'items' => [['custom_item_name' => 'Barang', 'budget_type' => 'OPEX', 'quantity' => 1, 'unit' => 'pcs']],
            ])
            ->assertRedirect(route('staff.requests.index'));

        $tanpaAtasan = Pengajuan::latest('id')->firstOrFail();

        $this->assertTrue($tanpaAtasan->approvals->contains('position_name', 'Atasan Langsung'));
        $this->actingAs($pemegang)->get(route('approvals.show', $tanpaAtasan))->assertOk();
    }
}
