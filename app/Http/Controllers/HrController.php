<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeHrDetail;
use App\Models\EmployeeHrDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:patron|super-admin');
    }

    public function index(Request $request)
    {
        $employees = Employee::with(['department', 'hrDetail'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('employee_code', 'like', "%{$s}%");
            }))
            ->when($request->department_id, fn ($q, $d) => $q->where('department_id', $d))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('hr.index', compact('employees', 'departments'));
    }

    public function create()
    {
        return view('hr.form', [
            'employee' => new Employee(['status' => 'active']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'hrDetail' => new EmployeeHrDetail(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEmployee($request);

        DB::transaction(function () use ($validated, $request) {
            $employee = Employee::create($validated);
            $this->syncHrDetail($employee, $request);
        });

        return redirect()->route('hr.index')->with('success', __('messages.created'));
    }

    public function show(Employee $employee)
    {
        $employee->load([
            'department',
            'hrDetail',
            'hrDocuments.uploader',
            'compensations.creator',
        ]);

        return view('hr.show', [
            'employee' => $employee,
            'hrDetail' => $employee->hrDetail ?? new EmployeeHrDetail(),
            'documentCategories' => $this->documentCategories(),
            'compensationTypes' => $this->compensationTypes(),
            'cvData' => $this->normalizeCvData($employee->hrDetail?->cv_data),
        ]);
    }

    public function edit(Employee $employee)
    {
        $employee->load('hrDetail');

        return view('hr.form', [
            'employee' => $employee,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'hrDetail' => $employee->hrDetail ?? new EmployeeHrDetail(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $this->validateEmployee($request, $employee);

        DB::transaction(function () use ($employee, $validated, $request) {
            $employee->update($validated);
            $this->syncHrDetail($employee, $request);
        });

        return redirect()->route('hr.show', $employee)->with('success', __('messages.updated'));
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('hr.index')->with('success', __('messages.deleted'));
    }

    public function storeDocument(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'category' => 'required|in:'.implode(',', array_keys($this->documentCategories())),
            'title' => 'required|string|max:200',
            'file' => 'required|file|max:10240',
            'document_date' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ]);

        $file = $request->file('file');
        $path = $file->store("hr/{$employee->uuid}", 'local');

        EmployeeHrDocument::create([
            'employee_id' => $employee->id,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'path' => $path,
            'disk' => 'local',
            'size' => $file->getSize() ?: 0,
            'document_date' => $validated['document_date'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', __('hr.document_uploaded'));
    }

    public function destroyDocument(Employee $employee, EmployeeHrDocument $document)
    {
        abort_unless($document->employee_id === $employee->id, 404);

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return back()->with('success', __('messages.deleted'));
    }

    public function downloadDocument(Employee $employee, EmployeeHrDocument $document): StreamedResponse
    {
        abort_unless($document->employee_id === $employee->id, 404);

        return Storage::disk($document->disk)->download($document->path, $document->title);
    }

    public function storeCompensation(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'type' => 'required|in:'.implode(',', array_keys($this->compensationTypes())),
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_date' => 'required|date',
            'period' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);

        EmployeeCompensation::create([
            ...$validated,
            'employee_id' => $employee->id,
            'created_by' => auth()->id(),
        ]);

        if ($validated['type'] === 'salary' && $employee->hrDetail) {
            $employee->hrDetail->update([
                'base_salary' => $validated['amount'],
                'salary_currency' => $validated['currency'],
            ]);
        }

        return back()->with('success', __('hr.compensation_added'));
    }

    public function destroyCompensation(Employee $employee, EmployeeCompensation $compensation)
    {
        abort_unless($compensation->employee_id === $employee->id, 404);
        $compensation->delete();

        return back()->with('success', __('messages.deleted'));
    }

    public function updateCv(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'summary' => 'nullable|string|max:5000',
            'skills' => 'nullable|string|max:2000',
            'experiences' => 'nullable|array',
            'experiences.*.company' => 'nullable|string|max:200',
            'experiences.*.position' => 'nullable|string|max:200',
            'experiences.*.start' => 'nullable|string|max:20',
            'experiences.*.end' => 'nullable|string|max:20',
            'experiences.*.description' => 'nullable|string|max:2000',
            'education' => 'nullable|array',
            'education.*.school' => 'nullable|string|max:200',
            'education.*.degree' => 'nullable|string|max:200',
            'education.*.year' => 'nullable|string|max:20',
            'languages' => 'nullable|array',
            'languages.*.name' => 'nullable|string|max:100',
            'languages.*.level' => 'nullable|string|max:50',
        ]);

        $cvData = [
            'summary' => $validated['summary'] ?? '',
            'skills' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $validated['skills'] ?? '')))),
            'experiences' => collect($validated['experiences'] ?? [])->filter(fn ($row) => trim($row['company'] ?? '') || trim($row['position'] ?? ''))->values()->all(),
            'education' => collect($validated['education'] ?? [])->filter(fn ($row) => trim($row['school'] ?? ''))->values()->all(),
            'languages' => collect($validated['languages'] ?? [])->filter(fn ($row) => trim($row['name'] ?? ''))->values()->all(),
        ];

        $detail = $employee->hrDetail()->firstOrCreate(['employee_id' => $employee->id]);
        $detail->update(['cv_data' => $cvData]);

        return back()->with('success', __('hr.cv_saved'));
    }

    public function cvPrint(Employee $employee)
    {
        $employee->load(['department', 'hrDetail']);
        $cvData = $this->normalizeCvData($employee->hrDetail?->cv_data);

        return view('hr.cv-print', compact('employee', 'cvData'));
    }

    protected function syncHrDetail(Employee $employee, Request $request): void
    {
        $hr = $request->validate([
            'birth_date' => 'nullable|date',
            'national_id' => 'nullable|string|max:20',
            'birth_place' => 'nullable|string|max:100',
            'marital_status' => 'nullable|in:single,married,other',
            'address' => 'nullable|string|max:2000',
            'emergency_contact' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:100',
            'iban' => 'nullable|string|max:34',
            'base_salary' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|size:3',
            'notes' => 'nullable|string|max:5000',
        ]);

        $employee->hrDetail()->updateOrCreate(
            ['employee_id' => $employee->id],
            $hr
        );
    }

    protected function validateEmployee(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'employee_code' => 'required|string|unique:employees,employee_code,'.($employee?->id ?? 'NULL'),
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
            'hire_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,on_leave',
        ]);
    }

    /** @return array<string, string> */
    protected function documentCategories(): array
    {
        return [
            'id_card' => __('hr.doc_id_card'),
            'contract' => __('hr.doc_contract'),
            'health' => __('hr.doc_health'),
            'diploma' => __('hr.doc_diploma'),
            'other' => __('hr.doc_other'),
        ];
    }

    /** @return array<string, string> */
    protected function compensationTypes(): array
    {
        return [
            'salary' => __('hr.comp_salary'),
            'bonus' => __('hr.comp_bonus'),
            'advance' => __('hr.comp_advance'),
            'deduction' => __('hr.comp_deduction'),
            'other' => __('hr.comp_other'),
        ];
    }

    /** @param  array<string, mixed>|null  $data */
    protected function normalizeCvData(?array $data): array
    {
        $data ??= [];

        return [
            'summary' => $data['summary'] ?? '',
            'skills' => $data['skills'] ?? [],
            'experiences' => $data['experiences'] ?? [['company' => '', 'position' => '', 'start' => '', 'end' => '', 'description' => '']],
            'education' => $data['education'] ?? [['school' => '', 'degree' => '', 'year' => '']],
            'languages' => $data['languages'] ?? [['name' => '', 'level' => '']],
        ];
    }
}
