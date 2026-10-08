@props(['name','label','type'=>'text','value'=>'','hint'=>null])
<div {{ $attributes->only('class')->class('field') }}>
<label for="{{ $name }}">{{ $label }}</label>
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" @if(!in_array($type,['file','password'])) value="{{ old($name,$value) }}" @endif {{ $attributes->except('class') }} @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
@if($hint)<small class="hint">{{ $hint }}</small>@endif
@error($name)<span id="{{ $name }}-error" class="error">{{ $message }}</span>@enderror
</div>
