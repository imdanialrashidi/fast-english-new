@extends('layouts.public')

@section('title', 'پشتیبانی Fast English')
@section('meta_description', 'پشتیبانی Fast English — راه‌های ارتباطی')
@section('canonical', config('app.url').'/support')

@section('content')
<p class="fe-draft-badge" role="note">پیش‌نویس — DRAFT — نیازمند بازبینی صاحب محصول</p>
<h1>پشتیبانی</h1>
<div class="fe-blocked" role="note">
    <p><strong>در انتظار اطلاعات صاحب محصول (BLOCKED)</strong></p>
    <p>کانال پشتیبانی (ایمیل، شناسه پیام‌رسان یا ساعات پاسخ‌گویی) هنوز اعلام نشده است. هیچ نشانی تماسی درج نشده تا اطلاعات واقعی تأمین شود.</p>
</div>
@endsection
