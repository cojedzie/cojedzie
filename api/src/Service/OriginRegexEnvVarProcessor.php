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

namespace App\Service;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Turns a URL into a regex matching exactly its origin (scheme, host and port), for configs that take origin
 * regexes, e.g. %env(origin_regex:FEDERATION_HUB)% in CORS.
 */
class OriginRegexEnvVarProcessor implements EnvVarProcessorInterface
{
    // matches nothing, for a URL without an origin
    private const string NOTHING = '(?!)';

    #[\Override]
    public function getEnv(string $prefix, string $name, \Closure $getEnv): string
    {
        $url = parse_url((string) $getEnv($name));

        if (!isset($url['scheme'], $url['host'])) {
            return self::NOTHING;
        }

        $origin = sprintf('%s://%s%s', $url['scheme'], $url['host'], isset($url['port']) ? ':' . $url['port'] : '');

        return '^' . preg_quote($origin) . '$';
    }

    #[\Override]
    public static function getProvidedTypes(): array
    {
        return [
            'origin_regex' => 'string',
        ];
    }
}
