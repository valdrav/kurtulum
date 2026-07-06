<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>CV — {{ $employee->full_name }}</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; line-height: 1.45; font-size: 11pt; }
        h1 { margin: 0 0 4px; font-size: 22pt; }
        h2 { font-size: 13pt; border-bottom: 2px solid #312e81; padding-bottom: 4px; margin: 18px 0 8px; color: #312e81; }
        .meta { color: #555; margin-bottom: 16px; }
        .skills span { display: inline-block; background: #eef2ff; padding: 2px 8px; border-radius: 4px; margin: 0 6px 6px 0; font-size: 10pt; }
        .item { margin-bottom: 10px; }
        .item strong { display: block; }
        .item small { color: #666; }
        .toolbar { margin-bottom: 16px; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Yazdır / PDF</button>
    </div>

    <h1>{{ $employee->full_name }}</h1>
    <div class="meta">
        {{ $employee->position ?? '' }}
        @if($employee->email) · {{ $employee->email }} @endif
        @if($employee->phone) · {{ $employee->phone }} @endif
    </div>

    @if($cvData['summary'])
    <h2>{{ __('hr.cv_summary') }}</h2>
    <p>{{ $cvData['summary'] }}</p>
    @endif

    @if(!empty($cvData['skills']))
    <h2>{{ __('hr.cv_skills') }}</h2>
    <div class="skills">
        @foreach($cvData['skills'] as $skill)<span>{{ $skill }}</span>@endforeach
    </div>
    @endif

    @if(collect($cvData['experiences'])->filter(fn($e) => trim($e['company'] ?? '') || trim($e['position'] ?? ''))->isNotEmpty())
    <h2>{{ __('hr.cv_experience') }}</h2>
    @foreach($cvData['experiences'] as $exp)
        @if(trim($exp['company'] ?? '') || trim($exp['position'] ?? ''))
        <div class="item">
            <strong>{{ $exp['position'] ?? '' }} — {{ $exp['company'] ?? '' }}</strong>
            <small>{{ trim(($exp['start'] ?? '').' – '.($exp['end'] ?? ''), ' –') }}</small>
            @if(!empty($exp['description']))<div>{{ $exp['description'] }}</div>@endif
        </div>
        @endif
    @endforeach
    @endif

    @if(collect($cvData['education'])->filter(fn($e) => trim($e['school'] ?? ''))->isNotEmpty())
    <h2>{{ __('hr.cv_education') }}</h2>
    @foreach($cvData['education'] as $edu)
        @if(trim($edu['school'] ?? ''))
        <div class="item">
            <strong>{{ $edu['school'] }}</strong>
            <small>{{ trim(($edu['degree'] ?? '').' · '.($edu['year'] ?? ''), ' ·') }}</small>
        </div>
        @endif
    @endforeach
    @endif

    @if(collect($cvData['languages'])->filter(fn($l) => trim($l['name'] ?? ''))->isNotEmpty())
    <h2>{{ __('hr.cv_languages') }}</h2>
    @foreach($cvData['languages'] as $lang)
        @if(trim($lang['name'] ?? ''))
        <div class="item"><strong>{{ $lang['name'] }}</strong> <small>{{ $lang['level'] ?? '' }}</small></div>
        @endif
    @endforeach
    @endif
</body>
</html>
