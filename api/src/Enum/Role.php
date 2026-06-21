<?php

namespace App\Enum;

enum Role: string
{
    case Base = 'base';
    case Flyer = 'flyer';
    case Both = 'both';
    case Solo = 'solo';
}
