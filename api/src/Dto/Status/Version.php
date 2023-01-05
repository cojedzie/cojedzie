<?php

namespace App\Dto\Status;

use OpenApi\Annotations as OA;
use JMS\Serializer\Annotation as Serializer;

class Version
{
    public function __construct(
        /**
         * Version of the software running on this instance.
         *
         * @OA\Property(type="string", format="version", example="v2022.3.0")
         */
        #[Serializer\Type('string')]
        private string $version,

        /**
         * Exact revision of the software running on this instance.
         *
         * @OA\Property(type="string", format="revision", example="43a97fd1f515bb49737fded905ad326fa8cb35b7")
         */
        #[Serializer\Type('string')]
        private string $revision,
    ) {
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    public function getRevision(): string
    {
        return $this->revision;
    }

    public function setRevision(string $revision): void
    {
        $this->revision = $revision;
    }
}
