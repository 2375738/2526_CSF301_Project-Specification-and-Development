<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentOperationsProfileSeeder extends Seeder
{
    public function run(): void
    {
        $shortDepartments = [
            ['name' => 'Customer Returns', 'color' => '#0f766e'],
            ['name' => 'Kariba', 'color' => '#0369a1'],
            ['name' => 'Inbound', 'color' => '#1d4ed8'],
            ['name' => 'ICQA', 'color' => '#7c3aed'],
            ['name' => 'Outbound', 'color' => '#059669'],
            ['name' => 'Support', 'color' => '#334155'],
            ['name' => 'TOM', 'color' => '#b45309'],
        ];

        foreach ($shortDepartments as $department) {
            Department::updateOrCreate(
                ['slug' => Str::slug($department['name'])],
                [
                    'name' => $department['name'],
                    'description' => $department['name'] . ' operations',
                    'color' => $department['color'],
                ]
            );
        }

        $profiles = [
            'customer-returns' => [
                'ops_code' => 'CRET',
                'description' => 'Return handling and reprocessing flow.',
                'planned_headcount' => 18,
                'target_units_per_hour' => 52.0,
                'target_quality_pct' => 97.0,
            ],
            'kariba' => [
                'ops_code' => 'KAR',
                'description' => 'VNA flow: vendor-packed units stowed without unboxing for faster downstream transfer.',
                'planned_headcount' => 16,
                'target_units_per_hour' => 48.0,
                'target_quality_pct' => 96.0,
            ],
            'inbound' => [
                'ops_code' => 'INB',
                'description' => 'Inbound receive and putaway operations.',
                'planned_headcount' => 24,
                'target_units_per_hour' => 42.0,
                'target_quality_pct' => 95.0,
            ],
            'icqa' => [
                'ops_code' => 'ICQA',
                'description' => 'Inventory quality and counting assurance.',
                'planned_headcount' => 14,
                'target_units_per_hour' => 36.0,
                'target_quality_pct' => 99.0,
            ],
            'outbound' => [
                'ops_code' => 'OUT',
                'description' => 'Pick, pack, and dispatch operations.',
                'planned_headcount' => 22,
                'target_units_per_hour' => 58.0,
                'target_quality_pct' => 96.5,
            ],
            'support' => [
                'ops_code' => 'SUP',
                'description' => 'Operational support and exception handling.',
                'planned_headcount' => 12,
                'target_units_per_hour' => 30.0,
                'target_quality_pct' => 97.5,
            ],
            'tom' => [
                'ops_code' => 'TOM',
                'description' => 'Transport operations and movement control.',
                'planned_headcount' => 10,
                'target_units_per_hour' => 26.0,
                'target_quality_pct' => 98.0,
            ],
        ];

        foreach (Department::query()->get() as $department) {
            $slug = $department->slug;
            $profile = $profiles[$slug] ?? $this->profileForName($department->name, $profiles);

            if (! $profile) {
                $profile = [
                    'ops_code' => strtoupper(Str::limit(Str::slug($department->name, ''), 4, '')),
                    'description' => $department->description ?: 'General operations profile.',
                    'planned_headcount' => max(8, (int) round(($department->members()->count() ?: 10) * 0.9)),
                    'target_units_per_hour' => 42.0,
                    'target_quality_pct' => 95.0,
                ];
            }

            $department->update([
                'ops_code' => $profile['ops_code'],
                'description' => $profile['description'],
                'planned_headcount' => $profile['planned_headcount'],
                'target_units_per_hour' => $profile['target_units_per_hour'],
                'target_quality_pct' => $profile['target_quality_pct'],
                'day_shift_start' => '10:00:00',
                'day_shift_end' => '20:00:00',
                'night_shift_start' => '18:30:00',
                'night_shift_end' => '04:45:00',
            ]);
        }
    }

    private function profileForName(string $name, array $profiles): ?array
    {
        $needle = strtolower($name);

        foreach ($profiles as $slug => $profile) {
            if (str_contains($needle, str_replace('-', ' ', $slug))) {
                return $profile;
            }
        }

        return null;
    }
}
