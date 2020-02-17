<?php

namespace App\Handler\Database;

use App\Entity\LineEntity;
use App\Entity\ProviderEntity;
use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Handler\ModifierHandler;
use App\Model\Line;
use App\Model\Referable;
use App\Model\Track;
use App\Modifier\RelatedFilter;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class RelatedFilterDatabaseGenericHandler implements ModifierHandler, ServiceSubscriberInterface
{
    protected $mapping = [
        Track::class => [
            Line::class => 'line',
        ],
    ];

    protected $references = [
        Line::class => LineEntity::class,
    ];

    private $em;
    private $inner;
    private $id;

    public function __construct(ContainerInterface $inner, EntityManagerInterface $em, IdUtils $idUtils)
    {
        $this->inner = $inner;
        $this->em = $em;
        $this->id = $idUtils;
    }

    public function process(HandleModifierEvent $event)
    {
        if (!$event instanceof HandleDatabaseModifierEvent) {
            return;
        }

        /** @var RelatedFilter $modifier */
        $modifier = $event->getModifier();
        $builder  = $event->getBuilder();
        $alias    = $event->getMeta()['alias'];
        $type     = $event->getMeta()['type'];

        if (!array_key_exists($type, $this->mapping)) {
            throw new \InvalidArgumentException(
                sprintf("Relationship filtering for %s is not supported.", $type)
            );
        }

        if (!array_key_exists($modifier->getRelationship(), $this->mapping[$type])) {
            throw new \InvalidArgumentException(
                sprintf("Relationship %s is not supported for .", $type)
            );
        }

        $relationship = $this->mapping[$type][$modifier->getRelationship()];

        $parameter = sprintf(":%s_%s", $alias, $relationship);
        $reference = $this->getEntityReference($modifier->getRelated(), $event->getMeta()['provider']);

        $builder
            ->join(sprintf('%s.%s', $alias, $relationship), $relationship)
            ->andWhere(sprintf("%s = %s", $relationship, $parameter))
            ->setParameter($parameter, $reference)
        ;
    }

    // todo: extract that to separate service
    private function getEntityReference(Referable $object, ProviderEntity $provider)
    {
        return $this->em->getReference(
            $this->references[get_class($object)],
            $this->id->generate($provider, $object->getId())
        );
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedServices()
    {
        return [
            TrackRelatedFilterDatabaseHandler::class,
        ];
    }
}
