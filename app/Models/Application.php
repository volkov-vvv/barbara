<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $full_name
 * @property string $email
 * @property string $phone
 * @property string $region
 * @property string $document_path
 * @property string $course
 * @property string $level
 * @property string|null $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'full_name',
    'email',
    'phone',
    'region',
    'document_path',
    'course',
    'level',
    'comment',
])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const REGIONS = [
        'Москва',
        'Санкт-Петербург',
        'Новосибирская область',
        'Краснодарский край',
        'Другой регион',
    ];

    /**
     * @var list<string>
     */
    public const COURSES = [
        'Frontend-разработчик',
        'Backend-разработчик',
        'QA-инженер',
    ];

    /**
     * @var list<string>
     */
    public const LEVELS = [
        'Новичок',
        'Есть база',
        'Junior',
    ];

    public function documentOriginalName(): string
    {
        $basename = basename($this->document_path);
        $parts = explode('_', $basename, 3);

        return $parts[2] ?? $basename;
    }
}
