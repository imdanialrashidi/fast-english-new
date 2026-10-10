@extends('layouts.learner')

@section('title', 'ذخیره‌شده‌ها')

@section('content')
<div class="fe-measure fe-library">
    <div class="fe-library-head">
        <h1>ذخیره‌شده‌ها</h1>
        <p class="fe-muted">مطلب‌هایی که برای بعد نگه داشته‌ای.</p>
    </div>

    @if ($topics->isEmpty())
        <div class="fe-empty" role="status">
            <span class="fe-empty-icon"><x-fe-icon name="bookmark" size="24" /></span>
            <p>هنوز مطلبی ذخیره نکرده‌اید.</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ route('app.home') }}" wire:navigate><x-fe-icon name="compass" size="20" />دیدن مطالب</a></p>
        </div>
    @else
        <ul class="fe-cards">
            @foreach ($topics as $topic)
                <li>
                    @include('library._card', ['topic' => $topic])
                </li>
            @endforeach
        </ul>

        @if ($topics->hasPages())
            <nav class="fe-pagination" aria-label="صفحه‌های ذخیره‌شده‌ها">
                {{ $topics->links('pagination.fe') }}
            </nav>
        @endif
    @endif
</div>
@endsection
