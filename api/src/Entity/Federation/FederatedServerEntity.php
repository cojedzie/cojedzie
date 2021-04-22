<?php

namespace App\Entity\Federation;

use App\Entity\ReferableEntityTrait;
use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity
 */
class FederatedServerEntity implements Referable, Fillable
{
    use FillTrait, ReferableEntityTrait;

    /**
     * Contact email to person responsible for this federated server.
     * @ORM\Column(type="string")
     */
    private string $email;

    /**
     * The name of federated server maintainer, could be full name but nicknames are allowed also.
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $maintainer;

    /**
     * Base URL associated with this federated server, could be a regex pattern.
     */
    private string $baseUrl;

    /**
     * All servers that are connected at the moment.
     * @ORM\OneToMany(targetEntity=FederatedConnectionEntity::class, cascade="persist", mappedBy="server", orphanRemoval=true)
     * @var Collection|FederatedConnectionEntity[]
     */
    private Collection $connections;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->connections = new ArrayCollection();
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

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getConnections(): Collection
    {
        return $this->connections;
    }
}
