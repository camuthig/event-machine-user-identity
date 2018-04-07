<?php

declare(strict_types=1);

namespace App\Model\User;

class CouldNotAuthenticateIdentityException extends \DomainException
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct('We were unable to authenticate the user identity', 0, $previous);
    }
}
