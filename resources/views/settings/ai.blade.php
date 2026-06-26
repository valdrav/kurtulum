@extends('layouts.settings')
@section('settings-title', __('settings.ai'))

@section('settings-content')
<div class="card">
    <div class="card-body">
        <p class="text-muted small mb-3">{{ __('settings.ai_groq_hint') }}</p>
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
                        <option value="groq" @selected(old('ai_provider', $settings['ai_provider']) === 'groq')>Groq (ücretsiz)</option>
                        <option value="openai" @selected(old('ai_provider', $settings['ai_provider']) === 'openai')>OpenAI</option>
                        <option value="anthropic" @selected(old('ai_provider', $settings['ai_provider']) === 'anthropic')>Anthropic</option>
                        <option value="custom" @selected(old('ai_provider', $settings['ai_provider']) === 'custom')>Custom</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Groq API Key</label>
                    <input type="password" name="groq_api_key" class="form-control" value="{{ old('groq_api_key', $settings['groq_api_key']) }}" placeholder="gsk_...">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Groq Model</label>
                    <input type="text" name="groq_model" class="form-control" value="{{ old('groq_model', $settings['groq_model']) }}" placeholder="llama-3.1-8b-instant">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.ai_model') }} (OpenAI)</label>
                    <input type="text" name="ai_model" class="form-control" value="{{ old('ai_model', $settings['ai_model']) }}">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('settings.ai_api_key') }} (OpenAI)</label>
                    <input type="password" name="ai_api_key" class="form-control" value="{{ old('ai_api_key', $settings['ai_api_key']) }}" placeholder="sk-...">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        </form>
    </div>
</div>
@endsection
