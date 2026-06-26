@php
    $amounts = $amounts ?? ['USD' => 0, 'TRY' => 0];
    $decimals = $decimals ?? 0;
    $sizeClass = $sizeClass ?? 'h1 mb-0';
@endphp
<div class="dual-money {!! $sizeClass !!}">
    {!! format_money_dual((float) ($amounts['USD'] ?? 0), (float) ($amounts['TRY'] ?? 0), $decimals) !!}
</div>
