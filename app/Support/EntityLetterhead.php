<?php

namespace App\Support;

use App\Models\Entity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Kop surat (latar satu halaman A4 berisi logo dan alamat) per perusahaan untuk dokumen cetak seperti BAST.
 * Disimpan di disk privat dan disisipkan ke halaman cetak sebagai data URI.
 */
class EntityLetterhead
{
    public const DISK = 'local';

    public static function store(Entity $entity, UploadedFile $file): void
    {
        self::delete($entity);

        $path = 'letterheads/' . $entity->id . '-' . time() . '.' . ($file->extension() ?: 'png');
        Storage::disk(self::DISK)->put($path, $file->get());

        $entity->forceFill(['letterhead_path' => $path])->save();
    }

    public static function delete(Entity $entity): void
    {
        if ($entity->letterhead_path && Storage::disk(self::DISK)->exists($entity->letterhead_path)) {
            Storage::disk(self::DISK)->delete($entity->letterhead_path);
        }

        $entity->forceFill(['letterhead_path' => null])->save();
    }

    public static function dataUri(?Entity $entity): ?string
    {
        $path = $entity?->letterhead_path;

        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk(self::DISK)->get($path));
    }
}
