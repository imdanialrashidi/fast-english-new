<?php

namespace App\Console\Commands;

use App\Actions\PublishLesson;
use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * S3 staff CLI: content:transition {publish|archive|to-draft} {topic|lesson} {id}.
 *
 * Calls the same App\Actions\PublishLesson methods the Filament resources
 * call — the transition logic lives in exactly one place. Refusals print
 * the specific per-field errors and exit non-zero with nothing changed.
 */
class ContentTransition extends Command
{
    protected $signature = 'content:transition
        {action : publish|archive|to-draft}
        {type : topic|lesson}
        {id : ID of the topic or lesson}';

    protected $description = 'Publish, archive, or return content to draft through the shared publish action.';

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        $type = (string) $this->argument('type');
        $id = (int) $this->argument('id');

        if (! in_array($action, ['publish', 'archive', 'to-draft'], true)) {
            $this->error("Unknown action [{$action}]. Use publish, archive, or to-draft.");

            return 1;
        }

        $record = $type === 'topic'
            ? Topic::query()->find($id)
            : ($type === 'lesson' ? Lesson::query()->find($id) : null);

        if ($record === null) {
            $this->error("Unknown type [{$type}] or missing record [{$id}].");

            return 1;
        }

        try {
            $result = match (true) {
                $record instanceof Topic && $action === 'publish' => PublishLesson::publishTopic($record),
                $record instanceof Topic && $action === 'archive' => PublishLesson::archiveTopic($record),
                $record instanceof Topic => PublishLesson::returnTopicToDraft($record),
                $record instanceof Lesson && $action === 'publish' => PublishLesson::publish($record),
                $record instanceof Lesson && $action === 'archive' => PublishLesson::archive($record),
                default => PublishLesson::returnToDraft($record),
            };
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->error("{$field}: {$message}");
                }
            }

            return 1;
        }

        $this->info(ucfirst($type)." [{$result->id}] is now {$result->status}.");

        return 0;
    }
}
