<?php

declare(strict_types=1);

namespace App\Api;

use App\Infrastructure\ContextProvider\SocialiteContextProvider;
use App\Model\User;
use Prooph\EventMachine\EventMachine;
use Prooph\EventMachine\EventMachineDescription;

class Aggregate implements EventMachineDescription
{
    /**
     * Define aggregate names using constants
     *
     * @example
     *
     * const USER = 'User';
     */
    public const USER = 'User';

    /**
     * @param EventMachine $eventMachine
     */
    public static function describe(EventMachine $eventMachine): void
    {
        /**
         * Describe how your aggregates handle commands
         *
         * @example
         *
         * $eventMachine->process(Command::REGISTER_USER) <-- Command name of the command that is expected by the Aggregate's handle method
         *      ->withNew(self::USER) //<-- aggregate type, defined as constant above, also tell event machine that a new Aggregate should be created
         *      ->identifiedBy(Payload::USER_ID) //<-- Payload property (of all user related commands) that identify the addressed User
         *      ->handle([User::class, 'register']) //<-- Aggregates are stateless and have static callable methods that can be linked to using PHP's callable array syntax
         *      ->recordThat(Event::USER_REGISTERED) //<-- Event name of the event yielded by the Aggregate's handle method
         *      ->apply([User::class, 'whenUserRegistered']) //<-- Aggregate method (again static) that is called when event is recorded
         *      ->orRecordThat(Event::DOUBLE_REGISTRATION_DETECTED) //Alternative event that can be yielded by the Aggregate's handle method
         *      ->apply([User::class, 'whenDoubleRegistrationDetected']); //Again the method that should be called in case above event is recorded
         *
         * $eventMachine->process(Command::CHANGE_USERNAME) //<-- User::changeUsername() expects a Command::CHANGE_USERNAME command
         *      ->withExisting(self::USER) //<-- Aggregate should already exist, Event Machine uses Payload::USER_ID to load User from event store
         *      ->handle([User::class, 'changeUsername'])
         *      ->recordThat(Event::USERNAME_CHANGED)
         *      ->apply([User::class, 'whenUsernameChanged']);
         */
        $eventMachine->process(Command::CREATE_USER_WITH_IDENTITY)
            ->provideContext(SocialiteContextProvider::class)
            ->withNew(self::USER)
            ->identifiedBy(Payload::USER_ID)
            ->handle([User::class, 'createUserWithIdentity'])
            ->recordThat(Event::USER_CREATED_WITH_IDENTITY)
            ->apply([User::class, 'whenUserCreatedWithIdentity']);

        $eventMachine->process(Command::ADD_USER_IDENTITY)
            ->provideContext(SocialiteContextProvider::class)
            ->withExisting(self::USER)
            ->identifiedBy(Payload::USER_ID)
            ->handle([User::class, 'addUserIdentity'])
            ->recordThat(Event::USER_IDENTITY_ADDED)
            ->apply([User::class, 'whenUserIdentityAdded']);
    }
}
