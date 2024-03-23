<?php

namespace App\Describer;

use App\Service\StatusService;
use Nelmio\ApiDocBundle\Describer\DescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\OpenApi;
use OpenApi\Attributes\Info;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('nelmio_api_doc.describer')]
class ApiDescriber implements DescriberInterface
{
    public function __construct(
        public readonly StatusService $status
    ) {
    }

    #[\Override]
    public function describe(OpenApi $api)
    {
        $version = $this->status->getVersionStatus();

        Util::merge(
            $api->info,
            new Info(version: $version->getVersion()),
            true
        );
    }
}
