<?php

namespace App\Filter\Handler\Common;

use App\Context\ProviderContext;
use App\Dto\HasRefs;
use App\Event\PostProcessEvent;
use App\Filter\Handler\PostProcessingHandler;
use App\Filter\Requirement\IdConstraint;
use App\Provider\FluentRepository;
use App\Service\ContentTypeResolver;
use Ds\Set;
use Kadet\Functional\Transforms as t;

class RefsEmbedHandler implements PostProcessingHandler
{
    public function __construct(
        private readonly ProviderContext $providerContext,
        private readonly ContentTypeResolver $contentTypeResolver,
    ) {
    }

    public function postProcess(PostProcessEvent $event): void
    {
        /** @var \App\Filter\Requirement\Embed $requirement */
        $requirement = $event->getRequirement();

        if (!str_starts_with($requirement->getRelationship(), '$refs.')) {
            return;
        }

        /** @var iterable<\App\Dto\Dto> $subject */
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

            /** @var \App\Dto\CollectionResult $collection */
            $collection = $refs->{$name};

            foreach ($collection->getItems() as $item) {
                $type = $this->contentTypeResolver->getContentType($item);

                $entitiesToLoad[$type] ??= new Set();
                $entitiesToLoad[$type]->add($item->getId());
            }
        }

        foreach ($entitiesToLoad as $type => $identifiers) {
            $repository = $this->getRepositoryForType($type);
            $entities = $repository->all(new IdConstraint($identifiers))->keyBy(t\getter('id'));
        }

        foreach ($items as $dto) {
            if (!$dto instanceof HasRefs) {
                continue;
            }

            $refs = $dto->getRefs();

            if (!isset($refs->{$name})) {
                continue;
            }

            /** @var \App\Dto\CollectionResult $collection */
            $collection = $refs->{$name};
            $collection->setItems(
                $collection->getItems()->map(fn ($dto) => $entities[$dto->getId()] ?? null)->filter()
            );
        }
    }

    public function getRepositoryForType(string $type): FluentRepository
    {
        $provider = $this->providerContext->getProvider();

        return match ($type) {
            'vnd.cojedzie.line' => $provider->getLineRepository(),
            'vnd.cojedzie.stop' => $provider->getStopRepository(),
        };
    }
}
