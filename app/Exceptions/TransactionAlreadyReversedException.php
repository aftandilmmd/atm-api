<?php

namespace App\Exceptions;

final class TransactionAlreadyReversedException extends \RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Bu əməliyyat artıq geri alınıb.'));
    }
}
