<?php

declare(strict_types=1);

namespace App\Infrastructure\Finder;

use App\Api\Payload;
use App\Api\Query;
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
    private $collectionName;

    /**
     * @var SocialiteManager
     */
    private $socialiteManager;

    public function __construct(string $collectionName, DocumentStore $documentStore, SocialiteManager $socialiteManager)
    {
        $this->collectionName = $collectionName;
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
        return $this->documentStore->getDoc($this->collectionName, $message->get(Payload::USER_ID));
    }

    private function getUserByIdentity(Message $message): ?array
    {
        $identity = $message->get(Payload::IDENTITY);

        $user = $this->socialiteManager
            ->driver($identity[Payload::IDENTITY_PROVIDER])
            ->user(new AccessToken(['access_token' => $identity[Payload::IDENTITY_TOKEN]]));

        $users = $this->documentStore->filterDocs(
            $this->collectionName,
            new DocumentStore\Filter\EqFilter(
                sprintf('identities.%s.id', $identity[Payload::IDENTITY_PROVIDER]),
                $user->getId()
            )
        );

        // We only care about the first user found.
        foreach ($users as $user) {
            return $user;
        }

        $users = $this->documentStore->filterDocs(
            $this->collectionName,
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
