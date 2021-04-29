<?php

namespace App\Entity\Federation;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use App\Repository\FederatedConnectionEntityRepository;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidV4Generator;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity(repositoryClass=FederatedConnectionEntityRepository::class)
 * @ORM\Table("federated_connection")
 */
class FederatedConnectionEntity implements Referable, Fillable
{
    use FillTrait;

    /**
     * Connection is new and awaiting it's first check.
     */
    public const STATE_NEW = "new";

    /**
     * Connection is open and ready to accept connections.
     */
    public const STATE_READY = "ready";

    /**
     * Connection is open but is not accepting connections. It can happen when for example node is synchronising data.
     */
    public const STATE_SUSPENDED = "suspended";

    /**
     * Connection has some problems and should be checked later.
     */
    public const STATE_BACKOFF = "backoff";

    /**
     * Connection failed too many times and was closed.
     */
    public const STATE_ERROR = "error";

    /**
     * Connection was closed by the server.
     */
    public const STATE_CLOSED = "closed";

    public const OPEN_STATES   = [ self::STATE_NEW, self::STATE_READY, self::STATE_SUSPENDED, self::STATE_BACKOFF ];
    public const CLOSED_STATES = [ self::STATE_ERROR, self::STATE_CLOSED ];

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
    private Carbon $openedAt;

    /**
     * Time when connection was closed.
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?Carbon $closedAt = null;

    /**
     * Time of the last connection check.
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?Carbon $lastCheck = null;

    /**
     * Time of the earliest next connection check.
     * @ORM\Column(type="datetime")
     */
    private Carbon $nextCheck;

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
    private string $state = self::STATE_NEW;

    /**
     * Last status received from the server.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $lastStatus;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getServer(): FederatedServerEntity
    {
        return $this->server;
    }

    public function setServer(FederatedServerEntity $server): void
    {
        $this->server = $server;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getOpenedAt(): Carbon
    {
        return $this->openedAt;
    }

    public function setOpenedAt(Carbon $openedAt): void
    {
        $this->openedAt = $openedAt;
    }

    public function getClosedAt(): ?Carbon
    {
        return $this->closedAt;
    }

    public function setClosedAt(?Carbon $closedAt): void
    {
        $this->closedAt = $closedAt;
    }

    public function getLastCheck(): ?Carbon
    {
        return $this->lastCheck;
    }

    public function setLastCheck(?Carbon $lastCheck): void
    {
        $this->lastCheck = $lastCheck;
    }

    public function getNextCheck(): Carbon
    {
        return $this->nextCheck;
    }

    public function setNextCheck(Carbon $nextCheck): void
    {
        $this->nextCheck = $nextCheck;
    }

    public function getFailures(): int
    {
        return $this->failures;
    }

    public function setFailures(int $failures): void
    {
        $this->failures = $failures;
    }

    public function getFailuresTotal(): int
    {
        return $this->failuresTotal;
    }

    public function setFailuresTotal(int $failuresTotal): void
    {
        $this->failuresTotal = $failuresTotal;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function isReady(): bool
    {
        return in_array($this->state, [ self::STATE_READY ]);
    }

    public function isSuspended(): bool
    {
        return in_array($this->state, [ self::STATE_SUSPENDED ]);
    }

    public function isOpen(): bool
    {
        return in_array($this->state, self::OPEN_STATES);
    }

    public function isClosed(): bool
    {
        return !$this->isOpen();
    }

    public function increaseFailureCount()
    {
        $this->failures++;
        $this->failuresTotal++;
    }

    public function resetFailureCount()
    {
        $this->failures = 0;
    }

    public function getLastStatus(): string
    {
        return $this->lastStatus;
    }

    public function setLastStatus(string $lastStatus): void
    {
        $this->lastStatus = $lastStatus;
    }
}
