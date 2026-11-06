<?php

namespace App\Service\PlanningModel;

use App\Service\Config;

enum Frequency: string
{
    case SLOW   = 'SLOW';
    case NORMAL = 'NORMAL';
    case QUICK  = 'QUICK';

    public function getMaintenanceCycleCount(): int
    {
        return match ($this) {
            self::QUICK  => Config::MAINTENANCE_CYCLE_COUNT_QUICK,
            self::NORMAL => Config::MAINTENANCE_CYCLE_COUNT_NORMAL,
            self::SLOW   => Config::MAINTENANCE_CYCLE_COUNT_SLOW,
        };
    }

    public function getMaintenanceCycleDuration(): int
    {
        return match ($this) {
            self::QUICK  => Config::MAINTENANCE_CYCLE_DURATION_QUICK,
            self::NORMAL => Config::MAINTENANCE_CYCLE_DURATION_NORMAL,
            self::SLOW   => Config::MAINTENANCE_CYCLE_DURATION_SLOW,
        };
    }

    public function getAcquisitionCycleDuration(): int
    {
        return match ($this) {
            self::QUICK  => Config::ACQUISITION_CYCLE_DURATION_QUICK,
            self::NORMAL => Config::ACQUISITION_CYCLE_DURATION_NORMAL,
            self::SLOW   => Config::ACQUISITION_CYCLE_DURATION_SLOW,
        };
    }
}
