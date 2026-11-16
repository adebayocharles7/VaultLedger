<?php

namespace App\Enums;

enum FolioStatus: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case CLOSED = 'closed';


    public function canTransact(): bool
    {
        return $this === self::ACTIVE;
    }
    
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::FROZEN => 'Frozen',
            self::CLOSED => 'Closed',
        };
    }
}
