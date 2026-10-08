@props(['name','label','options'=>[],'value'=>'','placeholder'=>null])
<div {{ $attributes->only('class')->class('field') }}><label for="{{ $name }}">{{ $label }}</label><select id="{{ $name }}" name="{{ $name }}" {{ $attributes->except('class') }}>
@if($placeholder!==null)<option value="">{{ $placeholder }}</option>@endif
@foreach($options as $key=>$text)<option value="{{ $key }}" @selected((string)old($name,$value)===(string)$key)>{{ $text }}</option>@endforeach
</select>@error($name)<span class="error">{{ $message }}</span>@enderror</div>
