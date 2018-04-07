<?php

declare(strict_types=1);

namespace App\Model\User;

use App\Api\Payload;
use Prooph\EventMachine\Data\ImmutableRecord;
use Prooph\EventMachine\Data\ImmutableRecordLogic;
use Prooph\EventMachine\JsonSchema\Type;

class State implements ImmutableRecord
{
    use ImmutableRecordLogic;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var array[]
     */
    private $identities = [];

    public static function __schema(): Type
    {
        return self::generateSchemaFromPropTypeMap([
            'identities' => UserIdentity::class,
       ]);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function identities(): array
    {
        return $this->identities;
    }

     public function identityForProvider(string $provider): ?UserIdentity
     {
         return $this->identities[$provider] ? UserIdentity::fromArray($this->identities[$provider]) : null;
     }

    public function withIdentity(array $identity): State
    {
        $copy = clone $this;

        $copy->identities[$identity[Payload::IDENTITY_PROVIDER]] = $identity;

        return $copy;
    }
}
