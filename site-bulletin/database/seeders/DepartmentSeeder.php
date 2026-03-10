<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * @var array<int, array<string,string>>
     */
    protected array $departments = [
        ['name' => 'Customer Returns', 'color' => '#0f766e'],
        ['name' => 'Kariba', 'color' => '#0369a1'],
        ['name' => 'Inbound', 'color' => '#1d4ed8'],
        ['name' => 'ICQA', 'color' => '#7c3aed'],
        ['name' => 'Outbound', 'color' => '#059669'],
        ['name' => 'Support', 'color' => '#334155'],
        ['name' => 'TOM', 'color' => '#b45309'],
    ];

    public function run(): void
    {
        foreach ($this->departments as $dept) {
            Department::updateOrCreate(
                ['slug' => Str::slug($dept['name'])],
                [
                    'name' => $dept['name'],
                    'description' => $dept['name'] . ' team',
                    'color' => $dept['color'],
                ]
            );
        }
    }
}
