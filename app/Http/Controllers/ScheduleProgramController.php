<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEntry;
use App\Models\ScheduleProgram;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScheduleProgramController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        $programs = ScheduleProgram::withCount('entries')
            ->where('year', $year)
            ->orderBy('month')
            ->get();

        return view('schedules.index', [
            'programs' => $programs,
            'year' => $year,
            'years' => range(now()->year - 2, now()->year + 2),
        ]);
    }

    public function create(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        [$periodStart, $periodEnd] = $this->monthBounds($year, $month);

        return view('schedules.form', [
            'program' => new ScheduleProgram([
                'year' => $year,
                'month' => $month,
                'week_start' => $periodStart,
                'week_end' => $periodEnd,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProgram($request);
        [$periodStart, $periodEnd] = $this->monthBounds($validated['year'], $validated['month']);

        $existing = ScheduleProgram::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->first();

        if ($existing) {
            return redirect()->route('schedules.show', $existing)
                ->with('warning', __('schedules.month_exists'));
        }

        $program = ScheduleProgram::create([
            ...$validated,
            'week_start' => $periodStart,
            'week_end' => $periodEnd,
            'week_number' => 0,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('schedules.show', $program)->with('success', __('messages.created'));
    }

    public function show(ScheduleProgram $schedule)
    {
        $schedule->load('entries');
        $columns = $this->columns();

        $entries = $this->entriesForEditor($schedule);

        return view('schedules.show', compact('schedule', 'columns', 'entries'));
    }

    public function edit(ScheduleProgram $schedule)
    {
        return view('schedules.form', ['program' => $schedule]);
    }

    public function update(Request $request, ScheduleProgram $schedule)
    {
        if (is_array($request->input('entries'))) {
            return $this->updateEntries($request, $schedule);
        }

        $validated = $this->validateProgram($request, $schedule);
        [$periodStart, $periodEnd] = $this->monthBounds($validated['year'], $validated['month']);

        $schedule->update([
            ...$validated,
            'week_start' => $periodStart,
            'week_end' => $periodEnd,
            'week_number' => 0,
        ]);

        return redirect()->route('schedules.show', $schedule)->with('success', __('messages.updated'));
    }

    public function destroy(ScheduleProgram $schedule)
    {
        $year = $schedule->year;
        $schedule->delete();

        return redirect()->route('schedules.index', ['year' => $year])
            ->with('success', __('messages.deleted'));
    }

    public function exportForm(ScheduleProgram $schedule)
    {
        $columns = $this->columns();

        return view('schedules.export-form', [
            'schedule' => $schedule,
            'columns' => $columns,
            'defaultFrom' => $schedule->week_start->format('Y-m-d'),
            'defaultTo' => $schedule->week_end->format('Y-m-d'),
        ]);
    }

    public function export(Request $request, ScheduleProgram $schedule)
    {
        $columns = $this->columns();
        $columnKeys = array_keys($columns);

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'columns' => 'nullable|array',
            'columns.*' => 'in:'.implode(',', $columnKeys),
        ]);

        $selected = collect($validated['columns'] ?? $columnKeys)
            ->filter(fn ($key) => isset($columns[$key]))
            ->values()
            ->all();

        if ($selected === []) {
            $selected = $columnKeys;
        }

        $visibleColumns = collect($columns)->only($selected)->all();

        $entries = $schedule->entries()
            ->whereBetween('entry_date', [$validated['date_from'], $validated['date_to']])
            ->orderBy('entry_date')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (ScheduleEntry $entry) => $this->entryHasExportableContent($entry, $selected))
            ->values();

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = Carbon::parse($validated['date_to']);

        return view('schedules.export', [
            'schedule' => $schedule,
            'columns' => $visibleColumns,
            'entries' => $entries,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    protected function updateEntries(Request $request, ScheduleProgram $schedule): \Illuminate\Http\RedirectResponse
    {
        $columns = array_keys($this->columns());
        $rows = $request->input('entries', []);

        DB::transaction(function () use ($schedule, $rows, $columns) {
            $schedule->entries()->delete();

            foreach ($rows as $index => $row) {
                $data = [];
                $hasContent = false;

                foreach ($columns as $key) {
                    if ($key === 'entry_date') {
                        continue;
                    }
                    $value = trim((string) ($row[$key] ?? ''));
                    $data[$key] = $value;
                    if ($value !== '') {
                        $hasContent = true;
                    }
                }

                $entryDate = $row['entry_date'] ?? null;
                if (! $hasContent && empty($entryDate)) {
                    continue;
                }

                ScheduleEntry::create([
                    'schedule_program_id' => $schedule->id,
                    'entry_date' => $entryDate ?: null,
                    'sort_order' => $index,
                    'data' => $data,
                ]);
            }
        });

        return redirect()->route('schedules.show', $schedule)->with('success', __('schedules.saved'));
    }

    protected function validateProgram(Request $request, ?ScheduleProgram $program = null): array
    {
        $uniqueRule = 'unique:schedule_programs,month,'.($program?->id ?? 'NULL').',id,year,'.$request->input('year');

        return $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12|'.$uniqueRule,
            'title' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    /** @return array{0: string, 1: string} */
    protected function monthBounds(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [$start->toDateString(), $end->toDateString()];
    }

    /** @return array<string, array{label: string, type: string, width: string}> */
    protected function columns(): array
    {
        $columns = config('ticari.schedule_columns', []);

        foreach ($columns as $key => &$col) {
            $label = __("schedules.columns.{$key}");
            if ($label !== "schedules.columns.{$key}") {
                $col['label'] = $label;
            }
        }

        return $columns;
    }

    /** @return Collection<int, ScheduleEntry> */
    protected function entriesForEditor(ScheduleProgram $schedule): Collection
    {
        if ($schedule->entries->isNotEmpty()) {
            return $schedule->entries;
        }

        $days = Carbon::create($schedule->year, $schedule->month, 1)->daysInMonth;
        $rows = collect();

        for ($day = 1; $day <= $days; $day++) {
            $rows->push(new ScheduleEntry([
                'entry_date' => Carbon::create($schedule->year, $schedule->month, $day),
                'sort_order' => $day - 1,
                'data' => [],
            ]));
        }

        return $rows;
    }

    protected function entryHasExportableContent(ScheduleEntry $entry, array $columnKeys): bool
    {
        foreach ($columnKeys as $key) {
            if ($key === 'entry_date') {
                continue;
            }
            if (trim($entry->rawValue($key)) !== '') {
                return true;
            }
        }

        return false;
    }
}
