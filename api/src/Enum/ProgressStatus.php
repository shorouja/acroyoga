<?php

namespace App\Enum;

enum ProgressStatus: string
{
    case Learning = 'learning';
    case Consistent = 'consistent';
    case Mastered = 'mastered';
}
