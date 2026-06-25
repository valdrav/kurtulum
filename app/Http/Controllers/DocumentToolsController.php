<?php

namespace App\Http\Controllers;

use App\Services\DocumentTools\DocumentToolService;
use App\Services\DocumentTools\OfficeBridgeService;
use Illuminate\Http\Request;
use RuntimeException;

class DocumentToolsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:documents.view')->only(['index']);
        $this->middleware('permission:documents.create')->only([
            'mergePdf', 'splitPdf', 'pdfToWord', 'wordToPdf', 'imagesToPdf',
            'pdfExtractText', 'createExcel', 'csvToExcel', 'excelToCsv',
        ]);
    }

    public function index(OfficeBridgeService $office)
    {
        return view('documents.tools.index', [
            'officeAvailable' => $office->isAvailable(),
        ]);
    }

    public function mergePdf(Request $request, DocumentToolService $tools)
    {
        $request->validate([
            'files' => 'required|array|min:2|max:20',
            'files.*' => 'required|file|mimes:pdf|max:20480',
            'filename' => 'nullable|string|max:120',
        ]);

        try {
            return $tools->mergePdfs(
                $request->file('files'),
                ($request->filename ?: 'birlesik') . '.pdf'
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['tool' => $e->getMessage()]);
        }
    }

    public function splitPdf(Request $request, DocumentToolService $tools)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:20480',
            'mode' => 'required|in:pages,range',
            'range' => 'nullable|string|max:20',
        ]);

        try {
            return $tools->splitPdf($request->file('file'), $request->mode, $request->range);
        } catch (RuntimeException $e) {
            return back()->withErrors(['tool' => $e->getMessage()]);
        }
    }

    public function pdfToWord(Request $request, DocumentToolService $tools)
    {
        $request->validate(['file' => 'required|file|mimes:pdf|max:20480']);

        try {
            return $tools->pdfToWord($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['tool' => $e->getMessage()]);
        }
    }

    public function wordToPdf(Request $request, DocumentToolService $tools)
    {
        $request->validate(['file' => 'required|file|mimes:doc,docx|max:20480']);

        try {
            return $tools->wordToPdf($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['tool' => $e->getMessage()]);
        }
    }

    public function imagesToPdf(Request $request, DocumentToolService $tools)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:30',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:10240',
        ]);

        try {
            return $tools->imagesToPdf($request->file('files'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['tool' => $e->getMessage()]);
        }
    }

    public function pdfExtractText(Request $request, DocumentToolService $tools)
    {
        $request->validate(['file' => 'required|file|mimes:pdf|max:20480']);

        try {
            return $tools->pdfExtractText($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['tool' => $e->getMessage()]);
        }
    }

    public function createExcel(Request $request, DocumentToolService $tools)
    {
        $request->validate([
            'sheet_name' => 'nullable|string|max:60',
            'rows' => 'required|string|max:500000',
        ]);

        try {
            return $tools->createExcel($request->sheet_name ?? 'Sayfa1', $request->rows);
        } catch (RuntimeException $e) {
            return back()->withErrors(['tool' => $e->getMessage()]);
        }
    }

    public function csvToExcel(Request $request, DocumentToolService $tools)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:10240']);

        try {
            return $tools->csvToExcel($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['tool' => $e->getMessage()]);
        }
    }

    public function excelToCsv(Request $request, DocumentToolService $tools)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:20480']);

        try {
            return $tools->excelToCsv($request->file('file'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['tool' => $e->getMessage()]);
        }
    }
}
