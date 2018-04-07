<?php

declare(strict_types=1);

namespace App\Infrastructure\ContextProvider;

use App\Api\Payload;
use App\Model\User\CouldNotAuthenticateIdentityException;
use Overtrue\Socialite\AccessToken;
use Overtrue\Socialite\SocialiteManager;
use Overtrue\Socialite\User;
use Prooph\EventMachine\Aggregate\ContextProvider;
use Prooph\EventMachine\Messaging\Message;

class SocialiteContextProvider implements ContextProvider
{
    /**
     * @var SocialiteManager
     */
    private $socialite;

    public function __construct(SocialiteManager $socialite)
    {
        $this->socialite = $socialite;
    }

    /**
     * @param Message $command
     *
     * @return mixed The context passed as last argument to aggregate functions
     */
    public function provide(Message $command): User
    {
        $identity = $command->get(Payload::IDENTITY);

        try {
            // @TODO Handle errors from JSON
            return $this->socialite
                ->driver($identity[Payload::IDENTITY_PROVIDER])
                ->user(new AccessToken(['access_token' => $identity[Payload::IDENTITY_TOKEN]]));
        } catch (\Throwable $t) {
            throw new CouldNotAuthenticateIdentityException($t);
        }

    }
}
