<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * S4 pre-slice pagination fixtures (labelled, never production): twelve
 * additional published topics with one A1 sample lesson each, so the
 * browser lane holds at least 13 visible topics and the pagination
 * controls render. Combined with the S1 + S3 fixtures, /app shows 12 on
 * page one and the remainder on page two.
 */
class S4PaginationSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('S4PaginationSeeder refuses to run in production.');
        }

        for ($i = 1; $i <= 12; $i++) {
            $slug = sprintf('s4-paginate-%02d', $i);

            $topic = Topic::updateOrCreate(
                ['slug' => $slug],
                [
                    'title_en' => "S4 Paginate {$i}",
                    'summary_public' => "S4 pagination fixture {$i}: labelled, tone audio.",
                    'status' => 'published',
                    'published_at' => now()->subMinutes($i),
                ]
            );

            Lesson::updateOrCreate(
                ['topic_id' => $topic->id, 'level' => 'A1'],
                [
                    'title_en' => "S4 Paginate {$i}",
                    'body_en' => "S4 pagination fixture paragraph {$i}.\n\nSecond paragraph for fixture {$i}.",
                    'audio_path' => "lessons/s4-{$slug}-a1.mp3",
                    'audio_revision' => 1,
                    'duration_seconds' => 20,
                    'estimated_minutes' => 2,
                    'is_public_sample' => true,
                    'status' => 'published',
                    'published_at' => now()->subMinutes($i),
                ]
            );

            $audioPath = Storage::disk('local')->path("lessons/s4-{$slug}-a1.mp3");
            Storage::disk('local')->makeDirectory('lessons');
            if (! is_file($audioPath)) {
                copy(
                    database_path('seeders/fixtures/s1-a2-tone-fixture.mp3'),
                    $audioPath
                );
            }
        }
    }
}
