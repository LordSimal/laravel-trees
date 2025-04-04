<?php

declare(strict_types=1);

namespace LordSimal\LaravelTrees\Exceptions;

class NotSupportedException extends Exception
{
    public function __construct(?string $className = null, string $message = 'Not Supported')
    {
        $message .= $className ? ': '.$className : '';
        parent::__construct($message);
    }
}
