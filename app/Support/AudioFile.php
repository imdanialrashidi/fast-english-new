<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * S3 audio-file checks (scope §9.3–9.4): uploads are MP3 up to 30 MB;
 * publishing requires the file to exist on the private disk, be non-empty,
 * and carry a valid MP3 signature (ID3v2 header or MPEG frame sync).
 *
 * "Playable" here means: a signature-valid MP3 the S1 browser player can
 * load (S1 proved playback of such files over the Range route). Duration
 * comes from the staff-entered duration_seconds (> 0, CHECK-constrained);
 * no runtime transcoding or ffprobe runs in the request path.
 */
final class AudioFile
{
    public const MAX_BYTES = 30 * 1024 * 1024;

    /**
     * Null when the stored audio is acceptable; otherwise a short reason.
     */
    public static function problem(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return 'missing audio file';
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            return 'audio file not found on the private disk';
        }

        if ($disk->size($path) <= 0) {
            return 'audio file is empty';
        }

        if (! self::hasMp3Signature($disk->path($path))) {
            return 'audio file is not a valid MP3';
        }

        return null;
    }

    public static function hasMp3Signature(string $absolutePath): bool
    {
        $handle = @fopen($absolutePath, 'rb');
        if ($handle === false) {
            return false;
        }

        try {
            $head = fread($handle, 10);
        } finally {
            fclose($handle);
        }

        if ($head === false || strlen($head) < 3) {
            return false;
        }

        // ID3v2 header ("ID3") or an MPEG audio frame sync (0xFFEx).
        if (str_starts_with($head, 'ID3')) {
            return true;
        }

        return ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0;
    }
}
