<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = Document::with(['documentable', 'uploader'])
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->when($request->folder, fn ($q, $f) => $q->where('folder', $f))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('original_name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(24);

        $folders = Document::whereNotNull('folder')->distinct()->pluck('folder');
        $categories = ['invoice', 'packing_list', 'bl', 'awb', 'cmr', 'certificate', 'contract', 'customs', 'other'];

        return view('documents.index', compact('documents', 'folders', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png,gif,webp',
            'category' => 'required|in:invoice,packing_list,bl,awb,cmr,certificate,contract,customs,other',
            'documentable_type' => 'nullable|string',
            'documentable_id' => 'nullable|integer',
            'description' => 'nullable|string',
            'folder' => 'nullable|string|max:100',
            'tags' => 'nullable|string',
            'is_confidential' => 'boolean',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents/' . date('Y/m'), 'local');

        Document::create([
            'name' => Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension(),
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'category' => $validated['category'],
            'documentable_type' => $validated['documentable_type'] ?? User::class,
            'documentable_id' => $validated['documentable_id'] ?? auth()->id(),
            'uploaded_by' => auth()->id(),
            'description' => $validated['description'] ?? null,
            'folder' => $validated['folder'] ?? null,
            'tags' => $validated['tags']
                ? array_values(array_filter(array_map('trim', explode(',', $validated['tags']))))
                : null,
            'is_confidential' => $request->boolean('is_confidential'),
        ]);

        return back()->with('success', __('messages.uploaded'));
    }

    public function download(Document $document)
    {
        if ($document->is_confidential && !auth()->user()->hasRole(['super-admin', 'admin'])) {
            abort(403);
        }

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function destroy(Document $document)
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
        return back()->with('success', __('messages.deleted'));
    }

    public function backup()
    {
        $backupDir = storage_path('app/backups/' . date('Y-m-d_His'));
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $docs = Document::all();
        foreach ($docs as $doc) {
            if (Storage::disk($doc->disk)->exists($doc->path)) {
                $dest = $backupDir . '/' . basename($doc->path);
                copy(Storage::disk($doc->disk)->path($doc->path), $dest);
            }
        }

        return back()->with('success', __('documents.backup_created'));
    }
}
