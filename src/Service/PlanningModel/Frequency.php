<?php

namespace App\Service\PlanningModel;

enum Frequency: int
{
    case SLOW   = 21;
    case NORMAL = 14;
    case QUICK  = 7;
}
