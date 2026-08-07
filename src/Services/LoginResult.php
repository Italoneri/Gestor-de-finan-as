<?php

declare(strict_types=1);

namespace App\Services;

enum LoginResult
{
    case Success;
    case InvalidCredentials;
    case RateLimited;
}
