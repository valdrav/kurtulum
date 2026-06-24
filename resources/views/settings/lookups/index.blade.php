@extends('layouts.app')
@section('title', __('extensions.lookups'))
@section('content')
@include('partials.page-header', ['title' => __('extensions.lookups')])
<div class="card mb-3"><div class="card-header">{{ __('extensions.add_lookup_type') }}</div><div class="card-body">
<form method="POST" action="{{ route('settings.lookups.types.store') }}" class="row g-2">@csrf
<div class="col-md-3"><input type="text" name="slug" class="form-control" placeholder="kod (slug)" required></div>
<div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Liste adı" required></div>
<div class="col-md-3"><button type="submit" class="btn btn-primary">{{ __('app.save') }}</button></div>
</form></div></div>
@foreach($types as $type)
<div class="card mb-3"><div class="card-header"><strong>{{ $type->name }}</strong> <code>{{ $type->slug }}</code></div>
<div class="card-body">
<form method="POST" action="{{ route('settings.lookups.values.store', $type) }}" class="row g-2 mb-3">@csrf
<div class="col-md-3"><input type="text" name="code" class="form-control" placeholder="Kod" required></div>
<div class="col-md-4"><input type="text" name="label" class="form-control" placeholder="Etiket" required></div>
<div class="col-md-2"><button type="submit" class="btn btn-sm btn-primary">+</button></div>
</form>
<div class="d-flex flex-wrap gap-2">@foreach($type->values as $v)<span class="badge bg-secondary-lt">{{ $v->label }} ({{ $v->code }})</span>@endforeach</div>
</div></div>
@endforeach
@endsection
