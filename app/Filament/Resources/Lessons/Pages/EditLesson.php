<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Concerns\HasContentTransitions;
use App\Filament\Resources\Lessons\LessonResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLesson extends EditRecord
{
    use HasContentTransitions;

    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ...self::lessonTransitionActions(),
            // Hidden for ever-published lessons; the model guard refuses the
            // delete server-side regardless of the button state.
            DeleteAction::make()
                ->hidden(fn ($record) => $record !== null && ($record->status !== 'draft' || $record->published_at !== null)),
        ];
    }
}
