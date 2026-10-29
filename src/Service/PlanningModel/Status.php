<?php

namespace App\Service\PlanningModel;

enum Status: string
{
    case OFF = 'OFF';
    case ON  = 'ON';
    case COMPLETED = 'COMPLETED';
}
