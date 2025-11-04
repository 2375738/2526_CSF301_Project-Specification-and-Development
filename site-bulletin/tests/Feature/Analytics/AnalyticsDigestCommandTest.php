<?php

namespace Tests\Feature\Analytics;

use App\Console\Commands\SendAnalyticsDigest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AnalyticsDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_digest_command_generates_file_and_logs(): void
    {
        Storage::fake('local');
        Log::spy();

        Ticket::factory()->create([ 'title' => 'Audit queue' ]);

        $notifier = Mockery::mock(NotificationService::class);
        $notifier->shouldReceive('analyticsDigestGenerated')->once();

        $this->instance(NotificationService::class, $notifier);

        $this->artisan('analytics:send-digest')
            ->assertExitCode(SendAnalyticsDigest::SUCCESS);

        $files = Storage::disk('local')->allFiles('exports');
        $this->assertNotEmpty($files);
    }
}
