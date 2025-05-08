<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case TRANSFER = 'transfer';
    case RECEIVED_TRANSFER = 'received_transfer';
}
