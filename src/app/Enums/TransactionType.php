<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case TransferSent = 'transfer_sent';
    case TransferReceived = 'transfer_received';
    case Reversal = 'reversal';
}

