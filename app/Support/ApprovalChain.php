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
 * Urutan tahap pada sebuah pengajuan:
 * 1. Atasan langsung (jika dipilih dari user sistem): tahap yang ditunjuk ke satu
 *    user tertentu, "Diketahui oleh".
 * 2. Tahap tetap dari pengaturan Alur Approval, misalnya Spv. GA lalu Manager HCGA.
 *
 * Aturan:
 * - Tahap berjalan berurutan; hanya tahap pending pertama yang bisa diproses.
 * - Tahap atasan langsung hanya bisa diproses user yang ditunjuk. Tahap tetap bisa
 *   diproses user dengan jabatan tahap itu (dan akses entitas pengajuan). Super admin
 *   dapat memproses tahap mana pun. Pembuat pengajuan tidak boleh memproses
 *   pengajuannya sendiri, kecuali super admin.
 * - Tahap terakhir yang disetujui menyelesaikan pengajuan. Jumlah disetujui
 *   per barang ditetapkan pada tahap terakhir.
 * - Saat disetujui, tanda tangan digital penyetuju disalin ke tahap itu (jika ada).
 * - Penolakan di tahap mana pun menghentikan alur; tahap sesudahnya ditandai skipped.
 * - Jika tidak ada tahap sama sekali, pengajuan memakai approval satu tahap seperti
 *   sebelumnya.
 */
class ApprovalChain
{
    public const SUPERIOR_LABEL = 'Diketahui oleh';
    public const SUPERIOR_FALLBACK_POSITION = 'Atasan Langsung';

    /**
     * Salin tahap ke pengajuan baru: atasan langsung lebih dulu, lalu tahap tetap.
     */
    public static function initialize(Pengajuan $request): void
    {
        if (! $request->isCustomRequest()) {
            return;
        }

        $order = 0;

        $superior = $request->superior_user_id
            ? User::query()->with('position')->find($request->superior_user_id)
            : null;

        if ($superior) {
            RequestApproval::create([
                'request_id' => $request->id,
                'step_order' => ++$order,
                'label' => self::SUPERIOR_LABEL,
                'position_id' => null,
                'position_name' => filled($request->superior_position)
                    ? $request->superior_position
                    : ($superior->position?->name ?? self::SUPERIOR_FALLBACK_POSITION),
                'assigned_user_id' => $superior->id,
                'status' => RequestApproval::PENDING,
            ]);
        }

        $steps = ApprovalStep::query()
            ->active()
            ->ordered()
            ->with('position')
            ->get()
            ->filter(fn (ApprovalStep $step) => $step->position !== null)
            // Atasan langsung sudah menjadi tahap tersendiri; tahap tetap bernama sama
            // (peninggalan pengaturan lama) dilewati agar persetujuan tidak dobel.
            ->reject(fn (ApprovalStep $step) => $superior && ApprovalStep::isSuperiorPosition($step->position->name));

        foreach ($steps as $step) {
            RequestApproval::create([
                'request_id' => $request->id,
                'step_order' => ++$order,
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

        // Tahap yang ditunjuk ke user tertentu (atasan langsung).
        if ($step->assigned_user_id !== null) {
            return (int) $step->assigned_user_id === (int) $user->id;
        }

        return $step->position_id !== null
            && (int) $user->position_id === (int) $step->position_id
            && $user->canAccessEntity($request->entity_id);
    }

    /**
     * Apakah user terlibat sebagai penyetuju pada salah satu tahap pengajuan ini.
     */
    public static function isParticipant(User $user, Pengajuan $request): bool
    {
        return $request->approvals->contains(function (RequestApproval $step) use ($user, $request) {
            if ($step->assigned_user_id !== null) {
                return (int) $step->assigned_user_id === (int) $user->id;
            }

            return $user->position_id
                && (int) $step->position_id === (int) $user->position_id
                && $user->canAccessEntity($request->entity_id);
        });
    }

    /**
     * Apakah user punya pengajuan yang melibatkannya sebagai penyetuju (untuk menu).
     */
    public static function isParticipantAnywhere(User $user): bool
    {
        return RequestApproval::query()
            ->where(function ($query) use ($user) {
                $query->where('assigned_user_id', $user->id);

                if ($user->position_id) {
                    $query->orWhere(function ($byPosition) use ($user) {
                        $byPosition->whereNull('assigned_user_id')
                            ->where('position_id', $user->position_id);
                    });
                }
            })
            ->exists();
    }

    /**
     * Apakah user boleh melihat pengajuan dengan alur bertingkat ini.
     *
     * Pengajuan custom bersifat terpisah antar departemen: hanya super admin, admin
     * (pembuat FPB), dan penyetuju yang terlibat pada alurnya yang boleh melihat.
     * Approver dan staff yang bukan peserta tidak mendapat akses lewat lokasi, sehingga
     * atasan langsung departemen lain tidak melihat pengajuan ini.
     */
    public static function canView(User $user, Pengajuan $request): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (self::isParticipant($user, $request)) {
            return true;
        }

        if ($user->role !== 'admin') {
            return false;
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

            // Tanda tangan digital disalin agar FPB lama tidak berubah bila tanda tangan diganti.
            SignatureStore::snapshot($user, $step);

            if ($isFinal) {
                $locked->update([
                    'status' => 'approved',
                    'approver_id' => $user->id,
                    'approver_note' => $note,
                    'approved_at' => now(),
                ]);

                SignatureStore::ensurePicSnapshot($locked);
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
