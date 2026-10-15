<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Entity;
use App\Models\Position;
use App\Models\Request as Pengajuan;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin mengisi FPB atas nama PIC dari berbagai divisi. FPB punya 4 kotak tanda
 * tangan: PIC, atasan langsung (keduanya diatur per pengajuan), Spv. GA dan
 * Manager HCGA (tetap, dari pengaturan Alur Approval).
 */
class FpbSignersTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entity;
    private Warehouse $gudang;
    private Position $spv;
    private Position $manager;
    private User $admin;
    private User $spvUser;
    private User $managerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = Entity::forceCreate(['code' => 'ANR', 'name' => 'PT Agrofarm Nusa Raya', 'is_active' => true]);
        $this->gudang = Warehouse::forceCreate(['code' => 'GD', 'name' => 'Gudang Pabrik', 'is_active' => true]);

        $this->spv = Position::create(['name' => 'Spv. GA']);
        $this->manager = Position::create(['name' => 'Manager HCGA']);

        ApprovalStep::create(['step_order' => 1, 'label' => 'Diketahui oleh', 'position_id' => $this->spv->id, 'is_active' => true]);
        ApprovalStep::create(['step_order' => 2, 'label' => 'Disetujui oleh', 'position_id' => $this->manager->id, 'is_active' => true]);

        $this->admin = $this->makeUser('admin', 'Admin GA', null, $this->gudang);
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

    private function payload(array $override = []): array
    {
        return array_merge([
            'entity_id' => $this->entity->id,
            'department' => 'Affair & Legal - HSE',
            'pic_name' => 'Al Haudhu Riyadhu Sholihin',
            'pic_position' => 'Staff K3L',
            'superior_name' => 'Wachidyah Romadhoni',
            'superior_position' => 'Head of Corporate Affair & Legal',
            'items' => [[
                'custom_item_name' => 'Emergency Eye Wash Portable',
                'budget_type' => 'CAPEX',
                'brand_type' => 'Unicare 30L',
                'quantity' => 2,
                'unit' => 'pcs',
            ]],
        ], $override);
    }

    private function buatSebagaiAdmin(array $override = []): Pengajuan
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload($override))
            ->assertRedirect(route('staff.requests.index'));

        return Pengajuan::latest('id')->firstOrFail();
    }

    public function test_admin_membuat_fpb_atas_nama_pic_dan_atasan_langsung(): void
    {
        $pengajuan = $this->buatSebagaiAdmin();

        $this->assertSame($this->admin->id, $pengajuan->user_id, 'Pembuat di sistem tetap admin.');
        $this->assertSame('Al Haudhu Riyadhu Sholihin', $pengajuan->pic_name);
        $this->assertSame('Staff K3L', $pengajuan->pic_position);
        $this->assertSame('Wachidyah Romadhoni', $pengajuan->superior_name);
        $this->assertSame('Head of Corporate Affair & Legal', $pengajuan->superior_position);
        $this->assertCount(2, $pengajuan->approvals);
    }

    public function test_pic_wajib_diisi_oleh_admin(): void
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload(['pic_name' => '']))
            ->assertSessionHasErrors('pic_name');

        $this->assertSame(0, Pengajuan::count());
    }

    public function test_atasan_langsung_boleh_dikosongkan(): void
    {
        $pengajuan = $this->buatSebagaiAdmin(['superior_name' => '', 'superior_position' => '']);

        $this->assertNull($pengajuan->superior_name);
        $this->assertNull($pengajuan->superior_position);
    }

    public function test_staff_selalu_menjadi_pic_dirinya_sendiri(): void
    {
        $k3l = Position::create(['name' => 'Staff K3L']);
        $staff = $this->makeUser('staff', 'Staff Divisi', $k3l, $this->gudang);

        $this->actingAs($staff)
            ->post(route('staff.requests.custom.store'), $this->payload([
                'pic_name' => 'Orang Lain',
                'pic_position' => 'Direktur',
            ]))
            ->assertRedirect(route('staff.requests.index'));

        $pengajuan = Pengajuan::latest('id')->firstOrFail();

        $this->assertSame('Staff Divisi', $pengajuan->pic_name);
        $this->assertSame('Staff K3L', $pengajuan->pic_position);
        // Atasan langsung tetap bisa diisi staff.
        $this->assertSame('Wachidyah Romadhoni', $pengajuan->superior_name);
    }

    public function test_form_admin_menampilkan_isian_pic_sedangkan_staff_tidak(): void
    {
        $this->actingAs($this->admin)
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertSee('name="pic_name"', false)
            ->assertSee('name="superior_name"', false)
            ->assertSee('Yudi Ardiyanto');

        $staff = $this->makeUser('staff', 'Staff Divisi', null, $this->gudang);

        $this->actingAs($staff)
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertDontSee('name="pic_name"', false)
            ->assertSee('name="superior_name"', false);
    }

    public function test_admin_dapat_membuka_daftar_dan_form_fpb_tetapi_tidak_pengajuan_stok(): void
    {
        $this->actingAs($this->admin)->get(route('staff.requests.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('staff.requests.custom.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('staff.requests.create'))->assertForbidden();

        $approver = $this->spvUser;
        $this->actingAs($approver)->get(route('staff.requests.custom.create'))->assertForbidden();
    }

    public function test_menu_fpb_hanya_tampil_untuk_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Buat FPB Custom');

        $staff = $this->makeUser('staff', 'Staff Divisi', null, $this->gudang);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Buat FPB Custom');
    }

    public function test_cetak_fpb_memiliki_empat_kotak_tanda_tangan(): void
    {
        $pengajuan = $this->buatSebagaiAdmin();

        $html = $this->actingAs($this->admin)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();

        // Diajukan oleh, Diketahui oleh (atasan), Diketahui oleh (Spv. GA), Disetujui oleh (Manager).
        $this->assertSame(4, substr_count($html, '<td class="label">'));

        preg_match_all('/<td class="label">([^<]+)<\/td>/', $html, $labels);
        $this->assertSame(['Diajukan oleh', 'Diketahui oleh', 'Diketahui oleh', 'Disetujui oleh'], $labels[1]);

        preg_match_all('/<td class="position">([^<]*)<\/td>/', $html, $positions);
        $this->assertSame(
            ['Staff K3L', 'Head of Corporate Affair &amp; Legal', 'Spv. GA', 'Manager HCGA'],
            array_map('trim', $positions[1])
        );
    }

    public function test_cetak_fpb_memakai_nama_pic_bukan_nama_admin_pembuat(): void
    {
        $pengajuan = $this->buatSebagaiAdmin();

        $this->actingAs($this->admin)
            ->get(route('fpb.print', $pengajuan))
            ->assertOk()
            ->assertSee('Al Haudhu Riyadhu Sholihin')
            ->assertSee('Wachidyah Romadhoni')
            ->assertDontSee('Admin GA');
    }

    public function test_nama_atasan_tercetak_tanpa_tanggal_dan_penyetuju_digital_menyusul(): void
    {
        $pengajuan = $this->buatSebagaiAdmin();

        $html = $this->actingAs($this->admin)->get(route('fpb.print', $pengajuan))->getContent();

        // PIC dan atasan langsung tercetak sejak awal; Spv. GA dan Manager masih kosong.
        $this->assertStringContainsString('Wachidyah Romadhoni', $html);
        $this->assertStringNotContainsString('Yudi Ardiyanto', $html);
        $this->assertStringNotContainsString('Muhammad Rudi', $html);

        $this->actingAs($this->spvUser)->post(route('approvals.approve', $pengajuan));

        $this->actingAs($this->admin)
            ->get(route('fpb.print', $pengajuan))
            ->assertSee('Yudi Ardiyanto')
            ->assertSee(now()->format('d/m/Y'))
            ->assertDontSee('Muhammad Rudi');
    }

    public function test_atasan_kosong_tetap_menyediakan_kotak_untuk_tanda_tangan(): void
    {
        $pengajuan = $this->buatSebagaiAdmin(['superior_name' => '', 'superior_position' => '']);

        $html = $this->actingAs($this->admin)->get(route('fpb.print', $pengajuan))->getContent();

        $this->assertSame(4, substr_count($html, '<td class="label">'));
        $this->assertStringContainsString('Atasan Langsung', $html);
    }

    public function test_tahap_digital_spv_ga_dan_manager_hcga_tetap_berurutan(): void
    {
        $pengajuan = $this->buatSebagaiAdmin();

        // Atasan langsung bukan tahap digital: alur dimulai dari Spv. GA.
        $this->assertSame(['Spv. GA', 'Manager HCGA'], $pengajuan->approvals->pluck('position_name')->all());

        $this->actingAs($this->managerUser)->post(route('approvals.approve', $pengajuan))->assertSessionHas('error');

        $this->actingAs($this->spvUser)->post(route('approvals.approve', $pengajuan))
            ->assertRedirect(route('approvals.index'));
        $this->actingAs($this->managerUser)->post(route('approvals.approve', $pengajuan))
            ->assertRedirect(route('approvals.index'));

        $this->assertSame('approved', $pengajuan->fresh()->status);
    }

    public function test_halaman_approval_menampilkan_pic_dan_pembuat(): void
    {
        $pengajuan = $this->buatSebagaiAdmin();

        $this->actingAs($this->spvUser)
            ->get(route('approvals.show', $pengajuan))
            ->assertOk()
            ->assertSee('Al Haudhu Riyadhu Sholihin')
            ->assertSee('dibuat oleh Admin GA')
            ->assertSee('Wachidyah Romadhoni');
    }

    public function test_admin_pembuat_tidak_bisa_menyetujui_fpb_buatannya_sendiri(): void
    {
        // Admin kebetulan memegang jabatan Spv. GA.
        $this->admin->update(['position_id' => $this->spv->id]);

        $pengajuan = $this->buatSebagaiAdmin();

        $this->actingAs($this->admin)
            ->post(route('approvals.approve', $pengajuan))
            ->assertSessionHas('error');

        $this->assertSame('pending', $pengajuan->fresh()->approvals[0]->status);
    }

    private function buatSebagaiStaff(string $atasan = 'Wachidyah Romadhoni'): array
    {
        $k3l = Position::firstOrCreate(['name' => 'Staff K3L']);
        $staff = $this->makeUser('staff', 'Al Haudhu', $k3l, $this->gudang);

        $this->actingAs($staff)
            ->post(route('staff.requests.custom.store'), $this->payload([
                'superior_name' => $atasan,
                'superior_position' => $atasan === '' ? '' : 'Head of Corporate Affair & Legal',
            ]))
            ->assertRedirect(route('staff.requests.index'));

        return [$staff, Pengajuan::latest('id')->firstOrFail()];
    }

    private function bacaKotakTandaTangan(string $html): array
    {
        preg_match_all('/<td class="label">([^<]+)<\/td>/', $html, $labels);
        preg_match_all('/<td class="position">([^<]*)<\/td>/', $html, $positions);

        return [
            'labels' => $labels[1],
            'positions' => array_map('trim', $positions[1]),
        ];
    }

    public function test_cetak_fpb_dari_akun_staff_memiliki_empat_kotak_tanda_tangan(): void
    {
        [$staff, $pengajuan] = $this->buatSebagaiStaff();

        $html = $this->actingAs($staff)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();
        $kotak = $this->bacaKotakTandaTangan($html);

        $this->assertSame(['Diajukan oleh', 'Diketahui oleh', 'Diketahui oleh', 'Disetujui oleh'], $kotak['labels']);
        $this->assertSame(
            ['Staff K3L', 'Head of Corporate Affair &amp; Legal', 'Spv. GA', 'Manager HCGA'],
            $kotak['positions']
        );

        // Staff menjadi PIC dirinya sendiri; atasan langsung dari isian form.
        $this->assertStringContainsString('Al Haudhu', $html);
        $this->assertStringContainsString('Wachidyah Romadhoni', $html);
    }

    public function test_cetak_fpb_staff_tetap_empat_kotak_walau_alur_approval_belum_diatur(): void
    {
        ApprovalStep::query()->update(['is_active' => false]);

        [$staff, $pengajuan] = $this->buatSebagaiStaff();
        $this->assertCount(0, $pengajuan->approvals);

        $html = $this->actingAs($staff)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();
        $kotak = $this->bacaKotakTandaTangan($html);

        $this->assertSame(['Diajukan oleh', 'Diketahui oleh', 'Diketahui oleh', 'Disetujui oleh'], $kotak['labels']);
        $this->assertSame(
            ['Staff K3L', 'Head of Corporate Affair &amp; Legal', 'Spv. GA', 'Manager HCGA'],
            $kotak['positions']
        );
    }

    public function test_tanpa_alur_kotak_terakhir_memuat_nama_penyetuju_setelah_disetujui(): void
    {
        ApprovalStep::query()->update(['is_active' => false]);

        [$staff, $pengajuan] = $this->buatSebagaiStaff();
        $detail = $pengajuan->details()->firstOrFail();

        $sebelum = $this->actingAs($staff)->get(route('fpb.print', $pengajuan))->getContent();
        $this->assertStringNotContainsString('Muhammad Rudi', $sebelum);

        $approver = $this->makeUser('approver', 'Muhammad Rudi', $this->manager, $this->gudang);
        $this->actingAs($approver)->post(route('approvals.approve', $pengajuan), [
            'approved_quantity' => [$detail->id => 2],
        ])->assertRedirect(route('approvals.index'));

        $html = $this->actingAs($staff)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();
        $kotak = $this->bacaKotakTandaTangan($html);

        $this->assertCount(4, $kotak['labels']);
        $this->assertSame('Manager HCGA', $kotak['positions'][3]);
        $this->assertStringContainsString('Muhammad Rudi', $html);
        $this->assertStringContainsString(now()->format('d/m/Y'), $html);
    }

    public function test_staff_tanpa_atasan_tetap_mendapat_empat_kotak(): void
    {
        [$staff, $pengajuan] = $this->buatSebagaiStaff('');

        $kotak = $this->bacaKotakTandaTangan(
            $this->actingAs($staff)->get(route('fpb.print', $pengajuan))->getContent()
        );

        $this->assertCount(4, $kotak['labels']);
        $this->assertSame('Atasan Langsung', $kotak['positions'][1]);
    }

    public function test_pengajuan_custom_lama_tanpa_data_penandatangan_tetap_tercetak(): void
    {
        $pengajuan = $this->buatSebagaiAdmin();
        $pengajuan->update(['pic_name' => null, 'pic_position' => null, 'superior_name' => null, 'superior_position' => null]);

        $this->actingAs($this->admin)
            ->get(route('fpb.print', $pengajuan))
            ->assertOk()
            ->assertSee('Admin GA')
            ->assertSee('Atasan Langsung');
    }
}
