<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    protected int $maxFileKb = 51200;

    public function __construct(
        protected DocumentStorageService $storage,
    ) {}

    public function index(Request $request)
    {
        $this->autoMaintainStorage();

        $search = $request->input('search');
        $folders = $this->folderSummaries($search);

        return view('documents.index', compact('folders', 'search'));
    }

    public function folder(Request $request, string $folder)
    {
        $this->autoMaintainStorage();

        $folderName = $folder === '__default' ? '' : $folder;
        $displayName = $folderName !== '' ? $folderName : __('documents.default_folder');

        $documents = Document::with(['uploader'])
            ->when($folderName === '', fn ($q) => $q->where(fn ($q) => $q->whereNull('folder')->orWhere('folder', '')))
            ->when($folderName !== '', fn ($q) => $q->where('folder', $folderName))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('original_name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->when($request->input('sort') === 'name', fn ($q) => $q->orderBy('original_name'))
            ->when($request->input('sort') === 'size', fn ($q) => $q->orderByDesc('size'))
            ->when(! in_array($request->input('sort'), ['name', 'size'], true), fn ($q) => $q->latest())
            ->paginate(48);

        $folders = $this->folderSummaries();

        return view('documents.folder', compact('documents', 'folderName', 'displayName', 'folders'));
    }

    protected function autoMaintainStorage(): void
    {
        $cacheKey = 'documents:auto_maintained_at';

        if (Cache::has($cacheKey)) {
            return;
        }

        $this->storage->purgeOrphans();
        $this->storage->purgeTrashedRecords();
        Cache::put($cacheKey, now(), now()->addHours(12));
    }

    protected function folderSummaries(?string $search = null)
    {
        return Document::query()
            ->selectRaw("COALESCE(NULLIF(folder, ''), ?) as folder_name", [__('documents.default_folder')])
            ->selectRaw('COUNT(*) as file_count')
            ->when($search, fn ($q, $s) => $q->where('folder', 'like', "%{$s}%"))
            ->groupBy('folder_name')
            ->orderBy('folder_name')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'files' => 'required|array|min:1|max:50',
            'folder' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'documentable_type' => 'nullable|string',
            'documentable_id' => 'nullable|integer',
        ]);

        [$files, $warnings] = $this->collectValidUploads($request);

        $folder = trim($validated['folder']);
        $count = 0;

        foreach ($files as $file) {
            $path = $file->store('documents/' . date('Y/m'), 'local');

            Document::create([
                'name' => Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension(),
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'disk' => 'local',
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
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
        $message = __('documents.files_uploaded', ['count' => $count]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'count' => $count,
                'redirect' => $redirect,
                'message' => $message,
                'warnings' => $warnings,
            ]);
        }

        $flash = redirect($redirect)->with('success', $message);

        if ($warnings !== []) {
            $flash->with('warning', implode(' ', $warnings));
        }

        return $flash;
    }

    /**
     * @return array{0: array<int, \Illuminate\Http\UploadedFile>, 1: array<int, string>}
     */
    protected function collectValidUploads(Request $request): array
    {
        $raw = $request->file('files');

        if ($raw === null) {
            throw ValidationException::withMessages([
                'files' => [__('documents.upload_no_files')],
            ]);
        }

        $candidates = is_array($raw) ? $raw : [$raw];
        $valid = [];
        $errors = [];

        foreach ($candidates as $index => $file) {
            if ($file === null) {
                continue;
            }

            $label = $file->getClientOriginalName() ?: __('documents.upload_file_number', ['n' => $index + 1]);

            if (! $file->isValid()) {
                $errors[] = $label . ': ' . $this->uploadErrorMessage($file->getError());
                continue;
            }

            $ext = strtolower($file->getClientOriginalExtension() ?: '');

            if ($this->storage->isBlockedExtension($ext)) {
                $errors[] = $label . ': ' . __('documents.upload_blocked_extension');
                continue;
            }

            if ($file->getSize() > $this->maxFileKb * 1024) {
                $errors[] = $label . ': ' . __('documents.upload_too_large', ['max' => $this->maxFileKb / 1024]);
                continue;
            }

            $valid[] = $file;
        }

        if ($valid === []) {
            throw ValidationException::withMessages([
                'files' => $errors !== [] ? $errors : [__('documents.upload_failed_all')],
            ]);
        }

        return [$valid, $errors];
    }

    protected function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __('documents.upload_php_size'),
            UPLOAD_ERR_PARTIAL => __('documents.upload_partial'),
            UPLOAD_ERR_NO_FILE => __('documents.upload_no_file'),
            default => __('documents.upload_failed'),
        };
    }

    public function preview(Document $document)
    {
        if ($document->is_confidential && ! auth()->user()->hasRole(['super-admin', 'admin'])) {
            abort(403);
        }

        if (! Storage::disk($document->disk ?: 'local')->exists($document->path)) {
            abort(404);
        }

        $path = Storage::disk($document->disk ?: 'local')->path($document->path);
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

        return Storage::disk($document->disk ?: 'local')->download($document->path, $document->original_name);
    }

    public function destroy(Document $document)
    {
        $folder = $document->folder;
        $document->forceDelete();

        $redirect = $folder
            ? route('documents.folder', $folder)
            : route('documents.folder', '__default');

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.deleted'),
            ]);
        }

        return redirect($redirect)->with('success', __('messages.deleted'));
    }

    public function destroyFolder(string $folder)
    {
        $folderName = $folder === '__default' ? '' : $folder;

        $documents = Document::query()
            ->when($folderName === '', fn ($q) => $q->where(fn ($q) => $q->whereNull('folder')->orWhere('folder', '')))
            ->when($folderName !== '', fn ($q) => $q->where('folder', $folderName))
            ->get();

        if ($documents->isEmpty()) {
            return redirect()->route('documents.index')->with('warning', __('documents.folder_empty'));
        }

        $count = $documents->count();

        foreach ($documents as $document) {
            $document->forceDelete();
        }

        return redirect()->route('documents.index')->with('success', __('documents.folder_deleted', ['count' => $count]));
    }

    public function backup()
    {
        $backupDir = storage_path('app/backups/' . date('Y-m-d_His'));
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $docs = Document::all();
        foreach ($docs as $doc) {
            if (Storage::disk($doc->disk ?: 'local')->exists($doc->path)) {
                $dest = $backupDir . '/' . basename($doc->path);
                copy(Storage::disk($doc->disk ?: 'local')->path($doc->path), $dest);
            }
        }

        return back()->with('success', __('documents.backup_created'));
    }
}
