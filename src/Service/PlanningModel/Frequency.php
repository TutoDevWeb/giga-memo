<?php

namespace App\Service\PlanningModel;

enum Frequency: int
{
    case SLOW   = 7;
    case NORMAL = 8;
    case QUICK  = 9;
}
