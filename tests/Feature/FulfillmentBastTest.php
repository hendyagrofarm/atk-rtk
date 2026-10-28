<?php

namespace Tests\Feature;

use App\Models\Entity;
use App\Models\Request as Pengajuan;
use App\Models\RequestDetail;
use App\Models\RequestFulfillment;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\Fulfillment;
use App\Support\RequestTimeline;
use App\Support\SignatureStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tahap pembelanjaan setelah FPB disetujui sampai barang diterima, dan cetak BAST.
 */
class FulfillmentBastTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

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

    private function signature(User $user): void
    {
        SignatureStore::store($user, UploadedFile::fake()->createWithContent('ttd.png', base64_decode(self::PNG_BASE64)));
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

    private function sampaiDiserahkan(Pengajuan $request): void
    {
        $this->majukan($request);
        $this->majukan($request, ['vendor' => 'Toko Maju', 'po_number' => 'PO-77', 'eta_date' => '2026-11-05']);
        $this->majukan($request);
        $this->majukan($request, ['note' => 'Kondisi baik, dus segel utuh']);
    }

    public function test_admin_memajukan_tahap_berurutan_dan_bast_dibuat_saat_diserahkan(): void
    {
        $request = $this->disetujui();

        $this->assertNull(Fulfillment::currentStage($request));
        $this->assertSame('purchasing', Fulfillment::nextAdminStage($request));

        $this->sampaiDiserahkan($request);

        $request->refresh();
        $this->assertSame('handed_over', Fulfillment::currentStage($request));
        $this->assertStringStartsWith('BAST-' . now()->format('Ymd') . '-', $request->bast_number);
        $this->assertSame('Kondisi baik, dus segel utuh', $request->bast_note);
        $this->assertSame($this->admin->id, $request->handed_over_by);

        $ordered = $request->fulfillments->firstWhere('stage', 'ordered');
        $this->assertSame('Toko Maju', $ordered->vendor);
        $this->assertSame('PO-77', $ordered->po_number);

        // Admin tidak bisa melompati ke tahap berikutnya setelah serah terima.
        $this->actingAs($this->admin)
            ->post(route('fulfillments.advance', $request))
            ->assertSessionHas('error');
        $this->assertSame(4, $request->fulfillments()->count());
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
    }

    public function test_daftar_pembelanjaan_memisahkan_proses_dan_selesai(): void
    {
        $proses = $this->disetujui(['request_number' => 'PGJ-PROSES']);
        $selesai = $this->disetujui(['request_number' => 'PGJ-SELESAI']);

        $this->sampaiDiserahkan($selesai);
        $this->actingAs($this->staff)->post(route('staff.requests.receive', $selesai->id))->assertRedirect();

        $this->actingAs($this->admin)->get(route('fulfillments.index'))
            ->assertOk()->assertSee('PGJ-PROSES')->assertDontSee('PGJ-SELESAI');

        $this->actingAs($this->admin)->get(route('fulfillments.index', ['tab' => 'selesai']))
            ->assertOk()->assertSee('PGJ-SELESAI')->assertDontSee('PGJ-PROSES');
    }

    public function test_penerima_berakun_mengonfirmasi_dengan_tanda_tangan_digital_lalu_bast_tercetak(): void
    {
        $this->signature($this->admin);
        $this->signature($this->staff);

        $request = $this->disetujui();
        $this->sampaiDiserahkan($request);

        // BAST belum bisa dicetak sebelum penerima mengonfirmasi.
        $this->actingAs($this->admin)->get(route('bast.print', $request->id))->assertRedirect();

        $this->actingAs($this->staff)
            ->get(route('staff.requests.show', $request->id))
            ->assertOk()
            ->assertSee('Konfirmasi Barang Diterima');

        $this->actingAs($this->staff)
            ->post(route('staff.requests.receive', $request->id), ['note' => 'Lengkap'])
            ->assertRedirect(route('staff.requests.show', $request->id));

        $request->refresh();
        $this->assertTrue(Fulfillment::isComplete($request));
        $this->assertFalse($request->received_on_behalf);
        $this->assertSame($this->staff->id, $request->received_user_id);
        $this->assertTrue(SignatureStore::exists($request->received_signature_path));

        $html = $this->actingAs($this->staff)->get(route('bast.print', $request->id))->assertOk()->getContent();
        $this->assertStringContainsString('BERITA ACARA SERAH TERIMA BARANG', $html);
        $this->assertStringContainsString($request->bast_number, $html);
        $this->assertStringContainsString('Scanner Epson DS-410', $html);
        $this->assertStringContainsString('Kondisi baik, dus segel utuh', $html);
        $this->assertSame(2, substr_count($html, 'src="data:image/png;base64,'));
    }

    public function test_hanya_penerima_yang_boleh_mengonfirmasi_dan_hanya_setelah_diserahkan(): void
    {
        $request = $this->disetujui();

        // Belum diserahkan.
        $this->actingAs($this->staff)
            ->post(route('staff.requests.receive', $request->id))
            ->assertSessionHas('receipt_error');

        $this->sampaiDiserahkan($request);

        // Staff lain bukan pembuat/PIC: pengajuan tidak terlihat.
        $this->actingAs($this->lain)
            ->post(route('staff.requests.receive', $request->id))
            ->assertNotFound();
        $this->assertFalse(Fulfillment::isComplete($request->fresh()));
    }

    public function test_admin_mengonfirmasi_atas_nama_penerima_tanpa_akun_dan_penerima_berakun_tidak_bisa_dilewati(): void
    {
        $manual = $this->disetujui(['pic_user_id' => null, 'pic_name' => 'Pak Budi (Manual)']);
        $this->sampaiDiserahkan($manual);

        $this->actingAs($this->admin)
            ->post(route('fulfillments.receive', $manual), ['note' => 'TTD basah'])
            ->assertRedirect(route('fulfillments.show', $manual));

        $manual->refresh();
        $this->assertTrue(Fulfillment::isComplete($manual));
        $this->assertTrue($manual->received_on_behalf);
        $this->assertNull($manual->received_user_id);
        $this->assertSame('Pak Budi (Manual)', $manual->received_name);

        $html = $this->actingAs($this->admin)->get(route('bast.print', $manual->id))->assertOk()->getContent();
        $this->assertStringContainsString('Pak Budi (Manual)', $html);
        $this->assertStringContainsString('atas nama penerima', $html);

        // Penerima berakun: admin tidak boleh mengonfirmasi menggantikannya.
        $berakun = $this->disetujui();
        $this->sampaiDiserahkan($berakun);

        $this->actingAs($this->admin)
            ->post(route('fulfillments.receive', $berakun))
            ->assertSessionHas('error');
        $this->assertFalse(Fulfillment::isComplete($berakun->fresh()));
    }

    public function test_pelacakan_menampilkan_tahap_pembelanjaan_sampai_diterima(): void
    {
        $request = $this->disetujui();

        $summary = RequestTimeline::summary($request->fresh());
        $this->assertSame('Disetujui, menunggu proses pembelian', $summary['label']);

        $this->majukan($request);
        $this->majukan($request, ['vendor' => 'Toko Maju', 'po_number' => 'PO-77', 'eta_date' => '2026-11-05']);

        $request = $request->fresh();
        $events = RequestTimeline::events($request);

        // Terbaru di atas: tahap yang ditunggu, lalu "Barang dipesan".
        $this->assertSame('Menunggu: Barang tiba', $events[0]['title']);
        $this->assertSame('Barang dipesan', $events[1]['title']);
        $this->assertStringContainsString('Vendor: Toko Maju', $events[1]['detail']);
        $this->assertStringContainsString('No. PO: PO-77', $events[1]['detail']);
        $this->assertStringContainsString('Estimasi tiba: 05-11-2026', $events[1]['detail']);
        $this->assertSame('Barang dipesan', RequestTimeline::summary($request)['label']);

        $this->majukan($request);
        $this->majukan($request);
        $request = $request->fresh();

        $this->assertSame('Menunggu konfirmasi penerimaan', RequestTimeline::events($request)[0]['title']);

        $this->actingAs($this->staff)->post(route('staff.requests.receive', $request->id));
        $request = $request->fresh();

        $this->assertSame('Diterima pengguna', RequestTimeline::events($request)[0]['title']);
        $this->assertSame('done', RequestTimeline::summary($request)['state']);
    }

    public function test_penerima_hanya_pic_yang_ditunjuk_admin_bisa_melihat_pengajuannya(): void
    {
        $request = $this->disetujui(['request_number' => 'PGJ-UNTUK-PIC']);

        $this->actingAs($this->staff)->get(route('staff.requests.index'))->assertOk()->assertSee('PGJ-UNTUK-PIC');
        $this->actingAs($this->staff)->get(route('staff.requests.show', $request->id))->assertOk();
        $this->actingAs($this->lain)->get(route('staff.requests.show', $request->id))->assertNotFound();
    }
}
