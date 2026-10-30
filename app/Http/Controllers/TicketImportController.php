<?php

namespace App\Http\Controllers;

use App\Services\TicketImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketImportController extends Controller
{
    public function __construct(
        protected TicketImportService $importService
    ) {}

    /**
     * Show CSV upload view.
     */
    public function show(): View
    {
        return view('pages.tickets.import', [
            'title' => 'Import Tickets from CSV',
        ]);
    }

    /**
     * Process uploaded CSV file with live progress tracking.
     */
    public function import(Request $request): View|RedirectResponse|JsonResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'], // Up to 20MB
            'import_id' => ['nullable', 'string', 'max:64'],
        ]);

        $importId = $request->input('import_id') ?: (string) Str::uuid();

        // Release PHP session file lock so concurrent live polling from the browser returns instantly
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $result = $this->importService->import(
            $request->file('csv_file'),
            Auth::user(),
            $importId
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'import_id' => $importId,
                'report' => $result,
            ]);
        }

        return view('pages.tickets.import', [
            'title' => 'Import Results',
            'report' => $result,
        ]);
    }

    /**
     * Retrieve live import progress for the given import ID.
     */
    public function progress(string $importId): JsonResponse
    {
        $data = Cache::get("import_progress_{$importId}");

        if (!$data) {
            return response()->json([
                'status' => 'waiting',
                'percentage' => 0,
                'processed' => 0,
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'message' => 'Waiting for stream initialization...',
            ]);
        }

        return response()->json($data);
    }

    /**
     * Download sample CSV template.
     */
    public function downloadSample(): StreamedResponse
    {
        $content = TicketImportService::generateSampleCsv();

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'sample_tickets_import.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
