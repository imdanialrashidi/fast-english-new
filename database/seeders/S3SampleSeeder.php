<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use App\Support\TopicCover;
use App\Support\TopicLicense;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * S3 fixture seeder: library content for the browser lane and local dev.
 *
 * Everything here is a labelled FIXTURE (never production): prose is
 * original sample text, audio bytes are copies of the S1 sine-tone
 * fixtures (A-series = 20 s, B-series = 30 s, ffprobe-measured), covers
 * are GD-generated deterministic abstracts (TopicCover, no baked text),
 * and the reviewer account is test-only. Refuses production.
 *
 * Adds to (never replaces) the S1 sample data:
 * - categories story + everyday;
 * - morning-market (2 published lessons, A1 sample, glossaries, cover);
 * - rainy-day (1 published B1 sample lesson, glossary, cover);
 * - draft-notebook (draft topic + draft lesson: invisible everywhere);
 * - empty-shelf (published topic, no lessons: invisible in the library);
 * - archived-whisper (published topic, only an archived lesson: invisible).
 *
 * Also backfills published_at on S1-seeded published lessons, which
 * predate the column: published rows always carry a publication time.
 */
class S3SampleSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('S3SampleSeeder refuses to run in production.');
        }

        $reviewer = User::updateOrCreate(
            ['email' => 's3-reviewer-fixture@example.com'],
            ['name' => 'S3 Fixture Reviewer', 'password' => 'password', 'is_staff' => true]
        );

        $story = Category::updateOrCreate(
            ['slug' => 'story'],
            ['name_fa' => 'داستان‌ها', 'display_order' => 1, 'is_active' => true]
        );
        $everyday = Category::updateOrCreate(
            ['slug' => 'everyday'],
            ['name_fa' => 'زندگی روزمره', 'display_order' => 2, 'is_active' => true]
        );

        $market = Topic::updateOrCreate(
            ['slug' => 'morning-market'],
            [
                'category_id' => $everyday->id,
                'title_en' => 'Morning Market',
                'summary_public' => 'A busy morning market, told at two levels with key words.',
                'cover_path' => $this->cover('morning-market'),
                'source_note' => TopicLicense::generatedRecord('morning-market'),
                'status' => 'published',
                'published_at' => now(),
            ]
        );
        $this->lesson($market, 'A1', 'Morning Market', $this->marketA1(), 's1-a2-tone-fixture.mp3', 20, 2, true, $reviewer->id, [
            ['word' => 'crowded', 'meaning_fa' => 'شلوغ', 'example_en' => 'The market is crowded.'],
            ['word' => 'fresh', 'meaning_fa' => 'تازه', 'example_en' => 'Fresh bread smells good.'],
            ['word' => 'seller', 'meaning_fa' => 'فروشنده', 'example_en' => null],
        ]);
        $this->lesson($market, 'B2', 'Morning Market', $this->marketB2(), 's1-b1-tone-fixture.mp3', 30, 4, false, $reviewer->id, [
            ['word' => 'bargain', 'meaning_fa' => 'چانه‌زنی', 'example_en' => 'They bargain over the price.'],
            ['word' => 'stall', 'meaning_fa' => 'دکه', 'example_en' => null],
        ]);

        $rainy = Topic::updateOrCreate(
            ['slug' => 'rainy-day'],
            [
                'category_id' => $story->id,
                'title_en' => 'Rainy Day',
                'summary_public' => 'A quiet rainy afternoon and an old umbrella.',
                'cover_path' => $this->cover('rainy-day'),
                'source_note' => TopicLicense::generatedRecord('rainy-day'),
                'status' => 'published',
                'published_at' => now(),
            ]
        );
        $this->lesson($rainy, 'B1', 'Rainy Day', $this->rainyB1(), 's1-b1-tone-fixture.mp3', 30, 3, true, $reviewer->id, [
            ['word' => 'umbrella', 'meaning_fa' => 'چتر', 'example_en' => 'She opens her umbrella.'],
        ]);

        $draft = Topic::updateOrCreate(
            ['slug' => 'draft-notebook'],
            [
                'category_id' => $story->id,
                'title_en' => 'Hidden Draft Notebook',
                'summary_public' => 'S3 fixture draft: must never appear outside the staff panel.',
                'cover_path' => $this->cover('draft-notebook'),
                'source_note' => TopicLicense::generatedRecord('draft-notebook'),
                'status' => 'draft',
                'published_at' => null,
            ]
        );
        $this->lesson($draft, 'A1', 'Hidden Draft Notebook', $this->draftBody(), 's1-a2-tone-fixture.mp3', 20, 2, false, null, [], 'draft');

        Topic::updateOrCreate(
            ['slug' => 'empty-shelf'],
            [
                'category_id' => $everyday->id,
                'title_en' => 'Empty Shelf',
                'summary_public' => 'S3 fixture: a published topic with no lessons.',
                'cover_path' => $this->cover('empty-shelf'),
                'source_note' => TopicLicense::generatedRecord('empty-shelf'),
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        $whisper = Topic::updateOrCreate(
            ['slug' => 'archived-whisper'],
            [
                'category_id' => $story->id,
                'title_en' => 'Archived Whisper',
                'summary_public' => 'S3 fixture: only an archived lesson lives here.',
                'cover_path' => $this->cover('archived-whisper'),
                'source_note' => TopicLicense::generatedRecord('archived-whisper'),
                'status' => 'published',
                'published_at' => now(),
            ]
        );
        $this->lesson($whisper, 'B2', 'Archived Whisper', $this->draftBody(), 's1-b1-tone-fixture.mp3', 30, 3, false, $reviewer->id, [], 'archived');

        // Backfill: S1-seeded published lessons predate published_at.
        Lesson::query()
            ->where('status', 'published')
            ->whereNull('published_at')
            ->update(['published_at' => now()]);
    }

    /**
     * @param  list<array{word: string, meaning_fa: string, example_en: ?string}>  $glossary
     */
    private function lesson(
        Topic $topic,
        string $level,
        string $titleEn,
        string $bodyEn,
        string $fixtureFile,
        int $durationSeconds,
        int $estimatedMinutes,
        bool $isPublicSample,
        ?int $reviewedBy,
        array $glossary,
        string $status = 'published',
    ): void {
        $audioPath = "lessons/s3-{$topic->slug}-".strtolower($level).'.mp3';
        Storage::disk('local')->makeDirectory('lessons');
        copy(
            database_path("seeders/fixtures/{$fixtureFile}"),
            Storage::disk('local')->path($audioPath)
        );

        Lesson::updateOrCreate(
            ['topic_id' => $topic->id, 'level' => $level],
            [
                'title_en' => $titleEn,
                'body_en' => $bodyEn,
                'glossary' => $glossary === [] ? null : $glossary,
                'audio_path' => $audioPath,
                'audio_revision' => 1,
                'duration_seconds' => $durationSeconds,
                'estimated_minutes' => $estimatedMinutes,
                'is_public_sample' => $isPublicSample,
                'status' => $status,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => $reviewedBy === null ? null : now(),
                'published_at' => $status === 'published' ? now() : null,
            ]
        );
    }

    private function cover(string $slug): string
    {
        // D4: deterministic GD abstract (no PIL, no baked text, no fetch).
        // Seeding runs WithoutModelEvents in DatabaseSeeder, so the Topic
        // hook is suppressed — the generator already re-encodes (EXIF out).
        return TopicCover::generate($slug);
    }

    private function marketA1(): string
    {
        return <<<'TEXT'
        Ali goes to the morning market near his house. The market is crowded, and the air smells of fresh bread and fruit. Sellers call out prices, and people walk slowly between the small stalls.

        Ali buys red apples, green vegetables, and warm bread for breakfast. He pays the seller and puts everything in a big bag. Then he walks home happily under the warm morning sun.
        TEXT;
    }

    private function marketB2(): string
    {
        return <<<'TEXT'
        The morning market wakes up long before the rest of the city, and Ali likes to arrive while the sellers are still arranging their stalls. Although prices are written on small cards, everybody knows that a friendly bargain is part of the game, and the sellers enjoy it as much as the buyers.

        By nine o'clock the narrow paths between the stalls are full of shoppers carrying heavy bags, and the smell of fresh bread mixes with coffee from the corner stand. Ali leaves with apples, herbs, and cheese, convinced once again that no supermarket shelf can compete with this crowded, noisy, generous place.
        TEXT;
    }

    private function rainyB1(): string
    {
        return <<<'TEXT'
        It rained all afternoon, so Mina stayed inside with a book and a cup of tea. The rain on the window sounded soft and slow, and the street outside looked empty and grey.

        When the rain finally stopped, she took her old umbrella and walked to the small park. The ground was wet, the air was clean, and a thin rainbow stood quietly over the far houses.
        TEXT;
    }

    private function draftBody(): string
    {
        return <<<'TEXT'
        S3 fixture text. This draft paragraph exists so the hidden-content tests have something to look for — and never find — outside the staff panel.
        TEXT;
    }
}
