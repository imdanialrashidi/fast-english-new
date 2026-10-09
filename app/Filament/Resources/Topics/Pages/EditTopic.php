<?php

namespace App\Filament\Resources\Topics\Pages;

use App\Filament\Resources\Concerns\HasContentTransitions;
use App\Filament\Resources\Topics\TopicResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTopic extends EditRecord
{
    use HasContentTransitions;

    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ...self::topicTransitionActions(),
            // Hidden for ever-published topics; the model guard refuses the
            // delete server-side regardless of the button state.
            DeleteAction::make()
                ->hidden(fn ($record) => $record !== null && ($record->status !== 'draft' || $record->published_at !== null)),
        ];
    }
}
