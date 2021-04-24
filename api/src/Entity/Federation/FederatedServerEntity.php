<?php

namespace App\Entity\Federation;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidV4Generator;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table("federated_server")
 */
class FederatedServerEntity implements Referable, Fillable
{
    use FillTrait;

    /**
     * Unique identifier for this particular connection.
     *
     * @ORM\Column(type="uuid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidV4Generator::class)
     */
    private Uuid $id;

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
     * Allowed URL associated with this federated server, could be a regex pattern.
     * @ORM\Column(type="string")
     */
    private string $allowedUrl;

    /**
     * All servers that are connected at the moment.
     * @ORM\OneToMany(targetEntity=FederatedConnectionEntity::class, cascade="persist", mappedBy="server", orphanRemoval=true)
     * @var Collection|FederatedConnectionEntity[]
     */
    private Collection $connections;

    public function __construct()
    {
        $this->connections = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
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
}
