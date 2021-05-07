<?php

namespace App\Model;

class Vehicle implements Referable, Fillable, DTO
{
    use ReferableTrait, FillTrait;

    // todo: what attributes? AC, USB, GPS, seat count, length, manufacturer...?
}
