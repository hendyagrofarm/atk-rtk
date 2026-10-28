<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Entity;
use App\Models\Position;
use App\Models\Request as Pengajuan;
use App\Models\RequestApproval;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\ApprovalChain;
use App\Support\SignatureStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Atasan langsung sebagai tahap approval digital dan tanda tangan digital (PNG).
 *
 * Urutan: atasan langsung (dipilih dari user sistem) -> Spv. GA -> Manager HCGA.
 * FPB hanya dicetak setelah semua tahap disetujui, dengan tanda tangan digital tercetak.
 */
class DigitalSignatureTest extends TestCase
{
    use RefreshDatabase;

    /** PNG 1x1 yang valid. */
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private Entity $entity;
    private Warehouse $gudang;
    private User $admin;
    private User $atasan;
    private User $spvUser;
    private User $managerUser;
    private User $super;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(SignatureStore::DISK);

        $this->entity = Entity::forceCreate(['code' => 'ANR', 'name' => 'PT Agrofarm Nusa Raya', 'is_active' => true]);
        $this->gudang = Warehouse::forceCreate(['code' => 'GD', 'name' => 'Gudang Pabrik', 'is_active' => true]);

        $spv = Position::create(['name' => 'Spv. GA']);
        $manager = Position::create(['name' => 'Manager HCGA']);
        $kepala = Position::create(['name' => 'Head of Corporate Affair & Legal']);

        ApprovalStep::create(['step_order' => 1, 'label' => 'Diketahui oleh', 'position_id' => $spv->id, 'is_active' => true]);
        ApprovalStep::create(['step_order' => 2, 'label' => 'Disetujui oleh', 'position_id' => $manager->id, 'is_active' => true]);

        $this->admin = $this->makeUser('admin', 'Admin GA', null, $this->gudang);
        // Atasan langsung berakun staff biasa: harus tetap bisa menyetujui bagiannya.
        $this->atasan = $this->makeUser('staff', 'Wachidyah Romadhoni', $kepala, $this->gudang);
        $this->spvUser = $this->makeUser('approver', 'Yudi Ardiyanto', $spv);
        $this->managerUser = $this->makeUser('approver', 'Muhammad Rudi', $manager);
        $this->super = $this->makeUser('super_admin', 'Super Admin');
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
            'entity_id' => $role === 'super_admin' ? null : $this->entity->id,
            'warehouse_id' => $warehouse?->id,
            'position_id' => $position?->id,
        ]);
    }

    private function png(string $name = 'ttd.png', ?int $width = null): UploadedFile
    {
        $bytes = base64_decode(self::PNG_BASE64);

        if ($width !== null) {
            // Ubah lebar pada header IHDR agar tampak sangat lebar.
            $bytes = substr_replace($bytes, pack('N', $width), 16, 4);
        }

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    private function simpanTandaTangan(User $user): void
    {
        SignatureStore::store($user, $this->png());
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'entity_id' => $this->entity->id,
            'department' => 'Affair & Legal - HSE',
            'pic_name' => 'Al Haudhu Riyadhu Sholihin',
            'pic_position' => 'Staff K3L',
            'superior_user_id' => $this->atasan->id,
            'items' => [[
                'custom_item_name' => 'Emergency Eye Wash Portable',
                'budget_type' => 'CAPEX',
                'quantity' => 2,
                'unit' => 'pcs',
            ]],
        ], $override);
    }

    private function buatFpb(array $override = []): Pengajuan
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload($override))
            ->assertRedirect(route('staff.requests.index'));

        return Pengajuan::latest('id')->firstOrFail();
    }

    private function setujui(User $user, Pengajuan $pengajuan, array $quantities = [])
    {
        return $this->actingAs($user)->post(
            route('approvals.approve', $pengajuan),
            $quantities ? ['approved_quantity' => $quantities] : []
        );
    }

    private function selesaikan(Pengajuan $pengajuan): void
    {
        foreach ([$this->atasan, $this->spvUser, $this->managerUser] as $penyetuju) {
            $this->setujui($penyetuju, $pengajuan)->assertRedirect(route('approvals.index'));
        }
    }

    /* ---------------------------------------------------------------
     | Unggah tanda tangan di Manajemen User
     |---------------------------------------------------------------*/

    private function dataUser(array $override = []): array
    {
        return array_merge([
            'name' => 'Staff Baru',
            'email' => 'baru@test.local',
            'role' => 'staff',
            'entity_ids' => [$this->entity->id],
            'entity_id' => $this->entity->id,
            'warehouse_ids' => [$this->gudang->id],
            'warehouse_id' => $this->gudang->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => 1,
        ], $override);
    }

    public function test_admin_mengunggah_tanda_tangan_saat_membuat_user(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->dataUser(['signature' => $this->png()]))
            ->assertRedirect();

        $user = User::where('email', 'baru@test.local')->firstOrFail();

        $this->assertSame('signatures/users/' . $user->id . '.png', $user->signature_path);
        Storage::disk(SignatureStore::DISK)->assertExists($user->signature_path);
    }

    public function test_tanda_tangan_dapat_diganti_dan_dihapus_dari_halaman_edit(): void
    {
        $this->simpanTandaTangan($this->atasan);
        $lama = Storage::disk(SignatureStore::DISK)->get($this->atasan->signature_path);

        $data = $this->dataUser([
            'name' => $this->atasan->name,
            'email' => $this->atasan->email,
            'password' => null,
            'password_confirmation' => null,
        ]);

        // Ganti dengan berkas baru (sama-sama PNG valid, isi dibedakan lewat lebar 10px).
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->atasan), $data + ['signature' => $this->png('baru.png', 10)])
            ->assertRedirect(route('users.index'));

        $this->assertNotSame($lama, Storage::disk(SignatureStore::DISK)->get($this->atasan->fresh()->signature_path));

        // Hapus.
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->atasan), $data + ['remove_signature' => 1])
            ->assertRedirect(route('users.index'));

        $this->assertNull($this->atasan->fresh()->signature_path);
        Storage::disk(SignatureStore::DISK)->assertMissing('signatures/users/' . $this->atasan->id . '.png');
    }

    public function test_tanda_tangan_wajib_berupa_png_yang_valid(): void
    {
        $palsu = UploadedFile::fake()->createWithContent('ttd.png', 'ini bukan gambar');
        $jpg = UploadedFile::fake()->createWithContent('ttd.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00");

        foreach ([$palsu, $jpg] as $berkas) {
            $this->actingAs($this->admin)
                ->post(route('users.store'), $this->dataUser(['signature' => $berkas]))
                ->assertSessionHasErrors('signature');
        }

        $this->assertNull(User::where('email', 'baru@test.local')->first());
    }

    public function test_tanda_tangan_dengan_dimensi_terlalu_besar_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), $this->dataUser(['signature' => $this->png('lebar.png', 5000)]))
            ->assertSessionHasErrors('signature');
    }

    public function test_pratinjau_tanda_tangan_hanya_untuk_admin_dan_super_admin(): void
    {
        $this->simpanTandaTangan($this->atasan);

        $this->actingAs($this->admin)
            ->get(route('users.signature', $this->atasan))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->actingAs($this->super)->get(route('users.signature', $this->atasan))->assertOk();

        $this->actingAs($this->atasan)->get(route('users.signature', $this->atasan))->assertForbidden();
        $this->actingAs($this->spvUser)->get(route('users.signature', $this->atasan))->assertForbidden();

        // User tanpa tanda tangan.
        $this->actingAs($this->admin)->get(route('users.signature', $this->spvUser))->assertNotFound();
    }

    public function test_halaman_edit_user_menampilkan_tanda_tangan_yang_ada(): void
    {
        $this->simpanTandaTangan($this->atasan);

        $this->actingAs($this->admin)
            ->get(route('users.edit', $this->atasan))
            ->assertOk()
            ->assertSee('Tanda Tangan Digital (PNG)')
            ->assertSee('name="remove_signature"', false)
            ->assertSee('multipart/form-data', false);

        $this->actingAs($this->admin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Tanda Tangan Digital (PNG)');
    }

    /* ---------------------------------------------------------------
     | Atasan langsung dipilih saat membuat FPB
     |---------------------------------------------------------------*/

    public function test_form_fpb_menampilkan_daftar_atasan_dan_penanda_ttd(): void
    {
        $this->simpanTandaTangan($this->spvUser);

        $this->actingAs($this->admin)
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertSee('name="superior_user_id"', false)
            ->assertSee('Wachidyah Romadhoni - Head of Corporate Affair &amp; Legal (belum ada TTD)', false)
            ->assertSee('name="superior_signature"', false)
            ->assertDontSee('Admin GA - ');

        // Staff tidak mendapat kolom unggah tanda tangan atasan.
        $this->actingAs($this->makeUser('staff', 'Staff Biasa', null, $this->gudang))
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertSee('name="superior_user_id"', false)
            ->assertDontSee('name="superior_signature"', false);
    }

    public function test_atasan_menjadi_tahap_pertama_diikuti_tahap_tetap(): void
    {
        $pengajuan = $this->buatFpb();

        $this->assertSame($this->atasan->id, $pengajuan->superior_user_id);
        $this->assertSame('Wachidyah Romadhoni', $pengajuan->superior_name);
        $this->assertSame('Head of Corporate Affair & Legal', $pengajuan->superior_position);

        $steps = $pengajuan->approvals;
        $this->assertSame([1, 2, 3], $steps->pluck('step_order')->all());
        $this->assertSame(['Diketahui oleh', 'Diketahui oleh', 'Disetujui oleh'], $steps->pluck('label')->all());
        $this->assertSame(
            ['Head of Corporate Affair & Legal', 'Spv. GA', 'Manager HCGA'],
            $steps->pluck('position_name')->all()
        );
        $this->assertSame($this->atasan->id, $steps[0]->assigned_user_id);
        $this->assertNull($steps[0]->position_id);
        $this->assertNull($steps[1]->assigned_user_id);
    }

    public function test_tanpa_memilih_atasan_alur_hanya_tahap_tetap_dan_isian_manual_tetap_dipakai(): void
    {
        $pengajuan = $this->buatFpb([
            'superior_user_id' => '',
            'superior_name' => 'Atasan Tanpa Akun',
            'superior_position' => 'Kepala Gudang',
        ]);

        $this->assertNull($pengajuan->superior_user_id);
        $this->assertSame('Atasan Tanpa Akun', $pengajuan->superior_name);
        $this->assertSame('Kepala Gudang', $pengajuan->superior_position);
        $this->assertSame(['Spv. GA', 'Manager HCGA'], $pengajuan->approvals->pluck('position_name')->all());
    }

    public function test_data_atasan_dari_sistem_menimpa_isian_manual(): void
    {
        $pengajuan = $this->buatFpb(['superior_name' => 'Nama Palsu', 'superior_position' => 'Jabatan Palsu']);

        $this->assertSame('Wachidyah Romadhoni', $pengajuan->superior_name);
        $this->assertSame('Head of Corporate Affair & Legal', $pengajuan->superior_position);
    }

    public function test_atasan_tidak_boleh_diri_sendiri_atau_user_nonaktif_atau_tidak_ada(): void
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload(['superior_user_id' => $this->admin->id]))
            ->assertSessionHasErrors('superior_user_id');

        $this->atasan->update(['is_active' => false]);

        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload())
            ->assertSessionHasErrors('superior_user_id');

        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload(['superior_user_id' => 99999]))
            ->assertSessionHasErrors('superior_user_id');

        $this->assertSame(0, Pengajuan::count());
    }

    public function test_admin_dapat_mengunggah_tanda_tangan_atasan_dari_form_fpb(): void
    {
        $this->assertFalse(SignatureStore::userHasSignature($this->atasan));

        $this->buatFpb(['superior_signature' => $this->png()]);

        $this->assertTrue(SignatureStore::userHasSignature($this->atasan->fresh()));
    }

    public function test_unggahan_tanda_tangan_atasan_yang_bukan_png_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload([
                'superior_signature' => UploadedFile::fake()->createWithContent('ttd.png', 'bukan png'),
            ]))
            ->assertSessionHasErrors('superior_signature');

        $this->assertSame(0, Pengajuan::count());
        $this->assertFalse(SignatureStore::userHasSignature($this->atasan->fresh()));
    }

    public function test_staff_tidak_bisa_mengunggah_tanda_tangan_atasan(): void
    {
        $staff = $this->makeUser('staff', 'Staff Pembuat', null, $this->gudang);

        $this->actingAs($staff)
            ->post(route('staff.requests.custom.store'), $this->payload(['superior_signature' => $this->png()]))
            ->assertRedirect(route('staff.requests.index'));

        $this->assertFalse(SignatureStore::userHasSignature($this->atasan->fresh()));
    }

    /* ---------------------------------------------------------------
     | Approval oleh atasan langsung
     |---------------------------------------------------------------*/

    public function test_hanya_atasan_yang_ditunjuk_bisa_memproses_tahap_pertama(): void
    {
        $pengajuan = $this->buatFpb();

        // Spv. GA dan Manager belum gilirannya.
        $this->setujui($this->spvUser, $pengajuan)->assertSessionHas('error');
        $this->setujui($this->managerUser, $pengajuan)->assertSessionHas('error');

        // Orang lain berjabatan sama bukan atasan yang ditunjuk, dan pengajuan custom tidak
        // terbuka lewat kesamaan lokasi: akses ditolak.
        $kepalaLain = $this->makeUser('approver', 'Kepala Lain', $this->atasan->position, $this->gudang);
        $this->setujui($kepalaLain, $pengajuan)->assertForbidden();

        $this->setujui($this->atasan, $pengajuan)->assertRedirect(route('approvals.index'));

        $pengajuan->refresh()->load('approvals');
        $this->assertSame('approved', $pengajuan->approvals[0]->status);
        $this->assertSame($this->atasan->id, $pengajuan->approvals[0]->user_id);
        $this->assertSame('pending', $pengajuan->status);
    }

    public function test_alur_lengkap_atasan_spv_manager_menyelesaikan_pengajuan(): void
    {
        $pengajuan = $this->buatFpb();
        $detail = $pengajuan->details()->firstOrFail();

        $this->setujui($this->atasan, $pengajuan);
        $this->setujui($this->spvUser, $pengajuan);
        $this->setujui($this->managerUser, $pengajuan, [$detail->id => 2])->assertRedirect(route('approvals.index'));

        $pengajuan->refresh();
        $this->assertSame('approved', $pengajuan->status);
        $this->assertSame(2, (int) $detail->fresh()->approved_quantity);
    }

    public function test_atasan_berakun_staff_dapat_membuka_dan_memproses_pengajuannya(): void
    {
        $pengajuan = $this->buatFpb();

        $this->actingAs($this->atasan)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee($pengajuan->request_number)
            ->assertSee('Giliran Anda');

        $this->actingAs($this->atasan)
            ->get(route('approvals.show', $pengajuan))
            ->assertOk()
            ->assertSee('giliran Anda')
            ->assertSee('Setujui Tahap Ini');

        $this->setujui($this->atasan, $pengajuan)->assertRedirect(route('approvals.index'));
    }

    public function test_akun_staff_lain_tidak_bisa_membuka_approval(): void
    {
        $pengajuan = $this->buatFpb();
        $lain = $this->makeUser('staff', 'Staff Lain', null, $this->gudang);

        $this->actingAs($lain)->get(route('approvals.show', $pengajuan))->assertForbidden();
        $this->setujui($lain, $pengajuan)->assertForbidden();

        $this->actingAs($lain)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertDontSee($pengajuan->request_number);
    }

    public function test_akun_staff_atasan_tidak_melihat_pengajuan_stok_di_lokasinya(): void
    {
        $stok = Pengajuan::forceCreate([
            'user_id' => $this->admin->id,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudang->id,
            'request_number' => 'REQ-STOK-RAHASIA',
            'request_date' => now()->toDateString(),
            'request_type' => 'stock',
            'status' => 'pending',
        ]);

        $this->buatFpb();

        // Atasan berakun staff satu lokasi dengan pengajuan stok, tetapi tidak berhak melihatnya.
        $this->actingAs($this->atasan)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertDontSee('REQ-STOK-RAHASIA');

        $this->actingAs($this->atasan)->get(route('approvals.show', $stok))->assertForbidden();
        $this->setujui($this->atasan, $stok)->assertForbidden();
    }

    public function test_menu_approval_hanya_tampil_untuk_staff_yang_terlibat(): void
    {
        $lain = $this->makeUser('staff', 'Staff Lain', null, $this->gudang);

        $this->actingAs($lain)->get(route('dashboard'))->assertOk()->assertDontSee('Approval Pengajuan');
        $this->actingAs($this->atasan)->get(route('dashboard'))->assertOk()->assertDontSee('Approval Pengajuan');

        $this->buatFpb();

        $this->actingAs($this->atasan)->get(route('dashboard'))->assertOk()->assertSee('Approval Pengajuan');
        $this->actingAs($lain)->get(route('dashboard'))->assertOk()->assertDontSee('Approval Pengajuan');
    }

    public function test_penolakan_oleh_atasan_menghentikan_alur(): void
    {
        $pengajuan = $this->buatFpb();

        $this->actingAs($this->atasan)
            ->post(route('approvals.reject', $pengajuan), ['rejection_reason' => 'tidak_sesuai_kebutuhan'])
            ->assertRedirect(route('approvals.index'));

        $pengajuan->refresh()->load('approvals');
        $this->assertSame('rejected', $pengajuan->status);
        $this->assertSame(['rejected', 'skipped', 'skipped'], $pengajuan->approvals->pluck('status')->all());
    }

    public function test_pembuat_tidak_bisa_menyetujui_tahap_atasan_dirinya_sendiri(): void
    {
        $pengajuan = $this->buatFpb();
        $this->assertFalse(ApprovalChain::canAct($this->admin, $pengajuan->load('approvals')));

        $this->setujui($this->admin, $pengajuan)->assertSessionHas('error');
    }

    public function test_super_admin_tetap_dapat_memproses_tahap_atasan(): void
    {
        $pengajuan = $this->buatFpb();

        $this->setujui($this->super, $pengajuan)->assertRedirect(route('approvals.index'));

        $this->assertSame($this->super->id, $pengajuan->fresh()->approvals[0]->user_id);
    }

    /* ---------------------------------------------------------------
     | Tanda tangan digital pada FPB
     |---------------------------------------------------------------*/

    public function test_tanda_tangan_disalin_saat_tahap_disetujui(): void
    {
        $this->simpanTandaTangan($this->atasan);
        $pengajuan = $this->buatFpb();

        $this->setujui($this->atasan, $pengajuan);

        $step = $pengajuan->fresh()->approvals[0];

        $this->assertSame('signatures/approvals/' . $step->id . '.png', $step->signature_path);
        Storage::disk(SignatureStore::DISK)->assertExists($step->signature_path);

        // Penyetuju tanpa tanda tangan: tidak ada salinan.
        $this->setujui($this->spvUser, $pengajuan);
        $this->assertNull($pengajuan->fresh()->approvals[1]->signature_path);
    }

    public function test_mengganti_tanda_tangan_tidak_mengubah_fpb_yang_sudah_disetujui(): void
    {
        $this->simpanTandaTangan($this->atasan);
        $pengajuan = $this->buatFpb();

        $this->setujui($this->atasan, $pengajuan);

        $step = $pengajuan->fresh()->approvals[0];
        $salinan = Storage::disk(SignatureStore::DISK)->get($step->signature_path);

        // Atasan mengganti tanda tangannya sesudahnya.
        SignatureStore::store($this->atasan->fresh(), $this->png('baru.png', 50));

        $this->assertNotSame($salinan, Storage::disk(SignatureStore::DISK)->get($this->atasan->fresh()->signature_path));
        $this->assertSame($salinan, Storage::disk(SignatureStore::DISK)->get($step->signature_path));
    }

    public function test_fpb_menampilkan_tanda_tangan_digital_semua_penyetuju_setelah_selesai(): void
    {
        foreach ([$this->atasan, $this->spvUser, $this->managerUser] as $penyetuju) {
            $this->simpanTandaTangan($penyetuju);
        }

        $pengajuan = $this->buatFpb();
        $this->selesaikan($pengajuan);

        $html = $this->actingAs($this->admin)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();

        // Tiga penyetuju digital -> tiga gambar; PIC tetap tanda tangan basah (kosong).
        $this->assertSame(3, substr_count($html, 'src="data:image/png;base64,'));

        preg_match_all('/<td class="label">([^<]+)<\/td>/', $html, $labels);
        $this->assertSame(['Diajukan oleh', 'Diketahui oleh', 'Diketahui oleh', 'Disetujui oleh'], $labels[1]);

        $this->assertStringContainsString('Wachidyah Romadhoni', $html);
        $this->assertStringContainsString('Head of Corporate Affair &amp; Legal', $html);
        $this->assertStringContainsString('Yudi Ardiyanto', $html);
        $this->assertStringContainsString('Muhammad Rudi', $html);
    }

    public function test_penyetuju_tanpa_tanda_tangan_tercetak_tanpa_gambar(): void
    {
        $this->simpanTandaTangan($this->atasan);

        $pengajuan = $this->buatFpb();
        $this->selesaikan($pengajuan);

        $html = $this->actingAs($this->admin)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'src="data:image/png;base64,'));
        $this->assertStringContainsString('Yudi Ardiyanto', $html);
    }

    public function test_fpb_tidak_bisa_dicetak_sebelum_semua_tahap_disetujui(): void
    {
        $this->simpanTandaTangan($this->atasan);
        $pengajuan = $this->buatFpb();

        $this->actingAs($this->admin)->get(route('fpb.print', $pengajuan))
            ->assertRedirect()->assertSessionHas('error');

        $this->setujui($this->atasan, $pengajuan);
        $this->setujui($this->spvUser, $pengajuan);

        $this->actingAs($this->admin)->get(route('fpb.print', $pengajuan))
            ->assertRedirect()->assertSessionHas('error');

        $this->setujui($this->managerUser, $pengajuan);

        $this->actingAs($this->admin)->get(route('fpb.print', $pengajuan))->assertOk();
    }

    public function test_atasan_berakun_staff_boleh_mencetak_fpb_yang_melibatkannya_tetapi_staff_lain_tidak(): void
    {
        $pengajuan = $this->buatFpb();
        $this->selesaikan($pengajuan);

        // Atasan yang ditunjuk terlibat pada pengajuan ini.
        $this->actingAs($this->atasan)->get(route('fpb.print', $pengajuan))->assertOk();
        $this->actingAs($this->managerUser)->get(route('fpb.print', $pengajuan))->assertOk();

        // Staff lain tanpa keterlibatan dilarang, walau satu lokasi.
        $lain = $this->makeUser('staff', 'Staff Lain', null, $this->gudang);
        $this->actingAs($lain)->get(route('fpb.print', $pengajuan))->assertForbidden();

        // Atasan pada pengajuan lain (tidak terlibat) juga dilarang.
        $lainnya = $this->buatFpb(['superior_user_id' => '']);
        $this->actingAs($this->atasan)->get(route('fpb.print', $lainnya))->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Pelacakan
     |---------------------------------------------------------------*/

    public function test_pelacakan_menyebut_nama_atasan_yang_ditunggu_dan_yang_sudah_menyetujui(): void
    {
        $staff = $this->makeUser('staff', 'Staff Pembuat', null, $this->gudang);

        $this->actingAs($staff)
            ->post(route('staff.requests.custom.store'), $this->payload())
            ->assertRedirect(route('staff.requests.index'));

        $pengajuan = Pengajuan::latest('id')->firstOrFail();

        $this->actingAs($staff)
            ->get(route('staff.requests.show', $pengajuan->id))
            ->assertOk()
            ->assertSee('Sedang diproses oleh Head of Corporate Affair &amp; Legal', false)
            ->assertSee('Menunggu diketahui oleh Wachidyah Romadhoni');

        $this->setujui($this->atasan, $pengajuan);

        $this->actingAs($staff)
            ->get(route('staff.requests.show', $pengajuan->id))
            ->assertSee('Disetujui oleh Wachidyah Romadhoni (Head of Corporate Affair &amp; Legal)', false)
            ->assertSee('Sedang diproses oleh Spv. GA');
    }

    public function test_peserta_approval_terdeteksi_untuk_menu(): void
    {
        $this->assertFalse(ApprovalChain::isParticipantAnywhere($this->atasan));

        $this->buatFpb();

        $this->assertTrue(ApprovalChain::isParticipantAnywhere($this->atasan));
        $this->assertTrue(ApprovalChain::isParticipantAnywhere($this->spvUser));
        $this->assertFalse(ApprovalChain::isParticipantAnywhere($this->admin));
        $this->assertSame(RequestApproval::PENDING, RequestApproval::first()->status);
    }
}
