<?php

namespace App\Enum;

enum PartnershipStatus: string
{
    case OneSided = 'one_sided';
    case Pending = 'pending';
    case Active = 'active';
    case Archived = 'archived';
}
