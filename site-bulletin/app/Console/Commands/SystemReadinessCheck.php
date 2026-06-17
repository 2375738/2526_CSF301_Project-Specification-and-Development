<?php

namespace App\Console\Commands;

use App\Services\SystemReadinessService;
use Illuminate\Console\Command;

class SystemReadinessCheck extends Command
{
    protected $signature = 'site:readiness-check {--fail-on-warning : Return a failure code when warnings are present}';

    protected $description = 'Check production readiness and operational health for Site Bulletin.';

    public function handle(SystemReadinessService $readiness): int
    {
        $report = $readiness->report();

        $this->table(
            ['Status', 'Check', 'Detail', 'Action'],
            $report['checks']->map(fn (array $check) => [
                strtoupper($check['status']),
                $check['label'],
                $check['detail'],
                $check['action'],
            ])->all()
        );

        $summary = $report['summary'];
        $this->line("Overall: {$report['overall_status']} ({$summary['ok']} ok, {$summary['warning']} warning, {$summary['critical']} critical)");

        if ($summary['critical'] > 0) {
            return self::FAILURE;
        }

        if ($this->option('fail-on-warning') && $summary['warning'] > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
