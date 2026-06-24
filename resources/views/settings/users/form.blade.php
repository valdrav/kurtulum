@extends('layouts.settings')
@section('settings-title', $user->exists ? __('settings.edit_user') : __('settings.add_user'))

@section('settings-content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $user->exists ? route('settings.users.update', $user) : route('settings.users.store') }}">
            @csrf
            @if($user->exists) @method('PUT') @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.users') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.user_password') }}</label>
                    <input type="password" name="password" class="form-control" {{ $user->exists ? '' : 'required' }}>
                    @if($user->exists)<small class="text-muted">Boş bırakılırsa değişmez</small>@endif
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Şifre Tekrar</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.user_role') }}</label>
                    <select name="role" class="form-select" required>
                        @foreach($roles as $role)
                        <option value="{{ $role->name }}" @selected(old('role', $user->roles->first()?->name) === $role->name)>{{ role_label($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.departments') }}</label>
                    <select name="department_id" class="form-select">
                        <option value="">—</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(old('department_id', $user->department_id) == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Dil</label>
                    <select name="locale" class="form-select">
                        @foreach(registry()->languages() as $lang)
                        <option value="{{ $lang->code }}" @selected(old('locale', $user->locale) === $lang->code)>{{ $lang->native_name ?? $lang->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tema</label>
                    <select name="theme" class="form-select">
                        <option value="light" @selected(old('theme', $user->theme) === 'light')>{{ __('app.light_theme') }}</option>
                        <option value="dark" @selected(old('theme', $user->theme) === 'dark')>{{ __('app.dark_theme') }}</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <label class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" @checked(old('is_active', $user->is_active ?? true))>
                        <span class="form-check-label">{{ __('settings.active') }}</span>
                    </label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
