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
        $search = $request->input('search');

        $folders = Document::query()
            ->selectRaw("COALESCE(NULLIF(folder, ''), ?) as folder_name", [__('documents.default_folder')])
            ->selectRaw('COUNT(*) as file_count')
            ->selectRaw('SUM(size) as total_size')
            ->when($search, fn ($q, $s) => $q->where('folder', 'like', "%{$s}%"))
            ->groupBy('folder_name')
            ->orderBy('folder_name')
            ->get();

        return view('documents.index', compact('folders', 'search'));
    }

    public function folder(Request $request, string $folder)
    {
        $folderName = $folder === '__default' ? '' : $folder;
        $displayName = $folderName !== '' ? $folderName : __('documents.default_folder');

        $documents = Document::with(['uploader'])
            ->when($folderName === '', fn ($q) => $q->where(fn ($q) => $q->whereNull('folder')->orWhere('folder', '')))
            ->when($folderName !== '', fn ($q) => $q->where('folder', $folderName))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('original_name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(24);

        $allFolders = Document::query()
            ->selectRaw("COALESCE(NULLIF(folder, ''), ?) as folder_name", [__('documents.default_folder')])
            ->distinct()
            ->pluck('folder_name');

        return view('documents.folder', compact('documents', 'folderName', 'displayName', 'allFolders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'files' => 'required|array|min:1|max:30',
            'files.*' => 'required|file|max:20480|mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png,gif,webp,csv,txt,zip',
            'folder' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'documentable_type' => 'nullable|string',
            'documentable_id' => 'nullable|integer',
        ]);

        $folder = trim($validated['folder']);
        $count = 0;

        foreach ($request->file('files') as $file) {
            $path = $file->store('documents/' . date('Y/m'), 'local');

            Document::create([
                'name' => Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension(),
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'category' => 'other',
                'documentable_type' => $validated['documentable_type'] ?? User::class,
                'documentable_id' => $validated['documentable_id'] ?? auth()->id(),
                'uploaded_by' => auth()->id(),
                'description' => $validated['description'] ?? null,
                'folder' => $folder,
                'tags' => null,
                'is_confidential' => false,
            ]);
            $count++;
        }

        $redirect = route('documents.folder', $folder !== '' ? $folder : '__default');

        return redirect($redirect)->with('success', __('documents.files_uploaded', ['count' => $count]));
    }

    public function preview(Document $document)
    {
        if ($document->is_confidential && ! auth()->user()->hasRole(['super-admin', 'admin'])) {
            abort(403);
        }

        if (! Storage::disk($document->disk)->exists($document->path)) {
            abort(404);
        }

        $path = Storage::disk($document->disk)->path($document->path);
        $mime = $document->mime_type ?: 'application/octet-stream';

        if (str_contains($mime, 'pdf') || str_contains($mime, 'image')) {
            return response()->file($path, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . $document->original_name . '"',
            ]);
        }

        return $this->download($document);
    }

    public function download(Document $document)
    {
        if ($document->is_confidential && ! auth()->user()->hasRole(['super-admin', 'admin'])) {
            abort(403);
        }

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function destroy(Document $document)
    {
        Storage::disk($document->disk)->delete($document->path);
        $folder = $document->folder;
        $document->delete();

        $redirect = $folder
            ? route('documents.folder', $folder)
            : route('documents.folder', '__default');

        return redirect($redirect)->with('success', __('messages.deleted'));
    }

    public function backup()
    {
        $backupDir = storage_path('app/backups/' . date('Y-m-d_His'));
        if (! is_dir($backupDir)) {
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
