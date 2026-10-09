<?php

namespace App\Filament\Resources\Topics\Pages;

use App\Filament\Resources\Concerns\HasContentTransitions;
use App\Filament\Resources\Topics\TopicResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTopic extends ViewRecord
{
    use HasContentTransitions;

    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            ...self::topicTransitionActions(),
        ];
    }
}
