@extends('layouts.app')
@section('title', $employee->full_name)

@section('content')
@include('partials.page-header', [
    'title' => $employee->full_name,
    'subtitle' => $employee->position.' · '.$employee->employee_code,
    'backRoute' => route('hr.index'),
])

<ul class="nav nav-tabs mb-3 ef-patron-tabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">{{ __('hr.personal_info') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">{{ __('hr.documents') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payroll" type="button">{{ __('hr.payroll') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-cv" type="button">{{ __('hr.cv') }}</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h3 class="card-title mb-0">{{ __('hr.personal_info') }}</h3>
                        <a href="{{ route('hr.edit', $employee) }}" class="btn btn-sm btn-primary">{{ __('app.edit') }}</a>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ __('settings.departments') }}</dt><dd class="col-sm-8">{{ $employee->department?->name ?? '—' }}</dd>
                            <dt class="col-sm-4">{{ __('hr.email') }}</dt><dd class="col-sm-8">{{ $employee->email ?? '—' }}</dd>
                            <dt class="col-sm-4">{{ __('settings.phone') }}</dt><dd class="col-sm-8">{{ $employee->phone ?? '—' }}</dd>
                            <dt class="col-sm-4">{{ __('hr.hire_date') }}</dt><dd class="col-sm-8">{{ $employee->hire_date?->format('d.m.Y') ?? '—' }}</dd>
                            <dt class="col-sm-4">{{ __('hr.birth_date') }}</dt><dd class="col-sm-8">{{ $hrDetail->birth_date?->format('d.m.Y') ?? '—' }}</dd>
                            <dt class="col-sm-4">{{ __('hr.national_id') }}</dt><dd class="col-sm-8">{{ $hrDetail->national_id ?? '—' }}</dd>
                            <dt class="col-sm-4">{{ __('hr.address') }}</dt><dd class="col-sm-8">{{ trans_content($hrDetail->address) ?: '—' }}</dd>
                            <dt class="col-sm-4">{{ __('hr.emergency') }}</dt><dd class="col-sm-8">{{ $hrDetail->emergency_contact ?? '—' }} {{ $hrDetail->emergency_phone ? '· '.$hrDetail->emergency_phone : '' }}</dd>
                            <dt class="col-sm-4">{{ __('hr.bank_info') }}</dt><dd class="col-sm-8">{{ $hrDetail->bank_name ?? '—' }}<br>{{ $hrDetail->iban ?? '' }}</dd>
                            @if($hrDetail->notes)<dt class="col-sm-4">{{ __('hr.notes') }}</dt><dd class="col-sm-8">{{ trans_content($hrDetail->notes) }}</dd>@endif
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card ef-patron-stat">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('hr.base_salary') }}</div>
                        <div class="h2 mb-0">
                            @if($hrDetail->base_salary)
                            {{ number_format($hrDetail->base_salary, 2, ',', '.') }} <small>{{ $hrDetail->salary_currency }}</small>
                            @else — @endif
                        </div>
                    </div>
                </div>
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="text-muted small">{{ __('hr.total_payments') }}</div>
                        <div class="h3 mb-0">{{ number_format($employee->compensations->sum('amount'), 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-documents">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('hr.upload_document') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('hr.documents.store', $employee) }}" enctype="multipart/form-data" class="row g-2">
                    @csrf
                    <div class="col-md-3">
                        <select name="category" class="form-select" required>
                            @foreach($documentCategories as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input type="text" name="title" class="form-control" placeholder="Belge adı" required></div>
                    <div class="col-md-2"><input type="date" name="document_date" class="form-control"></div>
                    <div class="col-md-2"><input type="date" name="expires_at" class="form-control" placeholder="Son geçerlilik"></div>
                    <div class="col-md-2"><input type="file" name="file" class="form-control" required></div>
                    <div class="col-12"><button class="btn btn-primary btn-sm">{{ __('hr.upload_document') }}</button></div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-modern">
                    <thead><tr><th>Kategori</th><th>Belge</th><th>Tarih</th><th>Yükleyen</th><th class="ef-table-actions"></th></tr></thead>
                    <tbody>
                        @forelse($employee->hrDocuments as $doc)
                        <tr>
                            <td>{{ $documentCategories[$doc->category] ?? $doc->category }}</td>
                            <td>{{ $doc->title }}</td>
                            <td>{{ $doc->document_date?->format('d.m.Y') ?? '—' }}</td>
                            <td>{{ $doc->uploader?->name ?? '—' }}</td>
                            <td class="ef-table-actions text-nowrap text-end">
                                <a href="{{ route('hr.documents.download', [$employee, $doc]) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-download"></i></a>
                                <form method="POST" action="{{ route('hr.documents.destroy', [$employee, $doc]) }}" class="d-inline" data-confirm="{{ __('app.confirm_delete') }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button></form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-payroll">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">{{ __('hr.add_payment') }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('hr.compensations.store', $employee) }}" class="row g-2">
                    @csrf
                    <div class="col-md-2">
                        <select name="type" class="form-select" required>
                            @foreach($compensationTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="{{ __('hr.amount') }}" required></div>
                    <div class="col-md-1"><input type="text" name="currency" class="form-control" value="TRY" maxlength="3"></div>
                    <div class="col-md-2"><input type="date" name="payment_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                    <div class="col-md-2"><input type="text" name="period" class="form-control" placeholder="{{ __('hr.period') }}"></div>
                    <div class="col-md-2"><input type="text" name="description" class="form-control" placeholder="{{ __('hr.description') }}"></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100">{{ __('hr.add') }}</button></div>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-modern">
                    <thead><tr><th>{{ __('hr.type') }}</th><th>{{ __('hr.amount') }}</th><th>{{ __('hr.date') }}</th><th>{{ __('hr.period') }}</th><th>{{ __('hr.description') }}</th><th class="ef-table-actions"></th></tr></thead>
                    <tbody>
                        @forelse($employee->compensations as $pay)
                        <tr>
                            <td>{{ $compensationTypes[$pay->type] ?? $pay->type }}</td>
                            <td>{{ number_format($pay->amount, 2, ',', '.') }} {{ $pay->currency }}</td>
                            <td>{{ $pay->payment_date->format('d.m.Y') }}</td>
                            <td>{{ $pay->period ?? '—' }}</td>
                            <td>{{ trans_content($pay->description) ?: '—' }}</td>
                            <td class="ef-table-actions text-end">
                                <form method="POST" action="{{ route('hr.compensations.destroy', [$employee, $pay]) }}" class="d-inline" data-confirm="{{ __('app.confirm_delete') }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button></form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ __('app.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tab-cv">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">{{ __('hr.cv') }}</h3>
            <a href="{{ route('hr.cv.print', $employee) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="ti ti-printer me-1"></i>{{ __('hr.cv_print') }}</a>
        </div>
        <form method="POST" action="{{ route('hr.cv.update', $employee) }}">
            @csrf @method('PUT')
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label">{{ __('hr.cv_summary') }}</label>
                    <textarea name="summary" class="form-control" rows="4">{{ old('summary', $cvData['summary']) }}</textarea>
                    <label class="form-label mt-3">{{ __('hr.cv_skills') }} <small class="text-muted">(virgül veya satır ile)</small></label>
                    <textarea name="skills" class="form-control" rows="2">{{ old('skills', implode(', ', $cvData['skills'])) }}</textarea>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h4 class="card-title mb-0">{{ __('hr.cv_experience') }}</h4></div>
                <div class="card-body" id="cv-experiences">
                    @foreach($cvData['experiences'] as $i => $exp)
                    <div class="row g-2 mb-2 cv-exp-row">
                        <div class="col-md-3"><input type="text" name="experiences[{{ $i }}][company]" class="form-control" placeholder="Şirket" value="{{ $exp['company'] ?? '' }}"></div>
                        <div class="col-md-3"><input type="text" name="experiences[{{ $i }}][position]" class="form-control" placeholder="Pozisyon" value="{{ $exp['position'] ?? '' }}"></div>
                        <div class="col-md-2"><input type="text" name="experiences[{{ $i }}][start]" class="form-control" placeholder="Başlangıç" value="{{ $exp['start'] ?? '' }}"></div>
                        <div class="col-md-2"><input type="text" name="experiences[{{ $i }}][end]" class="form-control" placeholder="Bitiş" value="{{ $exp['end'] ?? '' }}"></div>
                        <div class="col-md-2"><input type="text" name="experiences[{{ $i }}][description]" class="form-control" placeholder="Açıklama" value="{{ $exp['description'] ?? '' }}"></div>
                    </div>
                    @endforeach
                </div>
                <div class="card-footer"><button type="button" class="btn btn-sm btn-outline-secondary" id="add-exp">{{ __('hr.add_row') }}</button></div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h4 class="card-title mb-0">{{ __('hr.cv_education') }}</h4></div>
                <div class="card-body" id="cv-education">
                    @foreach($cvData['education'] as $i => $edu)
                    <div class="row g-2 mb-2">
                        <div class="col-md-5"><input type="text" name="education[{{ $i }}][school]" class="form-control" placeholder="Okul" value="{{ $edu['school'] ?? '' }}"></div>
                        <div class="col-md-5"><input type="text" name="education[{{ $i }}][degree]" class="form-control" placeholder="Bölüm / Derece" value="{{ $edu['degree'] ?? '' }}"></div>
                        <div class="col-md-2"><input type="text" name="education[{{ $i }}][year]" class="form-control" placeholder="Yıl" value="{{ $edu['year'] ?? '' }}"></div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h4 class="card-title mb-0">{{ __('hr.cv_languages') }}</h4></div>
                <div class="card-body">
                    @foreach($cvData['languages'] as $i => $lang)
                    <div class="row g-2 mb-2">
                        <div class="col-md-6"><input type="text" name="languages[{{ $i }}][name]" class="form-control" placeholder="Dil" value="{{ $lang['name'] ?? '' }}"></div>
                        <div class="col-md-6"><input type="text" name="languages[{{ $i }}][level]" class="form-control" placeholder="Seviye" value="{{ $lang['level'] ?? '' }}"></div>
                    </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('app.save') }}</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('add-exp')?.addEventListener('click', function () {
    const wrap = document.getElementById('cv-experiences');
    const i = wrap.querySelectorAll('.cv-exp-row').length;
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 cv-exp-row';
    row.innerHTML = `
        <div class="col-md-3"><input type="text" name="experiences[${i}][company]" class="form-control" placeholder="Şirket"></div>
        <div class="col-md-3"><input type="text" name="experiences[${i}][position]" class="form-control" placeholder="Pozisyon"></div>
        <div class="col-md-2"><input type="text" name="experiences[${i}][start]" class="form-control" placeholder="Başlangıç"></div>
        <div class="col-md-2"><input type="text" name="experiences[${i}][end]" class="form-control" placeholder="Bitiş"></div>
        <div class="col-md-2"><input type="text" name="experiences[${i}][description]" class="form-control" placeholder="Açıklama"></div>`;
    wrap.appendChild(row);
});
</script>
@endpush
@endsection
