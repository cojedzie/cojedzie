<?php

namespace App\Repository;

use App\Entity\Federation\FederatedConnectionEntity;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FederatedConnectionEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FederatedConnectionEntity::class);
    }

    /**
     * @return iterable<FederatedConnectionEntity>
     */
    public function findAllConnectionsToCheck()
    {
        return $this
            ->createQueryBuilder('fce')
            ->select('fce')
            ->where('fce.state in (:open_states)')
            ->andWhere('fce.nextCheck <= :now')
            ->getQuery()
            ->execute([
                'open_states' => FederatedConnectionEntity::OPEN_STATES,
                'now' => Carbon::now()
            ])
        ;
    }
}
