@extends('layouts.public')

@section('title', 'Fast English — خواندن و شنیدن انگلیسی در سطح مناسب')
@section('meta_description', 'Fast English — متن‌های کوتاه انگلیسی در سطح مناسب با صوت همان نسخه')
@section('canonical', config('app.url').'/')

@section('content')
<div class="fe-hero">
    <p class="fe-draft-badge" role="note">پیش‌نویس — DRAFT — نیازمند بازبینی صاحب محصول</p>
    <h1>انگلیسی را با خواندن و شنیدن متن‌های کوتاه تمرین کن</h1>
    <p class="fe-muted">یک مطلب کوتاه در سطح خودت انتخاب کن، متن انگلیسی را بخوان و همان نسخه را بشنو.</p>
    @if ($sample !== null)
        <p>
            <a class="fe-btn fe-btn-primary"
               href="{{ route('reader.show', $sample->topic) }}?level={{ $sample->level }}">مشاهده نمونه واقعی ({{ $sample->level }})</a>
        </p>
    @else
        <p class="fe-muted" role="note">نمونه عمومی هنوز آماده نیست.</p>
    @endif
</div>

<section class="fe-card" aria-label="روش کار">
    <div class="fe-card-body">
        <h2>روش کار</h2>
        <ol class="fe-steps">
            <li>نمونه واقعی را بدون حساب ببین.</li>
            <li>مطلب و سطح موردنظر را انتخاب کن.</li>
            <li>متن را بخوان و صوت همان نسخه را بشنو.</li>
        </ol>
    </div>
</section>

<section aria-label="پلن‌ها">
    <h2>پلن‌ها</h2>
    @if (! $salesOn)
        <div class="fe-empty">
            <p><strong>در دست آماده‌سازی</strong></p>
            <p class="fe-muted">فروش هنوز فعال نشده است. پلن‌ها پس از اعلام رسمی قابل خرید خواهند بود.</p>
        </div>
    @elseif ($plans->isEmpty())
        <div class="fe-empty">
            <p class="fe-muted">در حال حاضر پلن فعالی وجود ندارد.</p>
        </div>
    @else
        <ul class="fe-cards">
            @foreach ($plans as $plan)
                <li class="fe-card">
                    <div class="fe-card-body">
                        <h3 class="fe-card-title" lang="fa" dir="rtl">{{ $plan->name_fa }}</h3>
                        <p class="fe-muted">{{ $plan->duration_days }} روز دسترسی پس از تأیید</p>
                        <p>
                            <a class="fe-btn" href="{{ route('subscribe.index') }}">مشاهده پلن‌ها و خرید</a>
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>

<section class="fe-card" aria-label="نصب و دانلود">
    <div class="fe-card-body">
        <h2>نصب و دانلود</h2>
        <p class="fe-muted">نسخه وب روی مرورگر موبایل کار می‌کند. فایل نصب اندروید هم از همین‌جا اعلام می‌شود.</p>
        <p>
            <a class="fe-btn" href="{{ route('download') }}">صفحه دانلود</a>
        </p>
    </div>
</section>

<section class="fe-card" aria-label="پرسش‌های پرتکرار">
    <div class="fe-card-body">
        <h2>پرسش‌های پرتکرار</h2>
        <p class="fe-muted">پاسخ کوتاه پرسش‌های رایج درباره سطح‌ها، اشتراک و پشتیبانی.</p>
        <p>
            <a class="fe-btn" href="{{ route('trust.faq') }}">مشاهده پرسش‌ها</a>
        </p>
    </div>
</section>
@endsection
