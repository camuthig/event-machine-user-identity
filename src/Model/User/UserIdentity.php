<?php

declare(strict_types=1);

namespace App\Model\User;

use Prooph\EventMachine\Data\ImmutableRecord;
use Prooph\EventMachine\Data\ImmutableRecordLogic;

class UserIdentity implements ImmutableRecord
{
    use ImmutableRecordLogic;

    /**
     * @var string
     */
    private $provider;

    /**
     * @var string
     */
    private $id;

    public function provider(): string
    {
        return $this->provider;
    }

    public function id(): string
    {
        return $this->id;
    }
}
