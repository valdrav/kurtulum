@extends('layouts.portal')
@section('title', __('portal.my_profile'))

@section('content')
<form method="POST" action="{{ route('portal.profile.update') }}">
    @csrf @method('PUT')
    <div class="card">
        <div class="card-body">
            <div class="mb-3"><label class="form-label">Firma</label><input type="text" class="form-control" value="{{ $customer->company_name }}" disabled></div>
            <div class="mb-3"><label class="form-label">Yetkili</label><input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $customer->contact_person) }}"></div>
            <div class="mb-3"><label class="form-label">E-posta</label><input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}"></div>
            <div class="mb-3"><label class="form-label">Telefon</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}"></div>
            <div class="mb-3"><label class="form-label">Şehir</label><input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}"></div>
            <div class="mb-3"><label class="form-label">Adres</label><textarea name="address" class="form-control" rows="3">{{ old('address', $customer->address) }}</textarea></div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary">{{ __('app.save') }}</button></div>
    </div>
</form>
@endsection
