<?php

namespace App\Filter\Handler\Common;

use App\Context\ProviderContext;
use App\Dto\CollectionResult;
use App\Dto\Dto;
use App\Dto\HasRefs;
use App\Event\PostProcessEvent;
use App\Filter\Handler\PostProcessingHandler;
use App\Filter\Requirement\Embed;
use App\Filter\Requirement\IdConstraint;
use App\Provider\FluentRepository;
use App\Service\ContentTypeResolver;
use Ds\Map;
use Ds\Set;
use Kadet\Functional\Collections as c;
use Kadet\Functional\Transforms as t;

class RefsEmbedHandler implements PostProcessingHandler
{
    public function __construct(
        private readonly ProviderContext $providerContext,
        private readonly ContentTypeResolver $contentTypeResolver,
    ) {
    }

    #[\Override]
    public function postProcess(PostProcessEvent $event): void
    {
        /** @var Embed $requirement */
        $requirement = $event->getRequirement();

        if (!str_starts_with($requirement->getRelationship(), '$refs.')) {
            return;
        }

        /** @var iterable<Dto> $items */
        $items = $event->getData();

        $name = substr($requirement->getRelationship(), 6);

        $entitiesToLoad = [];
        foreach ($items as $dto) {
            if (!$dto instanceof HasRefs) {
                continue;
            }

            $refs = $dto->getRefs();

            if (!isset($refs->{$name})) {
                continue;
            }

            /** @var CollectionResult $collection */
            $collection = $refs->{$name};

            foreach ($collection->getItems() as $item) {
                $type = $this->contentTypeResolver->getContentType($item);

                $entitiesToLoad[$type] ??= new Set();
                $entitiesToLoad[$type]->add($item->getId());
            }
        }

        $entities = new Map();
        foreach ($entitiesToLoad as $type => $identifiers) {
            $entities->putAll(
                $this->getRepositoryForType($type)->all(new IdConstraint($identifiers)) |> c\keyBy(t\getter('id'))
            );
        }

        foreach ($items as $dto) {
            if (!$dto instanceof HasRefs) {
                continue;
            }

            $refs = $dto->getRefs();

            if (!isset($refs->{$name})) {
                continue;
            }

            /** @var CollectionResult $collection */
            $collection = $refs->{$name};
            $collection->setItems(
                $collection->getItems()
                    |> c\map(fn ($dto) => $entities->get($dto->getId(), null))
                    |> c\filter()
            );
        }
    }

    public function getRepositoryForType(string $type): FluentRepository
    {
        $provider = $this->providerContext->getProvider();

        return match ($type) {
            'vnd.cojedzie.line' => $provider->getLineRepository(),
            'vnd.cojedzie.stop' => $provider->getStopRepository(),
            default             => throw new \LogicException(sprintf('References of type %s can\'t be embedded.', $type)),
        };
    }
}
