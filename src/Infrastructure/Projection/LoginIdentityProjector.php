<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Api\Event;
use App\Api\Payload;
use Prooph\EventMachine\Messaging\Message;
use Prooph\EventMachine\Persistence\DocumentStore;
use Prooph\EventMachine\Projecting\AggregateProjector;
use Prooph\EventMachine\Projecting\Projector;

class LoginIdentityProjector implements Projector
{
    /**
     * @var DocumentStore
     */
    private $documentStore;

    public function __construct(DocumentStore $documentStore)
    {
        $this->documentStore = $documentStore;
    }

    public function prepareForRun(string $appVersion, string $projectionName): void
    {
        if (!$this->documentStore->hasCollection($this->generateCollectionName($appVersion, $projectionName))) {
            $this->documentStore->addCollection($this->generateCollectionName($appVersion, $projectionName));
        }
    }

    public function handle(string $appVersion, string $projectionName, Message $event): void
    {
        switch ($event->messageName()) {
            case Event::USER_CREATED_WITH_IDENTITY:
            case Event::USER_IDENTITY_ADDED:
                $identity = $event->get(Payload::IDENTITY);

                $this->documentStore->addDoc(
                    $this->generateCollectionName($appVersion, $projectionName),
                    self::generateId($identity[Payload::IDENTITY_PROVIDER], $identity[Payload::IDENTITY_ID]),
                    [
                        Payload::USER_ID => $event->get(Payload::USER_ID)
                    ]
                );
        }
    }

    public function deleteReadModel(string $appVersion, string $projectionName): void
    {
        $this->documentStore->dropCollection($this->generateCollectionName($appVersion, $projectionName));
    }

    private function generateCollectionName(string $appVersion, string $projectionName): string
    {
        return AggregateProjector::generateCollectionName($appVersion, $projectionName);
    }

    public static function generateId(string $provider, string $userId): string
    {
        return sprintf('%s:%s', $provider, $userId);
    }
}