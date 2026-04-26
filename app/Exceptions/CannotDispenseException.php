<?php

namespace App\Exceptions;

final class CannotDispenseException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('Bu məbləğ mövcud əskinazlarla verilə bilməz.'));
    }
}
