<?php

declare(strict_types=1);

namespace App\Model;

use App\Api\Event;
use App\Api\Payload;
use App\Infrastructure\ContextProvider\SocialiteContextProvider;
use App\Model\User\CouldNotAuthenticateIdentityException;
use App\Model\User\State;
use OverTrue\Socialite;
use Prooph\EventMachine\Messaging\Message;

class User
{
    public static function createUserWithIdentity(
        Message $createUserWithIdentity,
        Socialite\User $socialUser
    ): \Generator {
        $provider = $createUserWithIdentity->get(Payload::IDENTITY)[Payload::IDENTITY_PROVIDER];

        $contextualData = [
            Payload::USER_ID => $createUserWithIdentity->get(Payload::USER_ID),
            Payload::USER_NAME => $socialUser->getName(),
            Payload::USER_EMAIL => $socialUser->getEmail(),
            Payload::IDENTITY => [
                Payload::IDENTITY_PROVIDER => $provider,
                Payload::IDENTITY_ID => $socialUser->getId(),
            ]
        ];

        yield [Event::USER_CREATED_WITH_IDENTITY, $contextualData];
    }

    public static function addUserIdentity(
        State $state,
        Message $addUserIdentity,
        Socialite\User $socialUser
    ): \Generator {
        $provider = $addUserIdentity->get(Payload::IDENTITY)[Payload::IDENTITY_PROVIDER];
        if ($state->identityForProvider($provider)) {
            yield [];
        }

        $contextualData = [
            Payload::USER_ID => $addUserIdentity->get(Payload::USER_ID),
            Payload::IDENTITY => [
                Payload::IDENTITY_PROVIDER => $provider,
                Payload::IDENTITY_ID => $socialUser->getId(),
            ]
        ];

        yield [Event::USER_IDENTITY_ADDED, $contextualData];
    }

    public static function whenUserCreatedWithIdentity(Message $userCreatedWithIdentity): State
    {
        $identity = $userCreatedWithIdentity->get(Payload::IDENTITY);
        $payload = $userCreatedWithIdentity->payload();

        unset($payload[Payload::IDENTITY]);

        return State::fromArray($payload)->withIdentity($identity);
    }

    public static function whenUserIdentityAdded(State $state, Message $userIdentityAdded): State
    {
        return $state->withIdentity($userIdentityAdded->get(Payload::IDENTITY));
    }
}
