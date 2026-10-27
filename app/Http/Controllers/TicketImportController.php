<?php

namespace App\Http\Controllers;

use App\Services\TicketImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Process uploaded CSV file.
     */
    public function import(Request $request): View|RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'], // Up to 20MB
        ]);

        $result = $this->importService->import($request->file('csv_file'), Auth::user());

        return view('pages.tickets.import', [
            'title' => 'Import Results',
            'report' => $result,
        ]);
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
