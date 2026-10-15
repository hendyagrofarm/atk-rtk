<?php

namespace App\Http\Controllers;

use App\Models\Request as Pengajuan;
use App\Support\ApprovalChain;
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
     * Kelompokkan barang seperti formulir: OPEX (ATK, RTK, Obat P3K, ...) lalu CAPEX.
     *
     * @return array{opex: array<int, array{label: string, items: \Illuminate\Support\Collection}>, capex: \Illuminate\Support\Collection}
     */
    private function buildSections(Pengajuan $pengajuan): array
    {
        $opexItems = $pengajuan->details->filter(fn ($d) => $d->budget_type !== 'CAPEX');
        $capexItems = $pengajuan->details->filter(fn ($d) => $d->budget_type === 'CAPEX')->values();

        $fixedGroups = ['ATK', 'RTK', 'OBAT P3K'];

        $grouped = $opexItems->groupBy(fn ($d) => $this->normalizeGroup($d->custom_category));

        $groups = [];

        foreach ($fixedGroups as $label) {
            $groups[] = ['label' => $label, 'items' => ($grouped->pull($label) ?? collect())->values()];
        }

        // Kelompok lain yang diisi bebas oleh pemohon tetap ikut tercetak.
        foreach ($grouped as $label => $items) {
            $groups[] = ['label' => $label, 'items' => $items->values()];
        }

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
            'OBATP3K', 'P3K' => 'OBAT P3K',
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
            ],
            [
                // Tanda tangan basah saja: tidak ada tahap digital untuk atasan langsung.
                'label' => 'Diketahui oleh',
                'position' => filled($pengajuan->superior_position) ? $pengajuan->superior_position : 'Atasan Langsung',
                'name' => $pengajuan->superior_name,
                'date' => null,
                'note' => null,
            ],
        ];

        if ($pengajuan->approvals->isNotEmpty()) {
            foreach ($pengajuan->approvals as $step) {
                $acted = in_array($step->status, ['approved', 'rejected'], true);

                $signatures[] = [
                    'label' => $step->label,
                    'position' => $step->position_name,
                    'name' => $acted ? $step->user?->name : null,
                    'date' => $acted ? $step->acted_at?->format('d/m/Y') : null,
                    'note' => $step->status === 'rejected' ? 'Ditolak' : null,
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
        ];

        $signatures[] = [
            'label' => 'Disetujui oleh',
            'position' => $approved && $pengajuan->approver?->position?->name
                ? $pengajuan->approver->position->name
                : self::DEFAULT_APPROVED_BY_POSITION,
            'name' => $approved ? $pengajuan->approver?->name : null,
            'date' => $approved ? $pengajuan->approved_at?->format('d/m/Y') : null,
            'note' => null,
        ];

        return $signatures;
    }
}
