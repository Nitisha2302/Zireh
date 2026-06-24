<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;

abstract class ApiController extends Controller
{
    use ApiResponse;
}
