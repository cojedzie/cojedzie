<?php

namespace App\Dto;

use App\Dto\Links\CollectionLinks;
use App\Utility\IterableUtils;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ContentType('vnd.cojedzie.collection')]
class CollectionResult
{
    public function __construct(
        /**
         * @OA\Property(type="array", @OA\Items(type="object"))
         */
        private array $items,
        private int $total,
        #[SerializedName('$links')]
        private CollectionLinks $links,
    ) {
    }

    public function getCount(): int
    {
        return count($this->items);
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getLinks(): CollectionLinks
    {
        return $this->links;
    }

    public static function createFromIterable(iterable $items, int $total, CollectionLinks $links): static
    {
        return new static(
            IterableUtils::toArray($items),
            $total,
            $links
        );
    }
}
