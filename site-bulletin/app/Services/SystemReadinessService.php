<?php

namespace App\Services;

use App\Models\DepartmentMetric;
use App\Models\ManagerRelationship;
use App\Models\PerformanceRollup;
use App\Models\PerformanceSample;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SystemReadinessService
{
    public function report(): array
    {
        $checks = collect([
            $this->checkAppDebug(),
            $this->checkDemoBoundary(),
            $this->checkPrototypeFooter(),
            $this->checkQueueDriver(),
            $this->checkMailer(),
            $this->checkFailedJobs(),
            $this->checkStorageLink(),
            $this->checkWritableStorage(),
            $this->checkUsersWithoutDepartments(),
            $this->checkEmployeesWithoutManagers(),
            $this->checkDepartmentMetrics(),
            $this->checkDemoSamples(),
            $this->checkPerformanceRollups(),
        ]);

        return [
            'checks' => $checks,
            'summary' => [
                'ok' => $checks->where('status', 'ok')->count(),
                'warning' => $checks->where('status', 'warning')->count(),
                'critical' => $checks->where('status', 'critical')->count(),
                'total' => $checks->count(),
            ],
            'overall_status' => $checks->contains('status', 'critical')
                ? 'critical'
                : ($checks->contains('status', 'warning') ? 'warning' : 'ok'),
        ];
    }

    protected function checkAppDebug(): array
    {
        return $this->check(
            config('app.debug') ? 'critical' : 'ok',
            'Application debug mode',
            config('app.debug')
                ? 'APP_DEBUG is enabled. Disable it before production use.'
                : 'APP_DEBUG is disabled.',
            'Set APP_DEBUG=false in production.'
        );
    }

    protected function checkDemoBoundary(): array
    {
        $demoLogin = filter_var(config('site_bulletin.demo_login_enabled'), FILTER_VALIDATE_BOOLEAN);
        $demoSimulation = (bool) config('site_bulletin.demo_simulation_enabled');

        return $this->check(
            ($demoLogin || $demoSimulation) ? 'warning' : 'ok',
            'Demo mode boundary',
            ($demoLogin || $demoSimulation)
                ? 'Demo login or demo simulation is enabled.'
                : 'Demo login and simulation are disabled.',
            'Disable SITE_BULLETIN_DEMO_LOGIN_ENABLED and SITE_BULLETIN_DEMO_SIMULATION_ENABLED for production.'
        );
    }

    protected function checkPrototypeFooter(): array
    {
        return $this->check(
            config('site_bulletin.prototype_footer_enabled') ? 'warning' : 'ok',
            'Prototype footer',
            config('site_bulletin.prototype_footer_enabled')
                ? 'Prototype footer copy is enabled.'
                : 'Prototype footer copy is hidden.',
            'Set SITE_BULLETIN_PROTOTYPE_FOOTER_ENABLED=false for production.'
        );
    }

    protected function checkQueueDriver(): array
    {
        $driver = (string) config('queue.default');

        return $this->check(
            in_array($driver, ['sync', 'null'], true) ? 'warning' : 'ok',
            'Queue driver',
            "Queue connection is {$driver}.",
            'Use database, redis, sqs, or another durable queue for production jobs.'
        );
    }

    protected function checkMailer(): array
    {
        $mailer = (string) config('mail.default');

        return $this->check(
            in_array($mailer, ['log', 'array'], true) ? 'warning' : 'ok',
            'Mailer',
            "Default mailer is {$mailer}.",
            'Use a real mail transport when production notifications matter.'
        );
    }

    protected function checkFailedJobs(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return $this->check('warning', 'Failed job table', 'The failed_jobs table is missing.', 'Run the queue failed-job migration.');
        }

        $failedJobs = \DB::table('failed_jobs')->count();

        return $this->check(
            $failedJobs > 0 ? 'critical' : 'ok',
            'Failed jobs',
            "{$failedJobs} failed job" . ($failedJobs === 1 ? '' : 's') . ' recorded.',
            'Inspect and retry or clear failed jobs before release.'
        );
    }

    protected function checkStorageLink(): array
    {
        return $this->check(
            File::exists(public_path('storage')) ? 'ok' : 'warning',
            'Public storage link',
            File::exists(public_path('storage'))
                ? 'public/storage exists.'
                : 'public/storage is missing.',
            'Run php artisan storage:link if public files must be served.'
        );
    }

    protected function checkWritableStorage(): array
    {
        $paths = [storage_path(), storage_path('framework/cache'), storage_path('logs')];
        $blocked = collect($paths)->reject(fn (string $path) => File::isWritable($path))->values();

        return $this->check(
            $blocked->isEmpty() ? 'ok' : 'critical',
            'Writable storage',
            $blocked->isEmpty()
                ? 'Storage, cache, and logs are writable.'
                : 'Not writable: ' . $blocked->implode(', '),
            'Fix filesystem permissions for Laravel writable directories.'
        );
    }

    protected function checkUsersWithoutDepartments(): array
    {
        $count = User::query()
            ->whereNull('primary_department_id')
            ->where('role', '!=', 'admin')
            ->count();

        return $this->check(
            $count > 0 ? 'warning' : 'ok',
            'Users without departments',
            "{$count} non-admin user" . ($count === 1 ? '' : 's') . ' missing a primary department.',
            'Assign departments from Admin users before go-live.'
        );
    }

    protected function checkEmployeesWithoutManagers(): array
    {
        $count = User::query()
            ->where('role', 'employee')
            ->whereNotIn('id', ManagerRelationship::query()->select('manager_id'))
            ->count();

        return $this->check(
            $count > 0 ? 'warning' : 'ok',
            'Employees without managers',
            "{$count} employee" . ($count === 1 ? '' : 's') . ' without a manager relationship.',
            'Create manager relationships for escalation and messaging shortcuts.'
        );
    }

    protected function checkDepartmentMetrics(): array
    {
        $latest = DepartmentMetric::query()->latest('metric_date')->first();

        if (! $latest) {
            return $this->check('warning', 'Department metrics freshness', 'No department metrics have been calculated.', 'Run php artisan analytics:recalculate-departments.');
        }

        $daysOld = now()->startOfDay()->diffInDays($latest->metric_date?->copy()?->startOfDay() ?? now(), true);

        return $this->check(
            $daysOld > 1 ? 'warning' : 'ok',
            'Department metrics freshness',
            'Latest metric date is ' . $latest->metric_date?->toDateString() . '.',
            'Confirm the scheduler runs analytics:recalculate-departments daily.'
        );
    }

    protected function checkDemoSamples(): array
    {
        if (! config('site_bulletin.demo_simulation_enabled')) {
            return $this->check('ok', 'Demo sample freshness', 'Demo simulation is disabled.', 'No action needed.');
        }

        $latest = PerformanceSample::query()->latest('recorded_at')->first();

        if (! $latest) {
            return $this->check('warning', 'Demo sample freshness', 'Demo simulation is enabled but no samples exist.', 'Run php artisan demo:backfill-ops-data.');
        }

        return $this->check(
            $latest->recorded_at?->lessThan(now()->subHours(2)) ? 'warning' : 'ok',
            'Demo sample freshness',
            'Latest demo sample is ' . $latest->recorded_at?->diffForHumans() . '.',
            'Confirm demo:backfill-ops-data runs every fifteen minutes when demo simulation is enabled.'
        );
    }

    protected function checkPerformanceRollups(): array
    {
        $latestSample = PerformanceSample::query()->latest('recorded_at')->first();
        $latestRollup = PerformanceRollup::query()->latest('bucket_start')->first();

        if (! $latestSample) {
            return $this->check('ok', 'Performance rollups', 'No raw performance samples exist.', 'No action needed.');
        }

        if (! $latestRollup) {
            return $this->check('warning', 'Performance rollups', 'Raw samples exist but no rollups have been generated.', 'Run php artisan performance:rollup-samples.');
        }

        $lagHours = $latestRollup->bucket_start?->diffInHours($latestSample->recorded_at, false) ?? 0;

        return $this->check(
            $lagHours > 3 ? 'warning' : 'ok',
            'Performance rollups',
            'Latest rollup bucket is ' . $latestRollup->bucket_start?->toDateTimeString() . '.',
            'Confirm performance:rollup-samples runs hourly.'
        );
    }

    protected function check(string $status, string $label, string $detail, string $action): array
    {
        return compact('status', 'label', 'detail', 'action');
    }
}
