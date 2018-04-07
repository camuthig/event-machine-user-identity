<?php

declare(strict_types=1);

namespace App\Http;

use Fig\Http\Message\StatusCodeInterface;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

class JwtMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationHeader = $request->getHeader('authorization');

        if (empty($authorizationHeader)) {
            // @TODO determine the best formatting for errors
            return new JsonResponse(
                ['error' => 'Missing authorization header'],
                StatusCodeInterface::STATUS_UNAUTHORIZED
            );
        }

        $authorizationHeader = explode(' ', array_shift($authorizationHeader), 2);
        $authorizationHeader = array_pop($authorizationHeader);

        try {
            $token = (new Parser())->parse($authorizationHeader);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Invalid authorization token'],
                StatusCodeInterface::STATUS_UNAUTHORIZED
            );
        }

        if (!$token->verify(new Sha256(), getenv('APP_SECRET')) || !$token->validate(new ValidationData())) {
            return new JsonResponse(
                ['error' => 'Invalid authorization token'],
                StatusCodeInterface::STATUS_UNAUTHORIZED
            );
        }

        return $handler->handle($request);
    }
}
