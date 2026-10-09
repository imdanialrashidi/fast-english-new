<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * S1 audio delivery: /media/lessons/{lesson}/audio.
 *
 * The server-side policy runs before any byte is sent. Bytes stream from
 * the private disk through Laravel's file response (Symfony
 * BinaryFileResponse, which owns Range/206/416 and HEAD); the file is
 * never loaded into PHP memory. Nginx X-Accel-Redirect delivery belongs
 * to a later slice (recorded in the active exec plan).
 */
class LessonAudioController extends Controller
{
    public function show(Request $request, Lesson $lesson): BinaryFileResponse
    {
        if ($lesson->status !== 'published' || $lesson->topic->status !== 'published') {
            abort(404);
        }

        if (Gate::allows('view', $lesson) !== true) {
            abort(403);
        }

        $path = Storage::disk('local')->path($lesson->audio_path);
        if (! is_file($path)) {
            abort(404);
        }

        $size = filesize($path);
        $range = (string) $request->header('Range', '');
        if ($range !== '' && ! $this->isRangeSatisfiable($range, $size)) {
            abort(response('', 416, ['Content-Range' => "bytes */{$size}"]));
        }

        // BinaryFileResponse defaults to `public`; that directive would let
        // a shared cache store private audio. setPrivate() after
        // construction removes it deterministically (scope §16).
        $response = response()->file($path, [
            'Content-Type' => 'audio/mpeg',
            'Accept-Ranges' => 'bytes',
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }

    /**
     * Symfony's file response ignores unsatisfiable ranges (200). Scope
     * §16 requires 416 with Content-Range for out-of-range requests, so
     * that case is rejected here; valid ranges still stream via the file
     * response. Malformed headers are ignored per RFC 9110 (200).
     */
    private function isRangeSatisfiable(string $range, int $size): bool
    {
        if (! str_starts_with($range, 'bytes=')) {
            return true;
        }

        $spec = substr($range, strlen('bytes='));
        if (str_contains($spec, ',') || ! preg_match('/^(\d*)-(\d*)$/', $spec, $m)) {
            return true;
        }

        [$start, $end] = [$m[1], $m[2]];
        if ($start === '' && $end === '') {
            return true;
        }
        if ($start === '') {
            return (int) $end > 0 && $size > 0;
        }
        if ((int) $start >= $size) {
            return false;
        }

        return $end === '' || (int) $end >= (int) $start;
    }
}
