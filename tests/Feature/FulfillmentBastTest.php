<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Request as Pengajuan;
use App\Models\RequestDetail;
use App\Models\RequestFulfillment as Tahap;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Fulfillment;
use App\Support\RequestTimeline;
use App\Support\SignatureStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tahap pembelanjaan setelah FPB disetujui sampai serah terima, dan cetak BAST aset oleh admin.
 */
class FulfillmentBastTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entity;
    private Warehouse $gudang;
    private User $admin;
    private User $staff;
    private User $lain;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(SignatureStore::DISK);

        $this->entity = Entity::forceCreate(['code' => 'ANR', 'name' => 'PT Agrofarm Nusa Raya', 'is_active' => true]);
        $this->gudang = Warehouse::forceCreate(['code' => 'GD', 'name' => 'Gudang Pabrik', 'is_active' => true]);

        $this->admin = $this->makeUser('admin', 'Admin GA', true);
        $this->staff = $this->makeUser('staff', 'Dilla');
        $this->lain = $this->makeUser('staff', 'Orang Lain');
    }

    private function makeUser(string $role, string $name, bool $location = false): User
    {
        return User::forceCreate([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)) . uniqid() . '@test.local',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => $role,
            'is_active' => true,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $location ? $this->gudang->id : null,
        ]);
    }

    private function disetujui(array $override = []): Pengajuan
    {
        $request = Pengajuan::forceCreate(array_merge([
            'user_id' => $this->admin->id,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudang->id,
            'request_number' => 'PGJ-' . uniqid(),
            'request_date' => now()->toDateString(),
            'request_type' => 'custom',
            'status' => 'approved',
            'department' => 'Accounting & Tax',
            'pic_user_id' => $this->staff->id,
            'pic_name' => $this->staff->name,
            'approved_at' => now(),
        ], $override));

        RequestDetail::forceCreate([
            'request_id' => $request->id,
            'custom_item_name' => 'Scanner Epson DS-410',
            'budget_type' => 'CAPEX',
            'quantity' => 1,
            'approved_quantity' => 1,
            'unit' => 'unit',
        ]);

        return $request;
    }

    private function majukan(Pengajuan $request, array $data = []): void
    {
        $this->actingAs($this->admin)
            ->post(route('fulfillments.advance', $request), $data)
            ->assertSessionHasNoErrors();
    }

    private function bastData(Pengajuan $request, array $override = []): array
    {
        $detail = $request->details()->first();

        return array_replace_recursive([
            'note' => 'Dus segel utuh',
            'bast' => [
                'date' => '2026-10-29',
                'place' => 'Ponorogo',
                'first_name' => 'Hanik Mariatul Khoiriah',
                'first_nik' => '22080470',
                'first_position' => 'Staff General Affairs',
                'second_name' => 'Dilla Anggraini',
                'second_nik' => '23010001',
                'second_position' => 'Spv Cash Management',
                'known_name' => 'Yudi Ardiyanto',
                'known_position' => 'Spv. GA',
            ],
            'items' => [
                $detail->id => [
                    'name' => 'Scanner Epson DS-410',
                    'specification' => 'ADF 50 lembar, USB 3.0',
                    'asset_code' => 'AST-IT-0042',
                    'quantity' => 1,
                    'condition' => 'Baik',
                ],
            ],
        ], $override);
    }

    private function sampaiDiserahkan(Pengajuan $request, array $override = []): void
    {
        $this->majukan($request);
        $this->majukan($request, ['note' => 'Pembayaran diajukan ke Finance lewat FPB']);
        $this->majukan($request, ['vendor' => 'Toko Maju', 'po_number' => 'PO-77', 'eta_date' => '2026-11-05']);
        $this->majukan($request);
        $this->majukan($request, $this->bastData($request, $override));
    }

    private function sampaiBarangTiba(Pengajuan $request): void
    {
        $this->majukan($request);
        $this->majukan($request);
        $this->majukan($request);
        $this->majukan($request);
    }

    public function test_admin_memajukan_tahap_berurutan_termasuk_pembayaran_ke_finance_dan_bast_langsung_selesai(): void
    {
        $request = $this->disetujui();

        $this->assertNull(Fulfillment::currentStage($request));
        $this->assertSame('purchasing', Fulfillment::nextAdminStage($request));

        $this->majukan($request);
        $this->assertSame(Tahap::PAYMENT_REQUESTED, Fulfillment::nextAdminStage($request->fresh()));

        $this->majukan($request, ['note' => 'Pembayaran diajukan']);
        $this->assertSame('Pembayaran diajukan ke Finance', RequestTimeline::summary($request->fresh())['label']);

        $this->majukan($request, ['vendor' => 'Toko Maju', 'po_number' => 'PO-77', 'eta_date' => '2026-11-05']);
        $this->majukan($request);
        $this->majukan($request, $this->bastData($request));

        $request->refresh();
        $this->assertSame('handed_over', Fulfillment::currentStage($request));
        $this->assertTrue(Fulfillment::isComplete($request));
        $this->assertStringStartsWith('BAST-' . now()->format('Ymd') . '-', $request->bast_number);
        $this->assertSame($this->admin->id, $request->handed_over_by);
        $this->assertSame('Spv Cash Management', $request->bast_second_position);
        $this->assertSame(5, $request->fulfillments()->count());
        $this->assertSame('AST-IT-0042', $request->details()->first()->asset_code);
        $this->assertSame('done', RequestTimeline::summary($request)['state']);

        // Tidak ada tahap sesudah serah terima.
        $this->assertNull(Fulfillment::nextAdminStage($request));
        $this->actingAs($this->admin)->post(route('fulfillments.advance', $request))->assertSessionHas('error');
    }

    public function test_serah_terima_wajib_mengisi_formulir_bast(): void
    {
        $request = $this->disetujui();
        $this->sampaiBarangTiba($request);

        $this->actingAs($this->admin)
            ->post(route('fulfillments.advance', $request), ['note' => 'tanpa form'])
            ->assertSessionHasErrors(['bast', 'items']);

        $this->assertFalse(Fulfillment::isComplete($request->fresh()));
    }

    public function test_admin_langsung_cetak_bast_dengan_formulir_custom_tanpa_konfirmasi_penerima(): void
    {
        $request = $this->disetujui();
        $this->sampaiDiserahkan($request);

        $html = $this->actingAs($this->admin)->get(route('bast.print', $request->id))->assertOk()->getContent();

        $this->assertStringContainsString('BERITA ACARA SERAH TERIMA BARANG INVENTARIS', $html);
        $this->assertStringContainsString($request->fresh()->bast_number, $html);
        $this->assertStringContainsString('Hanik Mariatul Khoiriah', $html);
        $this->assertStringContainsString('22080470', $html);
        $this->assertStringContainsString('Staff General Affairs', $html);
        $this->assertStringContainsString('Dilla Anggraini', $html);
        $this->assertStringContainsString('23010001', $html);
        $this->assertStringContainsString('Spv Cash Management', $html);
        $this->assertStringContainsString('Scanner Epson DS-410', $html);
        $this->assertStringContainsString('ADF 50 lembar', $html);
        $this->assertStringContainsString('AST-IT-0042', $html);
        $this->assertStringContainsString('Yudi Ardiyanto', $html);
        $this->assertStringContainsString('1 (Satu) unit', $html);
        $this->assertStringContainsString('Surat Komitmen Pemeliharaan Fasilitas Kantor', $html);
    }

    public function test_admin_dapat_mengoreksi_data_bast_setelah_serah_terima(): void
    {
        $request = $this->disetujui();
        $this->sampaiDiserahkan($request);

        $this->actingAs($this->admin)
            ->put(route('fulfillments.bast.update', $request), $this->bastData($request, [
                'items' => [$request->details()->first()->id => ['asset_code' => 'AST-IT-0099', 'condition' => 'Baru']],
            ]))
            ->assertRedirect(route('fulfillments.show', $request));

        $this->assertSame('AST-IT-0099', $request->details()->first()->asset_code);

        $html = $this->actingAs($this->admin)->get(route('bast.print', $request->id))->getContent();
        $this->assertStringContainsString('AST-IT-0099', $html);
        $this->assertStringNotContainsString('AST-IT-0042', $html);
    }

    public function test_koreksi_bast_ditolak_sebelum_serah_terima(): void
    {
        $request = $this->disetujui();

        $this->actingAs($this->admin)
            ->put(route('fulfillments.bast.update', $request), $this->bastData($request))
            ->assertSessionHas('error');
    }

    public function test_formulir_awal_terisi_dari_admin_dan_pic(): void
    {
        $this->admin->forceFill(['nik' => '11112222'])->save();
        $this->staff->forceFill(['nik' => '33334444'])->save();

        $request = $this->disetujui();
        $this->sampaiBarangTiba($request);

        $this->actingAs($this->admin)->get(route('fulfillments.show', $request))
            ->assertOk()
            ->assertSee('Formulir BAST Aset')
            ->assertSee('11112222')
            ->assertSee('33334444')
            ->assertSee('Dilla')
            ->assertSee('Scanner Epson DS-410');
    }

    public function test_tahap_pembelanjaan_hanya_untuk_fpb_custom_yang_disetujui(): void
    {
        $pending = $this->disetujui(['status' => 'pending']);

        $this->actingAs($this->admin)->get(route('fulfillments.show', $pending))->assertNotFound();
        $this->actingAs($this->admin)->post(route('fulfillments.advance', $pending))->assertNotFound();
    }

    public function test_staff_tidak_bisa_membuka_halaman_pembelanjaan(): void
    {
        $request = $this->disetujui();

        $this->actingAs($this->staff)->get(route('fulfillments.index'))->assertForbidden();
        $this->actingAs($this->staff)->post(route('fulfillments.advance', $request))->assertForbidden();
        $this->actingAs($this->staff)->put(route('fulfillments.bast.update', $request))->assertForbidden();
    }

    public function test_daftar_pembelanjaan_memisahkan_proses_dan_selesai(): void
    {
        $this->disetujui(['request_number' => 'PGJ-PROSES']);
        $selesai = $this->disetujui(['request_number' => 'PGJ-SELESAI']);

        $this->sampaiDiserahkan($selesai);

        $this->actingAs($this->admin)->get(route('fulfillments.index'))
            ->assertOk()->assertSee('PGJ-PROSES')->assertDontSee('PGJ-SELESAI');

        $this->actingAs($this->admin)->get(route('fulfillments.index', ['tab' => 'selesai']))
            ->assertOk()->assertSee('PGJ-SELESAI')->assertDontSee('PGJ-PROSES');
    }

    public function test_staff_pic_tidak_perlu_konfirmasi_dan_tetap_bisa_mencetak_bast(): void
    {
        $request = $this->disetujui();
        $this->sampaiDiserahkan($request);

        $this->actingAs($this->staff)
            ->get(route('staff.requests.show', $request->id))
            ->assertOk()
            ->assertDontSee('Konfirmasi Barang Diterima')
            ->assertSee('Cetak BAST');

        $this->actingAs($this->staff)->get(route('bast.print', $request->id))->assertOk();
        $this->actingAs($this->lain)->get(route('bast.print', $request->id))->assertForbidden();
    }

    public function test_bast_belum_bisa_dicetak_sebelum_serah_terima(): void
    {
        $request = $this->disetujui();

        $this->actingAs($this->admin)->get(route('bast.print', $request->id))->assertRedirect();
    }

    public function test_pelacakan_menampilkan_tahap_pembelanjaan_sampai_diserahkan(): void
    {
        $request = $this->disetujui();

        $this->assertSame('Disetujui, menunggu proses pembelian', RequestTimeline::summary($request->fresh())['label']);

        $this->majukan($request);
        $this->majukan($request);
        $this->majukan($request, ['vendor' => 'Toko Maju', 'po_number' => 'PO-77', 'eta_date' => '2026-11-05']);

        $events = RequestTimeline::events($request->fresh());

        $this->assertSame('Menunggu: Barang tiba', $events[0]['title']);
        $this->assertSame('Barang dipesan', $events[1]['title']);
        $this->assertStringContainsString('Vendor: Toko Maju', $events[1]['detail']);
        $this->assertStringContainsString('No. PO: PO-77', $events[1]['detail']);
        $this->assertStringContainsString('Estimasi tiba: 05-11-2026', $events[1]['detail']);
        $this->assertSame('Pembayaran diajukan ke Finance', $events[2]['title']);

        $this->majukan($request);
        $this->majukan($request, $this->bastData($request));

        $events = RequestTimeline::events($request->fresh());
        $this->assertSame('Diserahkan ke penerima (BAST)', $events[0]['title']);
        $this->assertSame('done', RequestTimeline::summary($request->fresh())['state']);
    }

    public function test_penerima_hanya_pic_yang_ditunjuk_admin_bisa_melihat_pengajuannya(): void
    {
        $request = $this->disetujui(['request_number' => 'PGJ-UNTUK-PIC']);

        $this->actingAs($this->staff)->get(route('staff.requests.index'))->assertOk()->assertSee('PGJ-UNTUK-PIC');
        $this->actingAs($this->staff)->get(route('staff.requests.show', $request->id))->assertOk();
        $this->actingAs($this->lain)->get(route('staff.requests.show', $request->id))->assertNotFound();
    }

    public function test_kop_surat_per_perusahaan_dipakai_sebagai_latar_bast(): void
    {
        $super = $this->makeUser('super_admin', 'Super Kop');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');

        $request = $this->disetujui();
        $this->sampaiDiserahkan($request);

        // Tanpa kop: judul memakai nama perusahaan.
        $html = $this->actingAs($this->admin)->get(route('bast.print', $request->id))->getContent();
        $this->assertStringNotContainsString('class="kop"', $html);
        $this->assertStringContainsString('PT AGROFARM NUSA RAYA', $html);

        $this->actingAs($super)
            ->put(route('entities.update', $this->entity), [
                'code' => 'ANR',
                'name' => 'PT Agrofarm Nusa Raya',
                'is_active' => 1,
                'letterhead' => \Illuminate\Http\UploadedFile::fake()->createWithContent('kop.png', $png),
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($this->entity->fresh()->letterhead_path);

        $html = $this->actingAs($this->admin)->get(route('bast.print', $request->id))->getContent();
        $this->assertStringContainsString('class="kop"', $html);
        $this->assertSame(2, substr_count($html, 'class="kop"'));
        $this->assertStringContainsString('data:image/png;base64,', $html);

        $this->actingAs($super)
            ->put(route('entities.update', $this->entity), [
                'code' => 'ANR',
                'name' => 'PT Agrofarm Nusa Raya',
                'is_active' => 1,
                'remove_letterhead' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($this->entity->fresh()->letterhead_path);
    }

    public function test_logo_perusahaan_dipakai_pada_cetak_fpb(): void
    {
        $super = $this->makeUser('super_admin', 'Super Logo');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $request = $this->disetujui();

        $html = $this->actingAs($this->admin)->get(route('fpb.print', $request->id))->getContent();
        $this->assertStringContainsString('images/agrofarm-logo.png', $html);

        $this->actingAs($super)
            ->put(route('entities.update', $this->entity), [
                'code' => 'ANR',
                'name' => 'PT Agrofarm Nusa Raya',
                'is_active' => 1,
                'logo' => \Illuminate\Http\UploadedFile::fake()->createWithContent('logo.png', $png),
            ])
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($this->admin)->get(route('fpb.print', $request->id))->getContent();
        $this->assertStringNotContainsString('images/agrofarm-logo.png', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
    }
}
