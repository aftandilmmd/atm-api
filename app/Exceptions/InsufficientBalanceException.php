<?php

namespace App\Exceptions;

final class InsufficientBalanceException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('Hesabda kifayət qədər vəsait yoxdur.'));
    }
}
