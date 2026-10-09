@extends('layouts.public')

@section('title', 'همکاری با Fast English')
@section('meta_description', 'همکاری با Fast English — اطلاعات همکاری')
@section('canonical', config('app.url').'/cooperation')

@section('content')
<p class="fe-draft-badge" role="note">پیش‌نویس — DRAFT — نیازمند بازبینی صاحب محصول</p>
<h1>همکاری</h1>
<div class="fe-prose">
    <p>این صفحه شرایط همکاری (تولید محتوا، آموزش و سایر همکاری‌ها) را توضیح می‌دهد. شرایط نهایی هنوز اعلام نشده است.</p>
    <p>این متن پیش‌نویس است و پیش از انتشار عمومی توسط صاحب محصول بازبینی و نهایی می‌شود.</p>
</div>
<div class="fe-blocked" role="note">
    <p><strong>در انتظار اطلاعات صاحب محصول (BLOCKED)</strong></p>
    <p>کانال ارتباطی همکاری هنوز اعلام نشده است؛ از درج ایمیل یا شماره تماس خودداری شده تا اطلاعات واقعی تأمین شود.</p>
</div>
@endsection
