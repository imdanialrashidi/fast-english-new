@extends('layouts.learner')

@section('title', 'سطح آماده نیست')

@section('content')
<div class="fe-measure">
    <h1>این سطح هنوز آماده نیست</h1>
    <p class="fe-muted">سطح درخواستی ({{ $requestedLevel }}) برای «{{ $topic->title_en }}» منتشر نشده است. سطح دیگری جایگزین نشد؛ یکی از سطح‌های آماده را انتخاب کنید:</p>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        @foreach ($availableLevels as $level)
            <a class="fe-level-link"
               href="{{ route('reader.show', ['topic' => $topic->slug, 'level' => $level]) }}">{{ $level }}</a>
        @endforeach
    </div>
</div>
@endsection
