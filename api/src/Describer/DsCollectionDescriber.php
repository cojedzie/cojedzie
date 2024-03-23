<?php

namespace App\Describer;

use Ds\Collection;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use OpenApi\Annotations\Schema;
use OpenApi\Attributes\Items;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('nelmio_api_doc.model_describer')]
class DsCollectionDescriber implements ModelDescriberInterface
{
    #[\Override]
    public function describe(Model $model, Schema $schema)
    {
        $schema->type  = 'array';
        $schema->items = new Items(type: 'object');
    }

    #[\Override]
    public function supports(Model $model): bool
    {
        return is_subclass_of(
            $model->getType()->getClassName(),
            Collection::class
        );
    }
}
