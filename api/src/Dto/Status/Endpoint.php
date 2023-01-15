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

namespace App\Dto\Status;

use App\Dto\Dto;
use App\Dto\Fillable;
use App\Dto\FillTrait;
use OpenApi\Annotations as OA;

class Endpoint implements Fillable, Dto
{
    use FillTrait;

    /**
     * Name of the endpoint, machine-readable
     *
     * @OA\Property(type="string", example="v1_provider_list")
     */
    private string $name;

    /**
     * Route template for that endpoint.
     *
     * @OA\Property(type="string", example="/api/v1/providers")
     */
    private string $template;

    /**
     * Maximum version supported for that endpoint.
     *
     * @OA\Property(type="string", format="version", example="1.0")
     */
    private string $version;

    /**
     * Methods supported for that endpoint.
     *
     * @OA\Property(
     *     type="array",
     *     @OA\Items(type="string", enum={"GET", "POST", "DELETE", "PUT", "PATCH"})
     * )
     */
    private array $methods;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function setMethods(array $methods): void
    {
        $this->methods = $methods;
    }
}
