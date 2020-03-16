<?php

namespace App\Handler\Database;

use App\Event\HandleDatabaseModifierEvent;
use App\Event\HandleModifierEvent;
use App\Handler\ModifierHandler;
use App\Model\ScheduledStop;
use App\Model\Track;
use App\Model\TrackStop;
use App\Model\Trip;
use App\Modifier\RelatedFilter;
use App\Service\EntityReferenceFactory;
use App\Service\IdUtils;
use Doctrine\ORM\EntityManagerInterface;
use function Kadet\Functional\Transforms\property;

class GenericWithDatabaseHandler implements ModifierHandler
{
    protected $mapping = [
        Track::class         => [
            'line'  => 'line',
            'stops' => 'stopsInTrack',
        ],
        Trip::class          => [
            'schedule' => 'stops.stop',
        ],
        TrackStop::class     => [
            'track' => 'track',
        ],
        ScheduledStop::class => [
            'trip'        => 'trip',
            'track'       => 'trip.track',
            'destination' => 'trip.track.final',
        ],
    ];

    private $em;
    private $id;
    private $references;

    public function __construct(
        EntityManagerInterface $em,
        IdUtils $idUtils,
        EntityReferenceFactory $references
    ) {
        $this->em         = $em;
        $this->id         = $idUtils;
        $this->references = $references;
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

        if (!array_key_exists($modifier->getRelationship(), $this->mapping[$type])) {
            throw new \InvalidArgumentException(
                sprintf("Relationship %s is not supported for .", $type)
            );
        }

        $relationship = $this->mapping[$type][$modifier->getRelationship()];

        foreach ($this->getRelationships($relationship, $alias) as [$relationshipPath, $relationshipAlias]) {
            $selected = collect($builder->getDQLPart('select'))->flatMap(property('parts'));

            if ($selected->contains($relationshipAlias)) {
                continue;
            }

            $builder
                ->join($relationshipPath, $relationshipAlias)
                ->addSelect($relationshipAlias);
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedServices()
    {
        return [
            TrackByStopDatabaseHandler::class,
        ];
    }

    private function getRelationships($relationship, $alias)
    {
        $relationships = explode('.', $relationship);

        foreach ($relationships as $current) {
            yield [sprintf("%s.%s", $alias, $current), $alias = sprintf('%s_%s', $alias, $current)];
        }
    }
}
