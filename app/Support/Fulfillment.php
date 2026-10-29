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
 * Urutan tetap: diproses pembelian -> pembayaran diajukan ke Finance -> dipesan -> barang tiba -> diserahkan (BAST).
 * Tahap tidak boleh dilompati. Admin mencatat semua tahap; penerima tidak perlu mengonfirmasi.
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
     * Tahap berikutnya yang dicatat admin; null bila serah terima (BAST) sudah selesai.
     */
    public static function nextAdminStage(Pengajuan $request): ?string
    {
        $keys = self::stageKeys();
        $current = self::currentStage($request);

        return $current === null ? $keys[0] : ($keys[array_search($current, $keys, true) + 1] ?? null);
    }

    /**
     * Selesai bila barang sudah diserahkan dan BAST dicatat admin (tanpa konfirmasi penerima).
     */
    public static function isComplete(Pengajuan $request): bool
    {
        return self::currentStage($request) === RequestFulfillment::HANDED_OVER;
    }

    /**
     * Catat tahap berikutnya oleh admin. Tahap serah terima wajib menyertakan data formulir BAST.
     *
     * @param  array{vendor?: ?string, po_number?: ?string, eta_date?: ?string, note?: ?string, bast?: array, items?: array}  $data
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
                throw new RuntimeException('Seluruh tahap sudah selesai. Data BAST masih bisa diperbarui.');
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
                ])->save();

                self::saveBast($request, $data['bast'] ?? [], $data['items'] ?? []);
                self::snapshotHandedSignature($request, $admin);
            }

            return $row;
        });
    }

    /**
     * Simpan isian formulir BAST aset: pihak pertama/kedua, mengetahui, dan data tiap barang.
     *
     * @param  array<string, ?string>  $bast
     * @param  array<int|string, array<string, mixed>>  $items  detail_id => isian barang
     */
    public static function saveBast(Pengajuan $request, array $bast, array $items): void
    {
        $request->forceFill([
            'bast_date' => ($bast['date'] ?? null) ?: now()->toDateString(),
            'bast_place' => self::clean($bast['place'] ?? null),
            'bast_first_name' => self::clean($bast['first_name'] ?? null),
            'bast_first_nik' => self::clean($bast['first_nik'] ?? null),
            'bast_first_position' => self::clean($bast['first_position'] ?? null),
            'bast_second_name' => self::clean($bast['second_name'] ?? null),
            'bast_second_nik' => self::clean($bast['second_nik'] ?? null),
            'bast_second_position' => self::clean($bast['second_position'] ?? null),
            'bast_known_name' => self::clean($bast['known_name'] ?? null),
            'bast_known_position' => self::clean($bast['known_position'] ?? null),
        ])->save();

        foreach ($request->details()->get() as $detail) {
            $row = $items[$detail->id] ?? null;

            if ($row === null) {
                continue;
            }

            $detail->forceFill([
                'bast_name' => self::clean($row['name'] ?? null),
                'bast_specification' => self::clean($row['specification'] ?? null),
                'asset_code' => self::clean($row['asset_code'] ?? null),
                'bast_quantity' => isset($row['quantity']) && $row['quantity'] !== '' ? (int) $row['quantity'] : null,
                'bast_condition' => self::clean($row['condition'] ?? null),
            ])->save();
        }
    }
    /**
     * Isian formulir BAST: data tersimpan, atau nilai awal dari admin (pihak pertama), PIC (pihak kedua)
     * dan data barang pengajuan.
     *
     * @return array{bast: array<string, mixed>, items: array<int, array<string, mixed>>}
     */
    public static function formValues(Pengajuan $request, ?User $admin = null): array
    {
        $request->loadMissing(['details', 'picUser.position', 'user.position', 'handedOverBy.position']);

        $first = $request->handedOverBy ?? $admin;
        $second = $request->receiverUser();

        $bast = [
            'date' => ($request->bast_date ?? now())->toDateString(),
            'place' => $request->bast_place ?: 'Ponorogo',
            'first_name' => $request->bast_first_name ?? $first?->name,
            'first_nik' => $request->bast_first_nik ?? $first?->nik,
            'first_position' => $request->bast_first_position ?? $first?->position?->name,
            'second_name' => $request->bast_second_name ?? $request->receiverName(),
            'second_nik' => $request->bast_second_nik ?? $second?->nik,
            'second_position' => $request->bast_second_position
                ?? ($second ? $second->position?->name : $request->pic_position),
            'known_name' => $request->bast_known_name,
            'known_position' => $request->bast_known_position,
        ];

        $items = [];

        foreach ($request->details as $detail) {
            $items[$detail->id] = [
                'name' => $detail->bast_name ?? $detail->custom_item_name,
                'specification' => $detail->bast_specification ?? $detail->specification ?? $detail->brand_type,
                'asset_code' => $detail->asset_code,
                'quantity' => $detail->bast_quantity ?? $detail->approved_quantity ?? $detail->quantity,
                'condition' => $detail->bast_condition ?? 'Baik',
            ];
        }

        return ['bast' => $bast, 'items' => $items];
    }

    /**
     * Aturan validasi isian formulir BAST (nama field: bast[...] dan items[detail_id][...]).
     *
     * @return array<string, array<int, string>>
     */
    public static function bastRules(): array
    {
        return [
            'bast' => ['required', 'array'],
            'bast.date' => ['required', 'date'],
            'bast.place' => ['nullable', 'string', 'max:80'],
            'bast.first_name' => ['required', 'string', 'max:150'],
            'bast.first_nik' => ['nullable', 'string', 'max:30'],
            'bast.first_position' => ['nullable', 'string', 'max:150'],
            'bast.second_name' => ['required', 'string', 'max:150'],
            'bast.second_nik' => ['nullable', 'string', 'max:30'],
            'bast.second_position' => ['nullable', 'string', 'max:150'],
            'bast.known_name' => ['nullable', 'string', 'max:150'],
            'bast.known_position' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string', 'max:1000'],
            'items.*.asset_code' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.condition' => ['nullable', 'string', 'max:100'],
        ];
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
