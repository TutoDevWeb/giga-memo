<?php

namespace App\Service\PlanningModel;

enum Phase: string
{
    case ACQUISITION = 'acquisition';
    case MAINTENANCE = 'maintenance';
}
