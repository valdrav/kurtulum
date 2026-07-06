@extends('layouts.app')
@section('title', $employee->exists ? $employee->full_name : __('hr.new_employee'))

@section('content')
@include('partials.page-header', [
    'title' => $employee->exists ? $employee->full_name : __('hr.new_employee'),
    'backRoute' => route('hr.index'),
])

<form method="POST" action="{{ $employee->exists ? route('hr.update', $employee) : route('hr.store') }}">
    @csrf
    @if($employee->exists) @method('PUT') @endif

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">{{ __('hr.personal_info') }}</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('settings.employee_code') }} *</label><input type="text" name="employee_code" class="form-control" value="{{ old('employee_code', $employee->employee_code) }}" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Ad *</label><input type="text" name="first_name" class="form-control" value="{{ old('first_name', $employee->first_name) }}" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">Soyad *</label><input type="text" name="last_name" class="form-control" value="{{ old('last_name', $employee->last_name) }}" required></div>
                <div class="col-md-4 mb-3"><label class="form-label">E-posta</label><input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('settings.phone') }}</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('settings.position') }}</label><input type="text" name="position" class="form-control" value="{{ old('position', $employee->position) }}"></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.departments') }}</label>
                    <select name="department_id" class="form-select">
                        <option value="">—</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $employee->department_id) == $d->id)>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3"><label class="form-label">İşe Giriş</label><input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}"></div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('app.status') }}</label>
                    <select name="status" class="form-select" required>
                        @foreach(['active','inactive','on_leave'] as $st)<option value="{{ $st }}" @selected(old('status', $employee->status) === $st)>{{ __("settings.{$st}") }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Özlük & Maaş Bilgileri</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3"><label class="form-label">Doğum Tarihi</label><input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $hrDetail->birth_date?->format('Y-m-d')) }}"></div>
                <div class="col-md-3 mb-3"><label class="form-label">TC Kimlik No</label><input type="text" name="national_id" class="form-control" value="{{ old('national_id', $hrDetail->national_id) }}"></div>
                <div class="col-md-3 mb-3"><label class="form-label">Doğum Yeri</label><input type="text" name="birth_place" class="form-control" value="{{ old('birth_place', $hrDetail->birth_place) }}"></div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Medeni Hal</label>
                    <select name="marital_status" class="form-select">
                        <option value="">—</option>
                        @foreach(['single' => __('hr.marital_single'), 'married' => __('hr.marital_married'), 'other' => __('hr.marital_other')] as $val => $label)
                        <option value="{{ $val }}" @selected(old('marital_status', $hrDetail->marital_status) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 mb-3"><label class="form-label">Adres</label><textarea name="address" class="form-control" rows="2">{{ old('address', $hrDetail->address) }}</textarea></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('hr.emergency') }} Kişi</label><input type="text" name="emergency_contact" class="form-control" value="{{ old('emergency_contact', $hrDetail->emergency_contact) }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">{{ __('hr.emergency') }} Tel</label><input type="text" name="emergency_phone" class="form-control" value="{{ old('emergency_phone', $hrDetail->emergency_phone) }}"></div>
                <div class="col-md-2 mb-3"><label class="form-label">{{ __('hr.base_salary') }}</label><input type="number" step="0.01" name="base_salary" class="form-control" value="{{ old('base_salary', $hrDetail->base_salary) }}"></div>
                <div class="col-md-2 mb-3"><label class="form-label">PB</label><input type="text" name="salary_currency" class="form-control" maxlength="3" value="{{ old('salary_currency', $hrDetail->salary_currency ?? 'TRY') }}"></div>
                <div class="col-md-4 mb-3"><label class="form-label">Banka</label><input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $hrDetail->bank_name) }}"></div>
                <div class="col-md-8 mb-3"><label class="form-label">IBAN</label><input type="text" name="iban" class="form-control" value="{{ old('iban', $hrDetail->iban) }}"></div>
                <div class="col-12 mb-3"><label class="form-label">Notlar</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $hrDetail->notes) }}</textarea></div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        <a href="{{ route('hr.index') }}" class="btn btn-outline-secondary">{{ __('app.cancel') }}</a>
    </div>
</form>
@endsection
