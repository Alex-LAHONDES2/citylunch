<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class JwtAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly JwtTokenManager $jwtTokenManager,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $path = $request->getPathInfo();
        if (!str_starts_with($path, '/api/bag')) {
            return false;
        }

        $header = (string) $request->headers->get('Authorization', '');

        return str_starts_with($header, 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = (string) $request->headers->get('Authorization', '');
        $token = trim(substr($header, 7));

        if ($token === '') {
            throw new CustomUserMessageAuthenticationException('Missing bearer token');
        }

        try {
            $payload = $this->jwtTokenManager->parse($token);
        } catch (\Throwable) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }

        $userId = $payload['sub'] ?? null;
        if (!\is_int($userId) && !(\is_string($userId) && ctype_digit($userId))) {
            throw new CustomUserMessageAuthenticationException('Invalid token payload');
        }

        $identifier = (string) $userId;

        return new SelfValidatingPassport(
            new UserBadge($identifier, function (string $userIdentifier) {
                $user = $this->userRepository->find((int) $userIdentifier);
                if (!$user) {
                    $e = new UserNotFoundException(sprintf('User "%s" not found.', $userIdentifier));
                    $e->setUserIdentifier($userIdentifier);
                    throw $e;
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?JsonResponse
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): ?JsonResponse
    {
        return new JsonResponse(
            [
                'error' => $exception->getMessageKey(),
            ],
            Response::HTTP_UNAUTHORIZED
        );
    }

    public function start(Request $request, ?\Symfony\Component\Security\Core\Exception\AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
    }
}
