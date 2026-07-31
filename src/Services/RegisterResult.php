<?php

declare(strict_types=1);

namespace App\Services;

enum RegisterResult
{
    case Registered;
    case EmailTaken;
}
