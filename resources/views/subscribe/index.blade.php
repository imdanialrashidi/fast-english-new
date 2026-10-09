@extends('layouts.learner')

@section('title', 'اشتراک')

@section('content')
<div class="fe-measure fe-library">
    <h1>انتخاب پلن</h1>
    <p class="fe-muted">پلن موردنظر را انتخاب کنید. مبلغ و مشخصات واریز از سمت سرور ثبت می‌شود.</p>

    @if (session('status'))
        <p class="fe-muted" role="status">{{ session('status') }}</p>
    @endif

    @if (! $salesOn)
        <div class="fe-empty">
            <p><strong>در دست آماده‌سازی</strong></p>
            <p class="fe-muted">فروش هنوز فعال نشده است. پلن‌ها پس از اعلام رسمی قابل خرید خواهند بود.</p>
        </div>
    @elseif ($plans->isEmpty())
        <div class="fe-empty">
            <p>در حال حاضر پلن فعالی وجود ندارد. لطفاً بعداً مراجعه کنید.</p>
        </div>
    @else
        <ul class="fe-cards">
            @foreach ($plans as $plan)
                <li class="fe-card">
                    <div class="fe-card-body">
                        <h2 class="fe-card-title" lang="fa" dir="rtl">{{ $plan->name_fa }}</h2>
                        <p class="fe-card-levels">{{ \App\Support\Toman::format((int) $plan->price_toman) }}</p>
                        <p class="fe-muted">{{ $plan->duration_days }} روز دسترسی پس از تأیید</p>
                        <form method="POST" action="{{ route('payments.store') }}" lang="fa" dir="rtl">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                            <button class="fe-btn fe-btn-primary" type="submit">انتخاب این پلن</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($errors->any())
        <div class="fe-empty">
            <p class="fe-alert" role="alert">انتخاب پلن نامعتبر بود. لطفاً دوباره تلاش کنید.</p>
        </div>
    @endif
</div>
@endsection
