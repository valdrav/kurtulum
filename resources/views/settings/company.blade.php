@extends('layouts.settings')
@section('settings-title', __('settings.company'))
@section('settings-desc', __('settings.company_desc'))

@section('settings-content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('settings.company.update') }}">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.company_name') }}</label>
                    <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $settings['company_name']) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.company_website') }}</label>
                    <input type="url" name="company_website" class="form-control" value="{{ old('company_website', $settings['company_website']) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.company_email') }}</label>
                    <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $settings['company_email']) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.company_phone') }}</label>
                    <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone', $settings['company_phone']) }}">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">{{ __('settings.company_address') }}</label>
                    <textarea name="company_address" class="form-control" rows="2">{{ old('company_address', $settings['company_address']) }}</textarea>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.company_tax_number') }}</label>
                    <input type="text" name="company_tax_number" class="form-control" value="{{ old('company_tax_number', $settings['company_tax_number']) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.company_tax_office') }}</label>
                    <input type="text" name="company_tax_office" class="form-control" value="{{ old('company_tax_office', $settings['company_tax_office']) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('app.currency') }}</label>
                    <input type="text" name="default_currency" class="form-control" maxlength="3" value="{{ old('default_currency', $settings['default_currency']) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ __('settings.timezone') }}</label>
                    <select name="timezone" class="form-select">
                        @foreach(['Europe/Istanbul','UTC','Europe/London','America/New_York','Asia/Dubai'] as $tz)
                        <option value="{{ $tz }}" @selected(old('timezone', $settings['timezone']) === $tz)>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('app.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
