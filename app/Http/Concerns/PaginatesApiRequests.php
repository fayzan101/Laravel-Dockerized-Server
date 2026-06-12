<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;

trait PaginatesApiRequests
{
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min(max((int) $request->query('per_page', $default), 1), $max);
    }
}
