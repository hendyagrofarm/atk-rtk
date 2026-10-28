<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Entity;
use App\Models\Position;
use App\Models\Request as Pengajuan;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\SignatureStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tanda tangan digital PIC (kotak "Diajukan oleh") dan kesesuaian isian form dengan
 * formulir referensi (Emergency Eye Wash Portable).
 */
class PicSignatureTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private Entity $entity;
    private Warehouse $gudang;
    private User $admin;
    private User $staff;
    private User $atasan;
    private User $spvUser;
    private User $managerUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(SignatureStore::DISK);

        $this->entity = Entity::forceCreate(['code' => 'ANR', 'name' => 'PT Agrofarm Nusa Raya', 'is_active' => true]);
        $this->gudang = Warehouse::forceCreate(['code' => 'GD', 'name' => 'Gudang Pabrik', 'is_active' => true]);

        $spv = Position::create(['name' => 'Spv. GA']);
        $manager = Position::create(['name' => 'Manager HCGA']);
        $k3l = Position::create(['name' => 'Staff K3L']);
        $kepala = Position::create(['name' => 'Accounting & Tax Manager']);

        ApprovalStep::create(['step_order' => 1, 'label' => 'Diketahui oleh', 'position_id' => $spv->id, 'is_active' => true]);
        ApprovalStep::create(['step_order' => 2, 'label' => 'Disetujui oleh', 'position_id' => $manager->id, 'is_active' => true]);

        $this->admin = $this->makeUser('admin', 'Admin GA', null, $this->gudang);
        $this->staff = $this->makeUser('staff', 'Dilla', $k3l, $this->gudang);
        $this->atasan = $this->makeUser('staff', 'Muhammad Adlan', $kepala, $this->gudang);
        $this->spvUser = $this->makeUser('approver', 'SPV GA', $spv);
        $this->managerUser = $this->makeUser('approver', 'Manager HCGA', $manager);
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

    private function png(string $name = 'ttd.png', ?int $width = null): UploadedFile
    {
        $bytes = base64_decode(self::PNG_BASE64);

        if ($width !== null) {
            $bytes = substr_replace($bytes, pack('N', $width), 16, 4);
        }

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    private function simpanTandaTangan(User $user, ?int $width = null): void
    {
        SignatureStore::store($user, $this->png('ttd.png', $width));
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'entity_id' => $this->entity->id,
            'department' => 'Accounting & Tax',
            'superior_user_id' => $this->atasan->id,
            'items' => [[
                'custom_item_name' => 'acer core i3',
                'budget_type' => 'CAPEX',
                'brand_type' => 'acer',
                'quantity' => 1,
                'unit' => 'unit',
            ]],
        ], $override);
    }

    private function buat(User $by, array $override = []): Pengajuan
    {
        $this->actingAs($by)
            ->post(route('staff.requests.custom.store'), $this->payload($override))
            ->assertRedirect(route('staff.requests.index'));

        return Pengajuan::latest('id')->firstOrFail();
    }

    private function selesaikan(Pengajuan $pengajuan): void
    {
        foreach ([$this->atasan, $this->spvUser, $this->managerUser] as $penyetuju) {
            $this->actingAs($penyetuju)
                ->post(route('approvals.approve', $pengajuan))
                ->assertRedirect(route('approvals.index'));
        }
    }

    private function jumlahGambarTtd(string $html): int
    {
        return substr_count($html, 'src="data:image/png;base64,');
    }

    /* ---------------------------------------------------------------
     | Tanda tangan digital PIC
     |---------------------------------------------------------------*/

    public function test_staff_pembuat_menjadi_pic_dan_tanda_tangannya_disalin(): void
    {
        $this->simpanTandaTangan($this->staff);

        $pengajuan = $this->buat($this->staff);

        $this->assertSame($this->staff->id, $pengajuan->pic_user_id);
        $this->assertSame('Dilla', $pengajuan->pic_name);
        $this->assertSame('Staff K3L', $pengajuan->pic_position);
        $this->assertSame('signatures/requests/' . $pengajuan->id . '-pic.png', $pengajuan->pic_signature_path);
        Storage::disk(SignatureStore::DISK)->assertExists($pengajuan->pic_signature_path);
    }

    public function test_fpb_final_memuat_tanda_tangan_pic_staff_dan_semua_penyetuju(): void
    {
        foreach ([$this->staff, $this->atasan, $this->spvUser, $this->managerUser] as $orang) {
            $this->simpanTandaTangan($orang);
        }

        $pengajuan = $this->buat($this->staff);
        $this->selesaikan($pengajuan);

        $html = $this->actingAs($this->staff)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();

        // PIC + atasan + Spv. GA + Manager HCGA = 4 tanda tangan digital.
        $this->assertSame(4, $this->jumlahGambarTtd($html));

        preg_match_all('/<td class="label">([^<]+)<\/td>/', $html, $labels);
        $this->assertSame(['Diajukan oleh', 'Diketahui oleh', 'Diketahui oleh', 'Disetujui oleh'], $labels[1]);

        foreach (['Dilla', 'Muhammad Adlan', 'SPV GA', 'Manager HCGA', 'Accounting &amp; Tax Manager'] as $teks) {
            $this->assertStringContainsString($teks, $html);
        }
    }

    public function test_staff_tanpa_tanda_tangan_tercetak_kosong_tetapi_nama_tetap_ada(): void
    {
        foreach ([$this->atasan, $this->spvUser, $this->managerUser] as $orang) {
            $this->simpanTandaTangan($orang);
        }

        $pengajuan = $this->buat($this->staff);
        $this->assertNull($pengajuan->pic_signature_path);

        $this->selesaikan($pengajuan);

        $html = $this->actingAs($this->staff)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();

        $this->assertSame(3, $this->jumlahGambarTtd($html));
        $this->assertStringContainsString('Dilla', $html);
    }

    public function test_tanda_tangan_pic_yang_diunggah_sebelum_fpb_selesai_ikut_tercetak(): void
    {
        foreach ([$this->atasan, $this->spvUser, $this->managerUser] as $orang) {
            $this->simpanTandaTangan($orang);
        }

        $pengajuan = $this->buat($this->staff);
        $this->assertNull($pengajuan->pic_signature_path);

        // Admin mengunggah tanda tangan staff setelah FPB dibuat, sebelum disetujui penuh.
        $this->simpanTandaTangan($this->staff);

        $this->selesaikan($pengajuan);

        $this->assertNotNull($pengajuan->fresh()->pic_signature_path);

        $html = $this->actingAs($this->staff)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();
        $this->assertSame(4, $this->jumlahGambarTtd($html));
    }

    public function test_mengganti_tanda_tangan_pic_tidak_mengubah_salinan_di_fpb(): void
    {
        $this->simpanTandaTangan($this->staff);
        $pengajuan = $this->buat($this->staff);

        $salinan = Storage::disk(SignatureStore::DISK)->get($pengajuan->pic_signature_path);

        $this->simpanTandaTangan($this->staff, 50);

        $this->assertNotSame($salinan, Storage::disk(SignatureStore::DISK)->get($this->staff->fresh()->signature_path));
        $this->assertSame($salinan, Storage::disk(SignatureStore::DISK)->get($pengajuan->fresh()->pic_signature_path));
    }

    public function test_staff_tidak_bisa_mengganti_pic_menjadi_orang_lain(): void
    {
        $this->simpanTandaTangan($this->spvUser);

        $pengajuan = $this->buat($this->staff, [
            'pic_user_id' => $this->spvUser->id,
            'pic_name' => 'Orang Lain',
        ]);

        $this->assertSame($this->staff->id, $pengajuan->pic_user_id);
        $this->assertSame('Dilla', $pengajuan->pic_name);
        $this->assertNull($pengajuan->pic_signature_path);
    }

    /* ---------------------------------------------------------------
     | Admin mengisi atas nama PIC
     |---------------------------------------------------------------*/

    public function test_admin_memilih_pic_dari_sistem_dan_tanda_tangannya_disalin(): void
    {
        $this->simpanTandaTangan($this->staff);

        $pengajuan = $this->buat($this->admin, [
            'pic_user_id' => $this->staff->id,
            'pic_name' => 'Isian manual diabaikan',
            'pic_position' => 'Jabatan manual diabaikan',
        ]);

        $this->assertSame($this->admin->id, $pengajuan->user_id);
        $this->assertSame($this->staff->id, $pengajuan->pic_user_id);
        $this->assertSame('Dilla', $pengajuan->pic_name);
        $this->assertSame('Staff K3L', $pengajuan->pic_position);
        $this->assertNotNull($pengajuan->pic_signature_path);
    }

    public function test_admin_tidak_wajib_mengisi_nama_manual_bila_pic_dipilih_dari_sistem(): void
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload(['pic_user_id' => $this->staff->id]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('staff.requests.index'));
    }

    public function test_admin_tanpa_pic_dari_sistem_wajib_mengisi_nama_manual(): void
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload())
            ->assertSessionHasErrors('pic_name');

        $this->assertSame(0, Pengajuan::count());
    }

    public function test_pic_manual_tanpa_akun_tidak_membawa_tanda_tangan_digital(): void
    {
        $pengajuan = $this->buat($this->admin, [
            'pic_name' => 'Karyawan Tanpa Akun',
            'pic_position' => 'Operator',
        ]);

        $this->assertNull($pengajuan->pic_user_id);
        $this->assertSame('Karyawan Tanpa Akun', $pengajuan->pic_name);
        $this->assertSame('Operator', $pengajuan->pic_position);
        $this->assertNull($pengajuan->pic_signature_path);
    }

    public function test_admin_mengunggah_tanda_tangan_pic_dari_form_dan_langsung_tersalin(): void
    {
        $this->assertFalse(SignatureStore::userHasSignature($this->staff));

        $pengajuan = $this->buat($this->admin, [
            'pic_user_id' => $this->staff->id,
            'pic_signature' => $this->png(),
        ]);

        $this->assertTrue(SignatureStore::userHasSignature($this->staff->fresh()));
        $this->assertNotNull($pengajuan->pic_signature_path);
        Storage::disk(SignatureStore::DISK)->assertExists($pengajuan->pic_signature_path);
    }

    public function test_unggahan_tanda_tangan_pic_bukan_png_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload([
                'pic_user_id' => $this->staff->id,
                'pic_signature' => UploadedFile::fake()->createWithContent('ttd.png', 'bukan gambar'),
            ]))
            ->assertSessionHasErrors('pic_signature');

        $this->assertSame(0, Pengajuan::count());
    }

    public function test_pic_tidak_boleh_sama_dengan_atasan_langsung(): void
    {
        $this->actingAs($this->admin)
            ->post(route('staff.requests.custom.store'), $this->payload([
                'pic_user_id' => $this->atasan->id,
                'superior_user_id' => $this->atasan->id,
            ]))
            ->assertSessionHasErrors('superior_user_id');

        $this->assertSame(0, Pengajuan::count());
    }

    public function test_staff_tidak_bisa_memilih_dirinya_sendiri_sebagai_atasan(): void
    {
        $this->actingAs($this->staff)
            ->post(route('staff.requests.custom.store'), $this->payload(['superior_user_id' => $this->staff->id]))
            ->assertSessionHasErrors('superior_user_id');
    }

    /* ---------------------------------------------------------------
     | Form pengajuan custom
     |---------------------------------------------------------------*/

    public function test_form_admin_menampilkan_pemilih_pic_dan_unggah_tanda_tangan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertSee('name="pic_user_id"', false)
            ->assertSee('name="pic_signature"', false)
            ->assertSee('Dilla - Staff K3L (belum ada TTD)', false)
            ->assertSee('name="superior_user_id"', false);
    }

    public function test_form_staff_menjelaskan_status_tanda_tangan_digital_miliknya(): void
    {
        $this->actingAs($this->staff)
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertSee('Anda belum punya tanda tangan digital')
            ->assertDontSee('name="pic_user_id"', false);

        $this->simpanTandaTangan($this->staff);

        $this->actingAs($this->staff)
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertSee('Tanda tangan digital Anda akan tercetak otomatis')
            ->assertDontSee('Anda belum punya tanda tangan digital');
    }

    public function test_form_memakai_contoh_yang_seragam_dengan_formulir_referensi(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->getContent();

        // Contoh pada semua kolom mengacu pada barang yang sama dengan formulir referensi.
        foreach ([
            'Contoh: Emergency Eye Wash Portable',
            'Contoh: Unicare 30L',
            'Contoh: pcs',
            'Contoh: 2',
            'Sebagai fasilitas tanggap darurat pembilasan mata',
            'Contoh: Affair & Legal - HSE',
        ] as $contoh) {
            $this->assertStringContainsString($contoh, $html, "Contoh '{$contoh}' tidak ada pada form");
        }

        // Contoh lama yang tidak relevan sudah hilang.
        foreach (['Laptop Lenovo', 'kebutuhan laptop', 'RAM 16 GB', 'Perangkat IT'] as $lama) {
            $this->assertStringNotContainsString($lama, $html, "Contoh lama '{$lama}' masih ada");
        }
    }

    public function test_urutan_kolom_form_mengikuti_tabel_formulir(): void
    {
        $html = $this->actingAs($this->admin)->get(route('staff.requests.custom.create'))->assertOk()->getContent();

        $urutan = ['Nama Barang', 'Type / Merk', 'Jumlah', 'Satuan', 'Digunakan Untuk', 'Keterangan'];
        $posisi = array_map(fn ($label) => strpos($html, $label . ' <span') !== false
            ? strpos($html, $label . ' <span')
            : strpos($html, $label), $urutan);

        $this->assertNotContains(false, $posisi);

        // Dalam satu kartu barang, label muncul berurutan seperti kolom pada formulir.
        $kartu = substr($html, strpos($html, 'Barang 1'));
        $urut = [];
        foreach ($urutan as $label) {
            $urut[$label] = strpos($kartu, $label);
        }

        $nilai = array_values($urut);
        $terurut = $nilai;
        sort($terurut);
        $this->assertSame($terurut, $nilai, 'Urutan kolom form tidak sama dengan tabel formulir');
    }

    public function test_kolom_tidak_tercetak_dipisah_pada_bagian_informasi_tambahan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertSee('Informasi tambahan (opsional, tidak tercetak di FPB)')
            ->assertSee('Estimasi Harga per Satuan')
            ->assertSee('Link Produk');
    }

    public function test_form_menampilkan_alur_persetujuan_tetap_dan_kelompok_opex(): void
    {
        $this->actingAs($this->admin)
            ->get(route('staff.requests.custom.create'))
            ->assertOk()
            ->assertSee('atasan langsung → Spv. GA → Manager HCGA')
            ->assertSee('Kelompok OPEX')
            ->assertSee('<option value="Sarana Prasarana"', false)
            ->assertDontSee('Obat P3K')
            ->assertSee('CAPEX (investasi/aset)');
    }

    public function test_cetak_fpb_memakai_kelompok_sarana_prasarana_dan_data_lama_ikut_dikelompokkan(): void
    {
        $payload = $this->payload();
        $payload['items'] = [
            ['custom_item_name' => 'Kran Air', 'budget_type' => 'OPEX', 'custom_category' => 'Sarana Prasarana', 'quantity' => 2, 'unit' => 'pcs'],
            // Nilai lama sebelum kelompok diganti nama.
            ['custom_item_name' => 'Plester Luka', 'budget_type' => 'OPEX', 'custom_category' => 'Obat P3K', 'quantity' => 3, 'unit' => 'box'],
        ];

        $pengajuan = $this->buat($this->admin, $payload + ['pic_name' => 'PIC Manual']);
        $this->selesaikan($pengajuan);

        $html = $this->actingAs($this->admin)->get(route('fpb.print', $pengajuan))->assertOk()->getContent();

        $this->assertStringContainsString('SARANA PRASARANA', $html);
        $this->assertStringNotContainsString('OBAT P3K', $html);
        $this->assertSame(1, substr_count($html, '>SARANA PRASARANA<'), 'Kelompok tidak boleh muncul ganda');

        // Kedua barang berada di bawah kelompok Sarana Prasarana, sebelum CAPEX.
        $posisiKelompok = strpos($html, '>SARANA PRASARANA<');
        $this->assertGreaterThan($posisiKelompok, strpos($html, 'Kran Air'));
        $this->assertGreaterThan($posisiKelompok, strpos($html, 'Plester Luka'));
        $this->assertStringNotContainsString('>CAPEX<', $html, 'Bagian yang tidak diajukan tidak ditampilkan');
    }

    public function test_kelompok_opex_tidak_dikirim_untuk_barang_capex_dan_tersimpan_untuk_opex(): void
    {
        $payload = $this->payload();
        $payload['items'] = [
            ['custom_item_name' => 'Pulpen', 'budget_type' => 'OPEX', 'custom_category' => 'ATK', 'quantity' => 5, 'unit' => 'pcs'],
            ['custom_item_name' => 'Eye Wash', 'budget_type' => 'CAPEX', 'quantity' => 2, 'unit' => 'pcs'],
        ];

        $pengajuan = $this->buat($this->admin, $payload + ['pic_name' => 'PIC Manual']);

        $this->assertSame('ATK', $pengajuan->details->firstWhere('custom_item_name', 'Pulpen')->custom_category);
        $this->assertNull($pengajuan->details->firstWhere('custom_item_name', 'Eye Wash')->custom_category);
    }
}
