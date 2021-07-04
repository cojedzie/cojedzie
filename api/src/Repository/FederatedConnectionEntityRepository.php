<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Repository;

use App\Entity\Federation\FederatedConnectionEntity;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class FederatedConnectionEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FederatedConnectionEntity::class);
    }

    /**
     * @return iterable<FederatedConnectionEntity>
     */
    public function findConnectionsById(array $id)
    {
        // This has to stay that way because Doctrine does not work well with querying UUID columns
        return array_map(fn (Uuid $id) => $this->find($id), $id);
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

    /**
     * @return iterable<FederatedConnectionEntity>
     */
    public function findAllReadyConnections()
    {
        return $this
            ->createQueryBuilder('fce')
            ->select('fce')
            ->where('fce.state = :state')
            ->getQuery()
            ->execute([
                'state' => FederatedConnectionEntity::STATE_READY,
            ])
            ;
    }

    public function deleteClosedConnections()
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->delete(FederatedConnectionEntity::class,'fce')
            ->where('fce.state in (:closed_states)')
            ->getQuery()
            ->execute([
                'closed_states' => FederatedConnectionEntity::CLOSED_STATES,
            ])
        ;
    }
}
