<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Security;

use App\Entity\Federation\FederatedServerEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Authenticates federated servers by HTTP Basic credentials: the server id as the user and its secret as the
 * password. Missing or invalid credentials get a 401 shaped like the API's other errors.
 */
class FederatedServerAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    final public const string CHALLENGE = 'Basic realm="Co Jedzie federation"';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SerializerInterface $serializer,
    ) {
    }

    #[\Override]
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('PHP_AUTH_USER');
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        return new Passport(
            new UserBadge((string) $request->headers->get('PHP_AUTH_USER'), $this->loadServer(...)),
            new PasswordCredentials((string) $request->headers->get('PHP_AUTH_PW')),
        );
    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    #[\Override]
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->start($request, $exception);
    }

    #[\Override]
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        $exception = new UnauthorizedHttpException(self::CHALLENGE, 'Invalid federated server credentials.', $authException);

        return new JsonResponse(
            $this->serializer->serialize(FlattenException::createFromThrowable($exception), 'json', ['exception' => $exception]),
            $exception->getStatusCode(),
            $exception->getHeaders(),
            json: true,
        );
    }

    private function loadServer(string $id): ?FederatedServerEntity
    {
        return Uuid::isValid($id) ? $this->em->find(FederatedServerEntity::class, Uuid::fromString($id)) : null;
    }
}
