@extends('layouts.learner')

@section('title', 'سطح آماده نیست')

@section('content')
<div class="fe-measure">
    <div class="fe-empty" role="status">
        <span class="fe-empty-icon"><x-fe-icon name="book-open" size="24" /></span>
        <h1>این سطح هنوز آماده نیست</h1>
        <p class="fe-muted">سطح درخواستی ({{ $requestedLevel }}) برای «{{ $topic->title_en }}» منتشر نشده است. سطح دیگری جایگزین نشد؛ یکی از سطح‌های آماده را انتخاب کنید:</p>
        <div class="fe-pills">
            @foreach ($availableLevels as $level)
                <a class="fe-level-link"
                   href="{{ route('reader.show', ['topic' => $topic->slug, 'level' => $level]) }}"><span lang="en" dir="ltr">{{ $level }}</span></a>
            @endforeach
        </div>
    </div>
</div>
@endsection
