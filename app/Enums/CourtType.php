<?php

declare(strict_types=1);

namespace App\Enums;

enum CourtType: string
{
    case Futsal = 'futsal';
    case Football = 'football';
    case Basket = 'basket';
    case Basketball = 'basketball';
    case Tennis = 'tennis';
    case Badminton = 'badminton';
    case Padel = 'padel';
}
