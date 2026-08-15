@props(['url'])
@php
    $brand = config('marketing.brand', config('app.name'));
    $logo = url('/images/logo-white-fordark.png');
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ $logo }}" class="logo" alt="{{ $brand }}" height="40">
</a>
</td>
</tr>
