<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Facades\App;

enum UserRole: string
{
    case Admin = 'admin';
    case Student = 'student';
    case Parent = 'parent';

    public function label(): string
    {
        if (App::getLocale() === 'en') {
            return match ($this) {
                self::Admin => 'Administrator',
                self::Student => 'Learner',
                self::Parent => 'Parent',
            };
        }

        return match ($this) {
            self::Admin => 'Администратор',
            self::Student => 'Обучающийся',
            self::Parent => 'Родитель',
        };
    }
}
