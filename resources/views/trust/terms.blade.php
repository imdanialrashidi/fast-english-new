@extends('layouts.public')

@section('title', 'قوانین استفاده')
@section('meta_description', 'قوانین استفاده از Fast English')
@section('canonical', config('app.url').'/terms')

@section('content')
<p class="fe-draft-badge" role="note">پیش‌نویس — DRAFT — نیازمند بازبینی صاحب محصول</p>
<h1>قوانین استفاده</h1>
<div class="fe-blocked" role="note">
    <p><strong>در انتظار متن مصوب صاحب محصول (BLOCKED)</strong></p>
    <p>متن حقوقی قوانین استفاده هنوز تأمین و تصویب نشده است. هیچ متن حقوقی پیش‌فرضی درج نشده است.</p>
</div>
@endsection
