<?php

namespace App\Service;

class Config
{

    // Durées en jours des cycles d'acquisition selon le radio bouton Slow / Normal / Quick
    const ACQUISITION_CYCLE_DURATION_SLOW   = 21;
    const ACQUISITION_CYCLE_DURATION_NORMAL = 14;
    const ACQUISITION_CYCLE_DURATION_QUICK  =  7;

    // Nombre de cycle en mode Acquisition
    const ACQUISITION_CYCLE_COUNT = 12;
    // Nombre de jours en mode Acquisition
    const ACQUISITION_NB_REVISION_DAY = 24;

    // Nombre de cycle en mode Maintenance
    const MAINTENANCE_CYCLE_COUNT_SLOW   =  6;
    const MAINTENANCE_CYCLE_COUNT_NORMAL = 12;
    const MAINTENANCE_CYCLE_COUNT_QUICK  = 24;

    // Durées en jours des cycles de maintenance selon le radio bouton Slow / Normal / Quick
    const MAINTENANCE_CYCLE_DURATION_SLOW   = 60;
    const MAINTENANCE_CYCLE_DURATION_NORMAL = 30;
    const MAINTENANCE_CYCLE_DURATION_QUICK  = 15;

    public function getConfig(): array
    {

        return [

            'ACQUISITION_CYCLE_DURATION_SLOW'    => SELF::ACQUISITION_CYCLE_DURATION_SLOW,
            'ACQUISITION_CYCLE_DURATION_NORMAL'  => SELF::ACQUISITION_CYCLE_DURATION_NORMAL,
            'ACQUISITION_CYCLE_DURATION_QUICK'   => SELF::ACQUISITION_CYCLE_DURATION_QUICK,

            'ACQUISITION_CYCLE_COUNT'            => SELF::ACQUISITION_CYCLE_COUNT,
            'ACQUISITION_NB_REVISION_DAY'        => SELF::ACQUISITION_NB_REVISION_DAY,

            'MAINTENANCE_CYCLE_COUNT_SLOW'       => SELF::MAINTENANCE_CYCLE_COUNT_SLOW,
            'MAINTENANCE_CYCLE_COUNT_NORMAL'     => SELF::MAINTENANCE_CYCLE_COUNT_NORMAL,
            'MAINTENANCE_CYCLE_COUNT_QUICK'      => SELF::MAINTENANCE_CYCLE_COUNT_QUICK,

            'MAINTENANCE_CYCLE_DURATION_SLOW'    => SELF::MAINTENANCE_CYCLE_DURATION_SLOW,
            'MAINTENANCE_CYCLE_DURATION_NORMAL'  => SELF::MAINTENANCE_CYCLE_DURATION_NORMAL,
            'MAINTENANCE_CYCLE_DURATION_QUICK'   => SELF::MAINTENANCE_CYCLE_DURATION_QUICK,
        ];
    }
}
