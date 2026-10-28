<?php
/*
 * Copyright (C) 2026 Kacper Donat
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
declare(strict_types=1);

namespace App\Tests\DataConverter;

use App\DataConverter\EntityConverter;
use App\Dto\Operator;
use App\Entity\OperatorEntity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EntityConverterTest extends KernelTestCase
{
    public function testOperatorKeepsItsContactDetails(): void
    {
        self::bootKernel();

        $entity = new OperatorEntity();
        $entity->fill([
            'id'    => 'test::ztm',
            'name'  => 'ZTM Gdańsk',
            'email' => 'ztm@example.com',
            'url'   => 'https://ztm.example',
            'phone' => '+48 58 520 57 75',
        ]);

        $operator = self::getContainer()->get(EntityConverter::class)->convert($entity, Operator::class);

        $this->assertSame('ztm@example.com', $operator->getEmail());
        $this->assertSame('https://ztm.example', $operator->getUrl());
        $this->assertSame('+48 58 520 57 75', $operator->getPhone());
    }
}
