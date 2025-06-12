<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Completed = 'completed';
    case Reversed = 'reversed';
    case Canceled = 'canceled';
}