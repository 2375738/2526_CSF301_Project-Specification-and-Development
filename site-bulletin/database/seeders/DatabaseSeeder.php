<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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
use App\Models\ManagerRelationship;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\RoleChangeRequest;
use App\Models\DepartmentMetric;
use App\Models\KnowledgeSnippet;
use App\Services\DemoOperationsSimulationService;
use App\Services\DemoTicketLifecycleSimulationService;
use App\Services\SLAService;
use App\Services\DepartmentAnalyticsService;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DepartmentSeeder::class);
        $this->call(DepartmentOperationsProfileSeeder::class);

        $coreDepartmentSlugs = ['customer-returns', 'kariba', 'inbound', 'icqa', 'outbound', 'support', 'tom'];
        $departments = Department::query()
            ->whereIn('slug', $coreDepartmentSlugs)
            ->get()
            ->keyBy('slug');

        // --- Key Users (for Demo/Testing) ---
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'job_title' => 'Site Leader',
                'employee_id' => '10000001',
                'location' => 'Site Admin Office',
                'phone' => '+1 (555) 000-0001',
            ]
        );

        $hr = User::updateOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'HR Manager',
                'password' => Hash::make('password'),
                'role' => 'hr',
                'job_title' => 'Sr. HR Business Partner',
                'employee_id' => '10000002',
                'location' => 'HR Hub',
                'phone' => '+1 (555) 000-0002',
            ]
        );

        $mgr = User::updateOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Operations Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'job_title' => 'Area Manager',
                'employee_id' => '10000003',
                'location' => 'Inbound Dock Office',
                'phone' => '+1 (555) 000-0003',
            ]
        );

        $opsManager = User::updateOrCreate(
            ['email' => 'ops@example.com'],
            [
                'name' => 'Ops Manager',
                'password' => Hash::make('password'),
                'role' => 'ops_manager',
                'job_title' => 'Site Operations Manager',
                'employee_id' => '10000005',
                'location' => 'Operations Command Desk',
                'phone' => '+1 (555) 000-0005',
            ]
        );

        $emp = User::updateOrCreate(
            ['email' => 'employee@example.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'job_title' => 'Process Assistant',
                'employee_id' => '10000004',
                'location' => 'Inbound Dock',
                'phone' => '+1 (555) 000-0004',
            ]
        );

        // --- Assign Departments & Relationships for Key Users ---
        $inbound = $departments->get('inbound');
        $support = $departments->get('support');

        $assignDepartment = function (User $user, Department $department, string $role, bool $primary = false): void {
            if ($primary) {
                $user->primary_department_id = $department->id;
                $user->save();
            }
            $user->departments()->syncWithoutDetaching([
                $department->id => ['role' => $role, 'is_primary' => $primary],
            ]);
        };

        $assignDepartment($admin, $inbound, 'manager', true); // Admin technically oversees everything, but primary here
        $assignDepartment($hr, $support ?? $inbound, 'hr_manager', true);
        $assignDepartment($mgr, $inbound, 'manager', true);
        $assignDepartment($opsManager, $inbound, 'manager', true);
        $assignDepartment($emp, $inbound, 'member', true);

        // Reporting Lines for Key Users
        ManagerRelationship::updateOrCreate(['manager_id' => $mgr->id, 'reports_to_id' => $admin->id], ['relationship_type' => 'direct']);
        ManagerRelationship::updateOrCreate(['manager_id' => $opsManager->id, 'reports_to_id' => $admin->id], ['relationship_type' => 'direct']);
        ManagerRelationship::updateOrCreate(['manager_id' => $emp->id, 'reports_to_id' => $mgr->id], ['relationship_type' => 'direct']);
        ManagerRelationship::updateOrCreate(['manager_id' => $hr->id, 'reports_to_id' => $admin->id], ['relationship_type' => 'direct']);


        // --- Generate Generic Organizational Structure ---
        $faker = \Faker\Factory::create();

        foreach ($departments as $slug => $dept) {
            // Skip Inbound/People for generic generation if we want to keep them clean, 
            // but let's add more people to them too to make it busy.
            
            // 1. Create a Department Manager (if not already covered by key users)
            if ($slug === 'inbound') {
                $deptManager = $mgr;
            } else {
                $deptManager = User::updateOrCreate([
                    'email' => $slug . '.manager@example.com',
                ], [
                    'name' => $faker->name,
                    'password' => Hash::make('password'),
                    'role' => 'manager',
                    'job_title' => 'Area Manager',
                    'employee_id' => $faker->unique()->numerify('10######'),
                    'location' => $dept->name . ' Office',
                    'phone' => $faker->phoneNumber,
                    'primary_department_id' => $dept->id,
                ]);
                $assignDepartment($deptManager, $dept, 'manager', true);
                
                // Report to Admin (Site Lead)
                ManagerRelationship::updateOrCreate([
                    'manager_id' => $deptManager->id,
                    'reports_to_id' => $admin->id,
                ], [
                    'relationship_type' => 'direct',
                ]);
            }

            // 2. Create Employees for this Department
            $employeeCount = rand(5, 12);
            for ($i = 0; $i < $employeeCount; $i++) {
                $employee = User::updateOrCreate([
                    'email' => str_replace('-', '.', $slug) . '.emp' . $i . '@example.com',
                ], [
                    'name' => $faker->name,
                    'password' => Hash::make('password'),
                    'role' => 'employee',
                    'job_title' => $faker->randomElement(['Associate I', 'Associate II', 'Process Assistant', 'Specialist']),
                    'employee_id' => $faker->unique()->numerify('10######'),
                    'location' => $dept->name . ' Floor',
                    'phone' => $faker->phoneNumber,
                    'primary_department_id' => $dept->id,
                ]);
                $assignDepartment($employee, $dept, 'member', true);

                // Report to Department Manager
                ManagerRelationship::updateOrCreate([
                    'manager_id' => $employee->id,
                    'reports_to_id' => $deptManager->id,
                ], [
                    'relationship_type' => 'direct',
                ]);
            }
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
                'Current Vacancies' => [
                    'order' => 1,
                    'is_sensitive' => false,
                    'audience' => 'all',
                ],
                'My Site' => [
                    'order' => 2,
                    'is_sensitive' => false,
                    'audience' => 'all',
                ],
                'Diversity, Equity & Inclusion' => [
                    'order' => 3,
                    'is_sensitive' => false,
                    'audience' => 'all',
                ],
                'PxT' => [
                    'order' => 4,
                    'is_sensitive' => true,
                    'audience' => 'department',
                    'department_id' => $support?->id ?? null,
                ],
                'Site Tools' => [
                    'order' => 5,
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
                    $categoryData['department_id'] = $support?->id ?? $inbound?->id;
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
                    'department_id' => $support->id ?? null,
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

        // Keep quick links aligned to Linktree snapshot only.
        if ($generalInfoCategory = Category::query()->where('name', 'General Information')->first()) {
            Link::query()->where('category_id', $generalInfoCategory->id)->delete();
            $generalInfoCategory->delete();
        }

        // Dedicated operational ticket categories (separate from link categories).
        $ticketCategories = collect([
            ['name' => 'Safety', 'audience' => 'all', 'is_sensitive' => false],
            ['name' => 'HR', 'audience' => 'all', 'is_sensitive' => false],
            ['name' => 'Facilities', 'audience' => 'all', 'is_sensitive' => false],
            ['name' => 'IT Support', 'audience' => 'all', 'is_sensitive' => false],
            ['name' => 'Operations', 'audience' => 'all', 'is_sensitive' => false],
            ['name' => 'Transport', 'audience' => 'all', 'is_sensitive' => false],
        ])->map(function (array $category, int $index) {
            return Category::updateOrCreate(
                ['name' => $category['name']],
                [
                    'order' => 200 + $index,
                    'is_sensitive' => $category['is_sensitive'],
                    'audience' => $category['audience'],
                    'department_id' => null,
                ]
            );
        });

        // Announcements
        $globalAuthor = $hr ?? $admin;
        Announcement::query()->delete();

        $announcementSeed = [
            [
                'title' => 'Inbound dock scanner swap scheduled for Saturday night',
                'body' => "IT will replace handheld scanners on docks 1 to 4 between 21:30 and 23:00 on Saturday.\n\nPlease return spare devices to the charging bay before the swap window starts. If your station loses a device after 21:30, raise an IT Support ticket instead of borrowing from another lane so the asset list stays accurate.\n\nExpected impact: short pauses during device handover, no full process stop.",
                'audience' => 'all',
                'department_id' => null,
                'author_id' => $globalAuthor->id,
                'is_pinned' => true,
                'priority' => 'high',
                'starts_at' => now()->subHours(3),
                'ends_at' => now()->addDays(2),
            ],
            [
                'title' => 'Shuttle route B running 15 minutes later on Friday morning',
                'body' => "Transport support confirmed a delayed departure for route B because of road works near the east gate.\n\nPickup points remain the same. Day-shift associates using route B should allow extra travel time and notify their manager if the delay affects start-of-shift handover.",
                'audience' => 'all',
                'department_id' => null,
                'author_id' => $globalAuthor->id,
                'is_pinned' => false,
                'priority' => 'medium',
                'starts_at' => now()->subHours(10),
                'ends_at' => now()->addDay(),
            ],
            [
                'title' => 'Learning Loop 8.6 assigned for all process assistants this week',
                'body' => "This week’s development item is Learning Loop 8.6: owning outcomes end-to-end.\n\nAssociates and process assistants should complete the module before Sunday 18:00. Managers will review completion in the next weekly check-in, so use the Knowledge area if you need the link again.",
                'audience' => 'all',
                'department_id' => null,
                'author_id' => $hr->id,
                'is_pinned' => false,
                'priority' => 'medium',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(4),
            ],
            [
                'title' => 'Parking rota updated after overflow changes at Gate C',
                'body' => "Overflow parking has moved to the far side of Gate C for the next seven days.\n\nPlease check the latest parking rota before arriving on site. Security will redirect vehicles parked in the old overflow lane after 09:30 each day.",
                'audience' => 'all',
                'department_id' => null,
                'author_id' => $globalAuthor->id,
                'is_pinned' => false,
                'priority' => 'low',
                'starts_at' => now()->subHours(6),
                'ends_at' => now()->addDays(6),
            ],
            [
                'title' => 'Inbound quality checks tightened on pallet labels for late vendor loads',
                'body' => "Inbound teams should pause and re-check pallet labels arriving from late vendor loads after 18:00.\n\nWe saw three mismatched labels during the last night-shift intake. Use the usual escalation path if ASN detail does not match the label in hand. Do not move the pallet forward until the mismatch is resolved.",
                'audience' => 'department',
                'department_id' => $inbound?->id,
                'author_id' => $mgr->id,
                'is_pinned' => true,
                'priority' => 'urgent',
                'starts_at' => now()->subHours(2),
                'ends_at' => now()->addDays(3),
            ],
            [
                'title' => 'Weekend overtime sign-up open for inbound receive',
                'body' => "Additional inbound receive coverage is available for Saturday and Sunday day shift.\n\nIf you want overtime, reply in Messages to your area manager before Friday 12:00. Priority will go to associates already trained on dock unload and receive staging.",
                'audience' => 'department',
                'department_id' => $inbound?->id,
                'author_id' => $mgr->id,
                'is_pinned' => false,
                'priority' => 'medium',
                'starts_at' => now()->subHours(18),
                'ends_at' => now()->addDays(2),
            ],
            [
                'title' => 'Managers: submit shift staffing gaps before 16:00',
                'body' => "Ops and department managers should log today’s staffing gaps before 16:00 so support planning can finalize weekend coverage.\n\nInclude the affected area, required headcount, and whether the gap can be covered by cross-training.",
                'audience' => 'managers',
                'department_id' => null,
                'author_id' => $admin->id,
                'is_pinned' => false,
                'priority' => 'high',
                'starts_at' => now()->subHours(5),
                'ends_at' => now()->addDay(),
            ],
        ];

        foreach ($announcementSeed as $announcementData) {
            Announcement::updateOrCreate(
                ['title' => $announcementData['title']],
                $announcementData + ['is_active' => true]
            );
        }

        KnowledgeSnippet::updateOrCreate(
            ['title' => 'Scanner reset steps'],
            [
                'summary' => 'Quick recovery steps when a handheld scanner freezes or drops connection.',
                'body' => "1. Remove and reseat the battery.\n2. Restart the device and reconnect to Wi-Fi.\n3. If the issue returns, record the asset tag and raise a ticket.",
                'department_id' => null,
                'audience' => 'all',
                'is_active' => true,
                'order' => 0,
            ]
        );

        KnowledgeSnippet::updateOrCreate(
            ['title' => 'Missed punch route'],
            [
                'summary' => 'What to do when your shift punch is missing or incorrect.',
                'body' => "Check A to Z first.\nIf the punch is still missing, message your manager or HR and include the shift date, expected start/end time, and your badge ID.",
                'department_id' => null,
                'audience' => 'all',
                'is_active' => true,
                'order' => 1,
            ]
        );

        KnowledgeSnippet::updateOrCreate(
            ['title' => 'Transport escalation'],
            [
                'summary' => 'Use this path when the shuttle or transport contact is late or missing.',
                'body' => "1. Check the latest site announcement for route changes.\n2. Contact transport support through Site Bulletin or the posted transport line.\n3. If service is still missing, raise a transport ticket with route and stop details.",
                'department_id' => null,
                'audience' => 'all',
                'is_active' => true,
                'order' => 2,
            ]
        );

        if ($support) {
            KnowledgeSnippet::updateOrCreate(
                ['title' => 'Manager-only escalation pack'],
                [
                    'summary' => 'Short guidance for manager escalations on repeat operational blockers.',
                    'body' => "Use this when the same blocker repeats across the shift.\nCollect ticket IDs, affected area, and current workaround before escalating to site leadership.",
                    'department_id' => $support->id,
                    'audience' => 'managers',
                    'is_active' => true,
                    'order' => 3,
                ]
            );
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

        $departmentUsers = $departments->mapWithKeys(function (Department $department) {
            $members = User::query()
                ->where(function ($query) use ($department) {
                    $query->where('primary_department_id', $department->id)
                        ->orWhereHas('departments', fn ($departmentQuery) => $departmentQuery
                            ->where('departments.id', $department->id));
                })
                ->get(['id', 'role', 'primary_department_id']);

            $managers = $members->filter(fn (User $user) => $user->role?->value === 'manager')->values();
            $employees = $members->filter(fn (User $user) => $user->role?->value === 'employee')->values();

            return [$department->id => [
                'department' => $department,
                'members' => $members,
                'managers' => $managers,
                'employees' => $employees,
            ]];
        });

        // Tickets (mix) with realistic SLA timing spread and true multi-department distribution.
        $ticketCounter = 1;
        foreach ($departmentUsers as $departmentId => $bundle) {
            $department = $bundle['department'];
            $deptManagers = $bundle['managers'];
            $deptEmployees = $bundle['employees'];
            $deptMembers = $bundle['members'];

            $ticketTarget = match ($department->slug) {
                'inbound' => 26,
                'outbound' => 22,
                'customer-returns' => 18,
                'kariba' => 16,
                'icqa' => 14,
                'support' => 12,
                'tom' => 10,
                default => 12,
            };

            for ($i = 0; $i < $ticketTarget; $i++) {
            $priority = collect(['low', 'medium', 'high', 'critical'])->random();
            $targets = $slaService->targets($priority);

            $isClosedFlow = rand(1, 100) <= 52;
            $finalStatus = $isClosedFlow
                ? collect(['resolved', 'closed'])->random()
                : collect(['triaged', 'in_progress', 'waiting_employee'])->random();
            if (! $isClosedFlow) {
                $priorityRoll = rand(1, 100);
                $priority = match (true) {
                    $priorityRoll <= 35 => 'low',
                    $priorityRoll <= 75 => 'medium',
                    $priorityRoll <= 95 => 'high',
                    default => 'critical',
                };
                $targets = $slaService->targets($priority);
            }

            $assignee = $deptManagers->first() ?? $mgr;
            $requester = $deptEmployees->isNotEmpty()
                ? $deptEmployees->random()
                : ($deptMembers->first() ?? $emp);
            $affectedUser = $deptEmployees->isNotEmpty() ? $deptEmployees->random() : $requester;

            $dayOffset = rand(0, 6);
            $createdAt = now()->subDays($dayOffset)->subHours(rand(0, 20))->subMinutes(rand(0, 59));

            $firstResponseBreachedSeed = rand(1, 100) <= 25;
            $firstResponseMinutes = $firstResponseBreachedSeed
                ? rand((int) max(5, $targets['first_response_minutes'] + 10), (int) max(20, $targets['first_response_minutes'] * 2))
                : rand(5, (int) max(6, $targets['first_response_minutes'] * 0.9));
            $triagedAt = $createdAt->copy()->addMinutes($firstResponseMinutes);

            if ($isClosedFlow) {
                $resolutionBreachedSeed = rand(1, 100) <= 30;
                $resolutionMinutes = $resolutionBreachedSeed
                    ? rand((int) max(60, $targets['resolution_minutes'] + 30), (int) max(120, $targets['resolution_minutes'] * 2))
                    : rand((int) max(45, $targets['resolution_minutes'] * 0.45), (int) max(60, $targets['resolution_minutes'] * 0.95));

                $closedAt = $createdAt->copy()->addMinutes($resolutionMinutes);
                if ($closedAt->greaterThan(now()->subMinutes(10))) {
                    $closedAt = now()->subMinutes(rand(30, 360));
                }
                $updatedAt = $closedAt->copy();
            } else {
                $closedAt = null;
                $openMode = rand(1, 100); // Keep most open tickets within SLA, some near risk, fewer breached.
                if ($openMode <= 58) {
                    $ageMinutes = rand(20, (int) max(30, $targets['resolution_minutes'] * 0.55));
                } elseif ($openMode <= 85) {
                    $ageMinutes = rand((int) max(40, $targets['resolution_minutes'] * 0.56), (int) max(60, $targets['resolution_minutes'] * 0.95));
                } else {
                    $ageMinutes = rand((int) max(80, $targets['resolution_minutes'] * 1.05), (int) max(120, $targets['resolution_minutes'] * 1.45));
                }

                $createdAt = now()->subMinutes($ageMinutes);
                $triagedAt = $createdAt->copy()->addMinutes($firstResponseMinutes);
                $updatedAt = now()->subMinutes(rand(5, 180));
            }

            if ($triagedAt->greaterThan($updatedAt->copy()->subMinutes(5))) {
                $triagedAt = $updatedAt->copy()->subMinutes(rand(15, 90));
            }

            $statusPath = match ($finalStatus) {
                'waiting_employee' => ['new', 'triaged', 'in_progress', 'waiting_employee'],
                'in_progress' => ['new', 'triaged', 'in_progress'],
                'resolved' => ['new', 'triaged', 'in_progress', 'resolved'],
                'closed' => ['new', 'triaged', 'in_progress', 'resolved', 'closed'],
                default => ['new', 'triaged'],
            };

            $statusTrail = [];
            $newAt = $createdAt->copy()->addMinutes(1);
            $statusTrail[] = ['from' => null, 'to' => 'new', 'comment' => 'Ticket opened by requester', 'at' => $newAt];
            $statusTrail[] = ['from' => 'new', 'to' => 'triaged', 'comment' => 'Acknowledged by manager', 'at' => $triagedAt];

            $remainingStatuses = array_slice($statusPath, 2);
            $cursor = $triagedAt->copy();
            $remainingMinutes = max(30, $cursor->diffInMinutes($updatedAt, false));
            $steps = max(1, count($remainingStatuses));
            $stepMinutes = max(20, (int) floor($remainingMinutes / $steps));
            $currentFrom = 'triaged';

            foreach ($remainingStatuses as $index => $toStatus) {
                $isLast = $index === count($remainingStatuses) - 1;
                $at = $isLast
                    ? $updatedAt->copy()
                    : $cursor->copy()->addMinutes($stepMinutes * ($index + 1));

                if ($at->greaterThan($updatedAt)) {
                    $at = $updatedAt->copy()->subMinutes(max(1, count($remainingStatuses) - $index));
                }

                $comment = match ($toStatus) {
                    'in_progress' => 'Work in progress',
                    'waiting_employee' => 'Need more info from requester',
                    'resolved' => 'Issue resolved by operations',
                    'closed' => 'Ticket closed',
                    default => null,
                };

                $statusTrail[] = [
                    'from' => $currentFrom,
                    'to' => $toStatus,
                    'comment' => $comment,
                    'at' => $at,
                ];

                $currentFrom = $toStatus;
            }

            $ticket = Ticket::create([
                'requester_id' => $requester->id,
                'assignee_id' => $assignee?->id,
                'created_for_id' => $affectedUser->id,
                'department_id' => $departmentId,
                'category_id' => $ticketCategories->random()->id,
                'priority' => $priority,
                'status' => $finalStatus,
                'title' => strtoupper($department->ops_code ?? $department->slug) . ' issue #' . $ticketCounter,
                'description' => 'Seeded ticket for ' . $department->name . ' (' . ($ticketCounter) . ')',
                'location' => collect(['Gate B', 'Canteen', 'Inbound Dock', 'Office'])->random(),
                'closed_at' => in_array($finalStatus, ['resolved', 'closed'], true) ? $closedAt : null,
            ]);

            $ticket->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ])->saveQuietly();

            foreach ($statusTrail as $step) {
                $change = TicketStatusChange::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $step['from'] === null ? $requester->id : ($assignee?->id ?? $mgr->id),
                    'from_status' => $step['from'],
                    'to_status' => $step['to'],
                    'reason' => $step['comment'] ?: null,
                ]);

                $change->forceFill([
                    'created_at' => $step['at'],
                    'updated_at' => $step['at'],
                ])->saveQuietly();
            }

            $managerComment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $assignee?->id ?? $mgr->id,
                'body' => 'Acknowledged. Working on it.',
                'is_private' => false,
            ]);
            $managerCommentAt = $triagedAt->copy()->addMinutes(rand(5, 30));
            $managerComment->forceFill([
                'created_at' => $managerCommentAt,
                'updated_at' => $managerCommentAt,
            ])->saveQuietly();

            $requesterComment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $requester->id,
                'body' => 'Thanks for the update!',
                'is_private' => false,
            ]);
            $requesterCommentAt = $managerCommentAt->copy()->addMinutes(rand(20, 180));
            if ($requesterCommentAt->greaterThan($updatedAt)) {
                $requesterCommentAt = $updatedAt->copy()->subMinutes(rand(1, 20));
            }
            $requesterComment->forceFill([
                'created_at' => $requesterCommentAt,
                'updated_at' => $requesterCommentAt,
            ])->saveQuietly();

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

            $ticketCounter++;
            }
        }

        app(DemoTicketLifecycleSimulationService::class)->refreshWindow(7, now());
        app(DemoOperationsSimulationService::class)->seedHistoricalWindow(6, now());

        // Conversations & Messages
        $directConversation = Conversation::firstOrCreate([
            'subject' => 'Follow-up on ticket queue',
            'type' => 'direct',
            'creator_id' => $mgr->id,
        ]);

        $directConversation->participants()->sync([
            $mgr->id => ['role' => 'owner', 'last_read_at' => now()],
            $emp->id => ['role' => 'member', 'last_read_at' => null],
        ]);

        Message::firstOrCreate([
            'conversation_id' => $directConversation->id,
            'sender_id' => $mgr->id,
            'body' => 'Hey, can you update ticket #12 before stand-up?',
        ]);

        foreach ($departments as $department) {
            $conversationOwner = $departmentUsers->get($department->id)['managers']->first() ?? $mgr;
            $deptConversation = Conversation::firstOrCreate([
                'subject' => $department->name . ' Operations Update',
                'type' => 'department',
                'creator_id' => $conversationOwner->id,
                'department_id' => $department->id,
            ]);

            $participantIds = $department
                ->members()
                ->pluck('users.id')
                ->merge([$conversationOwner->id, $admin->id])
                ->unique()
                ->all();

            $syncData = collect($participantIds)->mapWithKeys(function ($id) {
                return [$id => ['role' => 'member', 'last_read_at' => null]];
            })->toArray();

            $deptConversation->participants()->sync($syncData);
            $deptConversation->participants()
                ->updateExistingPivot($conversationOwner->id, ['role' => 'owner', 'last_read_at' => now()]);

            Message::firstOrCreate([
                'conversation_id' => $deptConversation->id,
                'sender_id' => $conversationOwner->id,
                'body' => sprintf(
                    '%s shift note: focus on safety, quality checks, and backlog clearance this shift.',
                    $department->name
                ),
            ]);
        }

        // Recalculate metrics from seeded tickets/messages for a consistent 7-day manager view.
        DepartmentMetric::query()->delete();
        $departmentAnalytics = app(DepartmentAnalyticsService::class);
        foreach (range(6, 0) as $offset) {
            $departmentAnalytics->recalculateForDate(now()->subDays($offset));
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
