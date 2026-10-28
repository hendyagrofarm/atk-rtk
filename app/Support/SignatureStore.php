<?php

namespace App\Support;

use App\Models\Request as Pengajuan;
use App\Models\RequestApproval;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Penyimpanan tanda tangan digital (PNG) di disk privat.
 *
 * - File tidak bisa diakses publik; hanya ditampilkan lewat halaman yang
 *   memeriksa hak akses, atau disisipkan sebagai data URI pada FPB.
 * - Saat sebuah tahap disetujui, tanda tangan user disalin (snapshot) ke tahap
 *   itu, sehingga mengganti tanda tangan nanti tidak mengubah FPB lama.
 */
class SignatureStore
{
    public const DISK = 'local';
    public const MAX_KILOBYTES = 1024;
    public const MAX_DIMENSION = 3000;

    /**
     * Pastikan berkas benar-benar PNG (bukan sekadar berekstensi .png).
     *
     * @throws RuntimeException
     */
    public static function assertValidPng(UploadedFile $file): void
    {
        $info = @getimagesize($file->getRealPath());

        if ($info === false || $info[2] !== IMAGETYPE_PNG) {
            throw new RuntimeException('Tanda tangan harus berupa berkas PNG yang valid.');
        }

        if ($info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            throw new RuntimeException(
                'Ukuran gambar tanda tangan maksimal ' . self::MAX_DIMENSION . ' x ' . self::MAX_DIMENSION . ' piksel.'
            );
        }
    }

    /**
     * Simpan tanda tangan user, menggantikan yang lama.
     */
    public static function store(User $user, UploadedFile $file): string
    {
        self::assertValidPng($file);

        $path = 'signatures/users/' . $user->id . '.png';

        Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));

        $user->forceFill(['signature_path' => $path])->save();

        return $path;
    }

    public static function delete(User $user): void
    {
        if ($user->signature_path) {
            Storage::disk(self::DISK)->delete($user->signature_path);
        }

        $user->forceFill(['signature_path' => null])->save();
    }

    public static function exists(?string $path): bool
    {
        return filled($path) && Storage::disk(self::DISK)->exists($path);
    }

    public static function userHasSignature(User $user): bool
    {
        return self::exists($user->signature_path);
    }

    /**
     * Salin tanda tangan user ke tahap approval saat disetujui.
     */
    public static function snapshot(User $user, RequestApproval $step): void
    {
        if (! self::userHasSignature($user)) {
            return;
        }

        $path = 'signatures/approvals/' . $step->id . '.png';

        Storage::disk(self::DISK)->put($path, Storage::disk(self::DISK)->get($user->signature_path));

        $step->update(['signature_path' => $path]);
    }

    /**
     * Salin tanda tangan PIC ke pengajuan saat FPB dibuat.
     */
    public static function snapshotForRequest(Pengajuan $request, ?User $pic): void
    {
        if (! $pic || ! self::userHasSignature($pic)) {
            return;
        }

        $path = 'signatures/requests/' . $request->id . '-pic.png';

        Storage::disk(self::DISK)->put($path, Storage::disk(self::DISK)->get($pic->signature_path));

        $request->update(['pic_signature_path' => $path]);
    }

    /**
     * Jika saat dibuat PIC belum punya tanda tangan tetapi sudah mengunggahnya sebelum FPB
     * selesai, salin sekarang agar FPB final memuatnya.
     */
    public static function ensurePicSnapshot(Pengajuan $request): void
    {
        if (self::exists($request->pic_signature_path) || ! $request->pic_user_id) {
            return;
        }

        self::snapshotForRequest($request, User::find($request->pic_user_id));
    }

    /**
     * Data URI untuk disisipkan langsung pada halaman cetak.
     */
    public static function dataUri(?string $path): ?string
    {
        if (! self::exists($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(Storage::disk(self::DISK)->get($path));
    }
}
