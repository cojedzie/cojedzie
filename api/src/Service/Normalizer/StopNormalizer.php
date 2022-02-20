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

namespace App\Service\Normalizer;

use App\Model\Stop;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Tightenco\Collect\Support\Arr;

class StopNormalizer implements NormalizerInterface
{
    public function __construct(private readonly ObjectNormalizer $normalizer)
    {
    }

    public function normalize($object, $format = null, array $context = [])
    {
        return Arr::except($this->normalizer->normalize($object), ['latitude', 'longitude']);
    }

    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Stop;
    }
}
