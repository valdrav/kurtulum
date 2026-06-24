@extends('layouts.app')
@section('title', 'Sigorta Takibi')
@section('content')
@include('partials.page-header', ['title' => 'Sigorta Takibi'])
<div class="alert alert-success">
    <i class="ti ti-shield-check me-1"></i>
    Bu sayfa <strong>modules/Insurance</strong> örnek modülünden geliyor.
    Yeni modül eklemek için aynı yapıyı kopyalayın.
</div>
<div class="card"><div class="card-body">
    <p>Sigorta poliçelerini sevkiyatlara bağlayabilir, hook sistemi ile ödeme/finans süreçlerine entegre edebilirsiniz.</p>
    <pre class="bg-light p-3 rounded"><code>hook()->register('payment.before_create', fn($data) => $data);</code></pre>
</div></div>
@endsection
