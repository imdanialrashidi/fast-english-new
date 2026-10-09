<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * S5 receipt image pipeline (scope §10.3).
 *
 * Accepted: exactly one JPEG, PNG, or WebP image, at most 5 MB, decodable,
 * at most 6000×6000 px. SVG, HTML, PDF, and executables are refused by
 * signature (not just extension or client MIME). Valid files are
 * re-encoded through GD — which drops EXIF and all other metadata — and
 * stored under a random name on the private disk (never the public disk).
 *
 * Write order (scope §10.3): the file is written before the DB mutation.
 * On a failed DB write the caller removes the file; orphaned files are
 * reclaimed by the `receipts:cleanup` command.
 */
final class ReceiptImage
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    public const MAX_DIMENSION = 6000;

    /** mime => extension, the only receipt types we store. */
    public const MIME_MAP = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Validate the uploaded receipt. Returns null when acceptable,
     * otherwise a short Persian reason for the 422 response (S7
     * pre-slice 3: learner-facing receipt messages are Persian).
     */
    public static function problem(UploadedFile $file): ?string
    {
        if ($file->getSize() === false || $file->getSize() <= 0) {
            return 'فایل رسید خالی است';
        }

        if ($file->getSize() > self::MAX_BYTES) {
            return 'حجم فایل رسید بیشتر از ۵ مگابایت است';
        }

        $realPath = $file->getRealPath();
        if ($realPath === false) {
            return 'فایل رسید قابل خواندن نیست';
        }

        // Server-detected MIME first: finfo on bytes, never the client.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($realPath);
        if (! isset(self::MIME_MAP[$mime])) {
            return 'رسید باید تصویر JPEG، PNG یا WebP باشد';
        }

        // Magic-byte signatures: JPEG SOI, PNG header, WebP RIFF....WEBP.
        $head = @file_get_contents($realPath, false, null, 0, 12);
        if ($head === false || strlen($head) < 4) {
            return 'فایل رسید خالی است';
        }

        $isJpeg = str_starts_with($head, "\xFF\xD8\xFF");
        $isPng = str_starts_with($head, "\x89PNG");
        $isWebp = strlen($head) >= 12
            && str_starts_with($head, 'RIFF')
            && substr($head, 8, 4) === 'WEBP';

        $signatureOk = match ($mime) {
            'image/jpeg' => $isJpeg,
            'image/png' => $isPng,
            'image/webp' => $isWebp,
            default => false,
        };

        if (! $signatureOk) {
            return 'امضای فایل با نوع آن سازگار نیست';
        }

        // Explicit executable / document rejections: MZ, %PDF, HTML, SVG.
        // These can never be valid receipt bytes, even when the client
        // claims an image MIME.
        if (str_starts_with($head, 'MZ')) {
            return 'فایل اجرایی به‌عنوان رسید پذیرفته نمی‌شود';
        }
        if (str_starts_with($head, '%PDF')) {
            return 'فایل PDF به‌عنوان رسید پذیرفته نمی‌شود';
        }
        if (str_starts_with(ltrim($head), '<')) {
            return 'فایل SVG و HTML به‌عنوان رسید پذیرفته نمی‌شود';
        }

        // Decodability + dimension cap (resource control).
        $size = @getimagesize($realPath);
        if ($size === false) {
            return 'تصویر رسید قابل رمزگشایی نیست';
        }

        if ($size[0] > self::MAX_DIMENSION || $size[1] > self::MAX_DIMENSION) {
            return 'ابعاد تصویر رسید بیشتر از ۶۰۰۰ پیکسل است';
        }

        // GD decode test: proves the bytes are a real image the pipeline
        // can re-encode (and catches truncated/corrupt files getimagesize
        // alone may miss).
        $decoded = @imagecreatefromstring((string) @file_get_contents($realPath));
        if ($decoded === false) {
            return 'تصویر رسید قابل رمزگشایی نیست';
        }
        imagedestroy($decoded);

        return null;
    }

    /**
     * Re-encode the uploaded receipt (metadata stripped) and store it
     * under a random name on the private disk. Returns the stored path.
     *
     * @throws ValidationException when the file is not a decodable image.
     */
    public static function store(UploadedFile $file): string
    {
        $problem = self::problem($file);
        if ($problem !== null) {
            throw ValidationException::withMessages(['receipt' => 'رسید نامعتبر است: '.$problem]);
        }

        $realPath = $file->getRealPath();
        assert(is_string($realPath));

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($realPath);
        $extension = self::MIME_MAP[$mime] ?? 'jpg';

        $source = @imagecreatefromstring((string) @file_get_contents($realPath));
        if ($source === false) {
            throw ValidationException::withMessages([
                'receipt' => 'تصویر رسید قابل رمزگشایی نیست.',
            ]);
        }

        $name = 'receipts/'.((string) Str::ulid()).'.'.$extension;
        $disk = Storage::disk('local');
        $disk->makeDirectory('receipts');
        $absolute = $disk->path($name);
        $tmp = $absolute.'.reencode';

        try {
            $ok = match ($extension) {
                'jpg' => imagejpeg($source, $tmp, 85),
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
                'receipt' => 'تصویر رسید قابل ذخیره‌سازی نیست.',
            ]);
        }

        rename($tmp, $absolute);

        return $name;
    }
}
