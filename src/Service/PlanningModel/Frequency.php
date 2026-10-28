<?php

namespace App\Service\PlanningModel;

enum Frequency: string
{
    case SLOW   = 'SLOW';
    case NORMAL = 'NORMAL';
    case QUICK  = 'QUICK';
}
