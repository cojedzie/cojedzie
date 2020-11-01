<?php

namespace App\Service;

use App\Entity\Entity;
use App\Model\Line;
use App\Model\Operator;
use App\Model\ScheduledStop;
use App\Model\Stop;
use App\Model\Track;
use App\Model\Trip;

interface Converter
{
    public function convert($entity);
    public function supports($entity);
}
