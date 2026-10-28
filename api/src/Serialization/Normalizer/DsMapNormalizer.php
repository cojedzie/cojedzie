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

namespace App\Serialization\Normalizer;

use Ds\Map;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes a Map to an array with its keys.
 *
 * Without it the Map would be handled by JsonSerializableNormalizer, whose `jsonSerialize()` returns an object that
 * the object normalizer then empties out when serialization groups are set.
 */
class DsMapNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        /** @var Map $data */
        return $this->normalizer->normalize($data->toArray(), $format, $context);
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Map;
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [Map::class => true];
    }
}
