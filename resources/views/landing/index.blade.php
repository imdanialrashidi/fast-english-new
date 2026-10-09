@extends('layouts.public')

@section('title', 'Fast English — با داستان‌های واقعی انگلیسی را سریع‌تر یاد بگیر')
@section('meta_description', 'Fast English — متن‌های کوتاه انگلیسی در سطح مناسب با صوت همان نسخه؛ بخوان، گوش بده، واژه بساز')
@section('canonical', config('app.url').'/')

@section('content')
<div class="fe-hero-editorial">
    <div>
        <p class="fe-draft-badge" role="note">پیش‌نویس — DRAFT — نیازمند بازبینی صاحب محصول</p>
        <h1>با داستان‌های واقعی انگلیسی را سریع‌تر یاد بگیر</h1>
        <p class="fe-muted">مطالب جذاب، تمرین‌های هدفمند و تجربه‌ای متفاوت از یادگیری زبان. یک مطلب کوتاه در سطح خودت انتخاب کن، متن انگلیسی را بخوان و همان نسخه را بشنو.</p>
        <p style="display:flex;gap:.5rem;flex-wrap:wrap">
            @if ($sample !== null)
                <a class="fe-btn fe-btn-primary" href="{{ route('reader.show', $sample->topic) }}?level={{ $sample->level }}">شروع کنید ←</a>
            @else
                <a class="fe-btn fe-btn-primary" href="{{ route('app.home') }}">شروع کنید ←</a>
            @endif
            <a class="fe-btn" href="{{ route('app.home') }}">دیدن مطالب</a>
        </p>
        @if ($sample === null)
            <p class="fe-muted" role="note">نمونه عمومی هنوز آماده نیست.</p>
        @endif
    </div>
    @if ($sample !== null && $sample->topic->cover_path)
        <span class="fe-hero-media"><img src="/storage/{{ $sample->topic->cover_path }}" alt="" loading="eager" fetchpriority="high"></span>
    @elseif (($featured[0] ?? null) && $featured[0]->cover_path)
        <span class="fe-hero-media"><img src="/storage/{{ $featured[0]->cover_path }}" alt="" loading="eager" fetchpriority="high"></span>
    @else
        <span class="fe-hero-media" aria-hidden="true"><span class="fe-card-cover fe-card-cover-empty"><span lang="en" dir="ltr">Fast English</span></span></span>
    @endif
</div>

<section class="fe-card" aria-label="چرا خواندن و شنیدن">
    <div class="fe-card-body">
        <h2 class="fe-section-title">خواندن و شنیدن هوشمند</h2>
        <p class="fe-muted">همگام با صوت، متن را بخوانید و کلمات را برجسته ببینید. هر جمله را جداگانه پخش کنید، سرعت را تنظیم کنید و واژه‌های مهم را به دفترچه خود اضافه کنید.</p>
    </div>
</section>

@if ($sample !== null)
<section aria-label="نمونه واقعی">
    <h2 class="fe-section-title">یک نمونه واقعی، بدون ثبت‌نام</h2>
    <div class="fe-card">
        <a class="fe-card-link" href="{{ route('reader.show', $sample->topic) }}?level={{ $sample->level }}" aria-label="{{ $sample->title_en }}">
            <span class="fe-card-media">
                @if ($sample->topic->cover_path)
                    <img class="fe-card-cover" src="/storage/{{ $sample->topic->cover_path }}" alt="" loading="lazy">
                @else
                    <span class="fe-card-cover fe-card-cover-empty" aria-hidden="true"><span lang="en" dir="ltr">FE</span></span>
                @endif
            </span>
            <div class="fe-card-body">
                <h3 class="fe-card-title" lang="en" dir="ltr">{{ $sample->title_en }}</h3>
                <p class="fe-card-levels">سطح {{ $sample->level }} · حدود {{ $sample->duration_seconds }} ثانیه صوت</p>
            </div>
        </a>
    </div>
</section>
@endif

@if ($featured->isNotEmpty())
<section aria-label="منتخبی از مطالب">
    <h2 class="fe-section-title">منتخبی از مطالب</h2>
    <ul class="fe-cards">
        @foreach ($featured as $topic)
            <li>
                @include('library._card', ['topic' => $topic])
            </li>
        @endforeach
    </ul>
    <p><a class="fe-btn" href="{{ route('app.home') }}">مشاهده همه مطالب</a></p>
</section>
@endif

<section class="fe-card" aria-label="روش کار">
    <div class="fe-card-body">
        <h2 class="fe-section-title">مسیر یادگیری روزانه</h2>
        <ol class="fe-steps">
            <li>مطلب و سطح موردنظر را انتخاب کن.</li>
            <li>به صوت گوش بده و متن را دنبال کن.</li>
            <li>واژه‌های تازه را با روش SRS مرور کن.</li>
        </ol>
    </div>
</section>

<section class="fe-card" aria-label="مزیت‌ها">
    <div class="fe-card-body">
        <h2 class="fe-section-title">چرا Fast English</h2>
        <p class="fe-muted">متن و صوت همیشه از یک نسخه‌اند؛ موقعیت مطالعه‌ات ذخیره می‌شود؛ دفترچه واژه‌ها با مرور زمان‌بندی‌شده همراه است؛ و برنامه روزانه‌ات فقط از پیشرفت واقعی تو ساخته می‌شود.</p>
    </div>
</section>

<section aria-label="پلن‌ها">
    <h2 class="fe-section-title">پلن‌ها</h2>
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
        <h2 class="fe-section-title">نصب و دانلود</h2>
        <p class="fe-muted">نسخه وب روی مرورگر موبایل کار می‌کند. فایل نصب اندروید هم از همین‌جا اعلام می‌شود.</p>
        <p>
            <a class="fe-btn" href="{{ route('download') }}">صفحه دانلود</a>
        </p>
    </div>
</section>

<section class="fe-card" aria-label="پرسش‌های پرتکرار">
    <div class="fe-card-body">
        <h2 class="fe-section-title">پرسش‌های پرتکرار</h2>
        <ul class="fe-steps">
            <li>آیا بدون اشتراک می‌توانم امتحان کنم؟ بله — یک نمونه واقعی همیشه رایگان است.</li>
            <li>سطح‌ها یعنی چه؟ شش سطح A1 تا C2؛ هر مطلب نسخه خودش را دارد.</li>
            <li>واژه‌ها چطور مرور می‌شوند؟ هر واژه ذخیره‌شده سر موعد با کارت مرور برمی‌گردد.</li>
        </ul>
        <p>
            <a class="fe-btn" href="{{ route('trust.faq') }}">مشاهده همه پرسش‌ها</a>
        </p>
    </div>
</section>
@endsection
