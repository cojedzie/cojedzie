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

namespace App\Serialization\Normalizer;

use App\Exception\InvalidFormException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class InvalidFormExceptionNormalizer implements NormalizerInterface
{
    public function normalize($exception, $format = null, array $context = [])
    {
        /** @var InvalidFormException $exception */

        return [
            'type'           => 'https://cojedzie.pl/api/v1/error/invalid-form',
            'title'          => $exception->getMessage(),
            'invalid-params' => $this->normalizeFormErrors($exception),
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return 'json' === $format && $data instanceof InvalidFormException;
    }

    private function normalizeFormErrors(InvalidFormException $exception): array
    {
        $result = [];

        /** @var FormError $error */
        foreach ($exception->getErrors() as $error) {
            $path = $this->normalizeFormErrorPath($error->getOrigin());

            $result[$path][] = $error->getMessage();
        }

        return array_map(
            fn (string $path, array $messages) => [
                'path'     => $path,
                'messages' => $messages,
            ],
            array_keys($result),
            array_values($result)
        );
    }

    private function normalizeFormErrorPath(FormInterface $origin): string
    {
        $name = $origin->isRoot() ? '$' : $origin->getName();

        return $origin->getParent() && !$origin->getParent()->isRoot()
            ? sprintf("%s.%s", $this->normalizeFormErrorPath($origin->getParent()), $name)
            : $name;
    }
}
