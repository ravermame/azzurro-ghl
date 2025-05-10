<?php

namespace App\Enums;

use ReflectionClass;

abstract class WebhookSourceEnums
{
    const GHL = "GHL";
    const CLOUDBEDS = "CLOUDBEDS";
    const RESERVATION_CREATED = 'reservation/created';

    static function getConstants()
    {
        $allEnums = new ReflectionClass(__CLASS__);
        return $allEnums->getConstants();
    }
}
