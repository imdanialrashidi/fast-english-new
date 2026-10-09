<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * S3 cover pipeline (scope §9.4): covers are JPEG/PNG/WebP up to 2 MB on
 * the public disk under random names. Every write re-encodes through GD,
 * which drops EXIF and all other metadata — no metadata ever survives
 * storage. Production PHP needs ext-gd for this pipeline.
 */
final class CoverImage
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public const MAX_DIMENSION = 4000;

    /** mime => extension, the only cover types we store. */
    public const MIME_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Re-encode the stored cover in place and return its path.
     *
     * @throws ValidationException when the file is not a decodable image.
     */
    public static function reencode(string $path): string
    {
        $disk = Storage::disk('public');
        $absolute = $disk->path($path);

        $source = @imagecreatefromstring((string) @file_get_contents($absolute));
        if ($source === false) {
            throw ValidationException::withMessages([
                'cover_path' => 'The cover image could not be decoded.',
            ]);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $tmp = $absolute.'.reencode';

        try {
            $ok = match ($extension) {
                'jpg', 'jpeg' => imagejpeg($source, $tmp, 85),
                'png' => imagepng($source, $tmp, 6),
                'webp' => imagewebp($source, $tmp, 85),
                default => false,
            };
        } finally {
            imagedestroy($source);
        }

        if ($ok !== true || ! is_file($tmp)) {
            @unlink($tmp);

            throw ValidationException::withMessages([
                'cover_path' => 'The cover image could not be re-encoded.',
            ]);
        }

        // Atomic-ish swap so readers never see a half-written file.
        rename($tmp, $absolute);

        return $path;
    }

    /**
     * Null when the raw bytes look like a supported cover; else a reason.
     * Used by upload validation before anything is stored.
     */
    public static function problem(string $absolutePath): ?string
    {
        $head = @file_get_contents($absolutePath, false, null, 0, 12);
        if ($head === false || strlen($head) < 4) {
            return 'cover file is empty';
        }

        $isJpeg = str_starts_with($head, "\xFF\xD8\xFF");
        $isPng = str_starts_with($head, "\x89PNG");
        $isWebp = strlen($head) >= 12
            && str_starts_with($head, 'RIFF')
            && substr($head, 8, 4) === 'WEBP';

        if (! ($isJpeg || $isPng || $isWebp)) {
            return 'cover must be a JPEG, PNG, or WebP file';
        }

        $size = @getimagesize($absolutePath);
        if ($size === false) {
            return 'cover image could not be decoded';
        }

        if ($size[0] > self::MAX_DIMENSION || $size[1] > self::MAX_DIMENSION) {
            return 'cover image exceeds '.self::MAX_DIMENSION.'px on a side';
        }

        return null;
    }
}
