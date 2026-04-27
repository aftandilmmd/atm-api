<?php

namespace App\Enums;

enum TransactionType: string
{
    case WITHDRAW = 'withdraw';
    case REVERSE = 'reverse';
}
