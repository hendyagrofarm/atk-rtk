<?php

namespace App\Http\Controllers;

use App\Models\Request as Pengajuan;
use App\Support\ApprovalChain;
use App\Support\SignatureStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Cetak Formulir Pengajuan Barang (FPB) untuk pengajuan custom.
 */
class FpbController extends Controller
{
    /**
     * Jabatan bawaan untuk dua kotak tanda tangan terakhir bila Alur Approval
     * belum diatur, supaya formulir selalu memiliki 4 kotak.
     */
    private const DEFAULT_KNOWN_BY_POSITION = 'Spv. GA';
    private const DEFAULT_APPROVED_BY_POSITION = 'Manager HCGA';

    public function print(Pengajuan $pengajuan)
    {
        abort_unless($pengajuan->isCustomRequest(), 404);

        $user = Auth::user();

        $pengajuan->load([
            'user.position',
            'entity',
            'approver.position',
            'details',
            'approvals.user',
        ]);

        abort_unless($this->canPrint($user, $pengajuan), 403, 'Anda tidak memiliki akses ke formulir ini.');

        // FPB dicetak di akhir, setelah semua tahap approval selesai, agar tanda tangan
        // digital semua penyetuju sudah tercantum.
        if ($pengajuan->status !== 'approved') {
            return redirect()
                ->back(302, [], route('dashboard'))
                ->with('error', 'FPB dapat dicetak setelah seluruh tahap approval disetujui.');
        }

        return view('fpb.print', [
            'pengajuan' => $pengajuan,
            'sections' => $this->buildSections($pengajuan),
            'totalQuantity' => (int) $pengajuan->details->sum('quantity'),
            'signatures' => $this->buildSignatures($pengajuan),
        ]);
    }

    private function canPrint($user, Pengajuan $pengajuan): bool
    {
        if ($user->isSuperAdmin() || (int) $pengajuan->user_id === (int) $user->id) {
            return true;
        }

        // Penyetuju yang terlibat pada alur pengajuan ini (termasuk atasan langsung berakun
        // staff) boleh mencetak FPB final pengajuan tersebut.
        if ($pengajuan->usesApprovalChain() && ApprovalChain::isParticipant($user, $pengajuan)) {
            return true;
        }

        // Staff lain, walau satu lokasi, tidak berhak mencetak pengajuan orang lain.
        if (! in_array($user->role, ['admin', 'approver'], true)) {
            return false;
        }

        if ($pengajuan->usesApprovalChain()) {
            return ApprovalChain::canView($user, $pengajuan);
        }

        return $pengajuan->entity_id
            && $pengajuan->warehouse_id
            && $user->canAccessLocation($pengajuan->entity_id, $pengajuan->warehouse_id);
    }

    /**
     * Kelompokkan barang seperti formulir: OPEX (ATK, RTK, Sarana Prasarana, ...) lalu CAPEX.
     *
     * @return array{opex: array<int, array{label: string, items: \Illuminate\Support\Collection}>, capex: \Illuminate\Support\Collection}
     */
    private function buildSections(Pengajuan $pengajuan): array
    {
        $opexItems = $pengajuan->details->filter(fn ($d) => $d->budget_type !== 'CAPEX');
        $capexItems = $pengajuan->details->filter(fn ($d) => $d->budget_type === 'CAPEX')->values();

        $fixedGroups = ['ATK', 'RTK', 'SARANA PRASARANA'];

        $grouped = $opexItems->groupBy(fn ($d) => $this->normalizeGroup($d->custom_category));

        $groups = [];

        foreach ($fixedGroups as $label) {
            $groups[] = ['label' => $label, 'items' => ($grouped->pull($label) ?? collect())->values()];
        }

        // Kelompok lain yang diisi bebas oleh pemohon tetap ikut tercetak.
        foreach ($grouped as $label => $items) {
            $groups[] = ['label' => $label, 'items' => $items->values()];
        }

        // Hanya kelompok yang berisi barang yang ditampilkan.
        $groups = array_values(array_filter($groups, fn ($group) => $group['items']->isNotEmpty()));

        return ['opex' => $groups, 'capex' => $capexItems];
    }

    private function normalizeGroup(?string $category): string
    {
        $text = Str::upper(trim((string) $category));

        if ($text === '') {
            return 'LAINNYA';
        }

        return match (preg_replace('/[^A-Z0-9]/', '', $text)) {
            'ATK' => 'ATK',
            'RTK' => 'RTK',
            // "Obat P3K" diganti menjadi "Sarana Prasarana"; data lama berkategori Obat P3K
            // ikut dikelompokkan ke Sarana Prasarana.
            'SARANAPRASARANA', 'SARPRAS', 'OBATP3K', 'P3K' => 'SARANA PRASARANA',
            default => $text,
        };
    }

    /**
     * Kotak tanda tangan: pemohon, lalu setiap tahap approval.
     *
     * @return array<int, array{label: string, position: string, name: ?string, date: ?string, note: ?string}>
     */
    private function buildSignatures(Pengajuan $pengajuan): array
    {
        // PIC dan atasan langsung diisi per pengajuan (admin dapat mengisi atas nama
        // divisi lain). Pengajuan lama tanpa data itu memakai data pembuatnya.
        $signatures = [
            [
                'label' => 'Diajukan oleh',
                'position' => filled($pengajuan->pic_name)
                    ? ($pengajuan->pic_position ?? '')
                    : ($pengajuan->user?->position?->name ?? ''),
                'name' => filled($pengajuan->pic_name) ? $pengajuan->pic_name : $pengajuan->user?->name,
                'date' => $pengajuan->created_at?->format('d/m/Y'),
                'note' => null,
                // Tanda tangan digital PIC (staff pembuat atau user pilihan admin), jika ada.
                'image' => SignatureStore::dataUri($pengajuan->pic_signature_path),
            ],
        ];

        // Atasan langsung dari sistem sudah menjadi tahap approval digital (di daftar tahap).
        // Jika diisi manual (tanpa akun), kotaknya tetap ada untuk tanda tangan basah.
        $superiorIsDigital = $pengajuan->approvals->contains(fn ($step) => $step->assigned_user_id !== null);

        if (! $superiorIsDigital) {
            $signatures[] = [
                'label' => 'Diketahui oleh',
                'position' => filled($pengajuan->superior_position) ? $pengajuan->superior_position : 'Atasan Langsung',
                'name' => $pengajuan->superior_name,
                'date' => null,
                'note' => null,
                'image' => null,
            ];
        }

        if ($pengajuan->approvals->isNotEmpty()) {
            foreach ($pengajuan->approvals as $step) {
                $acted = in_array($step->status, ['approved', 'rejected'], true);

                $signatures[] = [
                    'label' => $step->label,
                    'position' => $step->position_name,
                    'name' => $acted ? $step->user?->name : null,
                    'date' => $acted ? $step->acted_at?->format('d/m/Y') : null,
                    'note' => $step->status === 'rejected' ? 'Ditolak' : null,
                    'image' => $step->status === 'approved' ? SignatureStore::dataUri($step->signature_path) : null,
                ];
            }

            return $signatures;
        }

        // Alur bertingkat belum diatur (atau pengajuan lama): formulir tetap memiliki
        // 4 kotak. Dua kotak terakhir memakai jabatan bawaan untuk tanda tangan basah;
        // jika sudah disetujui satu approver, nama dan tanggalnya tercetak di kotak akhir.
        $approved = $pengajuan->status === 'approved';

        $signatures[] = [
            'label' => 'Diketahui oleh',
            'position' => self::DEFAULT_KNOWN_BY_POSITION,
            'name' => null,
            'date' => null,
            'note' => null,
            'image' => null,
        ];

        $signatures[] = [
            'label' => 'Disetujui oleh',
            'position' => $approved && $pengajuan->approver?->position?->name
                ? $pengajuan->approver->position->name
                : self::DEFAULT_APPROVED_BY_POSITION,
            'name' => $approved ? $pengajuan->approver?->name : null,
            'date' => $approved ? $pengajuan->approved_at?->format('d/m/Y') : null,
            'note' => null,
            'image' => null,
        ];

        return $signatures;
    }
}
