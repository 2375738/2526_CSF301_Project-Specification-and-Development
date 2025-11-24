<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Department;
use App\Models\Category;
use App\Models\Link;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusChange;
use App\Models\TicketAttachment;
use App\Models\SLASetting;
use App\Models\PerformanceSnapshot;
use App\Models\ManagerRelationship;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RoleChangeRequest;
use App\Services\SLAService;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DepartmentSeeder::class);

        $departments = Department::all()->keyBy('slug');

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
        $hr = User::updateOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'HR Manager',
                'password' => Hash::make('password'),
                'role' => 'hr',
            ]
        );

        $assignDepartment = function (User $user, Department $department, string $role, bool $primary = false): void {
            if ($primary) {
                $user->primary_department_id = $department->id;
                $user->save();
            }

            $user->departments()->syncWithoutDetaching([
                $department->id => [
                    'role' => $role,
                    'is_primary' => $primary,
                ],
            ]);
        };

        $inbound = null;
        $people = null;

        if ($departments->isNotEmpty()) {
            $inbound = $departments->get('inbound-operations') ?? $departments->first();
            $people = $departments->get('people-experience') ?? $departments->first();

            $assignDepartment($admin, $people, 'hr_manager', true);
            $assignDepartment($hr, $people, 'hr_manager', true);
            $assignDepartment($mgr, $inbound, 'manager', true);
            $assignDepartment($emp, $inbound, 'member', true);

            ManagerRelationship::updateOrCreate(
                ['manager_id' => $mgr->id, 'reports_to_id' => $admin->id],
                ['relationship_type' => 'direct']
            );
        }

        $slaService = app(SLAService::class);

        // Categories & Links
        $rawPath = $this->resolveLinktreeSnapshotPath();
        $sections = $this->parseLinktreeSections($rawPath);

        if (! empty($sections)) {
            $metaMap = [
                'Hot Topics' => [
                    'order' => 0,
                    'is_sensitive' => false,
                    'audience' => 'all',
                ],
                'My Site' => [
                    'order' => 1,
                    'is_sensitive' => false,
                    'audience' => 'all',
                ],
                'Diversity, Equity & Inclusion' => [
                    'order' => 2,
                    'is_sensitive' => false,
                    'audience' => 'all',
                ],
                'PxT' => [
                    'order' => 3,
                    'is_sensitive' => true,
                    'audience' => 'department',
                    'department_id' => $people->id ?? null,
                ],
                'Site Tools' => [
                    'order' => 4,
                    'is_sensitive' => false,
                    'audience' => 'all',
                ],
            ];

            $cats = collect();
            $orderCounter = 0;

            foreach ($sections as $title => $links) {
                $meta = $metaMap[$title] ?? [];

                $categoryData = [
                    'name' => $title,
                    'order' => $meta['order'] ?? $orderCounter,
                    'is_sensitive' => $meta['is_sensitive'] ?? false,
                    'audience' => $meta['audience'] ?? 'all',
                    'department_id' => $meta['department_id'] ?? null,
                ];

                if ($categoryData['audience'] !== 'department') {
                    $categoryData['department_id'] = null;
                } elseif (! $categoryData['department_id']) {
                    $categoryData['department_id'] = $people?->id ?? $inbound?->id;
                }

                $category = Category::updateOrCreate(
                    ['name' => $title],
                    $categoryData
                );

                $cats->push($category);

                $labels = [];
                foreach ($links as $index => $linkData) {
                    $label = preg_replace('/\s+/', ' ', $linkData['label']);
                    $labels[] = $label;

                    Link::updateOrCreate(
                        [
                            'category_id' => $category->id,
                            'label' => $label,
                        ],
                        [
                            'url' => $linkData['url'],
                            'order' => $index,
                            'is_active' => true,
                            'is_hot' => $category->name === 'Hot Topics' && $index < 3,
                        ]
                    );
                }

                $category->links()
                    ->whereNotIn('label', $labels)
                    ->delete();

                $orderCounter++;
            }
        } else {
            $cats = collect([
                [
                    'name' => 'Hot Topics',
                    'order' => 0,
                    'is_sensitive' => false,
                    'audience' => 'all',
                    'department_id' => null,
                ],
                [
                    'name' => 'Health & Safety',
                    'order' => 1,
                    'is_sensitive' => false,
                    'audience' => 'all',
                    'department_id' => null,
                ],
                [
                    'name' => 'My Site',
                    'order' => 2,
                    'is_sensitive' => false,
                    'audience' => 'department',
                    'department_id' => $inbound->id ?? null,
                ],
                [
                    'name' => 'HR / PxT',
                    'order' => 3,
                    'is_sensitive' => true,
                    'audience' => 'department',
                    'department_id' => $people->id ?? null,
                ],
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
        }

        // Announcements
        $globalAuthor = $hr ?? $admin;

        Announcement::factory()
            ->state([
                'is_pinned' => true,
                'title' => 'Site upgrade this weekend',
                'audience' => 'all',
                'department_id' => null,
                'author_id' => $globalAuthor->id,
            ])
            ->create();

        Announcement::factory()->count(3)->state(function () use ($globalAuthor) {
            return [
                'audience' => 'all',
                'department_id' => null,
                'author_id' => $globalAuthor->id,
            ];
        })->create();

        if ($inbound) {
            Announcement::factory()->count(2)->state([
                'audience' => 'department',
                'department_id' => $inbound->id,
                'author_id' => $mgr->id,
            ])->create();
        }

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
            $affectedUser = $requester;

            if ($requester->id === $mgr->id) {
                $affectedUser = $emp;
            }

            $createdAt = now()->subDays(rand(0, 14));

            $ticket = Ticket::create([
                'requester_id' => $requester->id,
                'assignee_id' => $mgr->id,
                'created_for_id' => $affectedUser->id,
                'department_id' => $affectedUser->primary_department_id ?? $inbound->id ?? null,
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
                    'disk' => 'attachments',
                    'path' => 'attachments/' . Str::uuid() . '.jpg',
                    'original_name' => 'sample-' . $i . '.jpg',
                    'mime' => 'image/jpeg',
                    'size' => rand(50_000, 250_000),
                ]);
            }

            $sla = $slaService->evaluate($ticket);

            $ticket->forceFill([
                'sla_first_response_breached' => $sla['first_response_breached'],
                'sla_resolution_breached' => $sla['resolution_breached'],
            ])->saveQuietly();
        }

        // Performance snapshots (6 weeks for each user)
        foreach (User::all() as $u) {
            $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
            for ($i = 5; $i >= 0; $i--) {
                $week = (clone $monday)->subWeeks($i);

                PerformanceSnapshot::updateOrCreate(
                    [
                        'user_id' => $u->id,
                        'week_start' => $week->toDateString(),
                    ],
                    [
                        'units_per_hour' => rand(80, 150),
                        'rank_percentile' => rand(5, 98),
                    ]
                );
            }
        }

        // Conversations & Messages
        $directConversation = Conversation::create([
            'subject' => 'Follow-up on ticket queue',
            'type' => 'direct',
            'creator_id' => $mgr->id,
        ]);

        $directConversation->participants()->sync([
            $mgr->id => ['role' => 'owner', 'last_read_at' => now()],
            $emp->id => ['role' => 'member', 'last_read_at' => null],
        ]);

        Message::create([
            'conversation_id' => $directConversation->id,
            'sender_id' => $mgr->id,
            'body' => 'Hey, can you update ticket #12 before stand-up?',
        ]);

        if ($inbound) {
            $deptConversation = Conversation::create([
                'subject' => 'Inbound Operations Update',
                'type' => 'department',
                'creator_id' => $hr->id ?? $admin->id,
                'department_id' => $inbound->id,
            ]);

            $participantIds = $inbound
                ? $inbound->members()->pluck('users.id')->merge([$hr->id ?? $admin->id])->unique()->all()
                : [$mgr->id, $emp->id, $hr->id ?? $admin->id];

            $syncData = collect($participantIds)->mapWithKeys(function ($id) use ($hr, $admin) {
                return [$id => ['role' => 'member', 'last_read_at' => null]];
            })->toArray();

            $deptConversation->participants()->sync($syncData);
            $deptConversation->participants()
                ->updateExistingPivot($hr->id ?? $admin->id, ['role' => 'owner', 'last_read_at' => now()]);

            Message::create([
                'conversation_id' => $deptConversation->id,
                'sender_id' => $hr->id ?? $admin->id,
                'body' => 'Reminder: safety walkthrough tomorrow at 09:00. Please confirm attendance here.',
            ]);
        }

        // Mark a sample duplicate chain
        $primaryTicket = Ticket::orderBy('id')->first();
        $duplicateTicket = Ticket::orderBy('id', 'desc')->first();

        if ($primaryTicket && $duplicateTicket && $primaryTicket->id !== $duplicateTicket->id) {
            $duplicateTicket->markDuplicateOf($primaryTicket, $mgr, 'Duplicate seeded for demo');
        }

        // Governance samples
        RoleChangeRequest::firstOrCreate(
            [
                'requester_id' => $mgr->id,
                'target_user_id' => $emp->id,
                'requested_role' => 'manager',
            ],
            [
                'department_id' => $inbound?->id,
                'justification' => 'Employee covering night shifts should have manager access.',
                'status' => RoleChangeRequest::STATUS_PENDING,
            ]
        );

        AuditLog::factory()->create([
            'actor_id' => $mgr->id,
            'event_type' => 'ticket.status.updated',
            'auditable_type' => Ticket::class,
            'auditable_id' => $primaryTicket?->id,
            'payload' => [
                'status' => $primaryTicket?->status?->value,
                'priority' => $primaryTicket?->priority?->value,
            ],
        ]);

        AuditLog::factory()->create([
            'actor_id' => $admin->id,
            'event_type' => 'role.request.approved',
            'auditable_type' => RoleChangeRequest::class,
            'auditable_id' => RoleChangeRequest::query()->latest('id')->value('id'),
            'payload' => ['target_user_id' => $emp->id],
        ]);
    }

    /**
     * Parse the Linktree HTML snapshot into titled link sections.
     *
     * @return array<string, array<int, array{label:string, url:string}>>
     */
    private function parseLinktreeSections(?string $path): array
    {
        if ($path === null || ! is_file($path)) {
            return [];
        }

        $html = file_get_contents($path);

        if ($html === false || trim($html) === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = @$dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@id="links-container"]//*[self::h3 or self::a]');

        if (! $nodes || $nodes->length === 0) {
            return [];
        }

        $sections = [];
        $currentTitle = null;

        /** @var \DOMElement $node */
        foreach ($nodes as $node) {
            if ($node->nodeName === 'h3') {
                $title = trim(preg_replace('/\s+/', ' ', $node->textContent ?? ''));
                if ($title !== '') {
                    $currentTitle = $title;
                    $sections[$currentTitle] ??= [];
                }
                continue;
            }

            if ($node->nodeName === 'a' && $currentTitle !== null) {
                $label = trim(preg_replace('/\s+/', ' ', $node->textContent ?? ''));
                $href = trim($node->attributes->getNamedItem('href')?->nodeValue ?? '');

                if ($label !== '' && $href !== '') {
                    $sections[$currentTitle][] = [
                        'label' => html_entity_decode($label, ENT_QUOTES | ENT_HTML5),
                        'url' => html_entity_decode($href, ENT_QUOTES | ENT_HTML5),
                    ];
                }
            }
        }

        return array_filter($sections, fn ($links) => ! empty($links));
    }

    private function resolveLinktreeSnapshotPath(): ?string
    {
        $candidates = [
            base_path('data/cwl1informationportal/raw.html'),
            base_path('../data/cwl1informationportal/raw.html'),
            base_path('../../data/cwl1informationportal/raw.html'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        return null;
    }
}
