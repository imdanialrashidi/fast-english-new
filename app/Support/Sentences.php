<?php

namespace App\Support;

/**
 * R3 sentence segmentation (READ-03): one deterministic splitter shared by
 * the reader and the staff cue editor, so cue sentence_index values always
 * mean the same sentences in both places.
 *
 * Paragraphs split on blank lines; each paragraph splits after sentence
 * terminal punctuation (. ! ? …) followed by whitespace and an uppercase
 * letter, digit, or quote — or at the end of the paragraph. The result is
 * a flat list; cues must number it contiguously from 0 (AudioCues).
 */
final class Sentences
{
    /**
     * @return list<string>
     */
    public static function split(string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            return [];
        }

        $paragraphs = preg_split('/\\R{2,}/', $body) ?: [];
        $sentences = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim(preg_replace('/\s+/', ' ', $paragraph));
            if ($paragraph === '') {
                continue;
            }

            $parts = preg_split('/(?<=[.!?…])\s+(?=[A-Z0-9"“«])/u', $paragraph) ?: [$paragraph];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $sentences[] = $part;
                }
            }
        }

        return array_values($sentences);
    }
}
