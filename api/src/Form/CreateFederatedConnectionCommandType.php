<?php

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
                    'type' => 'text',
                    'description' => 'Base URL for this connection.',
                    'example' => 'https://cojedzie.pl',
                ]
            ])
            ->add('server_id', EntityType::class, [
                'class' => FederatedServerEntity::class,
                'property_path' => 'server',
                'documentation' => [
                    'type' => 'text',
                    'format' => 'uuid',
                    'description' => 'Server Id associated with this connection.',
                    'example' => 'a7cd192a-3dca-4fc8-b35d-91f2d6e10632',
                ]
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
