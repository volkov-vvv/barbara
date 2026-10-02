<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Application;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        Application::factory()->count(100)->create();
    }
}
