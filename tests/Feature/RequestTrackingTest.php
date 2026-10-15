<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Entity;
use App\Models\Position;
use App\Models\Request as Pengajuan;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\RequestTimeline;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pelacakan pengajuan di akun staff, seperti pelacakan jasa pengiriman:
 * kejadian dari yang terbaru, lengkap dengan siapa dan kapan (WIB).
 */
class RequestTrackingTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entity;
    private Warehouse $gudang;
    private User $staff;
    private User $spvUser;
    private User $managerUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');

        $this->entity = Entity::forceCreate(['code' => 'ANR', 'name' => 'PT Agrofarm Nusa Raya', 'is_active' => true]);
        $this->gudang = Warehouse::forceCreate(['code' => 'GD', 'name' => 'Gudang Pabrik', 'is_active' => true]);

        $spv = Position::create(['name' => 'Spv. GA']);
        $manager = Position::create(['name' => 'Manager HCGA']);

        ApprovalStep::create(['step_order' => 1, 'label' => 'Diketahui oleh', 'position_id' => $spv->id, 'is_active' => true]);
        ApprovalStep::create(['step_order' => 2, 'label' => 'Disetujui oleh', 'position_id' => $manager->id, 'is_active' => true]);

        $this->staff = $this->makeUser('staff', 'Al Haudhu', Position::create(['name' => 'Staff K3L']), $this->gudang);
        $this->spvUser = $this->makeUser('approver', 'Yudi Ardiyanto', $spv);
        $this->managerUser = $this->makeUser('approver', 'Muhammad Rudi', $manager);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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

    private function at(string $time): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-15 ' . $time, 'Asia/Jakarta'));
    }

    private function buatFpb(): Pengajuan
    {
        $this->actingAs($this->staff)
            ->post(route('staff.requests.custom.store'), [
                'entity_id' => $this->entity->id,
                'department' => 'Affair & Legal - HSE',
                'superior_name' => 'Wachidyah Romadhoni',
                'items' => [[
                    'custom_item_name' => 'Emergency Eye Wash Portable',
                    'budget_type' => 'CAPEX',
                    'quantity' => 2,
                    'unit' => 'pcs',
                ]],
            ])
            ->assertRedirect(route('staff.requests.index'));

        return Pengajuan::latest('id')->firstOrFail();
    }

    private function detail(Pengajuan $pengajuan)
    {
        return $this->actingAs($this->staff)->get(route('staff.requests.show', $pengajuan->id));
    }

    public function test_pengajuan_baru_menampilkan_pembuatan_dan_tahap_yang_sedang_berjalan(): void
    {
        $this->at('09:15:00');
        $pengajuan = $this->buatFpb();

        $this->detail($pengajuan)
            ->assertOk()
            ->assertSee('Lacak Pengajuan')
            ->assertSee('Pengajuan dibuat')
            ->assertSee('Oleh Al Haudhu (Staff K3L)')
            ->assertSee('15-10-2026 09:15 WIB')
            ->assertSee('Sedang diproses oleh Spv. GA')
            ->assertSee('Sedang berlangsung')
            ->assertSee('Berikutnya: Manager HCGA');
    }

    public function test_tahap_selesai_tercatat_dengan_nama_dan_waktu_wib(): void
    {
        $this->at('09:15:00');
        $pengajuan = $this->buatFpb();

        $this->at('14:00:00');
        $this->actingAs($this->spvUser)
            ->post(route('approvals.approve', $pengajuan), ['approver_note' => 'Sudah dicek'])
            ->assertRedirect(route('approvals.index'));

        $this->detail($pengajuan)
            ->assertOk()
            ->assertSee('Diketahui oleh Spv. GA')
            ->assertSee('Disetujui oleh Yudi Ardiyanto (Spv. GA)')
            ->assertSee('Catatan: Sudah dicek')
            ->assertSee('15-10-2026 14:00 WIB')
            ->assertSee('Sedang diproses oleh Manager HCGA')
            ->assertDontSee('Berikutnya:');
    }

    public function test_urutan_kejadian_dari_yang_terbaru(): void
    {
        $this->at('09:15:00');
        $pengajuan = $this->buatFpb();

        $this->at('14:00:00');
        $this->actingAs($this->spvUser)->post(route('approvals.approve', $pengajuan));

        $html = $this->detail($pengajuan)->getContent();

        $berjalan = strpos($html, 'Sedang diproses oleh Manager HCGA');
        $spv = strpos($html, 'Diketahui oleh Spv. GA');
        $dibuat = strpos($html, 'Pengajuan dibuat');

        $this->assertNotFalse($berjalan);
        $this->assertLessThan($spv, $berjalan);
        $this->assertLessThan($dibuat, $spv);
    }

    public function test_pengajuan_selesai_menampilkan_ringkasan_jumlah_disetujui(): void
    {
        $this->at('09:15:00');
        $pengajuan = $this->buatFpb();
        $detail = $pengajuan->details()->firstOrFail();

        $this->at('14:00:00');
        $this->actingAs($this->spvUser)->post(route('approvals.approve', $pengajuan));

        $this->at('16:30:00');
        $this->actingAs($this->managerUser)->post(route('approvals.approve', $pengajuan), [
            'approved_quantity' => [$detail->id => 1],
        ]);

        $html = $this->detail($pengajuan)->assertOk()->getContent();

        $this->assertStringContainsString('Pengajuan disetujui', $html);
        $this->assertStringContainsString('Disetujui 1 dari 2 unit', $html);
        $this->assertStringContainsString('15-10-2026 16:30 WIB', $html);
        $this->assertStringNotContainsString('Sedang diproses', $html);

        // Kejadian terakhir ada paling atas.
        $this->assertLessThan(strpos($html, 'Diketahui oleh Spv. GA'), strpos($html, 'Pengajuan disetujui'));
    }

    public function test_penolakan_menampilkan_alasan_dan_siapa_yang_menolak(): void
    {
        $this->at('09:15:00');
        $pengajuan = $this->buatFpb();

        $this->at('13:45:00');
        $this->actingAs($this->spvUser)->post(route('approvals.reject', $pengajuan), [
            'rejection_reason' => 'tidak_sesuai_kebutuhan',
            'approver_note' => 'Sudah ada di gudang lain',
        ]);

        $this->detail($pengajuan)
            ->assertOk()
            ->assertSee('Ditolak oleh Spv. GA')
            ->assertSee('Alasan: Tidak sesuai kebutuhan')
            ->assertSee('Yudi Ardiyanto')
            ->assertSee('Sudah ada di gudang lain')
            ->assertSee('15-10-2026 13:45 WIB')
            ->assertDontSee('Sedang diproses');
    }

    public function test_pengajuan_dari_stok_satu_tahap_juga_dilacak(): void
    {
        $this->at('08:00:00');

        $pengajuan = Pengajuan::forceCreate([
            'user_id' => $this->staff->id,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudang->id,
            'request_number' => 'REQ-STOK-1',
            'request_date' => '2026-10-15',
            'request_type' => 'stock',
            'status' => 'pending',
        ]);

        $this->detail($pengajuan)
            ->assertOk()
            ->assertSee('Pengajuan dibuat')
            ->assertSee('Menunggu persetujuan')
            ->assertSee('15-10-2026 08:00 WIB');

        $pengajuan->update([
            'status' => 'approved',
            'approver_id' => $this->spvUser->id,
            'approved_at' => Carbon::parse('2026-10-15 10:20:00', 'Asia/Jakarta'),
        ]);

        $this->detail($pengajuan)
            ->assertSee('Pengajuan disetujui')
            ->assertSee('Oleh Yudi Ardiyanto (Spv. GA)')
            ->assertSee('15-10-2026 10:20 WIB')
            ->assertDontSee('Cetak FPB');
    }

    public function test_pengajuan_stok_ditolak_menampilkan_alasan(): void
    {
        $this->at('08:00:00');

        $pengajuan = Pengajuan::forceCreate([
            'user_id' => $this->staff->id,
            'entity_id' => $this->entity->id,
            'warehouse_id' => $this->gudang->id,
            'request_number' => 'REQ-STOK-2',
            'request_date' => '2026-10-15',
            'request_type' => 'stock',
            'status' => 'rejected',
            'approver_id' => $this->spvUser->id,
            'rejection_reason' => 'stok_tidak_cukup',
            'approved_at' => Carbon::parse('2026-10-15 09:00:00', 'Asia/Jakarta'),
        ]);

        $this->detail($pengajuan)
            ->assertOk()
            ->assertSee('Pengajuan ditolak')
            ->assertSee('Alasan: Stok tidak cukup / kosong')
            ->assertSee('15-10-2026 09:00 WIB');
    }

    public function test_daftar_pengajuan_menampilkan_status_terkini_dan_waktunya(): void
    {
        $this->at('09:15:00');
        $pengajuan = $this->buatFpb();

        $this->actingAs($this->staff)
            ->get(route('staff.requests.index'))
            ->assertOk()
            ->assertSee('Sedang diproses oleh Spv. GA')
            ->assertSee('15-10-2026 09:15 WIB');

        $this->at('14:00:00');
        $this->actingAs($this->spvUser)->post(route('approvals.approve', $pengajuan));

        $this->actingAs($this->staff)
            ->get(route('staff.requests.index'))
            ->assertSee('Sedang diproses oleh Manager HCGA')
            ->assertSee('15-10-2026 14:00 WIB');
    }

    public function test_ringkasan_status_untuk_setiap_kondisi(): void
    {
        $this->at('09:15:00');
        $pengajuan = $this->buatFpb();

        $this->assertSame('Sedang diproses oleh Spv. GA', RequestTimeline::summary($pengajuan->fresh())['label']);

        $this->actingAs($this->spvUser)->post(route('approvals.reject', $pengajuan), ['rejection_reason' => 'duplikat']);

        $ringkasan = RequestTimeline::summary($pengajuan->fresh());
        $this->assertSame('rejected', $ringkasan['state']);
        $this->assertSame('Pengajuan ditolak: Pengajuan ganda', $ringkasan['label']);
    }

    public function test_staff_lain_tidak_bisa_melihat_pelacakan_pengajuan_orang_lain(): void
    {
        $pengajuan = $this->buatFpb();
        $lain = $this->makeUser('staff', 'Staff Lain', null, $this->gudang);

        $this->actingAs($lain)
            ->get(route('staff.requests.show', $pengajuan->id))
            ->assertNotFound();
    }
}
