<?php

use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Topics\Pages\CreateTopic;
use App\Filament\Resources\Topics\Pages\EditTopic;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\ContentFixtures;

// S3-6: the panel and its upload pipeline are staff-only on the server.
// Non-staff users are refused on every panel route; unsigned upload posts
// are denied (Livewire mints upload signatures only inside panel-rendered
// components). Staff can create and publish end to end; a forged MP3 and an
// oversized cover fail validation with nothing stored; stored covers carry
// no EXIF; audio is unreachable from any public URL.

beforeEach(function () {
    $this->student = ContentFixtures::student();
    $this->staff = ContentFixtures::staff();
});

/**
 * Real fixture bytes wrapped in the framework's test-upload file (the only
 * seam Livewire's test uploader accepts). Content, validation, and disks
 * are all real — never Storage::fake, never synthetic bytes.
 */
function s3UploadedFile(string $fixture, string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        (string) file_get_contents(database_path("seeders/fixtures/{$fixture}"))
    );
}

test('non-staff users are refused on every panel route', function () {
    $topic = ContentFixtures::publishedTopic();

    $panelUrls = [
        '/admin/topics',
        '/admin/topics/create',
        "/admin/topics/{$topic->id}",
        "/admin/topics/{$topic->id}/edit",
        '/admin/lessons',
        '/admin/lessons/create',
        '/admin/categories',
    ];

    // actingAs() persists for later requests in the same test, so each
    // actor gets its own pass in escalating order (never de-escalate).
    foreach ($panelUrls as $url) {
        $this->get($url)->assertRedirect('/admin/login');
    }
    foreach ($panelUrls as $url) {
        $this->actingAs($this->student)->get($url)->assertForbidden();
    }
    foreach ($panelUrls as $url) {
        $this->actingAs($this->staff)->get($url)->assertOk();
    }
});

test('unsigned upload posts are denied for anonymous, student, and staff alike', function () {
    $url = route('livewire.upload-file');

    // No signature, no upload: Livewire refuses before any byte is stored.
    $this->post($url, [])->assertStatus(401);
    $this->actingAs($this->student)->post($url, [])->assertStatus(401);
    $this->actingAs($this->staff)->post($url, [])->assertStatus(401);
});

test('staff creates a topic with a cover and publishes a lesson end to end', function () {
    $this->actingAs($this->staff);

    Livewire::test(CreateTopic::class)
        ->set('data.slug', 's3-staff-topic')
        ->set('data.title_en', 'S3 Staff Topic')
        ->set('data.summary_public', 'Created through the panel by staff.')
        ->set('data.cover_path', s3UploadedFile('s3-cover-fixture.jpg', 'cover.jpg'))
        ->set('data.source_note', "Source: staff archive\nLicense: CC-BY 4.0\nChecked: 2026-10-01\nNotes: S3 staff e2e cover.")
        ->call('create')
        ->assertHasNoErrors();

    $topic = Topic::where('slug', 's3-staff-topic')->firstOrFail();
    expect($topic->status)->toBe('draft');

    // The parent topic publishes first: lessons require a published parent.
    Livewire::test(EditTopic::class, ['record' => $topic->getRouteKey()])
        ->callAction('publish')
        ->assertHasNoErrors();

    expect($topic->fresh()->status)->toBe('published');
    expect(Storage::disk('public')->exists($topic->cover_path))->toBeTrue();
    expect(pathinfo($topic->cover_path, PATHINFO_DIRNAME))->toBe('covers');

    Livewire::test(CreateLesson::class)
        ->set('data.topic_id', $topic->id)
        ->set('data.level', 'A1')
        ->set('data.title_en', 'S3 Staff Lesson')
        ->set('data.body_en', "First paragraph.\n\nSecond paragraph.")
        ->set('data.audio_path', s3UploadedFile('s1-a2-tone-fixture.mp3', 'audio.mp3'))
        ->set('data.duration_seconds', 20)
        ->set('data.estimated_minutes', 3)
        ->set('data.reviewed_by', $this->staff->id)
        ->set('data.reviewed_at', now()->format('Y-m-d H:i:s'))
        ->call('create')
        ->assertHasNoErrors();

    $lesson = Lesson::where('topic_id', $topic->id)->firstOrFail();
    expect($lesson->status)->toBe('draft');

    Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
        ->callAction('publish')
        ->assertHasNoErrors();

    expect($lesson->fresh()->status)->toBe('published');

    // The stored cover carries no EXIF after the pipeline re-encoded it.
    $coverAbsolute = Storage::disk('public')->path($topic->fresh()->cover_path);
    $exif = @exif_read_data($coverAbsolute);
    expect($exif['Orientation'] ?? null)->toBeNull()
        ->and($exif['EXIF'] ?? null)->toBeNull();
});

test('an MP3 with a wrong signature fails lesson validation and stores nothing', function () {
    $this->actingAs($this->staff);
    $topic = ContentFixtures::publishedTopic();

    $forged = UploadedFile::fake()->createWithContent(
        'audio.mp3',
        'this is plain text pretending to be an mp3'
    );
    $count = Lesson::count();

    Livewire::test(CreateLesson::class)
        ->set('data.topic_id', $topic->id)
        ->set('data.level', 'A1')
        ->set('data.title_en', 'S3 Forged Audio')
        ->set('data.body_en', "Body.\n\nMore body.")
        ->set('data.audio_path', $forged)
        ->set('data.duration_seconds', 20)
        ->set('data.estimated_minutes', 3)
        ->call('create')
        ->assertHasErrors(['data.audio_path']);

    expect(Lesson::count())->toBe($count);
});

test('an oversized cover fails topic validation and stores nothing', function () {
    $this->actingAs($this->staff);

    // Valid JPEG bytes padded past the 2 MB limit: decodable, but too big.
    $big = UploadedFile::fake()->createWithContent(
        'cover.jpg',
        file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg')).str_repeat("\0", 2 * 1024 * 1024)
    );
    $count = Topic::count();

    Livewire::test(CreateTopic::class)
        ->set('data.slug', 's3-too-big')
        ->set('data.title_en', 'S3 Too Big')
        ->set('data.summary_public', 'Oversized cover attempt.')
        ->set('data.cover_path', $big)
        ->call('create')
        ->assertHasErrors(['data.cover_path']);

    expect(Topic::count())->toBe($count);
});

test('lesson audio is not reachable from any public URL', function () {
    $lesson = ContentFixtures::validDraftLesson();

    expect(Storage::disk('public')->exists($lesson->audio_path))->toBeFalse();

    // The private local disk answers unsigned file requests with 403 in
    // non-production (404 in production); either way the bytes never leave.
    // The only audio path is the policy-checked media route (S1).
    $this->get('/storage/'.$lesson->audio_path)->assertForbidden();
});
