<?php

declare(strict_types=1);

namespace App\Http;

use App\Api\Command;
use App\Api\Payload;
use App\Api\Query;
use App\Api\Schema;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Overtrue\Socialite\SocialiteManager;
use Prooph\EventMachine\EventMachine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;
use Zend\Diactoros\Response\JsonResponse;

class SocialLoginHandler implements RequestHandlerInterface
{
    /**
     * @var EventMachine
     */
    private $eventMachine;

    /**
     * @var SocialiteManager
     */
    private $socialiteManager;

    public function __construct(EventMachine $eventMachine, SocialiteManager $socialiteManager)
    {
        $this->eventMachine = $eventMachine;
        $this->socialiteManager = $socialiteManager;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $this->getBody($request);
        $provider = $body[Payload::IDENTITY_PROVIDER];
        $token = $body[Payload::IDENTITY_TOKEN];

        $identityQuery = $this->eventMachine->messageFactory()->createMessageFromArray(
            Query::USER_BY_IDENTITY,
            [
                'payload' => [
                    Payload::IDENTITY => $body
                ]
            ]
        );

        $foundUser = null;

        $this->eventMachine->dispatch($identityQuery)
            ->done(function (?array $user) use (&$foundUser) {
                $foundUser = $user;
            });

        if ($foundUser !== null) {
            $userId = $foundUser[Payload::USER_ID];

            if (!isset($foundUser['identities'][$provider])) {
                $addIdentityCommand = $this->eventMachine->messageFactory()->createMessageFromArray(
                    Command::ADD_USER_IDENTITY,
                    [
                        'payload' => [
                            Payload::USER_ID => $userId,
                            Payload::IDENTITY => [
                                Payload::IDENTITY_PROVIDER => $provider,
                                Payload::IDENTITY_TOKEN => $token,
                            ]
                        ]
                    ]
                );

                $this->eventMachine->dispatch($addIdentityCommand);
            }
        } else {
            $userId = Uuid::uuid4()->toString();

            $createUserCommand = $this->eventMachine->messageFactory()->createMessageFromArray(
                Command::CREATE_USER_WITH_IDENTITY,
                [
                    'payload' => [
                        Payload::USER_ID => $userId,
                        Payload::IDENTITY => [
                            Payload::IDENTITY_PROVIDER => $provider,
                            Payload::IDENTITY_TOKEN => $token,
                        ]
                    ]
                ]
            );

            $this->eventMachine->dispatch($createUserCommand);
        }

        $jwt = (new Builder())
            ->setSubject($userId)
            ->setIssuedAt(time())
            ->setExpiration(time() + 3600)
            ->setNotBefore(time())
            ->sign(new Sha256(), getenv('APP_SECRET'))
            ->getToken();

        return new JsonResponse(['token' => (string) $jwt]);
    }

    protected function getBody(ServerRequestInterface $request): array
    {
        $this->eventMachine->jsonSchemaAssertion()->assert(
            'Login',
            $request->getParsedBody(),
            Schema::userIdentityInput()->toArray()
        );

        return $request->getParsedBody();
    }
}
