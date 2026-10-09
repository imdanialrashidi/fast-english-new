<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse as FailedContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulContract;

/**
 * S8-2 account oracle fix (AUTH-02): unknown emails get byte-identical
 * treatment to known ones. Fortify's default failed response renders
 * «We can't find a user with that email address» — an existence oracle.
 * This response replaces BOTH the failed and the successful link-request
 * responses with one neutral Persian message, so the two paths are
 * indistinguishable over HTML and over JSON.
 */
class NeutralPasswordResetLinkResponse implements FailedContract, SuccessfulContract
{
    public const MESSAGE = 'اگر حسابی با این ایمیل وجود داشته باشد، پیوند بازیابی ارسال شد.';

    public function __construct(string $status = '') {}

    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse(['status' => self::MESSAGE], 200)
            : back()->with('status', self::MESSAGE);
    }
}
