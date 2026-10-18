<?php

namespace App\Model;

enum TimeFrame: string
{
    case ACQUISITION = 'acquisition';
    case MAINTENANCE = 'maintenance';
}
