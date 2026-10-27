<?php

namespace App\Service\PlanningModel;

enum TimeFrame: string
{
    case ACQUISITION = 'Acquisition des connaissances';
    case MAINTENANCE = 'Maintien des connaissances';
}
