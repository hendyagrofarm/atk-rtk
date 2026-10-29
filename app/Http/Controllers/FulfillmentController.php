<?php

namespace App\Http\Controllers;

use App\Models\Request as Pengajuan;
use App\Models\RequestFulfillment;
use App\Support\Fulfillment;
use App\Support\RequestTimeline;
use App\Support\SignatureStore;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Pembelanjaan dan serah terima (BAST) pengajuan custom yang sudah disetujui.
 * Dikelola admin sampai BAST tercetak; penerima tidak perlu mengonfirmasi.
 */
class FulfillmentController extends Controller
{
    public function index(HttpRequest $http)
    {
        $user = Auth::user();
        $tab = $http->query('tab') === 'selesai' ? 'selesai' : 'proses';

        $base = Pengajuan::query()
            ->where('request_type', 'custom')
            ->where('status', 'approved')
            ->when(
                ! $user->isSuperAdmin(),
                fn ($query) => $query
                    ->whereIn('entity_id', $user->allowedEntityIds())
                    ->whereIn('warehouse_id', $user->allowedWarehouseIds())
            );

        $done = fn ($query) => $query->whereHas('fulfillments', fn ($f) => $f->where('stage', RequestFulfillment::HANDED_OVER));

        $counts = [
            'proses' => (clone $base)->whereDoesntHave('fulfillments', fn ($f) => $f->where('stage', RequestFulfillment::HANDED_OVER))->count(),
            'selesai' => $done(clone $base)->count(),
        ];

        $requests = ($tab === 'selesai'
            ? $done(clone $base)
            : (clone $base)->whereDoesntHave('fulfillments', fn ($f) => $f->where('stage', RequestFulfillment::HANDED_OVER)))
            ->with(['user', 'entity', 'fulfillments', 'details'])
            ->latest('approved_at')
            ->paginate(15)
            ->withQueryString();

        return view('fulfillments.index', compact('requests', 'tab', 'counts'));
    }

    public function show(Pengajuan $pengajuan)
    {
        $this->authorizeAccess($pengajuan);

        $pengajuan->load(['user.position', 'entity', 'warehouse', 'details', 'fulfillments.user', 'picUser.position']);

        return view('fulfillments.show', [
            'pengajuan' => $pengajuan,
            'events' => RequestTimeline::events($pengajuan),
            'nextStage' => Fulfillment::nextAdminStage($pengajuan),
            'adminHasSignature' => SignatureStore::userHasSignature(Auth::user()),
            'form' => Fulfillment::formValues($pengajuan, Auth::user()),
        ]);
    }

    public function advance(HttpRequest $http, Pengajuan $pengajuan)
    {
        $this->authorizeAccess($pengajuan);

        $stage = Fulfillment::nextAdminStage($pengajuan);

        $rules = [
            'vendor' => ['nullable', 'string', 'max:150'],
            'po_number' => ['nullable', 'string', 'max:100'],
            'eta_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];

        if ($stage === RequestFulfillment::HANDED_OVER) {
            $rules += Fulfillment::bastRules();
        }

        $data = $http->validate($rules);

        try {
            Fulfillment::advance($pengajuan, Auth::user(), $data);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('fulfillments.show', $pengajuan)
            ->with('success', $stage === RequestFulfillment::HANDED_OVER
                ? 'Serah terima dicatat. BAST siap dicetak.'
                : 'Tahap pembelanjaan diperbarui.');
    }

    /**
     * Perbarui isian formulir BAST setelah serah terima dicatat (mis. koreksi kode aset).
     */
    public function updateBast(HttpRequest $http, Pengajuan $pengajuan)
    {
        $this->authorizeAccess($pengajuan);

        if (! Fulfillment::isComplete($pengajuan)) {
            return back()->with('error', 'Data BAST dapat diperbarui setelah tahap serah terima dicatat.');
        }

        $data = $http->validate(Fulfillment::bastRules());

        DB::transaction(fn () => Fulfillment::saveBast($pengajuan, $data['bast'], $data['items']));

        return redirect()
            ->route('fulfillments.show', $pengajuan)
            ->with('success', 'Data BAST diperbarui.');
    }
    private function authorizeAccess(Pengajuan $pengajuan): void
    {
        $user = Auth::user();

        abort_unless($pengajuan->isCustomRequest() && $pengajuan->status === 'approved', 404);

        abort_unless(
            $user->isSuperAdmin()
                || ($pengajuan->entity_id && $pengajuan->warehouse_id
                    && $user->canAccessLocation($pengajuan->entity_id, $pengajuan->warehouse_id)),
            403,
            'Anda tidak memiliki akses ke pengajuan ini.'
        );
    }
}
