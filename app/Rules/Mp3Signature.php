<?php

namespace App\Rules;

use App\Support\AudioFile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * S3: an uploaded audio file must carry a valid MP3 signature (ID3v2 or
 * MPEG frame sync), not just an .mp3 extension or a client-sent MIME.
 */
class Mp3Signature implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $path = $value instanceof UploadedFile ? $value->getRealPath() : null;
        if ($path === false || $path === null) {
            $fail('The audio upload could not be read.');

            return;
        }

        if (! AudioFile::hasMp3Signature($path)) {
            $fail('The audio file is not a valid MP3.');
        }
    }
}
