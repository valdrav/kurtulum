<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['assignee', 'creator']);

        if ($request->view === 'kanban') {
            $columns = [
                'pending' => Task::with(['assignee'])->where('status', 'pending')->orderBy('due_date')->get(),
                'in_progress' => Task::with(['assignee'])->where('status', 'in_progress')->orderBy('due_date')->get(),
                'completed' => Task::with(['assignee'])->where('status', 'completed')->latest('completed_at')->limit(20)->get(),
            ];
            $users = User::orderBy('name')->get(['id', 'name']);

            return view('tasks.kanban', compact('columns', 'users'));
        }

        $tasks = $query
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($request->assigned_to, fn ($q, $a) => $q->where('assigned_to', $a))
            ->when($request->mine, fn ($q) => $q->where('assigned_to', auth()->id()))
            ->latest()
            ->paginate(20);

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('tasks.index', compact('tasks', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'due_date' => 'nullable|date',
            'reminder_at' => 'nullable|date',
            'estimated_hours' => 'nullable|integer|min:1|max:999',
            'assigned_to' => 'nullable|exists:users,id',
            'labels' => 'nullable|string',
            'checklist' => 'nullable|array',
            'checklist.*.text' => 'required|string|max:255',
            'checklist.*.done' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['labels'] = $this->parseLabels($validated['labels'] ?? null);

        Task::create($validated);

        return back()->with('success', __('messages.created'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'due_date' => 'nullable|date',
            'reminder_at' => 'nullable|date',
            'estimated_hours' => 'nullable|integer|min:1|max:999',
            'assigned_to' => 'nullable|exists:users,id',
            'labels' => 'nullable|string',
            'checklist' => 'nullable|array',
        ]);

        if ($validated['status'] === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        }

        $validated['labels'] = $this->parseLabels($validated['labels'] ?? null);

        $task->update($validated);

        return back()->with('success', __('messages.updated'));
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return back()->with('success', __('messages.deleted'));
    }

    public function calendar()
    {
        $events = CalendarEvent::where('start_at', '>=', now()->startOfMonth())
            ->where('start_at', '<=', now()->endOfMonth()->addMonth())
            ->get();

        $tasks = Task::whereNotNull('due_date')
            ->where('status', '!=', 'completed')
            ->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()->addMonth()])
            ->get();

        return view('tasks.calendar', compact('events', 'tasks'));
    }

    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after:start_at',
            'all_day' => 'boolean',
            'type' => 'required|in:meeting,deadline,shipment,payment,other',
            'color' => 'nullable|string|max:20',
        ]);

        $validated['user_id'] = auth()->id();
        CalendarEvent::create($validated);

        return back()->with('success', __('messages.created'));
    }

    protected function parseLabels(?string $raw): ?array
    {
        if (! $raw) {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
