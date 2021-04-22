<?php

namespace App\Entity\Federation;

use App\Entity\ReferableEntityTrait;
use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity
 */
class FederatedConnectionEntity implements Referable, Fillable
{
    use ReferableEntityTrait, FillTrait;

    /**
     * Connection is open and ready to accept connections.
     */
    const STATE_READY = "ready";

    /**
     * Connection has some problems and should be checked later.
     */
    const STATE_BACKOFF = "backoff";

    /**
     * Connection failed too many times and was closed.
     */
    const STATE_ERROR = "error";

    /**
     * Connection was closed by the server.
     */
    const STATE_CLOSED = "closed";

    /**
     * Federated server associated with this connection. In principle server can have multiple connections, it's recommended though.
     * @ORM\ManyToOne(targetEntity=FederatedServerEntity::class, inversedBy="connections")
     */
    private FederatedServerEntity $server;

    /**
     * Base URL address for this particular connection.
     * @ORM\Column(type="string")
     */
    private string $url;

    /**
     * Time when connection was opened by the federated server.
     * @ORM\Column(type="datetime")
     */
    private \DateTimeInterface $openTime;

    /**
     * Time when connection was closed.
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $closeTime;

    /**
     * Time of the last connection check.
     * @ORM\Column(type="datetime")
     */
    private \DateTimeInterface $lastCheck;

    /**
     * Time of the earliest next connection check.
     * @ORM\Column(type="datetime")
     */
    private \DateTimeInterface $nextCheck;

    /**
     * Number of failed checks, zeroed after successful check.
     * @ORM\Column(type="integer")
     */
    private int $failures = 0;

    /**
     * Number of failed checks
     * @ORM\Column(type="integer")
     */
    private int $failuresTotal = 0;

    /**
     * Current state of the connection.
     * @see self::STATE_*
     *
     * @ORM\Column(type="string")
     */
    private string $state = self::STATE_READY;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    public function isReady(): bool
    {
        return in_array($this->state, [ self::STATE_READY ]);
    }

    public function isOpen(): bool
    {
        return in_array($this->state, [ self::STATE_READY, self::STATE_BACKOFF ]);
    }

    public function isClosed(): bool
    {
        return !$this->isOpen();
    }
}
