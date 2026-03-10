<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Item::factory()->count(5)->create();
    }
}
