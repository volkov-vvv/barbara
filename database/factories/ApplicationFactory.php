<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentName = fake()->randomElement([
            'passport.pdf',
            'diploma.pdf',
            'certificate.jpg',
            'scan.png',
        ]);

        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+7 ('.fake()->numerify('9##').') '.fake()->numerify('###-##-##'),
            'region' => fake()->randomElement(Application::REGIONS),
            'document_path' => 'applications/'.uniqid('seed_', true).'_'.$documentName,
            'course' => fake()->randomElement(Application::COURSES),
            'level' => fake()->randomElement(Application::LEVELS),
            'comment' => fake()->optional(0.4)->sentence(),
        ];
    }
}
