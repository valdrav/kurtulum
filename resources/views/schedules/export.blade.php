<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $schedule->displayTitle() }}</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 9pt; color: #111; margin: 0; }
        h1 { font-size: 14pt; margin: 0 0 4px; }
        .meta { color: #444; margin-bottom: 10px; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #333; padding: 4px 5px; vertical-align: top; word-wrap: break-word; }
        th { background: #312e81; color: #fff; font-size: 8pt; }
        tr:nth-child(even) td { background: #f8fafc; }
        .toolbar { margin-bottom: 12px; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">{{ __('schedules.export_pdf') }}</button>
    </div>

    <h1>{{ $schedule->displayTitle() }}</h1>
    <div class="meta">
        {{ $schedule->week_start->format('d.m.Y') }} — {{ $schedule->week_end->format('d.m.Y') }}
        @if($schedule->notes) · {{ $schedule->notes }} @endif
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
            @forelse($schedule->entries as $entry)
            <tr>
                @foreach($columns as $key => $col)
                <td>
                    @if($key === 'entry_date')
                    {{ $entry->entry_date?->format('d.m.Y') ?? '' }}
                    @else
                    {{ $entry->value($key) }}
                    @endif
                </td>
                @endforeach
            </tr>
            @empty
            <tr><td colspan="{{ count($columns) }}" style="text-align:center;color:#666;">{{ __('schedules.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
