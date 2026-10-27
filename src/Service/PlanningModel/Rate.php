<?php

namespace App\Service\PlanningModel;


enum Rate: string
{
    case SLOW   = 'slow';
    case NORMAL = 'normal';
    case QUICK  = 'quick';
}
