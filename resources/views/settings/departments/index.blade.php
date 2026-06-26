@extends('layouts.settings')
@section('settings-title', __('settings.departments'))

@section('settings-content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ __('settings.add_department') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('settings.departments.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Ad</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kod</label>
                        <input type="text" name="code" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('settings.parent_department') }}</label>
                        <select name="parent_id" class="form-select">
                            <option value="">—</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('settings.manager') }}</label>
                        <select name="manager_user_id" class="form-select">
                            <option value="">—</option>
                            @foreach($managers as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <textarea name="description" class="form-control" rows="2" placeholder="{{ __('app.description') }}"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">{{ __('app.create') }}</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-modern">
                    <thead>
                        <tr>
                            <th>Ad</th>
                            <th>Kod</th>
                            <th>{{ __('settings.manager') }}</th>
                            <th>{{ __('settings.employee_count') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $dept->name }}</div>
                                @if($dept->parent)<small class="text-muted">← {{ $dept->parent->name }}</small>@endif
                            </td>
                            <td>{{ $dept->code ?? '—' }}</td>
                            <td>{{ $dept->manager?->name ?? '—' }}</td>
                            <td><span class="badge bg-secondary-lt">{{ $dept->employees_count }}</span></td>
                            <td>
                                <form action="{{ route('settings.departments.destroy', $dept) }}" method="POST" class="d-inline"
                                      data-confirm="{{ __('app.confirm_delete') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">{{ __('app.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-4">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
