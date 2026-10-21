<?php

namespace App\Service\PlanningModel;

enum Status: string
{
    case OFF = 'off';
    case ON  = 'on';
    case COMPLETED = 'completed';
}
