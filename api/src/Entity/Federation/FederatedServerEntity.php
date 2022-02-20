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

namespace App\Entity\Federation;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table('federated_server')]
class FederatedServerEntity implements Referable, Fillable
{
    use FillTrait;

    /**
     * Unique identifier for this particular connection.
     */
    #[ORM\Column(type: 'uuid')]
    #[ORM\Id]
    #[Serializer\Type('uuid')]
    private Uuid $id;

    /**
     * Contact email to person responsible for this federated server.
     */
    #[ORM\Column(type: 'string')]
    private string $email;

    /**
     * The name of federated server maintainer, could be full name but nicknames are allowed also.
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $maintainer = null;

    /**
     * Allowed URL associated with this federated server, could be a regex pattern.
     */
    #[ORM\Column(type: 'string')]
    private string $allowedUrl;

    /**
     * All servers that are connected at the moment.
     * @var Collection<FederatedConnectionEntity>
     */
    #[ORM\OneToMany(targetEntity: FederatedConnectionEntity::class, cascade: ['persist'], mappedBy: 'server', orphanRemoval: true)]
    #[Serializer\Groups(['Connections', 'All'])]
    private readonly Collection $connections;

    /**
     * Secret for that server required for authenticating some endpoints.
     */
    #[ORM\Column(type: 'string')]
    #[Serializer\Exclude]
    private string $secret;

    public function __construct()
    {
        $this->connections = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getMaintainer(): ?string
    {
        return $this->maintainer;
    }

    public function setMaintainer(?string $maintainer): void
    {
        $this->maintainer = $maintainer;
    }

    public function getAllowedUrl(): string
    {
        return $this->allowedUrl;
    }

    public function setAllowedUrl(string $allowedUrl): void
    {
        $this->allowedUrl = $allowedUrl;
    }

    public function getConnections(): Collection
    {
        return $this->connections;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }
}
