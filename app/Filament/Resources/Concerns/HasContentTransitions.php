<?php

namespace App\Filament\Resources\Concerns;

use App\Actions\PublishLesson;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * S3: header actions that wire the Filament resources to the single shared
 * transition owner (App\Actions\PublishLesson). No transition logic lives
 * here — these actions only call it and report the outcome. The CLI calls
 * the same action methods.
 */
trait HasContentTransitions
{
    /**
     * @param  callable(object): object  $publish
     * @param  callable(object): object  $archive
     * @param  callable(object): object  $toDraft
     * @return list<Action>
     */
    protected static function transitionActions(callable $publish, callable $archive, callable $toDraft): array
    {
        $report = function (Action $action, callable $run, string $verb): void {
            try {
                $run();
                Notification::make()->success()->title($verb.' done.')->send();
            } catch (ValidationException $e) {
                $detail = collect($e->errors())->flatten()->implode(' ');
                Notification::make()->danger()->title("Cannot {$verb}.")->body($detail)->send();
                $action->halt();
            }
        };

        return [
            Action::make('publish')
                ->label('Publish')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record !== null && $record->status === 'draft')
                ->action(function (Action $action, $record) use ($publish, $report) {
                    $report($action, fn () => $publish($record), 'publish');
                }),
            Action::make('archive')
                ->label('Archive')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record !== null && in_array($record->status, ['draft', 'published'], true))
                ->action(function (Action $action, $record) use ($archive, $report) {
                    $report($action, fn () => $archive($record), 'archive');
                }),
            Action::make('toDraft')
                ->label('Return to draft')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record !== null && $record->status === 'archived')
                ->action(function (Action $action, $record) use ($toDraft, $report) {
                    $report($action, fn () => $toDraft($record), 'return to draft');
                }),
        ];
    }

    /** @return list<Action> */
    protected static function topicTransitionActions(): array
    {
        return self::transitionActions(
            fn ($topic) => PublishLesson::publishTopic($topic),
            fn ($topic) => PublishLesson::archiveTopic($topic),
            fn ($topic) => PublishLesson::returnTopicToDraft($topic),
        );
    }

    /** @return list<Action> */
    protected static function lessonTransitionActions(): array
    {
        return self::transitionActions(
            fn ($lesson) => PublishLesson::publish($lesson),
            fn ($lesson) => PublishLesson::archive($lesson),
            fn ($lesson) => PublishLesson::returnToDraft($lesson),
        );
    }
}
