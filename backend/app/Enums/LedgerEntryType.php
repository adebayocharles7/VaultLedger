<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    case DEBIT  = 'debit';
    case CREDIT = 'credit';

    public function opposite(): self
    {
        return $this === self::DEBIT ? self::CREDIT : self::DEBIT;
    }
}