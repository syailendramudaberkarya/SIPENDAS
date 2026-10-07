<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Teacher => 'Guru',
            self::Student => 'Siswa',
        };
    }
}
