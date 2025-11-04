<?php

namespace App\Console\Commands;

use App\Services\AnalyticsExportService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class SendAnalyticsDigest extends Command
{
    protected $signature = 'analytics:send-digest';

    protected $description = 'Generate the analytics CSV export and notify managers.';

    public function handle(AnalyticsExportService $exporter, NotificationService $notifier): int
    {
        $export = $exporter->generateTicketExport();
        $csv = $exporter->toCsv($export);

        $fileName = 'exports/analytics-' . Carbon::now()->format('Y-m-d_His') . '.csv';
        Storage::disk('local')->put($fileName, $csv);

        $notifier->analyticsDigestGenerated($fileName);

        $this->info('Analytics digest generated: ' . storage_path('app/' . $fileName));

        return Command::SUCCESS;
    }
}
