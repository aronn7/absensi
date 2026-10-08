@extends('layouts.guest')
@section('title','Selamat datang')
@section('content')<span class="eyebrow">SELAMAT DATANG DI SEKOLAH DIGITAL</span><h1>Mari mulai hari ini.</h1><p>Pilih peran Anda untuk masuk ke ruang absensi.</p>
@foreach([['student','graduation-cap','Login Murid','Catat kehadiran & pantau perjalananmu'],['teacher','book-open','Login Guru','Kelola kehadiran & dampingi kelas']] as [$role,$icon,$title,$subtitle])<a class="role-card" href="{{ route('login.show',$role) }}"><span class="role-icon"><x-icon :name="$icon"/></span><span><strong>{{ $title }}</strong><small>{{ $subtitle }}</small></span><x-icon name="arrow-up-right"/></a>@endforeach
<p class="!text-xs !mt-8 !mb-0">Belum memiliki akun? Hubungi administrator sekolah.</p>
<script>addEventListener('keydown',e=>{if(e.ctrlKey&&e.shiftKey&&e.key.toLowerCase()==='a'){e.preventDefault();window.location.href='{{ route('login.admin.gate', config('services.admin_gate.secret')) }}';}});</script>
@endsection
