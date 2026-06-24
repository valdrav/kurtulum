@extends('layouts.app')
@section('title', __('app.ai_assistant'))
@section('content')
@include('partials.page-header', ['title' => __('app.ai_assistant')])
@if(!$configured)<div class="alert alert-warning">AI yapılandırılmamış. .env dosyasına AI_API_KEY ekleyin.</div>@endif
<div class="row" x-data="{ result: '', loading: false, type: 'email' }">
<div class="col-lg-4"><div class="list-group"><button @click="type='email'" class="list-group-item list-group-item-action" :class="type==='email' && 'active'">{{ __('ai.generate_email') }}</button>
<button @click="type='translate'" class="list-group-item list-group-item-action" :class="type==='translate' && 'active'">{{ __('ai.translate') }}</button>
<button @click="type='summarize'" class="list-group-item list-group-item-action" :class="type==='summarize' && 'active'">{{ __('ai.summarize') }}</button></div></div>
<div class="col-lg-8"><div class="card"><div class="card-body">
<textarea x-model="input" class="form-control mb-3" rows="6" placeholder="Metin veya bağlam girin..."></textarea>
<button @click="loading=true; fetch(type==='email'?'{{ route('ai.email') }}':type==='translate'?'{{ route('ai.translate') }}':'{{ route('ai.summarize') }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(type==='translate'?{text:input,from:'tr',to:'en'}:{context:input,data:input})}).then(r=>r.json()).then(d=>{result=d.result;loading=false})" class="btn btn-primary" :disabled="loading"><i class="ti ti-sparkles"></i> Çalıştır</button>
<div x-show="result" class="mt-4 p-3 bg-light rounded" x-text="result"></div>
</div></div></div></div>
@endsection
