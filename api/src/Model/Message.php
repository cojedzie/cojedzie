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

namespace App\Model;

use Carbon\Carbon;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;

class Message implements Fillable, DTO
{
    use FillTrait;

    final public const TYPE_INFO      = 'info';
    final public const TYPE_BREAKDOWN = 'breakdown';
    final public const TYPE_UNKNOWN   = 'unknown';

    /**
     * Message content.
     * @OA\Property(example="Tram accident on Haller alley, possible delays on lines: 2, 3, 4, 5.")
     */
    #[Serializer\Type('string')]
    private string $message;

    /**
     * Message type, see TYPE_* constants
     * @OA\Property(type="string", enum={ Message::TYPE_INFO, Message::TYPE_BREAKDOWN, Message::TYPE_UNKNOWN })
     */
    #[Serializer\Type('string')]
    private string $type = self::TYPE_UNKNOWN;

    /**
     * Message validity time span start
     * @OA\Property(type="string", format="date-time")
     */
    #[Serializer\Type('Carbon')]
    private ?Carbon $validFrom = null;

    /**
     * Message validity time span end
     * @OA\Property(type="string", format="date-time")
     */
    #[Serializer\Type('Carbon')]
    private ?Carbon $validTo = null;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function getValidFrom(): ?Carbon
    {
        return $this->validFrom;
    }

    public function setValidFrom(?Carbon $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidTo(): ?Carbon
    {
        return $this->validTo;
    }

    public function setValidTo(?Carbon $validTo): void
    {
        $this->validTo = $validTo;
    }
}
