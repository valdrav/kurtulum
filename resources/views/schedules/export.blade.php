<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $schedule->displayTitle() }} — {{ $dateFrom->format('d.m.Y') }} / {{ $dateTo->format('d.m.Y') }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 8pt; color: #111; margin: 0; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #312e81; }
        .header-left { flex: 1; min-width: 0; }
        .header-left h1 { font-size: 13pt; margin: 0 0 6px; color: #1e1b4b; line-height: 1.3; }
        .header-left .range { font-size: 10pt; color: #334155; font-weight: 600; }
        .header-left .sub { font-size: 8.5pt; color: #64748b; margin-top: 4px; }
        .header-logo { flex-shrink: 0; text-align: right; max-width: 180px; }
        .header-logo img { max-height: 52px; max-width: 170px; object-fit: contain; }
        .header-logo .brand { font-size: 11pt; font-weight: 700; color: #312e81; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #475569; padding: 4px 5px; vertical-align: top; word-wrap: break-word; line-height: 1.25; }
        th { background: #312e81; color: #fff; font-size: 7.5pt; font-weight: 600; }
        tr:nth-child(even) td { background: #f8fafc; }
        .toolbar { margin-bottom: 12px; display: flex; gap: 8px; }
        .toolbar button { padding: 8px 14px; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; }
        .empty { text-align: center; color: #64748b; padding: 20px; }
        @media print {
            .toolbar { display: none; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">{{ __('schedules.export_pdf') }}</button>
    </div>

    <div class="header">
        <div class="header-left">
            <h1>{{ $dateFrom->format('d.m.Y') }} — {{ $dateTo->format('d.m.Y') }} {{ __('schedules.range_title') }}</h1>
            <div class="range">{{ trans_content($schedule->displayTitle()) }}</div>
            @if($schedule->notes)
            <div class="sub">{{ trans_content($schedule->notes) }}</div>
            @endif
        </div>
        <div class="header-logo">
            @if(site_branding()->hasLogo())
            <img src="{{ site_branding()->logoUrl() }}" alt="{{ app_brand() }}">
            @else
            <div class="brand">{{ app_brand() }}</div>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($columns as $key => $col)
                <th style="width: {{ $col['width'] ?? 'auto' }}">{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
            <tr>
                @foreach($columns as $key => $col)
                <td>{{ trans_field($entry->value($key), $col['type'] ?? 'text') }}</td>
                @endforeach
            </tr>
            @empty
            <tr><td colspan="{{ count($columns) }}" class="empty">{{ __('schedules.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
