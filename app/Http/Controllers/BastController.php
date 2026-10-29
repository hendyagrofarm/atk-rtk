<?php

namespace App\Http\Controllers;

use App\Models\Request as Pengajuan;
use App\Support\ApprovalChain;
use App\Support\Fulfillment;
use App\Support\SignatureStore;
use Illuminate\Support\Facades\Auth;

/**
 * Cetak Berita Acara Serah Terima Barang Inventaris (BAST) setelah admin mencatat serah terima.
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
                ->with('error', 'BAST dapat dicetak setelah tahap serah terima dicatat admin.');
        }

        $form = Fulfillment::formValues($pengajuan);
        $handedBy = $pengajuan->handedOverBy;

        // Tanda tangan digital hanya dicetak bila pihak pertama adalah admin yang menyerahkan.
        $sameSigner = $handedBy
            && mb_strtolower(trim((string) $form['bast']['first_name'])) === mb_strtolower(trim($handedBy->name));

        return view('bast.print', [
            'pengajuan' => $pengajuan,
            'bast' => $form['bast'],
            'items' => $form['items'],
            'handedImage' => $sameSigner ? SignatureStore::dataUri($pengajuan->handed_signature_path) : null,
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
