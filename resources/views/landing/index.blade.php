@extends('layouts.public')

@section('title', 'Fast English — انگلیسی را با داستان کوتاه و صوت هماهنگ یاد بگیر')
@section('meta_description', 'Fast English — متن‌های کوتاه انگلیسی در سطح A1 تا C2 با صوت همان نسخه؛ جمله‌به‌جمله بخوان و بشنو، سرعت را تنظیم کن، واژه بساز')
@section('canonical', config('app.url').'/')

@section('content')
<div class="fe-landing">
    {{-- 1. Hero: promise + staged real reader. Primary opens /sample (no login), secondary to /app library. --}}
    <section class="fe-landing-hero" aria-label="معرفی Fast English">
        <div class="fe-reveal" style="--fe-reveal-delay: 0ms">
            <p class="fe-eyebrow"><x-fe-icon name="headphones" size="16" />Fast English — خواندن و شنیدن هم‌زمان</p>
            <h1>انگلیسی را با داستان‌های کوتاه و صوت هماهنگ یاد بگیر</h1>
            <p class="fe-lede">هر مطلب یک متن انگلیسی در سطح توست با صوت همان نسخه.</p>
            <p class="fe-cta-row">
                <a class="fe-btn fe-btn-primary" href="{{ route('sample') }}"><x-fe-icon name="play" size="20" />شروع با نمونه رایگان</a>
                <a class="fe-btn" href="{{ route('app.home') }}"><x-fe-icon name="compass" size="20" />دیدن مطالب</a>
            </p>
            <p class="fe-cta-note">نمونه بدون ثبت‌نام باز می‌شود.</p>
        </div>

        <div class="fe-preview fe-reveal" style="--fe-reveal-delay: 90ms" aria-label="پیش‌نمایش روش یادگیری با نمونه واقعی">
            <div class="fe-preview-head">
                @if ($sample !== null && $sample->topic->cover_path)
                    <img class="fe-preview-cover" src="/storage/{{ $sample->topic->cover_path }}" alt="" width="52" height="52" loading="eager" fetchpriority="high">
                @else
                    <img class="fe-preview-cover" src="/icons/icon-192.png" alt="Fast English" width="52" height="52" loading="eager" fetchpriority="high">
                @endif
                <div>
                    @if ($sample !== null)
                        <p class="fe-preview-title" lang="en" dir="ltr">{{ $sample->title_en }}</p>
                        <p class="fe-muted" style="margin: 0; font-size: 0.8125rem;">سطح <span lang="en" dir="ltr">{{ $sample->level }}</span> · خواندن هم‌گام با صوت</p>
                    @else
                        <p class="fe-preview-title" lang="en" dir="ltr">Fast English</p>
                        <p class="fe-muted" style="margin: 0; font-size: 0.8125rem;">نمونه عمومی به‌زودی آماده می‌شود</p>
                    @endif
                </div>
            </div>
            @if (! empty($sampleSentences))
                <ol class="fe-preview-lines" lang="en" dir="ltr" aria-label="سه جمله از نمونه واقعی">
                    @foreach ($sampleSentences as $index => $line)
                        <li @if($index === 1) aria-current="true" @else aria-current="false" @endif>{{ $line }}</li>
                    @endforeach
                </ol>
            @endif
            <div class="fe-preview-bar">
                <a class="fe-preview-play" href="{{ route('sample') }}" aria-label="@if($sample !== null)شنیدن نمونه واقعی: {{ $sample->title_en }}@else باز کردن نمونه واقعی@endif"><x-fe-icon name="play" size="20" /></a>
                <span class="fe-preview-track" aria-hidden="true"><span></span></span>
                <span class="fe-preview-speed" dir="ltr" title="سرعت پخش از ۰٫۷۵ تا ۱٫۵ برابر قابل تنظیم است">1×</span>
            </div>
            <p class="fe-cta-note">پیش‌نمایش روش کار با متن واقعی نمونه — برای شنیدن، نمونه را باز کن.</p>
        </div>
    </section>

    {{-- 2. How it works: 3 steps, direct Persian, no claims. --}}
    <section class="fe-landing-section fe-reveal" style="--fe-reveal-delay: 0ms" aria-label="روش کار">
        <h2><x-fe-icon name="compass" size="20" />روش کار در سه قدم</h2>
        <ol class="fe-how">
            <li>
                <div>
                    <h3>مطلب و سطحت را انتخاب کن</h3>
                    <p>هر موضوع نسخه‌های خودش را از A1 تا C2 دارد؛ همان سطحی را بخوان که برایت مناسب است.</p>
                </div>
            </li>
            <li>
                <div>
                    <h3>بخوان و هم‌زمان بشنو</h3>
                    <p>هر جمله با صوت همان نسخه هماهنگ است؛ روی جمله بزن تا همان‌جا پخش شود و سرعت را از ۰٫۷۵ تا ۱٫۵ برابر تنظیم کن.</p>
                </div>
            </li>
            <li>
                <div>
                    <h3>واژه بساز و سر موعد مرور کن</h3>
                    <p>واژه‌های هر درس را در دفترچه‌ات ذخیره کن؛ کارت‌ها سر موعد برمی‌گردند و برنامه امروزت فقط از پیشرفت واقعی تو ساخته می‌شود.</p>
                </div>
            </li>
        </ol>
    </section>

    {{-- 3. Real sample: deep link to the published lesson, never drafts. --}}
    @if ($sample !== null)
        <section class="fe-landing-section fe-reveal" style="--fe-reveal-delay: 0ms" aria-label="نمونه واقعی">
            <h2><x-fe-icon name="book-open" size="20" />یک نمونه واقعی، بدون ثبت‌نام</h2>
            <div class="fe-card">
                <a class="fe-card-link" href="{{ route('reader.show', $sample->topic) }}?level={{ $sample->level }}" aria-label="{{ $sample->title_en }}">
                    <span class="fe-card-media">
                        @if ($sample->topic->cover_path)
                            <img class="fe-card-cover" src="/storage/{{ $sample->topic->cover_path }}" alt="" loading="lazy">
                        @else
                            <span class="fe-card-cover fe-card-cover-empty" aria-hidden="true"><img class="fe-cover-logo" src="/icons/icon-192.png" alt="" width="56" height="56" loading="lazy"></span>
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

    {{-- 4. Featured: newest published topics, one row per topic, real covers. --}}
    @if ($featured->isNotEmpty())
        <section class="fe-landing-section fe-reveal" style="--fe-reveal-delay: 0ms" aria-label="تازه‌ترین مطالب">
            <h2><x-fe-icon name="file-text" size="20" />تازه‌ترین مطالب</h2>
            <ul class="fe-cards">
                @foreach ($featured as $topic)
                    <li>
                        @include('library._card', ['topic' => $topic])
                    </li>
                @endforeach
            </ul>
            <p class="fe-cta-row"><a class="fe-btn" href="{{ route('app.home') }}">مشاهده همه مطالب<x-fe-icon name="arrow-left" size="20" /></a></p>
        </section>
    @endif

    {{-- 5. Workflow: the daily path, built only from persisted progress. No counts, no streaks. --}}
    <section class="fe-landing-section fe-reveal" style="--fe-reveal-delay: 0ms" aria-label="مسیر روزانه">
        <h2><x-fe-icon name="clock" size="20" />مسیر روزانه‌ات از پیشرفت واقعی ساخته می‌شود</h2>
        <p class="fe-lede">هدف ۵، ۱۰ یا ۱۵ دقیقه را خودت انتخاب می‌کنی. برنامه هر روز از درس‌های نیمه‌تمام، واژه‌های موعد مرور و سطح خودت ساخته می‌شود.</p>
        <ul class="fe-divider-list">
            <li class="fe-workflow-row">
                <span class="fe-workflow-icon" aria-hidden="true"><x-fe-icon name="book-open" size="20" /></span>
                <div>
                    <h3>ادامه درس نیمه‌تمام</h3>
                    <p>از همان جمله‌ای که مانده‌ای ادامه می‌دهی؛ موقعیت ذخیره‌شده پس از ورود دوباره برمی‌گردد.</p>
                </div>
            </li>
            <li class="fe-workflow-row">
                <span class="fe-workflow-icon" aria-hidden="true"><x-fe-icon name="headphones" size="20" /></span>
                <div>
                    <h3>شنیدن همان نسخه</h3>
                    <p>متن و صوت همیشه از یک نسخه‌اند؛ تغییر سطح، صوت قبلی را متوقف می‌کند.</p>
                </div>
            </li>
            <li class="fe-workflow-row">
                <span class="fe-workflow-icon" aria-hidden="true"><x-fe-icon name="languages" size="20" /></span>
                <div>
                    <h3>مرور واژه‌های موعد</h3>
                    <p>هر واژه با دوباره، سخت، خوب یا آسان زمان‌بندی می‌شود و سر موعد برمی‌گردد.</p>
                </div>
            </li>
            <li class="fe-workflow-row">
                <span class="fe-workflow-icon" aria-hidden="true"><x-fe-icon name="compass" size="20" /></span>
                <div>
                    <h3>درس بعدی در سطح خودت</h3>
                    <p>سطح، ویژگی محتواست نه اجازه؛ همه سطح‌های منتشرشده برای مشترک فعال باز است.</p>
                </div>
            </li>
        </ul>
    </section>

    {{-- 6. Benefits: calm divider rows, real product properties, no fabrication. --}}
    <section class="fe-landing-section fe-reveal" style="--fe-reveal-delay: 0ms" aria-label="چرا Fast English">
        <h2><x-fe-icon name="circle-check" size="20" />برای خواندن و شنیدن واقعی ساخته شده</h2>
        <ul class="fe-divider-list">
            <li class="fe-workflow-row">
                <span class="fe-workflow-icon" aria-hidden="true"><x-fe-icon name="file-text" size="20" /></span>
                <div>
                    <h3>متن کوتاه در سطح خودت</h3>
                    <p>هر مطلب نسخه خودش را در هر سطح دارد؛ اگر سطحی آماده نباشد، همان پیام را می‌بینی و جایگزین پنهان نمی‌شود.</p>
                </div>
            </li>
            <li class="fe-workflow-row">
                <span class="fe-workflow-icon" aria-hidden="true"><x-fe-icon name="play" size="20" /></span>
                <div>
                    <h3>جمله‌به‌جمله با صوت واقعی</h3>
                    <p>جمله در حال پخش مشخص است و با انتخاب جمله، همان بازه پخش می‌شود. اگر زمان‌بندی آماده نباشد، پخش عادی فعال است.</p>
                </div>
            </li>
            <li class="fe-workflow-row">
                <span class="fe-workflow-icon" aria-hidden="true"><x-fe-icon name="bookmark" size="20" /></span>
                <div>
                    <h3>ذخیره و ادامه بدون گم شدن</h3>
                    <p>موقعیت هر نسخه جدا ذخیره می‌شود و مطلب‌های ذخیره‌شده در صفحه خودت می‌مانند.</p>
                </div>
            </li>
        </ul>
    </section>

    {{-- 7. Plans: real DB names + durations. 299k is the owner-approved single package (limited-time label only). Fixture amounts never appear here. --}}
    <section class="fe-landing-section fe-reveal" style="--fe-reveal-delay: 0ms" aria-label="پلن‌ها">
        <h2><x-fe-icon name="card" size="20" />پلن‌ها</h2>
        @if (! $salesOn)
            <div class="fe-empty">
                <p><strong>در دست آماده‌سازی</strong></p>
                <p class="fe-muted">فروش هنوز فعال نشده است. بسته واحد {{ \App\Support\Toman::format(299000) }} (پیشنهاد محدود) پس از اعلام رسمی قابل خرید خواهد بود؛ نمونه رایگان همیشه در دسترس است.</p>
            </div>
        @elseif ($plans->isEmpty())
            <div class="fe-empty">
                <p class="fe-muted">در حال حاضر پلن فعالی وجود ندارد.</p>
            </div>
        @else
            <p class="fe-lede">بسته واحد {{ \App\Support\Toman::format(299000) }} <span class="fe-chip">پیشنهاد محدود</span></p>
            <ul class="fe-cards">
                @foreach ($plans as $plan)
                    <li class="fe-card">
                        <div class="fe-card-body">
                            <h3 class="fe-card-title" lang="fa" dir="rtl">{{ $plan->name_fa }}</h3>
                            <p class="fe-muted">{{ $plan->duration_days }} روز دسترسی پس از تأیید</p>
                            <p class="fe-cta-row">
                                <a class="fe-btn fe-btn-primary" href="{{ route('subscribe.index') }}">مشاهده پلن‌ها و خرید</a>
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- 8. FAQ: 3 direct answers + link to the full page. --}}
    <section class="fe-landing-section" aria-label="پرسش‌های پرتکرار">
        <h2><x-fe-icon name="info" size="20" />پرسش‌های پرتکرار</h2>
        <ul class="fe-how">
            <li>
                <div>
                    <h3>بدون اشتراک می‌توانم امتحان کنم؟</h3>
                    <p>بله — نمونه واقعی همیشه رایگان است و بدون ثبت‌نام باز می‌شود.</p>
                </div>
            </li>
            <li>
                <div>
                    <h3>سطح‌ها یعنی چه؟</h3>
                    <p>شش سطح از A1 تا C2؛ هر مطلب نسخه خودش را در هر سطح دارد و متن و صوت همیشه از یک نسخه‌اند.</p>
                </div>
            </li>
            <li>
                <div>
                    <h3>واژه‌ها چطور مرور می‌شوند؟</h3>
                    <p>هر واژه‌ای که ذخیره کنی سر موعد با کارت مرور برمی‌گردد؛ دوباره، سخت، خوب یا آسان را خودت ثبت می‌کنی.</p>
                </div>
            </li>
        </ul>
        <p class="fe-cta-row"><a class="fe-btn" href="{{ route('trust.faq') }}">مشاهده همه پرسش‌ها</a></p>
    </section>

    {{-- 9. Final CTA: sample first, account second. --}}
    <section class="fe-final-cta" aria-label="شروع">
        <h2>با همان نمونه رایگان شروع کن</h2>
        <p class="fe-lede">یک داستان کوتاه در سطح خودت بخوان و بشنو؛ اگر دوست داشتی، حساب بساز و ادامه بده.</p>
        <p class="fe-cta-row">
            <a class="fe-btn fe-btn-primary" href="{{ route('sample') }}"><x-fe-icon name="play" size="20" />شروع با نمونه رایگان</a>
            <a class="fe-btn" href="{{ route('register') }}"><x-fe-icon name="user" size="20" />ساخت حساب</a>
        </p>
    </section>

    {{-- 10. Install: web + download page, last per the accepted order. --}}
    <section class="fe-landing-section" aria-label="نصب و دانلود">
        <h2><x-fe-icon name="file-text" size="20" />نصب و دانلود</h2>
        <p class="fe-lede">نسخه وب روی مرورگر موبایل کار می‌کند و بدون نصب اضافه باز می‌شود. فایل نصب اندروید هم از صفحه دانلود اعلام می‌شود.</p>
        <p class="fe-cta-row">
            <a class="fe-btn" href="{{ route('download') }}">صفحه دانلود</a>
            <a class="fe-btn" href="{{ route('app.home') }}">باز کردن نسخه وب</a>
        </p>
    </section>
</div>

<script>
(function () {
    document.documentElement.classList.add('fe-js');
    try {
        var items = document.querySelectorAll('.fe-reveal');
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        items.forEach(function (el) { io.observe(el); });
    } catch (_) {
        document.querySelectorAll('.fe-reveal').forEach(function (el) { el.classList.add('is-visible'); });
    }
})();
</script>
@endsection
