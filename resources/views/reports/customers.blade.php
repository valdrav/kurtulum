@extends('layouts.app')
@section('title', 'Müşteri Raporu')
@section('content')
@include('partials.page-header', ['title' => 'Top Müşteriler'])
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Müşteri</th><th>Sipariş</th><th>Toplam</th></tr></thead>
<tbody>@foreach($topCustomers as $c)<tr><td>{{ $c->company_name }}</td><td>{{ $c->orders_count }}</td><td>{{ number_format($c->orders_sum_total_amount ?? 0, 2) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
