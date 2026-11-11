<?php

namespace App\Enums;

enum InspectionType: string
{
    case Handover = 'handover';
    case Return = 'return';
}
