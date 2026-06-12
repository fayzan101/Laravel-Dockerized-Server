<?php

namespace App\Http\Controllers;

use App\Http\Concerns\PaginatesApiRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests, PaginatesApiRequests;
}
