@extends('layouts.public')

@section('title', 'دانلود Fast English')
@section('meta_description', 'دانلود Fast English — فایل نصب اندروید')
@section('canonical', config('app.url').'/download')

@section('content')
<p class="fe-draft-badge" role="note">پیش‌نویس — DRAFT — نیازمند بازبینی صاحب محصول</p>
<h1>دانلود</h1>
@if (! $published)
    <div class="fe-empty">
        <p><strong>فایل نصب هنوز منتشر نشده</strong></p>
        <p class="fe-muted">نسخه اندروید هنوز آماده انتشار نیست. مشخصات نسخه (نسخه، کد نسخه، تاریخ، حجم و SHA-256) تنها پس از ساخت واقعی اعلام می‌شود.</p>
    </div>
@else
    <table class="fe-meta-table">
        <caption>مشخصات نسخه منتشرشده</caption>
        <tbody>
            <tr><th scope="row">نسخه</th><td>{{ $release['version_name'] }}</td></tr>
            <tr><th scope="row">کد نسخه</th><td>{{ $release['version_code'] }}</td></tr>
            <tr><th scope="row">تاریخ انتشار</th><td>{{ $release['release_date'] }}</td></tr>
            <tr><th scope="row">حجم</th><td>{{ $release['size_bytes'] }} بایت</td></tr>
            <tr><th scope="row">SHA-256</th><td lang="en" dir="ltr">{{ $release['sha256'] }}</td></tr>
        </tbody>
    </table>
    <p>
        <a class="fe-btn fe-btn-primary" href="{{ $release['file_url'] }}">دانلود فایل نصب</a>
    </p>
@endif
@endsection
