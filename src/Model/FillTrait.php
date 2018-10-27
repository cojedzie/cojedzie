<?php


namespace App\Model;


trait FillTrait
{
    public function fill(array $vars = [])
    {
        foreach ($vars as $name => $value) {
            switch (true) {
                case method_exists($this, $setter = 'set' . strtoupper($name)):
                    $this->{$setter}($value);
                    break;

                case property_exists($this, $name) && (new \ReflectionProperty($this, $name))->isPublic():
                    $this->$name = $value;
                    break;
            }
        }
    }

    public static function createFromArray(array $vars = [], ...$args)
    {
        $reflection  = new \ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();


        $object = empty($args) && ($constructor && $constructor->getNumberOfRequiredParameters() > 0)
            ? $reflection->newInstanceWithoutConstructor()
            : $reflection->newInstanceArgs($args);

        $object->fill($vars);

        return $object;
    }
}