<?php

namespace App\Dto;

use App\Dto\Links\CollectionLinks;
use App\Utility\IterableUtils;
use Ds\Collection;
use Ds\Set;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ContentType('vnd.cojedzie.collection')]
class CollectionResult
{
    public function __construct(
        private Set $items = new Set(),
        private int $total = 0,
        #[SerializedName('$links')]
        private CollectionLinks $links = new CollectionLinks(),
    ) {
        if ($this->total < count($this->items)) {
            $this->total = count($this->items);
        }
    }

    public function getCount(): int
    {
        return count($this->items);
    }

    public function getItems(): Set
    {
        return $this->items;
    }

    public function setItems(Set $items): void
    {
        $this->items = $items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getLinks(): CollectionLinks
    {
        return $this->links;
    }

    public function setLinks(CollectionLinks $links): void
    {
        $this->links = $links;
    }

    public static function createFromIterable(iterable $items, int $total, CollectionLinks $links): static
    {
        return new static(
            new Set($items),
            $total,
            $links
        );
    }
}
