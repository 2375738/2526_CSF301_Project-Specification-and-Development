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
        ['name' => 'Inbound Operations', 'color' => '#0f172a'],
        ['name' => 'Outbound Operations', 'color' => '#1d4ed8'],
        ['name' => 'Facilities & Safety', 'color' => '#059669'],
        ['name' => 'People Experience', 'color' => '#db2777'],
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
