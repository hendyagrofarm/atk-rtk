<?php

namespace App\Support;

use App\Models\ApprovalStep;
use App\Models\Request as Pengajuan;
use App\Models\RequestApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Alur approval bertingkat untuk pengajuan custom (Formulir Pengajuan Barang).
 *
 * Aturan:
 * - Tahap berjalan berurutan; hanya tahap pending pertama yang bisa diproses.
 * - Yang boleh memproses tahap: user dengan jabatan tahap itu (dan akses entitas
 *   pengajuan), atau super admin. Pembuat pengajuan tidak boleh memproses
 *   pengajuannya sendiri, kecuali super admin.
 * - Tahap terakhir yang disetujui menyelesaikan pengajuan. Jumlah disetujui
 *   per barang ditetapkan pada tahap terakhir.
 * - Penolakan di tahap mana pun menghentikan alur; tahap sesudahnya ditandai skipped.
 * - Jika tidak ada tahap aktif yang dikonfigurasi, pengajuan memakai approval
 *   satu tahap seperti sebelumnya.
 */
class ApprovalChain
{
    /**
     * Salin pengaturan tahap aktif ke pengajuan baru.
     */
    public static function initialize(Pengajuan $request): void
    {
        if (! $request->isCustomRequest()) {
            return;
        }

        $steps = ApprovalStep::query()
            ->active()
            ->ordered()
            ->with('position')
            ->get()
            ->filter(fn (ApprovalStep $step) => $step->position !== null);

        foreach ($steps->values() as $index => $step) {
            RequestApproval::create([
                'request_id' => $request->id,
                'step_order' => $index + 1,
                'label' => $step->label,
                'position_id' => $step->position_id,
                'position_name' => $step->position->name,
                'status' => RequestApproval::PENDING,
            ]);
        }
    }

    /**
     * Tahap pending pertama, atau null jika alur sudah selesai.
     */
    public static function currentStep(Pengajuan $request): ?RequestApproval
    {
        if ($request->status !== 'pending') {
            return null;
        }

        return $request->approvals->first(fn (RequestApproval $step) => $step->isPending());
    }

    public static function isFinalStep(Pengajuan $request, RequestApproval $step): bool
    {
        return (int) $request->approvals->max('step_order') === (int) $step->step_order;
    }

    /**
     * Apakah user boleh memproses tahap berjalan pada pengajuan ini.
     */
    public static function canAct(User $user, Pengajuan $request): bool
    {
        $step = self::currentStep($request);

        if (! $step || ! $user->is_active) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $request->user_id === (int) $user->id) {
            return false;
        }

        return $step->position_id !== null
            && (int) $user->position_id === (int) $step->position_id
            && $user->canAccessEntity($request->entity_id);
    }

    /**
     * Apakah user boleh melihat pengajuan dengan alur bertingkat ini.
     */
    public static function canView(User $user, Pengajuan $request): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->position_id && $request->approvals->contains('position_id', $user->position_id)
            && $user->canAccessEntity($request->entity_id)) {
            return true;
        }

        return $request->entity_id
            && $request->warehouse_id
            && $user->canAccessLocation($request->entity_id, $request->warehouse_id);
    }

    /**
     * Setujui tahap berjalan. Mengembalikan true jika ini tahap terakhir
     * sehingga pengajuan menjadi disetujui.
     *
     * @param  array<int|string, mixed>  $approvedQuantities  detail_id => jumlah (dipakai pada tahap terakhir)
     */
    public static function approve(
        Pengajuan $request,
        User $user,
        ?string $note,
        array $approvedQuantities = []
    ): bool {
        return DB::transaction(function () use ($request, $user, $note, $approvedQuantities) {
            $locked = self::lock($request);

            if ($locked->status !== 'pending') {
                throw new RuntimeException('Pengajuan ini sudah diproses sebelumnya.');
            }

            $step = self::currentStep($locked);

            if (! $step || ! self::canAct($user, $locked)) {
                throw new RuntimeException('Anda tidak berwenang memproses tahap approval saat ini.');
            }

            $isFinal = self::isFinalStep($locked, $step);

            if ($isFinal) {
                foreach ($locked->details as $detail) {
                    $quantity = (int) ($approvedQuantities[$detail->id] ?? $detail->quantity);

                    if ($quantity < 0 || $quantity > $detail->quantity) {
                        throw new RuntimeException(
                            'Jumlah disetujui tidak boleh lebih dari jumlah diminta.'
                        );
                    }

                    $detail->update(['approved_quantity' => $quantity]);
                }
            }

            $step->update([
                'status' => RequestApproval::APPROVED,
                'user_id' => $user->id,
                'acted_at' => now(),
                'note' => $note,
            ]);

            if ($isFinal) {
                $locked->update([
                    'status' => 'approved',
                    'approver_id' => $user->id,
                    'approver_note' => $note,
                    'approved_at' => now(),
                ]);
            }

            return $isFinal;
        });
    }

    /**
     * Tolak pengajuan pada tahap berjalan.
     */
    public static function reject(
        Pengajuan $request,
        User $user,
        string $reasonKey,
        ?string $note
    ): void {
        DB::transaction(function () use ($request, $user, $reasonKey, $note) {
            $locked = self::lock($request);

            if ($locked->status !== 'pending') {
                throw new RuntimeException('Pengajuan ini sudah diproses sebelumnya.');
            }

            $step = self::currentStep($locked);

            if (! $step || ! self::canAct($user, $locked)) {
                throw new RuntimeException('Anda tidak berwenang memproses tahap approval saat ini.');
            }

            $step->update([
                'status' => RequestApproval::REJECTED,
                'user_id' => $user->id,
                'acted_at' => now(),
                'note' => $note,
            ]);

            $locked->approvals
                ->filter(fn (RequestApproval $other) => $other->isPending() && $other->id !== $step->id)
                ->each(fn (RequestApproval $other) => $other->update(['status' => RequestApproval::SKIPPED]));

            $locked->update([
                'status' => 'rejected',
                'approver_id' => $user->id,
                'approver_note' => $note,
                'rejection_reason' => $reasonKey,
                'approved_at' => now(),
            ]);
        });
    }

    private static function lock(Pengajuan $request): Pengajuan
    {
        $locked = Pengajuan::query()
            ->whereKey($request->id)
            ->lockForUpdate()
            ->firstOrFail();

        $locked->load(['details', 'approvals']);

        return $locked;
    }
}
