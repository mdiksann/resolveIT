<?php

namespace App\Enums;

enum Role: string
{
    case Employee = 'EMPLOYEE';
    case Agent = 'AGENT';
    case Admin = 'ADMIN';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee',
            self::Agent => 'Agent',
            self::Admin => 'Administrator',
        };
    }
}
