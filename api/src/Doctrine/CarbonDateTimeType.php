<?php

namespace App\Doctrine;

use Carbon\Carbon;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

class CarbonDateTimeType extends DateTimeType
{
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof \DateTimeInterface) {
            $value = Carbon::instance($value);
        }

        if ($value instanceof Carbon) {
            $value = $value->tz('UTC');
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value || $value instanceof Carbon) {
            return $value;
        }

        $converted = Carbon::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            'UTC'
        );

        if (!$converted) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        return $converted;
    }
}
