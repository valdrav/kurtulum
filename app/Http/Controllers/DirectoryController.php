<?php

namespace App\Http\Controllers;

use App\Models\DirectoryContact;
use App\Services\CsvExportService;
use App\Services\DirectoryImportService;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:directory.view')->only(['index', 'export']);
        $this->middleware('permission:directory.create')->only(['create', 'store']);
        $this->middleware('permission:directory.edit')->only(['edit', 'update']);
        $this->middleware('permission:directory.delete')->only(['destroy']);
        $this->middleware('permission:directory.import')->only(['importForm', 'import']);
    }

    public function index(Request $request)
    {
        $contacts = DirectoryContact::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(30)
            ->withQueryString();

        return view('directory.index', compact('contacts'));
    }

    public function create()
    {
        return view('directory.form', ['contact' => new DirectoryContact()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateContact($request);
        DirectoryContact::create([
            ...$validated,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('directory.index')->with('success', __('messages.created'));
    }

    public function edit(DirectoryContact $directory)
    {
        return view('directory.form', ['contact' => $directory]);
    }

    public function update(Request $request, DirectoryContact $directory)
    {
        $directory->update([
            ...$this->validateContact($request),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('directory.index')->with('success', __('messages.updated'));
    }

    public function destroy(DirectoryContact $directory)
    {
        $directory->delete();

        return redirect()->route('directory.index')->with('success', __('messages.deleted'));
    }

    public function importForm()
    {
        return view('directory.import');
    }

    public function import(Request $request, DirectoryImportService $importer)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $result = $importer->importFromCsv(
            $request->file('file')->getRealPath(),
            (int) auth()->id()
        );

        $message = __('directory.import_done', ['count' => $result['imported']]);
        if ($result['skipped'] > 0) {
            $message .= ' '.__('directory.import_skipped', ['count' => $result['skipped']]);
        }

        return redirect()->route('directory.index')
            ->with($result['imported'] > 0 ? 'success' : 'warning', $message)
            ->with('import_errors', $result['errors']);
    }

    public function export(CsvExportService $csv, Request $request)
    {
        $contacts = DirectoryContact::query()
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return $csv->download('rehber-'.now()->format('Y-m-d').'.csv', [
            'Ad', 'Soyad', 'Telefon', 'Açıklama',
        ], $contacts->map(fn (DirectoryContact $c) => [
            $c->first_name,
            $c->last_name,
            $c->phone,
            $c->description,
        ]));
    }

    protected function validateContact(Request $request): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'description' => 'nullable|string|max:5000',
        ]);
    }
}
