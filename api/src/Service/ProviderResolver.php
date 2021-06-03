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

namespace App\Service;

use App\Exception\NonExistentServiceException;
use App\Provider\Dummy\DummyProvider;
use App\Provider\Provider;
use Illuminate\Support\Collection;
use Kadet\Functional\Predicates as p;
use Kadet\Functional\Transforms as t;

class ProviderResolver
{
    private $providers;

    public function __construct($providers, bool $debug)
    {
        $this->providers = collect($providers)->keyBy(t\property('identifier'));

        if (!$debug) {
            $this->providers = $this->providers->filter(p\instance(DummyProvider::class)->negate());
        }
    }

    public function resolve(?string $name): ?Provider
    {
        if (empty($name)) {
            return null;
        }

        if (!$this->providers->has($name)) {
            $message = sprintf(
                "Provider '%s' doesn't exist, you can choose from: %s",
                $name,
                $this->providers->keys()->implode(', ')
            );

            throw new NonExistentServiceException($message);
        }

        return $this->providers->get($name);
    }

    /** @return Provider[] */
    public function all(): Collection
    {
        return clone $this->providers;
    }
}
