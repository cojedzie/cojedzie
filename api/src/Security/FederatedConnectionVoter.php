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

namespace App\Security;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Entity\Federation\FederatedServerEntity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Lets a federated server close only its own connections.
 *
 * @extends Voter<string, FederatedConnectionEntity>
 */
class FederatedConnectionVoter extends Voter
{
    final public const string CLOSE = 'FEDERATED_CONNECTION_CLOSE';

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::CLOSE && $subject instanceof FederatedConnectionEntity;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $server = $token->getUser();

        return $server instanceof FederatedServerEntity && $subject->getServer()->getId()->equals($server->getId());
    }
}
