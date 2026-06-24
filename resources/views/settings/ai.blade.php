@extends('layouts.settings')
@section('settings-title', __('settings.ai'))

@section('settings-content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('settings.ai.update') }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-check form-switch">
                    <input type="checkbox" name="ai_enabled" class="form-check-input" value="1" @checked(old('ai_enabled', $settings['ai_enabled']) == '1')>
                    <span class="form-check-label">{{ __('settings.ai_enabled') }}</span>
                </label>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.ai_provider') }}</label>
                    <select name="ai_provider" class="form-select">
                        <option value="openai" @selected(old('ai_provider', $settings['ai_provider']) === 'openai')>OpenAI</option>
                        <option value="anthropic" @selected(old('ai_provider', $settings['ai_provider']) === 'anthropic')>Anthropic</option>
                        <option value="custom" @selected(old('ai_provider', $settings['ai_provider']) === 'custom')>Custom</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.ai_model') }}</label>
                    <input type="text" name="ai_model" class="form-control" value="{{ old('ai_model', $settings['ai_model']) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.ai_api_key') }}</label>
                    <input type="password" name="ai_api_key" class="form-control" value="{{ old('ai_api_key', $settings['ai_api_key']) }}" placeholder="sk-...">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        </form>
    </div>
</div>
@endsection
