<?php

namespace App\Support;

use App\Models\Entity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Logo dan kop surat per perusahaan untuk dokumen cetak (FPB memakai logo, BAST memakai kop surat A4).
 * Disimpan di disk privat dan disisipkan ke halaman cetak sebagai data URI.
 */
class EntityLetterhead
{
    public const DISK = 'local';

    public const LETTERHEAD = 'letterhead_path';
    public const LOGO = 'logo_path';

    public static function store(Entity $entity, UploadedFile $file, string $column = self::LETTERHEAD): void
    {
        self::delete($entity, $column);

        $folder = $column === self::LOGO ? 'logos' : 'letterheads';
        $path = $folder . '/' . $entity->id . '-' . time() . '.' . ($file->extension() ?: 'png');
        Storage::disk(self::DISK)->put($path, $file->get());

        $entity->forceFill([$column => $path])->save();
    }

    public static function delete(Entity $entity, string $column = self::LETTERHEAD): void
    {
        $path = $entity->{$column};

        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }

        $entity->forceFill([$column => null])->save();
    }

    public static function dataUri(?Entity $entity, string $column = self::LETTERHEAD): ?string
    {
        $path = $entity?->{$column};

        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk(self::DISK)->get($path));
    }
}
