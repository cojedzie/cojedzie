<?php
/*
 * Copyright (C) 2026 Kacper Donat
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

declare(strict_types=1);

namespace App\Tests\Behat\Support;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Sends requests to the API through the kernel and keeps the last response for assertions.
 */
final class ApiClient
{
    private ?Response $response = null;

    /**
     * @var array{string, string}|null HTTP Basic credentials sent with every following request
     */
    private ?array $credentials = null;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function get(string $uri, array $query = []): Response
    {
        return $this->request(Request::METHOD_GET, $uri, $query);
    }

    public function authenticate(?string $user, ?string $password): void
    {
        $this->credentials = $user === null ? null : [$user, (string) $password];
    }

    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $uri, array $query = [], ?string $body = null, array $headers = []): Response
    {
        // a real request starts with an empty identity map, so relations its query doesn't fetch stay lazy
        $this->em->clear();

        $server = $body === null ? [] : ['CONTENT_TYPE' => 'application/json'];
        if ($this->credentials !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode(implode(':', $this->credentials));
        }

        // headers go through the server bag, as PHP passes them, so derived ones (PHP_AUTH_USER, ...) get parsed
        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        $request = Request::create($uri, $method, $query, server: $server, content: $body);
        $request->headers->set('Accept', 'application/json');

        return $this->response = $this->kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, catch: true);
    }

    public function lastResponse(): Response
    {
        return $this->response ?? throw new \LogicException('No request has been sent yet.');
    }

    public function json(): mixed
    {
        return json_decode((string) $this->lastResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
