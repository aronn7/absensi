@extends('layouts.guest')
@section('title', 'Akses Khusus')
@section('content')
    <div class="eyebrow">AKSES KHUSUS</div>
    <h1>Verifikasi diperlukan.</h1>
    <p>Masukkan PIN keamanan untuk melanjutkan ke ruang administrator.</p>
    <form method="post" action="{{ route('login.admin.gate.verify') }}" class="guest-form" autofocus>@csrf
        <x-field name="pin" label="PIN Keamanan" type="password" inputmode="numeric" required autofocus autocomplete="off"
            maxlength="12" />
        <button class="btn" type="submit">Lanjutkan <x-icon name="arrow-right" /></button>
    </form>
<p class="!text-xs !mt-6 !mb-0">Halaman ini hanya untuk administrator sekolah.</p>@endsection