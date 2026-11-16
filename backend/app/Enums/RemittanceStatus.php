<?php

namespace App\Enums;

enum RemittanceStatus: string
{
    case PENDING   = 'pending';
    case POSTED    = 'posted';
    case REVERSED  = 'reversed';
    case FAILED    = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::POSTED, self::REVERSED, self::FAILED]);
    }
}