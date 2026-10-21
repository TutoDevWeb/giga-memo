<?php

namespace App\Service\PlanningModel;

enum TimeFrame: string
{
    case ACQUISITION = 'acquisition';
    case MAINTENANCE = 'maintenance';
}
