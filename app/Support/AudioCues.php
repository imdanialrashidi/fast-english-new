<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * R3 sentence cues (READ-03): start/end times per sentence for one audio
 * revision. Staff enter cues against the lesson's measured duration; the
 * reader only uses cues whose audio_cues_revision equals the lesson's
 * audio_revision. No word-level data is stored or claimed.
 *
 * Shape: list of {sentence_index, start_seconds, end_seconds}, ordered by
 * sentence_index from 0, non-overlapping, within [0, duration].
 */
final class AudioCues
{
    public const MAX_SENTENCES = 200;

    /**
     * @return list<array{sentence_index: int, start_seconds: float, end_seconds: float}>
     *
     * @throws ValidationException
     */
    public static function validate(mixed $value, int $durationSeconds): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        if (! is_array($value) || array_is_list($value) === false) {
            throw ValidationException::withMessages([
                'audio_cues' => 'زمان‌بندی جمله‌ها باید یک فهرست باشد.',
            ]);
        }

        if (count($value) > self::MAX_SENTENCES) {
            throw ValidationException::withMessages([
                'audio_cues' => 'زمان‌بندی جمله‌ها نمی‌تواند بیش از '.self::MAX_SENTENCES.' جمله داشته باشد.',
            ]);
        }

        if ($durationSeconds < 1) {
            throw ValidationException::withMessages([
                'audio_cues' => 'مدت صوت برای اعتبارسنجی زمان‌بندی معتبر نیست.',
            ]);
        }

        $normalized = [];
        foreach (array_values($value) as $index => $cue) {
            $normalized[] = self::validateCue($cue, $index, $durationSeconds);
        }

        $indexes = array_column($normalized, 'sentence_index');
        if ($indexes !== range(0, count($normalized) - 1)) {
            throw ValidationException::withMessages([
                'audio_cues' => 'شماره جمله‌ها باید از صفر و بدون وقفه پشت سر هم باشند.',
            ]);
        }

        usort($normalized, fn ($a, $b) => $a['sentence_index'] <=> $b['sentence_index']);

        $previousEnd = 0.0;
        foreach ($normalized as $cue) {
            if ($cue['start_seconds'] < $previousEnd) {
                throw ValidationException::withMessages([
                    'audio_cues' => 'بازه‌های زمانی جمله‌ها نباید هم‌پوشانی داشته باشند.',
                ]);
            }
            $previousEnd = $cue['end_seconds'];
        }

        return $normalized;
    }

    /**
     * @return array{sentence_index: int, start_seconds: float, end_seconds: float}
     *
     * @throws ValidationException
     */
    private static function validateCue(mixed $cue, int $position, int $durationSeconds): array
    {
        $field = "audio_cues.{$position}";

        if (! is_array($cue)) {
            throw ValidationException::withMessages([
                $field => 'هر زمان‌بندی باید شماره جمله و بازه شروع/پایان داشته باشد.',
            ]);
        }

        $errors = [];

        $sentenceIndex = $cue['sentence_index'] ?? null;
        if (! is_int($sentenceIndex) && ! (is_numeric($sentenceIndex) && (int) $sentenceIndex == $sentenceIndex)) {
            $errors["{$field}.sentence_index"] = 'شماره جمله باید عدد صحیح باشد.';
        }
        $sentenceIndex = (int) $sentenceIndex;
        if ($sentenceIndex < 0) {
            $errors["{$field}.sentence_index"] = 'شماره جمله نمی‌تواند منفی باشد.';
        }

        $start = isset($cue['start_seconds']) ? (float) $cue['start_seconds'] : null;
        $end = isset($cue['end_seconds']) ? (float) $cue['end_seconds'] : null;

        if ($start === null || ! is_finite($start) || $start < 0) {
            $errors["{$field}.start_seconds"] = 'زمان شروع باید عددی از صفر به بعد باشد.';
        }
        if ($end === null || ! is_finite($end) || $end <= 0) {
            $errors["{$field}.end_seconds"] = 'زمان پایان باید عددی مثبت باشد.';
        }
        if ($start !== null && $end !== null && $end <= $start) {
            $errors["{$field}.end_seconds"] = 'زمان پایان باید بعد از زمان شروع باشد.';
        }
        if ($end !== null && $end > $durationSeconds + 0.5) {
            $errors["{$field}.end_seconds"] = 'زمان پایان نمی‌تواند از مدت صوت بیشتر باشد.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'sentence_index' => $sentenceIndex,
            'start_seconds' => round($start, 2),
            'end_seconds' => round($end, 2),
        ];
    }
}
