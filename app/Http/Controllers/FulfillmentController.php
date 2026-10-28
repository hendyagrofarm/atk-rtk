<?php

namespace App\Http\Controllers;

use App\Models\Request as Pengajuan;
use App\Models\RequestFulfillment;
use App\Support\Fulfillment;
use App\Support\RequestTimeline;
use App\Support\SignatureStore;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Pembelanjaan dan serah terima (BAST) pengajuan custom yang sudah disetujui.
 * Dikelola admin; penerima mengonfirmasi lewat halaman pengajuannya.
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

        $done = fn ($query) => $query->whereHas('fulfillments', fn ($f) => $f->where('stage', RequestFulfillment::RECEIVED));

        $counts = [
            'proses' => (clone $base)->whereDoesntHave('fulfillments', fn ($f) => $f->where('stage', RequestFulfillment::RECEIVED))->count(),
            'selesai' => $done(clone $base)->count(),
        ];

        $requests = ($tab === 'selesai'
            ? $done(clone $base)
            : (clone $base)->whereDoesntHave('fulfillments', fn ($f) => $f->where('stage', RequestFulfillment::RECEIVED)))
            ->with(['user', 'entity', 'fulfillments', 'details'])
            ->latest('approved_at')
            ->paginate(15)
            ->withQueryString();

        return view('fulfillments.index', compact('requests', 'tab', 'counts'));
    }

    public function show(Pengajuan $pengajuan)
    {
        $this->authorizeAccess($pengajuan);

        $pengajuan->load(['user.position', 'entity', 'warehouse', 'details', 'fulfillments.user', 'picUser']);

        return view('fulfillments.show', [
            'pengajuan' => $pengajuan,
            'events' => RequestTimeline::events($pengajuan),
            'nextStage' => Fulfillment::nextAdminStage($pengajuan),
            'adminHasSignature' => SignatureStore::userHasSignature(Auth::user()),
        ]);
    }

    public function advance(HttpRequest $http, Pengajuan $pengajuan)
    {
        $this->authorizeAccess($pengajuan);

        $stage = Fulfillment::nextAdminStage($pengajuan);

        $data = $http->validate([
            'vendor' => ['nullable', 'string', 'max:150'],
            'po_number' => ['nullable', 'string', 'max:100'],
            'eta_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'received_name' => [$stage === RequestFulfillment::HANDED_OVER ? 'nullable' : 'prohibited', 'string', 'max:150'],
        ]);

        try {
            Fulfillment::advance($pengajuan, Auth::user(), $data);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('fulfillments.show', $pengajuan)
            ->with('success', 'Tahap pembelanjaan diperbarui.');
    }

    /**
     * Admin mengonfirmasi penerimaan atas nama penerima yang tidak punya akun.
     * Penerima berakun harus mengonfirmasi sendiri agar tanda tangannya sah.
     */
    public function receiveOnBehalf(HttpRequest $http, Pengajuan $pengajuan)
    {
        $this->authorizeAccess($pengajuan);

        $pengajuan->load(['user', 'picUser']);

        if ($pengajuan->receiverUser() !== null) {
            return back()->with('error', 'Penerima punya akun dan harus mengonfirmasi sendiri.');
        }

        $data = $http->validate(['note' => ['nullable', 'string', 'max:1000']]);

        try {
            Fulfillment::confirmReceipt($pengajuan, Auth::user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('fulfillments.show', $pengajuan)
            ->with('success', 'Penerimaan barang dikonfirmasi atas nama penerima.');
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
