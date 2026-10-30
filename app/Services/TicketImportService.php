<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TicketImportService
{
    /**
     * Import tickets from a CSV file with streaming memory efficiency and validation.
     *
     * @param UploadedFile|string $file
     * @param User $importedBy
     * @param string|null $importId Optional tracking ID for live progress reporting
     * @return array{total: int, imported: int, skipped: int, errors: array<string>}
     */
    public function import(UploadedFile|string $file, User $importedBy, ?string $importId = null): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Unable to read uploaded CSV file.'],
            ];
        }

        // Fast scan to count total lines for accurate live percentage calculation
        $totalLines = 0;
        $countHandle = fopen($filePath, 'r');
        if ($countHandle) {
            while (!feof($countHandle)) {
                if (fgets($countHandle) !== false) {
                    $totalLines++;
                }
            }
            fclose($countHandle);
            $totalLines = max(1, $totalLines - 1); // Exclude header row
        }

        if ($importId) {
            \Illuminate\Support\Facades\Cache::put("import_progress_{$importId}", [
                'total' => $totalLines,
                'processed' => 0,
                'imported' => 0,
                'skipped' => 0,
                'percentage' => 0,
                'status' => 'processing',
                'message' => 'Validating headers and preparing import...',
            ], 600);
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Failed to open CSV stream.'],
            ];
        }

        // Read and normalize headers
        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            fclose($handle);
            return [
                'total' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['The CSV file is empty.'],
            ];
        }

        $headers = array_map(function ($h) {
            return strtolower(trim(str_replace([' ', '_', '-'], '', (string) $h)));
        }, $rawHeaders);

        // Required header validation
        $requiredColumns = ['subject', 'description', 'priority', 'category', 'customeremail'];
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $headers, true)) {
                fclose($handle);
                return [
                    'total' => 0,
                    'imported' => 0,
                    'skipped' => 0,
                    'errors' => ["Missing required CSV column: '{$col}'. Expected headers: subject, description, priority, category, customer_email."],
                ];
            }
        }

        $subjectIdx = array_search('subject', $headers, true);
        $descIdx = array_search('description', $headers, true);
        $priorityIdx = array_search('priority', $headers, true);
        $categoryIdx = array_search('category', $headers, true);
        $emailIdx = array_search('customeremail', $headers, true);

        // Preload categories cache for speed
        $categoriesMap = Category::all()->keyBy(fn($c) => strtolower(trim($c->name)));
        $defaultCategory = Category::first();

        $total = 0;
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $seenInFile = [];
        $rowNumber = 1;

        $batch = [];
        $batchSize = 50;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $total++;

            $subject = trim($row[$subjectIdx] ?? '');
            $description = trim($row[$descIdx] ?? '');
            $priority = strtolower(trim($row[$priorityIdx] ?? 'medium'));
            $categoryName = strtolower(trim($row[$categoryIdx] ?? ''));
            $customerEmail = strtolower(trim($row[$emailIdx] ?? ''));

            // Row validation
            $validator = Validator::make([
                'subject' => $subject,
                'description' => $description,
                'priority' => $priority,
                'customer_email' => $customerEmail,
            ], [
                'subject' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'priority' => ['required', 'in:low,medium,high,critical'],
                'customer_email' => ['required', 'email'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(' ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            // Find or resolve category
            $category = $categoriesMap->get($categoryName) ?? $defaultCategory;
            if (!$category) {
                $category = Category::create([
                    'name' => ucwords($categoryName ?: 'General Support'),
                    'slug' => Str::slug($categoryName ?: 'general-support'),
                    'is_active' => true,
                ]);
                $categoriesMap->put($categoryName, $category);
            }

            // Find or create customer
            $customer = User::firstOrCreate(
                ['email' => $customerEmail],
                [
                    'name' => ucfirst(explode('@', $customerEmail)[0]),
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_CUSTOMER,
                    'is_active' => true,
                ]
            );

            // Duplicate detection: check within current file and database
            $seenKey = $customer->id . '|' . strtolower($subject);
            if (isset($seenInFile[$seenKey])) {
                $errors[] = "Row {$rowNumber}: Duplicate ticket skipped for '{$subject}' by {$customerEmail}.";
                $skipped++;
                continue;
            }

            $exists = Ticket::where('created_by', $customer->id)
                ->where('subject', $subject)
                ->exists();

            if ($exists) {
                $errors[] = "Row {$rowNumber}: Duplicate ticket skipped for '{$subject}' by {$customerEmail}.";
                $skipped++;
                continue;
            }

            $seenInFile[$seenKey] = true;

            $batch[] = [
                'subject' => $subject,
                'description' => $description,
                'priority' => $priority,
                'category_id' => $category->id,
                'created_by' => $customer->id,
            ];

            if (count($batch) >= $batchSize) {
                $this->processBatch($batch, $importedBy);
                $imported += count($batch);
                $batch = [];
            }

            // Live progress report every 5 rows or on last row
            if ($importId && ($total % 5 === 0 || $total === $totalLines)) {
                $pct = min(99, (int) round(($total / $totalLines) * 100));
                \Illuminate\Support\Facades\Cache::put("import_progress_{$importId}", [
                    'total' => $totalLines,
                    'processed' => $total,
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'percentage' => $pct,
                    'status' => 'processing',
                    'message' => "Processing row {$total} of {$totalLines} ({$pct}%)...",
                ], 600);
            }
        }

        if (!empty($batch)) {
            $this->processBatch($batch, $importedBy);
            $imported += count($batch);
        }

        fclose($handle);

        $report = [
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];

        if ($importId) {
            \Illuminate\Support\Facades\Cache::put("import_progress_{$importId}", [
                'total' => $total,
                'processed' => $total,
                'imported' => $imported,
                'skipped' => $skipped,
                'percentage' => 100,
                'status' => 'completed',
                'message' => 'Import completed successfully!',
                'report' => $report,
            ], 600);
        }

        return $report;
    }

    /**
     * Process and insert a batch inside a database transaction.
     *
     * @param array<array<string, mixed>> $batch
     * @param User $importedBy
     */
    protected function processBatch(array $batch, User $importedBy): void
    {
        DB::transaction(function () use ($batch, $importedBy) {
            foreach ($batch as $data) {
                $ticketNumber = TicketNumberService::generate();
                $slaDue = SlaCalculatorService::calculateDeadline($data['priority']);

                $ticket = Ticket::create([
                    'ticket_number' => $ticketNumber,
                    'subject' => $data['subject'],
                    'description' => $data['description'],
                    'priority' => $data['priority'],
                    'category_id' => $data['category_id'],
                    'status' => Ticket::STATUS_OPEN,
                    'created_by' => $data['created_by'],
                    'sla_due_at' => $slaDue,
                    'last_reply_at' => Carbon::now(),
                ]);

                TicketActivity::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $importedBy->id,
                    'type' => TicketActivity::TYPE_IMPORTED,
                    'description' => "Ticket imported from CSV by {$importedBy->name}",
                ]);
            }
        });
    }

    /**
     * Generate template CSV content with sample rows for download.
     */
    public static function generateSampleCsv(): string
    {
        $filePath = public_path('sample_tickets_import.csv');
        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }

        $headers = ['subject', 'description', 'priority', 'category', 'customer_email'];
        $rows = [
            ['Payment gateway timeout', 'Checkout throws 504 gateway timeout on webhook.', 'critical', 'Technical Support', 'client1@example.com'],
            ['Update invoice address', 'Please update the corporate billing address on our latest receipt.', 'medium', 'Billing & Invoicing', 'billing.contact@example.com'],
            ['Reset two-factor device', 'Lost recovery backup codes for mobile authenticator.', 'high', 'Account & Access', 'security.user@example.com'],
            ['Feature suggestion: dark mode export', 'We would love to export our monthly tickets in PDF format.', 'low', 'Feature Request', 'customer2@example.com'],
        ];

        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent ?: '';
    }
}
