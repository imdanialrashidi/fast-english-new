<div>
    <form wire:submit="save">
        <label for="display-name">Display name</label>
        <input id="display-name" type="text" wire:model="name" autocomplete="name">

        @error('name')
            <p role="alert">{{ $message }}</p>
        @enderror

        <button type="submit">Save</button>

        @if ($saved)
            <p role="status">Saved.</p>
        @endif
    </form>
</div>
