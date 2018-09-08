<?php

namespace App\Entity;

use App\Model\Referable;

interface Entity extends Referable
{
    public function getProvider(): ProviderEntity;
    public function setProvider(ProviderEntity $provider): void;
}