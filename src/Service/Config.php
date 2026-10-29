<?php

namespace App\Service;

class Config
{

    const ACQUISITION_SLOW   = 21;
    const ACQUISITION_NORMAL = 14;
    const ACQUISITION_QUICK  =  7;

    const MAINTENANCE_SLOW   = 60;
    const MAINTENANCE_NORMAL = 30;
    const MAINTENANCE_QUICK  = 15;

    public function getConfig(): array
    {

        return [
            'ACQUISITION_SLOW'    => Config::ACQUISITION_SLOW,
            'ACQUISITION_NORMAL'  => Config::ACQUISITION_NORMAL,
            'ACQUISITION_QUICK'   => Config::ACQUISITION_QUICK,
            'MAINTENANCE_SLOW'    => Config::MAINTENANCE_SLOW,
            'MAINTENANCE_NORMAL'  => Config::MAINTENANCE_NORMAL,
            'MAINTENANCE_QUICK'   => Config::MAINTENANCE_QUICK,
        ];
    }
}
