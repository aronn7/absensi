@props(['stats'])
<div class="status-grid">@foreach(['HADIR'=>'#62a889','TERLAMBAT'=>'#d5b263','SAKIT'=>'#94b7d5','IZIN'=>'#b3a5cd','ALFA'=>'#d89986'] as $status=>$color)<div class="status-tile"><span class="status-dot" style="--status-color:{{ $color }}"></span><small>{{ ucfirst(strtolower($status)) }}</small><strong>{{ $stats[$status]??0 }}</strong></div>@endforeach</div>
