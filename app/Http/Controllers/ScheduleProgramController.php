<?php

namespace App\Http\Controllers;

use App\Models\ScheduleEntry;
use App\Models\ScheduleProgram;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleProgramController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $programs = ScheduleProgram::withCount('entries')
            ->where('year', $year)
            ->when($month, fn ($q) => $q->where('month', $month))
            ->orderBy('month')
            ->orderBy('week_number')
            ->get();

        return view('schedules.index', [
            'programs' => $programs,
            'year' => $year,
            'month' => $month,
            'years' => range(now()->year - 2, now()->year + 2),
        ]);
    }

    public function create(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $weekNumber = (int) $request->input('week_number', 1);
        [$weekStart, $weekEnd] = $this->defaultWeekRange($year, $month, $weekNumber);

        return view('schedules.form', [
            'program' => new ScheduleProgram([
                'year' => $year,
                'month' => $month,
                'week_number' => $weekNumber,
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProgram($request);

        $program = ScheduleProgram::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('schedules.show', $program)->with('success', __('messages.created'));
    }

    public function show(ScheduleProgram $schedule)
    {
        $schedule->load('entries');
        $columns = config('ticari.schedule_columns', []);

        return view('schedules.show', compact('schedule', 'columns'));
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
        $schedule->update($validated);

        return redirect()->route('schedules.show', $schedule)->with('success', __('messages.updated'));
    }

    public function destroy(ScheduleProgram $schedule)
    {
        $schedule->delete();

        return redirect()->route('schedules.index', [
            'year' => $schedule->year,
            'month' => $schedule->month,
        ])->with('success', __('messages.deleted'));
    }

    public function export(ScheduleProgram $schedule)
    {
        $schedule->load('entries');
        $columns = config('ticari.schedule_columns', []);

        return view('schedules.export', compact('schedule', 'columns'));
    }

    protected function updateEntries(Request $request, ScheduleProgram $schedule)
    {
        $columns = array_keys(config('ticari.schedule_columns', []));
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
        return $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'week_number' => 'required|integer|min:1|max:53|unique:schedule_programs,week_number,'.($program?->id ?? 'NULL').',id,year,'.$request->input('year').',month,'.$request->input('month'),
            'week_start' => 'required|date',
            'week_end' => 'required|date|after_or_equal:week_start',
            'title' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    /** @return array{0: string, 1: string} */
    protected function defaultWeekRange(int $year, int $month, int $weekNumber): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $weekStart = $start->copy()->addWeeks(max(0, $weekNumber - 1))->startOfWeek(Carbon::MONDAY);
        if ($weekStart->month !== $month) {
            $weekStart = $start->copy();
        }
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        if ($weekEnd->month !== $month) {
            $weekEnd = $start->copy()->endOfMonth();
        }

        return [$weekStart->toDateString(), $weekEnd->toDateString()];
    }
}
