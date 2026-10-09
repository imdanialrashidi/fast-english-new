<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * S0: one real persisted mutation — the user's display name.
 * Server-validated; nothing is stored on validation failure and no
 * success flag is raised. No secrets leave the server in hydration.
 */
class UpdateDisplayName extends Component
{
    public string $name = '';

    public bool $saved = false;

    public function mount(): void
    {
        $this->name = (string) Auth::user()->name;
    }

    public function save(): void
    {
        $this->saved = false;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Auth::user()->forceFill(['name' => $this->name])->save();

        $this->saved = true;
    }

    public function render(): View
    {
        return view('livewire.update-display-name');
    }
}
