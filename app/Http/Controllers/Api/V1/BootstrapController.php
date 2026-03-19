<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BootstrapResource;
use App\Services\Bootstrap\BootstrapService;

class BootstrapController extends Controller
{
    public function __invoke(BootstrapService $bootstrapService): BootstrapResource
    {
        return new BootstrapResource($bootstrapService->build());
    }
}
