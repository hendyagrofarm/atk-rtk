<?php

namespace App\Support;

use App\Models\Request as Pengajuan;
use App\Models\RequestFulfillment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Tahap pembelanjaan pengajuan custom setelah FPB disetujui, sampai serah terima (BAST).
 *
 * Urutan tetap: diproses pembelian -> dipesan -> barang tiba -> diserahkan -> diterima.
 * Tahap tidak boleh dilompati. Tahap "diterima" dikonfirmasi oleh penerima
 * (atau admin atas nama penerima yang tidak punya akun).
 */
class Fulfillment
{
    /**
     * @return array<int, string>
     */
    public static function stageKeys(): array
    {
        return array_keys(RequestFulfillment::STAGES);
    }

    public static function applies(Pengajuan $request): bool
    {
        return $request->isCustomRequest() && $request->status === 'approved';
    }

    public static function currentStage(Pengajuan $request): ?string
    {
        $request->loadMissing('fulfillments');

        $reached = $request->fulfillments->pluck('stage')->all();
        $current = null;

        foreach (self::stageKeys() as $key) {
            if (in_array($key, $reached, true)) {
                $current = $key;
            }
        }

        return $current;
    }

    /**
     * Tahap berikutnya yang boleh dicatat admin (tahap "diterima" bukan tugas admin).
     */
    public static function nextAdminStage(Pengajuan $request): ?string
    {
        $keys = self::stageKeys();
        $current = self::currentStage($request);
        $next = $current === null ? $keys[0] : ($keys[array_search($current, $keys, true) + 1] ?? null);

        return ($next && $next !== RequestFulfillment::RECEIVED) ? $next : null;
    }

    public static function isComplete(Pengajuan $request): bool
    {
        return self::currentStage($request) === RequestFulfillment::RECEIVED;
    }

    public static function isAwaitingReceipt(Pengajuan $request): bool
    {
        return self::currentStage($request) === RequestFulfillment::HANDED_OVER;
    }

    /**
     * Catat tahap berikutnya oleh admin.
     *
     * @param  array{vendor?: ?string, po_number?: ?string, eta_date?: ?string, note?: ?string, received_name?: ?string}  $data
     */
    public static function advance(Pengajuan $request, User $admin, array $data): RequestFulfillment
    {
        return DB::transaction(function () use ($request, $admin, $data) {
            $request = Pengajuan::query()->lockForUpdate()->findOrFail($request->id);

            if (! self::applies($request)) {
                throw new RuntimeException('Pembelanjaan hanya untuk pengajuan custom yang sudah disetujui.');
            }

            $stage = self::nextAdminStage($request);

            if ($stage === null) {
                throw new RuntimeException(
                    self::isAwaitingReceipt($request)
                        ? 'Barang sudah diserahkan dan menunggu konfirmasi penerima.'
                        : 'Seluruh tahap sudah selesai.'
                );
            }

            $row = RequestFulfillment::create([
                'request_id' => $request->id,
                'stage' => $stage,
                'user_id' => $admin->id,
                'vendor' => $stage === RequestFulfillment::ORDERED ? self::clean($data['vendor'] ?? null) : null,
                'po_number' => $stage === RequestFulfillment::ORDERED ? self::clean($data['po_number'] ?? null) : null,
                'eta_date' => $stage === RequestFulfillment::ORDERED ? ($data['eta_date'] ?? null) : null,
                'note' => self::clean($data['note'] ?? null),
                'happened_at' => now(),
            ]);

            if ($stage === RequestFulfillment::HANDED_OVER) {
                $request->forceFill([
                    'bast_number' => self::generateBastNumber(),
                    'bast_note' => self::clean($data['note'] ?? null),
                    'handed_over_by' => $admin->id,
                    'received_name' => self::clean($data['received_name'] ?? null)
                        ?: $request->receiverName(),
                ])->save();

                self::snapshotHandedSignature($request, $admin);
            }

            return $row;
        });
    }

    /**
     * Konfirmasi barang diterima. Penerima berakun menandatangani digital; admin dapat
     * mengonfirmasi atas nama penerima yang tidak berakun (tanpa tanda tangan digital).
     */
    public static function confirmReceipt(Pengajuan $request, User $actor, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $actor, $note) {
            $request = Pengajuan::query()->lockForUpdate()->findOrFail($request->id);

            if (! self::isAwaitingReceipt($request)) {
                throw new RuntimeException('Barang belum diserahkan atau sudah dikonfirmasi.');
            }

            $isReceiver = $request->receiverUser()?->id === $actor->id;
            $isAdmin = in_array($actor->role, ['admin', 'super_admin'], true);

            if (! $isReceiver && ! $isAdmin) {
                throw new RuntimeException('Hanya penerima barang yang dapat mengonfirmasi penerimaan.');
            }

            $onBehalf = ! $isReceiver;

            $request->forceFill([
                'received_at' => now(),
                'received_on_behalf' => $onBehalf,
                'received_user_id' => $isReceiver ? $actor->id : null,
                'received_name' => $request->received_name ?: ($isReceiver ? $actor->name : $request->receiverName()),
            ])->save();

            if ($isReceiver && SignatureStore::userHasSignature($actor)) {
                $path = 'signatures/requests/' . $request->id . '-received.png';
                Storage::disk(SignatureStore::DISK)->put($path, Storage::disk(SignatureStore::DISK)->get($actor->signature_path));
                $request->forceFill(['received_signature_path' => $path])->save();
            }

            RequestFulfillment::create([
                'request_id' => $request->id,
                'stage' => RequestFulfillment::RECEIVED,
                'user_id' => $actor->id,
                'note' => trim(
                    ($onBehalf ? 'Dikonfirmasi admin atas nama penerima. ' : '') . (self::clean($note) ?? '')
                ) ?: null,
                'happened_at' => now(),
            ]);
        });
    }

    private static function snapshotHandedSignature(Pengajuan $request, User $admin): void
    {
        if (! SignatureStore::userHasSignature($admin)) {
            return;
        }

        $path = 'signatures/requests/' . $request->id . '-handed.png';
        Storage::disk(SignatureStore::DISK)->put($path, Storage::disk(SignatureStore::DISK)->get($admin->signature_path));
        $request->forceFill(['handed_signature_path' => $path])->save();
    }

    private static function generateBastNumber(): string
    {
        $prefix = 'BAST-' . now()->format('Ymd') . '-';

        $last = Pengajuan::query()
            ->where('bast_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('bast_number')
            ->value('bast_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
