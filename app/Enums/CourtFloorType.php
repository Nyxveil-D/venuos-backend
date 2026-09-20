<?php

declare(strict_types=1);

namespace App\Enums;

enum CourtFloorType: string
{
    case SyntheticGrass = 'synthetic_grass';
    case NaturalGrass = 'natural_grass';
    case Vinyl = 'vinyl';
    case Interlock = 'interlock';
    case HardCourt = 'hard_court';
    case Wood = 'wood';
    case Rubber = 'rubber';
    case Concrete = 'concrete';
}
