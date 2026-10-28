<?php

namespace App\Service\PlanningModel;

enum TimeFrame: string
{
    case ACQUISITION = 'ACQUISITION';
    case MAINTENANCE = 'MAINTENANCE';
}
