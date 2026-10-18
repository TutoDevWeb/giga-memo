<?php

namespace App\Model;

enum Frequency: string
{
    case SLOW   = 'slow';
    case NORMAL = 'normal';
    case QUICK  = 'quick';
}
