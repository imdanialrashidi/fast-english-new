<div class="fe-card">
    <div class="fe-card-body">
        <h2 class="fe-section-title"><x-fe-icon name="user" size="20" />نام نمایشی</h2>
        <form wire:submit="save" class="fe-auth-form">
            <div class="fe-field">
                <label for="display-name"><x-fe-icon name="user" size="16" />نام</label>
                <input id="display-name" type="text" wire:model="name" autocomplete="name">
                @error('name')
                    <p class="fe-field-error" role="alert"><x-fe-icon name="circle-alert" size="16" />{{ $message }}</p>
                @enderror
            </div>
            <div class="fe-field fe-field-actions">
                <button class="fe-btn" type="submit"><x-fe-icon name="check" size="20" />ذخیره نام</button>
            </div>
            @if ($saved)
                <p class="fe-success-note" role="status"><x-fe-icon name="circle-check" size="20" />ذخیره شد.</p>
            @endif
        </form>
    </div>
</div>
