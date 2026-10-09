@extends('layouts.learner')

@section('title', 'ذخیره‌شده‌ها')

@section('content')
<div class="fe-measure fe-library">
    <h1>ذخیره‌شده‌ها</h1>

    @if ($topics->isEmpty())
        <div class="fe-empty">
            <p>هنوز مطلبی ذخیره نکرده‌اید.</p>
            <p><a class="fe-btn" href="{{ route('app.home') }}" wire:navigate>دیدن مطالب</a></p>
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
                {{ $topics->links() }}
            </nav>
        @endif
    @endif
</div>
@endsection
