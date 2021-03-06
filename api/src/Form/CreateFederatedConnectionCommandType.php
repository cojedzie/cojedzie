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

namespace App\Form;

use App\Entity\Federation\FederatedConnectionEntity;
use App\Entity\Federation\FederatedServerEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateFederatedConnectionCommandType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url', TextType::class, [
                'documentation' => [
                    'type'        => 'text',
                    'description' => 'Base URL for this connection.',
                    'example'     => 'https://cojedzie.pl',
                ],
            ])
            ->add('server_id', EntityType::class, [
                'class'           => FederatedServerEntity::class,
                'property_path'   => 'server',
                'invalid_message' => '{{ value }} is not a valid server identifier.',
                'documentation'   => [
                    'type'        => 'text',
                    'format'      => 'uuid',
                    'description' => 'Server Id associated with this connection.',
                    'example'     => 'a7cd192a-3dca-4fc8-b35d-91f2d6e10632',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FederatedConnectionEntity::class,
        ]);
    }
}
