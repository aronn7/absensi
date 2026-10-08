@props(['user'])
<div class="avatar">@if($user->photo_path)<img src="{{ asset('storage/'.$user->photo_path) }}" alt="Foto {{ $user->name }}">@else{{ mb_strtoupper(mb_substr($user->name,0,2)) }}@endif</div>
