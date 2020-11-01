<?php

namespace App\Model;

interface Fillable
{
    public function fill(array $vars = []);
    public static function createFromArray(array $vars = [], ...$args);
}