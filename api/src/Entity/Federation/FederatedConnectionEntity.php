<?php

namespace App\Entity\Federation;

use App\Model\Fillable;
use App\Model\FillTrait;
use App\Model\Referable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidV4Generator;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity
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
    public const STATE_PAUSE = "pause";

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
    private string $state = self::STATE_NEW;

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

    public function getOpenTime(): \DateTimeInterface
    {
        return $this->openTime;
    }

    public function setOpenTime(\DateTimeInterface $openTime): void
    {
        $this->openTime = $openTime;
    }

    public function getCloseTime(): ?\DateTimeInterface
    {
        return $this->closeTime;
    }

    public function setCloseTime(?\DateTimeInterface $closeTime): void
    {
        $this->closeTime = $closeTime;
    }

    public function getLastCheck(): \DateTimeInterface
    {
        return $this->lastCheck;
    }

    public function setLastCheck(\DateTimeInterface $lastCheck): void
    {
        $this->lastCheck = $lastCheck;
    }

    public function getNextCheck(): \DateTimeInterface
    {
        return $this->nextCheck;
    }

    public function setNextCheck(\DateTimeInterface $nextCheck): void
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

    public function isOpen(): bool
    {
        return in_array($this->state, [ self::STATE_READY, self::STATE_BACKOFF ]);
    }

    public function isClosed(): bool
    {
        return !$this->isOpen();
    }
}
