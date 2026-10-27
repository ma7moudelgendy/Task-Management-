<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\Api\HandlesApiExceptions;
use App\Traits\ApiResponse;

abstract class ApiController extends Controller
{
    use HandlesApiExceptions, ApiResponse;
}
