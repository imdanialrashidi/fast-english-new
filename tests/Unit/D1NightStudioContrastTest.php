<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// D1 Night Studio contrast guard: parses the token tables in docs/DESIGN.md
// (dark / light / sepia) and recomputes WCAG relative-luminance contrast with
// the same method as S1/S3. Body-text pairs must hold >= 4.5:1; control and
// focus pairs (primary fills + input-border boundaries) must hold >= 3:1.
// Decorative divider borders are exempt and never sole indicators.
class D1NightStudioContrastTest extends TestCase
{
    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = array_map(
            fn ($c) => hexdec($c) / 255,
            [substr($hex, 0, 2), substr($hex, 2, 2), substr($hex, 4, 2)]
        );
        $linear = array_map(
            fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
            $channels
        );

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }

    private function contrast(string $a, string $b): float
    {
        $lighter = max($this->luminance($a), $this->luminance($b));
        $darker = min($this->luminance($a), $this->luminance($b));

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function nightStudioTokens(): array
    {
        $path = dirname(__DIR__, 2).'/docs/DESIGN.md';
        $this->assertFileExists($path, 'docs/DESIGN.md must exist');

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        $this->assertNotFalse($lines, 'docs/DESIGN.md must be readable');

        $themes = ['dark' => [], 'light' => [], 'sepia' => []];
        $current = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            // The Night Studio section ends at its measured-contrast block;
            // later tables (e.g. the approved Soft Day palette) belong to
            // other directions and must not overwrite these tokens.
            if (str_starts_with($trimmed, '### Measured contrast')) {
                break;
            }
            if (str_starts_with($trimmed, 'Dark —') || str_starts_with($trimmed, 'Dark --')) {
                $current = 'dark';

                continue;
            }
            if (str_starts_with($trimmed, 'Light —') || str_starts_with($trimmed, 'Light --')) {
                $current = 'light';

                continue;
            }
            if (str_starts_with($trimmed, 'Sepia —') || str_starts_with($trimmed, 'Sepia --')) {
                $current = 'sepia';

                continue;
            }
            if ($current === null || ! str_starts_with($trimmed, '|')) {
                continue;
            }

            $cells = array_map('trim', explode('|', $trimmed));
            // explode gives ['', token, hex, role, '']; require at least token + hex.
            if (count($cells) < 4) {
                continue;
            }
            $token = strtolower(trim($cells[1]));
            if ($token === '' || $token === 'token') {
                continue;
            }
            if (! preg_match('/#[0-9a-fA-F]{6}/', $cells[2], $m)) {
                continue;
            }
            $themes[$current][$token] = strtoupper($m[0]);
        }

        foreach (['dark', 'light', 'sepia'] as $theme) {
            foreach (['canvas', 'surface', 'text', 'muted-text', 'border', 'input-border', 'primary', 'primary-hover', 'on-primary', 'success', 'danger'] as $required) {
                $this->assertArrayHasKey(
                    $required,
                    $themes[$theme],
                    "docs/DESIGN.md {$theme} table must list `{$required}`"
                );
            }
        }

        return $themes;
    }

    public function test_body_text_meets_4_5_to_1(): void
    {
        $themes = $this->nightStudioTokens();

        // Body/secondary/label text on fills: WCAG 2.2 AA normal text >= 4.5:1.
        $pairs = [
            'text on canvas' => ['text', 'canvas'],
            'text on surface' => ['text', 'surface'],
            'muted-text on canvas' => ['muted-text', 'canvas'],
            'muted-text on surface' => ['muted-text', 'surface'],
            'on-primary on primary' => ['on-primary', 'primary'],
            'on-primary on primary-hover' => ['on-primary', 'primary-hover'],
            'success on surface' => ['success', 'surface'],
            'danger on surface' => ['danger', 'surface'],
        ];

        foreach ($themes as $theme => $tokens) {
            foreach ($pairs as $label => [$fg, $bg]) {
                $ratio = $this->contrast($tokens[$fg], $tokens[$bg]);
                fwrite(STDERR, "\n[D1 contrast:{$theme}] {$label}: {$ratio}:1 ({$tokens[$fg]}/{$tokens[$bg]})");
                $this->assertGreaterThanOrEqual(
                    4.5,
                    $ratio,
                    "{$theme} {$label} measured {$ratio}:1 ({$tokens[$fg]} on {$tokens[$bg]}), needs >= 4.5:1"
                );
            }
        }
    }

    public function test_controls_and_focus_meet_3_to_1(): void
    {
        $themes = $this->nightStudioTokens();

        // Non-text controls, focus fills, and input/checkbox boundaries >= 3:1.
        $pairs = [
            'primary on surface (focus/non-text)' => ['primary', 'surface'],
            'primary on canvas (focus/non-text)' => ['primary', 'canvas'],
            'primary-hover on surface (focus)' => ['primary-hover', 'surface'],
            'primary-hover on canvas (focus)' => ['primary-hover', 'canvas'],
            'input-border on surface (control boundary)' => ['input-border', 'surface'],
        ];

        foreach ($themes as $theme => $tokens) {
            foreach ($pairs as $label => [$fg, $bg]) {
                $ratio = $this->contrast($tokens[$fg], $tokens[$bg]);
                fwrite(STDERR, "\n[D1 contrast:{$theme}] {$label}: {$ratio}:1 ({$tokens[$fg]}/{$tokens[$bg]})");
                $this->assertGreaterThanOrEqual(
                    3.0,
                    $ratio,
                    "{$theme} {$label} measured {$ratio}:1 ({$tokens[$fg]} on {$tokens[$bg]}), needs >= 3:1"
                );
            }
        }
    }
}
