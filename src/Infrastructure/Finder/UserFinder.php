<?php

declare(strict_types=1);

namespace App\Infrastructure\Finder;

use App\Api\Payload;
use App\Api\Query;
use App\Infrastructure\Projection\LoginIdentityProjector;
use Overtrue\Socialite\AccessToken;
use Overtrue\Socialite\SocialiteManager;
use Prooph\EventMachine\Messaging\Message;
use Prooph\EventMachine\Persistence\DocumentStore;
use React\Promise\Deferred;

class UserFinder
{
    /**
     * @var DocumentStore
     */
    private $documentStore;

    /**
     * @var string
     */
    private $userCollection;

    /**
     * @var string
     */
    private $loginIdentityCollection;

    /**
     * @var SocialiteManager
     */
    private $socialiteManager;

    public function __construct(
        string $userCollection,
        string $loginIdentityCollection,
        DocumentStore $documentStore,
        SocialiteManager $socialiteManager
    ) {
        $this->userCollection = $userCollection;
        $this->loginIdentityCollection = $loginIdentityCollection;
        $this->documentStore = $documentStore;
        $this->socialiteManager = $socialiteManager;
    }

    public function __invoke(Message $message, Deferred $deferred): void
    {
        switch ($message->messageName()) {
            case Query::USER_BY_IDENTITY:
                $deferred->resolve($this->getUserByIdentity($message));

                break;
            case Query::USER:
                $deferred->resolve($this->getuserById($message));

                break;
        }
    }

    private function getUserById(Message $message): ?array
    {
        return $this->documentStore->getDoc($this->userCollection, $message->get(Payload::USER_ID));
    }

    private function getUserByIdentity(Message $message): ?array
    {
        $identity = $message->get(Payload::IDENTITY);

        $user = $this->socialiteManager
            ->driver($identity[Payload::IDENTITY_PROVIDER])
            ->user(new AccessToken(['access_token' => $identity[Payload::IDENTITY_TOKEN]]));

        $loginIdentity = $this->documentStore->getDoc(
            $this->loginIdentityCollection,
            LoginIdentityProjector::generateId(strtolower($user->getProviderName()), $user->getId())
        );


        if ($loginIdentity !== null) {
            return $this->documentStore->getDoc($this->userCollection, $loginIdentity[Payload::USER_ID]);
        }

        $users = $this->documentStore->filterDocs(
            $this->userCollection,
            new DocumentStore\Filter\EqFilter(
                'email',
                $user->getEmail()
            )
        );

        // We only care about the first user found.
        foreach ($users as $user) {
            return $user;
        }

        return null;
    }
}
