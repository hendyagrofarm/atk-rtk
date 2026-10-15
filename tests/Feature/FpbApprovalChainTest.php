<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Entity;
use App\Models\Position;
use App\Models\Request as Pengajuan;
use App\Models\RequestApproval;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FpbApprovalChainTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entity;
    private Warehouse $gudang;
    private Position $k3l;
    private Position $head;
    private Position $spv;
    private Position $manager;
    private User $staff;
    private User $headUser;
    private User $spvUser;
    private User $managerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = Entity::forceCreate(['code' => 'ANR', 'name' => 'PT Agrofarm Nusa Raya', 'is_active' => true]);
        $this->gudang = Warehouse::forceCreate(['code' => 'GD', 'name' => 'Gudang Pabrik', 'is_active' => true]);

        $this->k3l = Position::create(['name' => 'Staff K3L']);
        $this->head = Position::create(['name' => 'Head of Corporate Affair & Legal']);
        $this->spv = Position::create(['name' => 'Spv. GA']);
        $this->manager = Position::create(['name' => 'Manager HCGA']);

        ApprovalStep::create(['step_order' => 1, 'label' => 'Diketahui oleh', 'position_id' => $this->head->id, 'is_active' => true]);
        ApprovalStep::create(['step_order' => 2, 'label' => 'Diketahui oleh', 'position_id' => $this->spv->id, 'is_active' => true]);
        ApprovalStep::create(['step_order' => 3, 'label' => 'Disetujui oleh', 'position_id' => $this->manager->id, 'is_active' => true]);

        $this->staff = $this->makeUser('staff', 'Al Haudhu', $this->k3l, $this->gudang);
        $this->headUser = $this->makeUser('approver', 'Wachidyah', $this->head);
        $this->spvUser = $this->makeUser('approver', 'Yudi', $this->spv);
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
            'entity_id' => $role === 'super_admin' ? null : $this->entity->id,
            'warehouse_id' => $warehouse?->id,
            'position_id' => $position?->id,
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'entity_id' => $this->entity->id,
            'department' => 'Affair & Legal - HSE',
            'note' => 'Kebutuhan tanggap darurat',
            'items' => [[
                'custom_item_name' => 'Emergency Eye Wash Portable',
                'custom_category' => '',
                'budget_type' => 'CAPEX',
                'brand_type' => 'Unicare 30L',
                'quantity' => 2,
                'unit' => 'pcs',
                'usage_purpose' => 'Sebagai fasilitas tanggap darurat',
                'note' => 'Untuk paparan bahan kimia',
            ]],
        ], $override);
    }

    private function buatPengajuan(?User $by = null, array $override = []): Pengajuan
    {
        $this->actingAs($by ?? $this->staff)
            ->post(route('staff.requests.custom.store'), $this->payload($override))
            ->assertRedirect(route('staff.requests.index'));

        return Pengajuan::latest('id')->firstOrFail();
    }

    private function setujui(User $user, Pengajuan $pengajuan, array $quantities = [], ?string $note = null)
    {
        return $this->actingAs($user)->post(route('approvals.approve', $pengajuan), array_filter([
            'approved_quantity' => $quantities ?: null,
            'approver_note' => $note,
        ]));
    }

    /* ---------------------------------------------------------------
     | Pembuatan pengajuan
     |---------------------------------------------------------------*/

    public function test_pengajuan_custom_menyalin_tahap_dan_menyimpan_data_formulir(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->assertSame('Affair & Legal - HSE', $pengajuan->department);

        $detail = $pengajuan->details()->firstOrFail();
        $this->assertSame('CAPEX', $detail->budget_type);
        $this->assertSame('Unicare 30L', $detail->brand_type);
        $this->assertSame('Sebagai fasilitas tanggap darurat', $detail->usage_purpose);

        $steps = $pengajuan->approvals;
        $this->assertCount(3, $steps);
        $this->assertSame([1, 2, 3], $steps->pluck('step_order')->all());
        $this->assertSame(['Diketahui oleh', 'Diketahui oleh', 'Disetujui oleh'], $steps->pluck('label')->all());
        $this->assertSame(
            ['Head of Corporate Affair & Legal', 'Spv. GA', 'Manager HCGA'],
            $steps->pluck('position_name')->all()
        );
        $this->assertSame(['pending'], $steps->pluck('status')->unique()->all());
    }

    public function test_divisi_dan_jenis_anggaran_wajib_diisi(): void
    {
        $this->actingAs($this->staff)
            ->post(route('staff.requests.custom.store'), $this->payload(['department' => '']))
            ->assertSessionHasErrors('department');

        $payload = $this->payload();
        $payload['items'][0]['budget_type'] = 'LAINNYA';

        $this->actingAs($this->staff)
            ->post(route('staff.requests.custom.store'), $payload)
            ->assertSessionHasErrors('items.0.budget_type');

        $this->assertSame(0, Pengajuan::count());
    }

    public function test_perubahan_pengaturan_tidak_mengubah_pengajuan_yang_sudah_berjalan(): void
    {
        $pengajuan = $this->buatPengajuan();

        ApprovalStep::where('step_order', 2)->update(['is_active' => false]);
        $this->spv->update(['name' => 'Spv. GA (baru)']);

        $this->assertCount(3, $pengajuan->fresh()->approvals);
        $this->assertSame('Spv. GA', $pengajuan->fresh()->approvals[1]->position_name);

        // Pengajuan baru memakai pengaturan terbaru: 2 tahap dengan nama baru.
        $baru = $this->buatPengajuan();
        $this->assertCount(2, $baru->approvals);
    }

    public function test_tanpa_tahap_aktif_pengajuan_memakai_approval_satu_tahap(): void
    {
        ApprovalStep::query()->update(['is_active' => false]);

        $pengajuan = $this->buatPengajuan();
        $this->assertCount(0, $pengajuan->approvals);

        $approver = $this->makeUser('approver', 'Approver Lokasi', null, $this->gudang);

        $this->setujui($approver, $pengajuan, [$pengajuan->details()->first()->id => 2])
            ->assertRedirect(route('approvals.index'));

        $this->assertSame('approved', $pengajuan->fresh()->status);
        $this->assertSame(2, (int) $pengajuan->details()->first()->approved_quantity);
    }

    /* ---------------------------------------------------------------
     | Alur approval berurutan
     |---------------------------------------------------------------*/

    public function test_alur_berjalan_berurutan_sampai_disetujui(): void
    {
        $pengajuan = $this->buatPengajuan();
        $detail = $pengajuan->details()->firstOrFail();

        // Tahap 1.
        $this->setujui($this->headUser, $pengajuan, [], 'Diketahui')->assertRedirect(route('approvals.index'));
        $pengajuan->refresh()->load('approvals');
        $this->assertSame('pending', $pengajuan->status);
        $this->assertSame('approved', $pengajuan->approvals[0]->status);
        $this->assertSame($this->headUser->id, $pengajuan->approvals[0]->user_id);
        $this->assertNotNull($pengajuan->approvals[0]->acted_at);
        $this->assertSame(0, (int) $detail->fresh()->approved_quantity, 'Jumlah belum ditetapkan sebelum tahap akhir.');

        // Tahap 2.
        $this->setujui($this->spvUser, $pengajuan)->assertRedirect(route('approvals.index'));
        $this->assertSame('pending', $pengajuan->fresh()->status);

        // Tahap 3 (akhir) menetapkan jumlah disetujui.
        $this->setujui($this->managerUser, $pengajuan, [$detail->id => 1], 'Disetujui sebagian')
            ->assertRedirect(route('approvals.index'));

        $pengajuan->refresh()->load('approvals');
        $this->assertSame('approved', $pengajuan->status);
        $this->assertSame($this->managerUser->id, $pengajuan->approver_id);
        $this->assertNotNull($pengajuan->approved_at);
        $this->assertSame(1, (int) $detail->fresh()->approved_quantity);
        $this->assertSame(['approved'], $pengajuan->approvals->pluck('status')->unique()->all());
    }

    public function test_tahap_tidak_bisa_dilompati(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->setujui($this->managerUser, $pengajuan)->assertSessionHas('error');
        $this->setujui($this->spvUser, $pengajuan)->assertSessionHas('error');

        $pengajuan->refresh()->load('approvals');
        $this->assertSame('pending', $pengajuan->status);
        $this->assertSame(['pending'], $pengajuan->approvals->pluck('status')->unique()->all());
    }

    public function test_tahap_tidak_bisa_diproses_dua_kali(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->setujui($this->headUser, $pengajuan);
        $this->setujui($this->headUser, $pengajuan)->assertSessionHas('error');

        $this->assertSame(['approved', 'pending', 'pending'], $pengajuan->fresh()->approvals->pluck('status')->all());
    }

    public function test_jumlah_disetujui_tidak_boleh_melebihi_permintaan_pada_tahap_akhir(): void
    {
        $pengajuan = $this->buatPengajuan();
        $detail = $pengajuan->details()->firstOrFail();

        $this->setujui($this->headUser, $pengajuan);
        $this->setujui($this->spvUser, $pengajuan);

        $this->setujui($this->managerUser, $pengajuan, [$detail->id => 99])->assertSessionHas('error');

        $pengajuan->refresh()->load('approvals');
        $this->assertSame('pending', $pengajuan->status);
        $this->assertSame('pending', $pengajuan->approvals[2]->status);
    }

    public function test_tanpa_jumlah_pada_tahap_akhir_dianggap_disetujui_penuh(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->setujui($this->headUser, $pengajuan);
        $this->setujui($this->spvUser, $pengajuan);
        $this->setujui($this->managerUser, $pengajuan);

        $this->assertSame(2, (int) $pengajuan->details()->first()->approved_quantity);
    }

    /* ---------------------------------------------------------------
     | Penolakan
     |---------------------------------------------------------------*/

    public function test_penolakan_menghentikan_alur_dan_melewati_tahap_sisanya(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->setujui($this->headUser, $pengajuan);

        $this->actingAs($this->spvUser)
            ->post(route('approvals.reject', $pengajuan), [
                'rejection_reason' => 'tidak_sesuai_kebutuhan',
                'approver_note' => 'Sudah ada di gudang lain',
            ])
            ->assertRedirect(route('approvals.index'));

        $pengajuan->refresh()->load('approvals');

        $this->assertSame('rejected', $pengajuan->status);
        $this->assertSame('tidak_sesuai_kebutuhan', $pengajuan->rejection_reason);
        $this->assertSame($this->spvUser->id, $pengajuan->approver_id);
        $this->assertSame(['approved', 'rejected', 'skipped'], $pengajuan->approvals->pluck('status')->all());

        // Setelah ditolak, tahap berikutnya tidak bisa diproses.
        $this->setujui($this->managerUser, $pengajuan)->assertSessionHas('error');
    }

    public function test_penolakan_wajib_memilih_alasan(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->actingAs($this->headUser)
            ->post(route('approvals.reject', $pengajuan), ['approver_note' => 'tanpa alasan'])
            ->assertSessionHasErrors('rejection_reason');

        $this->assertSame('pending', $pengajuan->fresh()->status);
    }

    public function test_hanya_pemegang_jabatan_tahap_berjalan_yang_bisa_menolak(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->actingAs($this->managerUser)
            ->post(route('approvals.reject', $pengajuan), ['rejection_reason' => 'duplikat'])
            ->assertSessionHas('error');

        $this->assertSame('pending', $pengajuan->fresh()->status);
    }

    /* ---------------------------------------------------------------
     | Hak akses
     |---------------------------------------------------------------*/

    public function test_pemohon_tidak_bisa_menyetujui_pengajuannya_sendiri(): void
    {
        $pengajuan = $this->buatPengajuan($this->staff);

        // Setelah mengajukan, pemohon kebetulan dipindah ke jabatan tahap pertama.
        $this->staff->update(['position_id' => $this->head->id, 'role' => 'approver']);

        $this->setujui($this->staff, $pengajuan)->assertSessionHas('error');

        $this->assertSame('pending', $pengajuan->fresh()->approvals[0]->status);
    }

    public function test_user_tanpa_jabatan_yang_sesuai_tidak_bisa_memproses(): void
    {
        $pengajuan = $this->buatPengajuan();
        $orangLain = $this->makeUser('approver', 'Approver Lain', $this->k3l);

        $this->setujui($orangLain, $pengajuan)->assertForbidden();
        $this->assertSame('pending', $pengajuan->fresh()->approvals[0]->status);
    }

    public function test_super_admin_dapat_memproses_tahap_berjalan(): void
    {
        $pengajuan = $this->buatPengajuan();
        $super = $this->makeUser('super_admin', 'Super Admin');

        $this->setujui($super, $pengajuan)->assertRedirect(route('approvals.index'));

        $pengajuan->refresh();
        $this->assertSame($super->id, $pengajuan->approvals[0]->user_id);
        $this->assertSame('pending', $pengajuan->status);
    }

    public function test_approver_beda_lokasi_tetap_melihat_pengajuan_pada_tahapnya(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->actingAs($this->headUser)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee($pengajuan->request_number)
            ->assertSee('Giliran Anda');

        // Tahap 2 belum gilirannya: terlihat tetapi bukan giliran.
        $this->actingAs($this->spvUser)
            ->get(route('approvals.index'))
            ->assertOk()
            ->assertSee($pengajuan->request_number)
            ->assertDontSee('Giliran Anda');

        $this->actingAs($this->headUser)
            ->get(route('approvals.show', $pengajuan))
            ->assertOk()
            ->assertSee('Alur Approval')
            ->assertSee('giliran Anda')
            ->assertSee('Setujui Tahap Ini');

        $this->actingAs($this->managerUser)
            ->get(route('approvals.show', $pengajuan))
            ->assertOk()
            ->assertSee('tidak berwenang')
            ->assertDontSee('Setujui Tahap Ini');
    }

    /* ---------------------------------------------------------------
     | Cetak FPB
     |---------------------------------------------------------------*/

    public function test_cetak_fpb_memuat_formulir_lengkap(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->actingAs($this->staff)
            ->get(route('fpb.print', $pengajuan))
            ->assertOk()
            ->assertSee('FORMULIR PENGAJUAN BARANG (FPB)')
            ->assertSee('PT AGROFARM NUSA RAYA')
            ->assertSee('Affair &amp; Legal - HSE', false)
            ->assertSee('OPEX')
            ->assertSee('CAPEX')
            ->assertSee('Emergency Eye Wash Portable')
            ->assertSee('Unicare 30L')
            ->assertSee('TOTAL PENGAJUAN')
            ->assertSee('Diajukan oleh')
            ->assertSee('Staff K3L')
            ->assertSee('Al Haudhu')
            ->assertSee('Head of Corporate Affair &amp; Legal', false)
            ->assertSee('Spv. GA')
            ->assertSee('Manager HCGA')
            ->assertSee('agrofarm-logo.png');
    }

    public function test_cetak_fpb_menghitung_total_unit_dan_mengelompokkan_opex(): void
    {
        $payload = $this->payload();
        $payload['items'] = [
            ['custom_item_name' => 'Pulpen', 'custom_category' => 'atk', 'budget_type' => 'OPEX', 'quantity' => 5, 'unit' => 'pcs'],
            ['custom_item_name' => 'Tisu', 'custom_category' => 'RTK', 'budget_type' => 'OPEX', 'quantity' => 3, 'unit' => 'pack'],
            ['custom_item_name' => 'Plester', 'custom_category' => 'Obat P3K', 'budget_type' => 'OPEX', 'quantity' => 4, 'unit' => 'box'],
            ['custom_item_name' => 'Printer', 'custom_category' => '', 'budget_type' => 'CAPEX', 'quantity' => 1, 'unit' => 'unit'],
        ];

        $pengajuan = $this->buatPengajuan(null, $payload);

        $html = $this->actingAs($this->staff)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();

        // Total unit 5 + 3 + 4 + 1 = 13 (bukan nominal harga).
        $this->assertStringContainsString('TOTAL PENGAJUAN', $html);
        $this->assertMatchesRegularExpression('/TOTAL PENGAJUAN<\/td>\s*<td class="center">13<\/td>/', $html);

        // Barang masuk ke kelompoknya, dan CAPEX tampil setelah OPEX.
        $this->assertLessThan(strpos($html, 'Tisu'), strpos($html, 'Pulpen'));
        $this->assertLessThan(strpos($html, 'Plester'), strpos($html, 'Tisu'));
        $this->assertLessThan(strpos($html, 'Printer'), strpos($html, 'Plester'));
        $this->assertLessThan(strpos($html, 'CAPEX'), strpos($html, 'Plester'));
        $this->assertGreaterThan(strpos($html, 'CAPEX'), strpos($html, 'Printer'));
    }

    public function test_cetak_fpb_tidak_mencantumkan_harga_perkiraan(): void
    {
        $payload = $this->payload();
        $payload['items'][0]['estimated_price'] = 7654321;

        $pengajuan = $this->buatPengajuan(null, $payload);

        $this->actingAs($this->staff)
            ->get(route('fpb.print', $pengajuan))
            ->assertOk()
            ->assertDontSee('7654321')
            ->assertDontSee('7.654.321');
    }

    public function test_nama_dan_tanggal_penyetuju_tercetak_setelah_disetujui(): void
    {
        $pengajuan = $this->buatPengajuan();

        $sebelum = $this->actingAs($this->staff)->get(route('fpb.print', $pengajuan))->getContent();
        $this->assertStringNotContainsString('Wachidyah', $sebelum);

        $this->setujui($this->headUser, $pengajuan);

        $this->actingAs($this->staff)
            ->get(route('fpb.print', $pengajuan))
            ->assertOk()
            ->assertSee('Wachidyah')
            ->assertSee(now()->format('d/m/Y'))
            ->assertDontSee('Yudi')
            ->assertDontSee('Muhammad Rudi');
    }

    public function test_cetak_fpb_menandai_tahap_yang_menolak(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->actingAs($this->headUser)
            ->post(route('approvals.reject', $pengajuan), ['rejection_reason' => 'lainnya']);

        $this->actingAs($this->staff)
            ->get(route('fpb.print', $pengajuan))
            ->assertOk()
            ->assertSee('Wachidyah')
            ->assertSee('Ditolak, ' . now()->format('d/m/Y'));
    }

    public function test_cetak_fpb_hanya_untuk_pihak_terkait_dan_pengajuan_custom(): void
    {
        $pengajuan = $this->buatPengajuan();

        // Pemohon lain tanpa kaitan.
        $lain = $this->makeUser('staff', 'Staff Lain', null, $this->gudang);
        $this->actingAs($lain)->get(route('fpb.print', $pengajuan))->assertForbidden();

        // Pihak yang ada di alur boleh.
        $this->actingAs($this->managerUser)->get(route('fpb.print', $pengajuan))->assertOk();

        // Pengajuan dari stok bukan FPB.
        $stok = Pengajuan::forceCreate([
            'user_id' => $this->staff->id,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudang->id,
            'request_number' => 'REQ-STOK',
            'request_date' => now()->toDateString(),
            'request_type' => 'stock',
            'status' => 'pending',
        ]);

        $this->actingAs($this->staff)->get(route('fpb.print', $stok))->assertNotFound();
    }

    public function test_cetak_fpb_pengajuan_custom_lama_tanpa_alur_bertingkat(): void
    {
        ApprovalStep::query()->update(['is_active' => false]);
        $pengajuan = $this->buatPengajuan();

        $approver = $this->makeUser('approver', 'Approver Lokasi', $this->manager, $this->gudang);
        $this->setujui($approver, $pengajuan, [$pengajuan->details()->first()->id => 2])
            ->assertRedirect(route('approvals.index'));

        $this->actingAs($this->staff)
            ->get(route('fpb.print', $pengajuan))
            ->assertOk()
            ->assertSee('Diajukan oleh')
            ->assertSee('Disetujui oleh')
            ->assertSee('Approver Lokasi');
    }

    public function test_halaman_detail_staff_menampilkan_alur_dan_tombol_cetak(): void
    {
        $pengajuan = $this->buatPengajuan();

        $this->actingAs($this->staff)
            ->get(route('staff.requests.show', $pengajuan->id))
            ->assertOk()
            ->assertSee('Lacak Pengajuan')
            ->assertSee('Cetak FPB')
            ->assertSee('Spv. GA');
    }

    /* ---------------------------------------------------------------
     | Pengaturan: jabatan dan tahap
     |---------------------------------------------------------------*/

    public function test_super_admin_mengelola_master_jabatan(): void
    {
        $super = $this->makeUser('super_admin', 'Super Admin');

        $this->actingAs($super)->get(route('positions.index'))->assertOk()->assertSee('Master Jabatan');

        $this->actingAs($super)
            ->post(route('positions.store'), ['name' => 'Direktur'])
            ->assertRedirect(route('positions.index'));
        $this->assertDatabaseHas('positions', ['name' => 'Direktur', 'is_active' => true]);

        $this->actingAs($super)->post(route('positions.store'), ['name' => 'Direktur'])
            ->assertSessionHasErrors('name');

        $direktur = Position::where('name', 'Direktur')->firstOrFail();

        $this->actingAs($super)
            ->put(route('positions.update', $direktur), ['name' => 'Direktur Utama', 'is_active' => 1])
            ->assertRedirect(route('positions.index'));
        $this->assertSame('Direktur Utama', $direktur->fresh()->name);

        $this->actingAs($super)->delete(route('positions.destroy', $direktur))->assertRedirect(route('positions.index'));
        $this->assertDatabaseMissing('positions', ['id' => $direktur->id]);
    }

    public function test_jabatan_yang_dipakai_tidak_bisa_dihapus_atau_dinonaktifkan(): void
    {
        $super = $this->makeUser('super_admin', 'Super Admin');

        // Dipakai user dan tahap.
        $this->actingAs($super)->delete(route('positions.destroy', $this->spv))->assertSessionHas('error');
        $this->assertDatabaseHas('positions', ['id' => $this->spv->id]);

        // Dipakai tahap aktif: tidak boleh dinonaktifkan.
        $this->actingAs($super)
            ->put(route('positions.update', $this->spv), ['name' => 'Spv. GA'])
            ->assertSessionHas('error');
        $this->assertTrue($this->spv->fresh()->is_active);
    }

    public function test_admin_biasa_tidak_bisa_membuka_pengaturan_jabatan_dan_alur(): void
    {
        $admin = $this->makeUser('admin', 'Admin', null, $this->gudang);

        $this->actingAs($admin)->get(route('positions.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('approval-steps.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('positions.store'), ['name' => 'X'])->assertForbidden();
        $this->actingAs($admin)->post(route('approval-steps.store'), [
            'label' => 'Disetujui oleh',
            'position_id' => $this->head->id,
        ])->assertForbidden();
    }

    public function test_super_admin_mengatur_tahap_approval(): void
    {
        $super = $this->makeUser('super_admin', 'Super Admin');
        $direktur = Position::create(['name' => 'Direktur']);

        $this->actingAs($super)->get(route('approval-steps.index'))
            ->assertOk()
            ->assertSee('Alur Approval Pengajuan Custom')
            ->assertSee('Manager HCGA');

        // Tambah: urutan dilanjutkan.
        $this->actingAs($super)
            ->post(route('approval-steps.store'), ['label' => 'Disetujui oleh', 'position_id' => $direktur->id])
            ->assertRedirect(route('approval-steps.index'));

        $this->assertSame(4, ApprovalStep::where('position_id', $direktur->id)->value('step_order'));

        // Naikkan tahap 4 -> menukar dengan tahap 3.
        $empat = ApprovalStep::where('step_order', 4)->firstOrFail();
        $this->actingAs($super)
            ->patch(route('approval-steps.move', $empat), ['direction' => 'up'])
            ->assertRedirect(route('approval-steps.index'));

        $this->assertSame(3, $empat->fresh()->step_order);
        $this->assertSame(4, ApprovalStep::where('position_id', $this->manager->id)->value('step_order'));

        // Tahap pertama tidak bisa naik lagi: urutan tetap.
        $pertama = ApprovalStep::where('step_order', 1)->firstOrFail();
        $this->actingAs($super)->patch(route('approval-steps.move', $pertama), ['direction' => 'up']);
        $this->assertSame(1, $pertama->fresh()->step_order);

        // Hapus tahap 2: nomor urut dirapatkan.
        $this->actingAs($super)->delete(route('approval-steps.destroy', ApprovalStep::where('step_order', 2)->first()));
        $this->assertSame([1, 2, 3], ApprovalStep::ordered()->pluck('step_order')->all());
    }

    public function test_tahap_tidak_bisa_memakai_jabatan_nonaktif(): void
    {
        $super = $this->makeUser('super_admin', 'Super Admin');
        $lama = Position::create(['name' => 'Jabatan Lama', 'is_active' => false]);

        $this->actingAs($super)
            ->post(route('approval-steps.store'), ['label' => 'Disetujui oleh', 'position_id' => $lama->id])
            ->assertSessionHasErrors('position_id');

        $this->actingAs($super)
            ->post(route('approval-steps.store'), ['label' => 'Label Asal', 'position_id' => $this->head->id])
            ->assertSessionHasErrors('label');
    }

    public function test_user_dapat_diberi_jabatan_dari_manajemen_user(): void
    {
        $super = $this->makeUser('super_admin', 'Super Admin');
        $warehouse = $this->gudang;

        $this->actingAs($super)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Jabatan')
            ->assertSee('Spv. GA');

        $this->actingAs($super)
            ->post(route('users.store'), [
                'name' => 'Staff Baru',
                'email' => 'baru@test.local',
                'role' => 'approver',
                'position_id' => $this->spv->id,
                'entity_ids' => [$this->entity->id],
                'entity_id' => $this->entity->id,
                'warehouse_ids' => [$warehouse->id],
                'warehouse_id' => $warehouse->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame($this->spv->id, User::where('email', 'baru@test.local')->value('position_id'));
    }

    public function test_alur_baru_dapat_dipakai_oleh_user_yang_baru_diberi_jabatan(): void
    {
        $pengajuan = $this->buatPengajuan();
        $pengganti = $this->makeUser('approver', 'Pengganti Head', $this->head);

        $this->setujui($pengganti, $pengajuan)->assertRedirect(route('approvals.index'));

        $this->assertSame($pengganti->id, $pengajuan->fresh()->approvals[0]->user_id);
        $this->assertSame(RequestApproval::APPROVED, $pengajuan->fresh()->approvals[0]->status);
    }
}
