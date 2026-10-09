<?php

namespace App\Rules;

use App\Support\CoverImage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * S3: an uploaded cover must be decodable JPEG/PNG/WebP bytes (magic
 * signature + getimagesize), within the dimension cap — before storage.
 * The model hook re-encodes on save and strips all metadata.
 */
class CoverSignature implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $path = $value instanceof UploadedFile ? $value->getRealPath() : null;
        if ($path === false || $path === null) {
            $fail('The cover upload could not be read.');

            return;
        }

        $problem = CoverImage::problem($path);
        if ($problem !== null) {
            $fail($problem.'.');
        }
    }
}
