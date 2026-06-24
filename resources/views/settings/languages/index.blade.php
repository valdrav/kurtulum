@extends('layouts.settings')
@section('settings-title', __('extensions.languages'))

@section('settings-content')
<div class="row">
    <div class="col-lg-4">
        <div class="card"><div class="card-header">{{ __('extensions.add_language') }}</div><div class="card-body">
            <form method="POST" action="{{ route('settings.languages.store') }}">@csrf
                <div class="mb-2"><input type="text" name="code" class="form-control" placeholder="tr" required maxlength="10"></div>
                <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Türkçe" required></div>
                <div class="mb-2"><input type="text" name="native_name" class="form-control" placeholder="Türkçe"></div>
                <div class="mb-2"><select name="direction" class="form-select"><option value="ltr">{{ __('settings.direction_ltr') }}</option><option value="rtl">{{ __('settings.direction_rtl') }}</option></select></div>
                <button type="submit" class="btn btn-primary w-100">{{ __('app.save') }}</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="table-responsive"><table class="table table-vcenter card-table">
            <thead><tr><th>Kod</th><th>Ad</th><th>Yön</th><th>{{ __('app.status') }}</th><th></th></tr></thead>
            <tbody>@foreach($languages as $lang)<tr>
                <td><strong>{{ strtoupper($lang->code) }}</strong> @if($lang->is_default)<span class="badge bg-primary">{{ __('settings.default') }}</span>@endif</td>
                <td>{{ $lang->native_name ?? $lang->name }}</td>
                <td>{{ $lang->direction === 'rtl' ? __('settings.direction_rtl') : __('settings.direction_ltr') }}</td>
                <td><span class="badge bg-{{ $lang->is_active ? 'success' : 'secondary' }}-lt">{{ $lang->is_active ? __('settings.active') : __('settings.inactive') }}</span></td>
                <td>@if(!$lang->is_default)<form method="POST" action="{{ route('settings.languages.destroy', $lang) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button></form>@endif</td>
            </tr>@endforeach</tbody>
        </table></div></div>
    </div>
</div>
@endsection
