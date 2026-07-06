@if($url = whatsapp_url($phone ?? null))
<a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success {{ $class ?? '' }}" title="{{ __('directory.whatsapp') }}">
    <i class="ti ti-brand-whatsapp"></i>
    @if(empty($iconOnly))<span class="d-none d-md-inline ms-1">WA</span>@endif
</a>
@endif
