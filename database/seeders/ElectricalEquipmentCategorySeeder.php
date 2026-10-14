<?php

namespace Database\Seeders;

use App\Models\ElectricalEquipmentCategory;
use Illuminate\Database\Seeder;

class ElectricalEquipmentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Inversores', 'slug' => 'inversores'],
            ['name' => 'Paneles solares', 'slug' => 'paneles-solares'],
            ['name' => 'Baterías', 'slug' => 'baterias'],
            ['name' => 'Breakers DC', 'slug' => 'breakers-dc'],
            ['name' => 'Breakers AC', 'slug' => 'breakers-ac'],
            ['name' => 'Cable solar', 'slug' => 'cable-solar'],
        ];

        foreach ($categories as $category) {
            ElectricalEquipmentCategory::updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']],
            );
        }
    }
}
