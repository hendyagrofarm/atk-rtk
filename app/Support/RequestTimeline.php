<?php

namespace App\Support;

use App\Models\Request as Pengajuan;
use App\Models\RequestApproval;
use Carbon\CarbonInterface;

/**
 * Riwayat pelacakan pengajuan seperti pelacakan jasa pengiriman:
 * daftar kejadian dari yang terbaru, lengkap dengan siapa dan kapan (WIB).
 *
 * Tiap kejadian: state (start|current|done|rejected), title, detail, at.
 * - current : tahap yang sedang berjalan (belum punya waktu).
 * - done    : tahap yang sudah selesai.
 * - rejected: penolakan.
 * - start   : pengajuan dibuat.
 */
class RequestTimeline
{
    /**
     * @return array<int, array{state: string, title: string, detail: ?string, at: ?CarbonInterface, time: ?string}>
     */
    public static function events(Pengajuan $request): array
    {
        $request->loadMissing([
            'user.position',
            'approver.position',
            'approvals.user.position',
            'details',
        ]);

        $events = [self::createdEvent($request)];

        if ($request->approvals->isNotEmpty()) {
            array_push($events, ...self::chainEvents($request));
        } else {
            array_push($events, ...self::singleStepEvents($request));
        }

        // Terbaru di atas, seperti pelacakan paket.
        return array_map(
            fn (array $event) => $event + ['time' => self::formatTime($event['at'])],
            array_reverse($events)
        );
    }

    /**
     * Ringkasan status terkini untuk daftar pengajuan.
     *
     * @return array{label: string, time: ?string, state: string}
     */
    public static function summary(Pengajuan $request): array
    {
        $request->loadMissing(['approvals']);

        if ($request->status === 'approved') {
            return ['label' => 'Pengajuan disetujui', 'time' => self::formatTime($request->approved_at), 'state' => 'done'];
        }

        if ($request->status === 'rejected') {
            $reason = $request->rejection_reason_label;

            return [
                'label' => 'Pengajuan ditolak' . ($reason ? ': ' . $reason : ''),
                'time' => self::formatTime($request->approved_at),
                'state' => 'rejected',
            ];
        }

        $current = $request->approvals->first(fn (RequestApproval $step) => $step->isPending());

        if ($current) {
            $lastDone = $request->approvals
                ->filter(fn (RequestApproval $step) => $step->status === RequestApproval::APPROVED && $step->acted_at)
                ->sortByDesc('acted_at')
                ->first();

            return [
                'label' => 'Sedang diproses oleh ' . $current->position_name,
                'time' => self::formatTime($lastDone?->acted_at ?? $request->created_at),
                'state' => 'current',
            ];
        }

        return ['label' => 'Menunggu persetujuan', 'time' => self::formatTime($request->created_at), 'state' => 'current'];
    }

    public static function formatTime(?CarbonInterface $time): ?string
    {
        return $time
            ? $time->copy()->timezone(config('app.timezone'))->format('d-m-Y H:i T')
            : null;
    }

    private static function createdEvent(Pengajuan $request): array
    {
        $creator = $request->user;
        $name = filled($request->pic_name) ? $request->pic_name : $creator?->name;
        $position = filled($request->pic_name) ? $request->pic_position : $creator?->position?->name;

        $detail = 'Oleh ' . ($name ?? '-') . ($position ? ' (' . $position . ')' : '');

        if (filled($request->pic_name) && $creator && $creator->name !== $request->pic_name) {
            $detail .= ' | dibuat oleh ' . $creator->name;
        }

        return [
            'state' => 'start',
            'title' => 'Pengajuan dibuat',
            'detail' => $detail . ' | No. ' . $request->request_number,
            'at' => $request->created_at,
        ];
    }

    /**
     * Kejadian untuk pengajuan custom dengan alur bertingkat.
     */
    private static function chainEvents(Pengajuan $request): array
    {
        $events = [];
        $total = $request->approvals->count();
        $currentShown = false;

        foreach ($request->approvals as $step) {
            if ($step->status === RequestApproval::APPROVED) {
                $events[] = [
                    'state' => 'done',
                    'title' => $step->label . ' ' . $step->position_name,
                    'detail' => trim(self::actor($step) . self::note($step->note), ' |'),
                    'at' => $step->acted_at,
                ];
            } elseif ($step->status === RequestApproval::REJECTED) {
                $reason = $request->rejection_reason_label;

                $events[] = [
                    'state' => 'rejected',
                    'title' => 'Ditolak oleh ' . $step->position_name,
                    'detail' => trim(
                        ($reason ? 'Alasan: ' . $reason . '. ' : '') . self::actor($step, 'Oleh ') . self::note($step->note),
                        ' |'
                    ),
                    'at' => $step->acted_at,
                ];
            } elseif ($step->isPending() && ! $currentShown && $request->status === 'pending') {
                $currentShown = true;

                $upcoming = $request->approvals
                    ->filter(fn (RequestApproval $other) => $other->isPending() && $other->id !== $step->id)
                    ->map(fn (RequestApproval $other) => $other->position_name)
                    ->values();

                $events[] = [
                    'state' => 'current',
                    'title' => 'Sedang diproses oleh ' . $step->position_name,
                    'detail' => 'Menunggu ' . lcfirst($step->label) . ' (tahap ' . $step->step_order . ' dari ' . $total . ')'
                        . ($upcoming->isNotEmpty() ? '. Berikutnya: ' . $upcoming->implode(', ') : ''),
                    'at' => null,
                ];
            }
        }

        if ($request->status === 'approved') {
            $events[] = [
                'state' => 'done',
                'title' => 'Pengajuan disetujui',
                'detail' => self::quantitySummary($request),
                'at' => $request->approved_at,
            ];
        }

        return $events;
    }

    /**
     * Kejadian untuk pengajuan satu tahap (pengajuan dari stok atau custom tanpa alur).
     */
    private static function singleStepEvents(Pengajuan $request): array
    {
        if ($request->status === 'approved') {
            return [[
                'state' => 'done',
                'title' => 'Pengajuan disetujui',
                'detail' => trim(
                    'Oleh ' . ($request->approver?->name ?? '-')
                    . ($request->approver?->position ? ' (' . $request->approver->position->name . ')' : '')
                    . ' | ' . self::quantitySummary($request)
                    . self::note($request->approver_note),
                    ' |'
                ),
                'at' => $request->approved_at,
            ]];
        }

        if ($request->status === 'rejected') {
            $reason = $request->rejection_reason_label;

            return [[
                'state' => 'rejected',
                'title' => 'Pengajuan ditolak',
                'detail' => trim(
                    ($reason ? 'Alasan: ' . $reason . '. ' : '')
                    . 'Oleh ' . ($request->approver?->name ?? '-')
                    . self::note($request->approver_note),
                    ' |'
                ),
                'at' => $request->approved_at,
            ]];
        }

        return [[
            'state' => 'current',
            'title' => 'Menunggu persetujuan',
            'detail' => 'Pengajuan sedang menunggu diproses approver',
            'at' => null,
        ]];
    }

    private static function actor(RequestApproval $step, string $prefix = 'Disetujui oleh '): string
    {
        if (! $step->user) {
            return '';
        }

        return $prefix . $step->user->name
            . ($step->user->position ? ' (' . $step->user->position->name . ')' : '');
    }

    private static function note(?string $note): string
    {
        return filled($note) ? ' | Catatan: ' . $note : '';
    }

    private static function quantitySummary(Pengajuan $request): string
    {
        $requested = (int) $request->details->sum('quantity');
        $approved = (int) $request->details->sum('approved_quantity');

        return 'Disetujui ' . number_format($approved, 0, ',', '.')
            . ' dari ' . number_format($requested, 0, ',', '.') . ' unit';
    }
}
