<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Category;
use App\Models\Link;
use App\Models\Announcement;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusChange;
use App\Models\TicketAttachment;
use App\Models\SLASetting;
use App\Models\PerformanceSnapshot;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );
        $mgr = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ]
        );
        $emp = User::updateOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'Employee',
                'password' => Hash::make('password'),
                'role' => 'employee',
            ]
        );

        // Categories & Links
        $cats = collect([
            ['name' => 'Hot Topics', 'order' => 0, 'is_sensitive' => false],
            ['name' => 'Health & Safety', 'order' => 1, 'is_sensitive' => false],
            ['name' => 'My Site', 'order' => 2, 'is_sensitive' => false],
            ['name' => 'HR / PxT', 'order' => 3, 'is_sensitive' => true],
        ])->map(
            fn ($c) => Category::updateOrCreate(['name' => $c['name']], $c)
        );

        foreach ($cats as $cat) {
            Link::factory()
                ->count(5)
                ->sequence(fn ($sequence) => [
                    'order' => $sequence->index,
                    'is_hot' => $cat->order === 0 && $sequence->index < 2,
                ])
                ->create([
                    'category_id' => $cat->id,
                    'is_active' => true,
                ]);
        }

        // Announcements
        Announcement::factory()
            ->state(['is_pinned' => true, 'title' => 'Site upgrade this weekend'])
            ->create();
        Announcement::factory()->count(5)->create();

        // SLA defaults
        foreach (['low' => 1440, 'medium' => 1440, 'high' => 720, 'critical' => 240] as $p => $resMins) {
            SLASetting::updateOrCreate(
                ['priority' => $p],
                [
                    'first_response_minutes' => ($p === 'critical' ? 60 : ($p === 'high' ? 120 : 480)),
                    'resolution_minutes' => $resMins,
                    'pause_statuses' => ['waiting_employee'],
                ]
            );
        }

        // Tickets (mix)
        for ($i = 1; $i <= 20; $i++) {
            $priority = collect(['low', 'medium', 'high', 'critical'])->random();
            $statusTrail = [
                ['from' => null, 'to' => 'new', 'comment' => 'Ticket opened by requester'],
                ['from' => 'new', 'to' => 'triaged', 'comment' => 'Acknowledged by manager'],
            ];

            if (rand(0, 1)) {
                $statusTrail[] = ['from' => 'triaged', 'to' => 'in_progress', 'comment' => 'Work in progress'];
            }

            if (rand(0, 1)) {
                $statusTrail[] = ['from' => 'in_progress', 'to' => 'waiting_employee', 'comment' => 'Need more info from requester'];
            }

            $finalStatus = collect(['resolved', 'closed', 'in_progress', 'waiting_employee'])->random();
            $previousStatus = $statusTrail[array_key_last($statusTrail)]['to'];
            $statusTrail[] = ['from' => $previousStatus, 'to' => $finalStatus, 'comment' => ''];

            $requester = rand(0, 1) ? $emp : $mgr;

            $createdAt = now()->subDays(rand(0, 14));

            $ticket = Ticket::create([
                'requester_id' => $requester->id,
                'assignee_id' => $mgr->id,
                'category_id' => $cats->random()->id,
                'priority' => $priority,
                'status' => $finalStatus,
                'title' => 'Issue #' . $i,
                'description' => 'Demo ticket ' . $i,
                'location' => collect(['Gate B', 'Canteen', 'Inbound Dock', 'Office'])->random(),
                'closed_at' => in_array($finalStatus, ['resolved', 'closed'], true) ? now()->subDays(rand(0, 2)) : null,
            ]);

            $ticket->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addHours(rand(1, 72)),
            ])->saveQuietly();

            foreach ($statusTrail as $step) {
                TicketStatusChange::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $step['from'] === null ? $requester->id : $mgr->id,
                    'from_status' => $step['from'],
                    'to_status' => $step['to'],
                    'reason' => $step['comment'] ?: null,
                ]);
            }

            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $mgr->id,
                'body' => 'Acknowledged. Working on it.',
                'is_private' => false,
            ]);

            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $requester->id,
                'body' => 'Thanks for the update!',
                'is_private' => false,
            ]);

            if ($i <= 5) {
                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $requester->id,
                    'path' => 'attachments/' . Str::uuid() . '.jpg',
                    'original_name' => 'sample-' . $i . '.jpg',
                    'mime' => 'image/jpeg',
                    'size' => rand(50_000, 250_000),
                ]);
            }
        }

        // Performance snapshots (6 weeks for each user)
        foreach (User::all() as $u) {
            $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
            for ($i=5;$i>=0;$i--) {
                $week = (clone $monday)->subWeeks($i);
                PerformanceSnapshot::updateOrCreate(
                    ['user_id'=>$u->id,'week_start'=>$week->toDateString()],
                    ['units_per_hour'=>rand(80,150),'rank_percentile'=>rand(5,98)]
                );
            }
        }

        // Mark a sample duplicate chain
        $primaryTicket = Ticket::orderBy('id')->first();
        $duplicateTicket = Ticket::orderBy('id', 'desc')->first();

        if ($primaryTicket && $duplicateTicket && $primaryTicket->id !== $duplicateTicket->id) {
            $duplicateTicket->markDuplicateOf($primaryTicket, $mgr, 'Duplicate seeded for demo');
        }
    }
}
