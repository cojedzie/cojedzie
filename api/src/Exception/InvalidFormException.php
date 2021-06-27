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

namespace App\Exception;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidFormException extends HttpException
{
    /** @var FormInterface */
    private $form;

    public function __construct(
        FormInterface $form,
        int $statusCode = Response::HTTP_BAD_REQUEST,
        string $message = 'Submitted form contains invalid parameters.',
        \Throwable $previous = null,
        array $headers = [],
        ?int $code = 0
    ) {
        parent::__construct($statusCode, $message, $previous, $headers, $code);

        $this->form = $form;
    }

    public function getForm(): FormInterface
    {
        return $this->form;
    }

    public function getErrors()
    {
        return $this->form->getErrors(true);
    }
}
