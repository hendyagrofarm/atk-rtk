<?php

namespace App\Http\Controllers;

use App\Models\Request as Pengajuan;
use App\Support\ApprovalChain;
use App\Support\Fulfillment;
use App\Support\SignatureStore;
use Illuminate\Support\Facades\Auth;

/**
 * Cetak Berita Acara Serah Terima (BAST) setelah penerima mengonfirmasi barang diterima.
 */
class BastController extends Controller
{
    public function print(Pengajuan $pengajuan)
    {
        abort_unless($pengajuan->isCustomRequest(), 404);

        $user = Auth::user();

        $pengajuan->load(['user.position', 'entity', 'warehouse', 'details', 'approvals', 'handedOverBy.position', 'fulfillments']);

        abort_unless($this->canPrint($user, $pengajuan), 403, 'Anda tidak memiliki akses ke BAST ini.');

        if (! Fulfillment::isComplete($pengajuan)) {
            return redirect()
                ->back(302, [], route('dashboard'))
                ->with('error', 'BAST dapat dicetak setelah penerima mengonfirmasi barang diterima.');
        }

        return view('bast.print', [
            'pengajuan' => $pengajuan,
            'handedBy' => $pengajuan->handedOverBy,
            'handedImage' => SignatureStore::dataUri($pengajuan->handed_signature_path),
            'receivedImage' => SignatureStore::dataUri($pengajuan->received_signature_path),
            'receivedFulfillment' => $pengajuan->fulfillments->firstWhere('stage', 'received'),
        ]);
    }

    private function canPrint($user, Pengajuan $pengajuan): bool
    {
        if ($user->isSuperAdmin()
            || (int) $pengajuan->user_id === (int) $user->id
            || (int) $pengajuan->pic_user_id === (int) $user->id) {
            return true;
        }

        if ($pengajuan->usesApprovalChain() && ApprovalChain::isParticipant($user, $pengajuan)) {
            return true;
        }

        return $user->role === 'admin'
            && $pengajuan->entity_id
            && $pengajuan->warehouse_id
            && $user->canAccessLocation($pengajuan->entity_id, $pengajuan->warehouse_id);
    }
}
