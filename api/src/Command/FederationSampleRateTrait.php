<?php

namespace App\Command;

trait FederationSampleRateTrait
{
    public function getSentrySampleRate(): float
    {
        return 0.01;
    }
}
