<?php

namespace App\Http\Controllers;

use App\Services\Tenancy\PlatformMetricsService;
use Inertia\Inertia;
use Inertia\Response;

class PlatformController extends Controller
{
    public function index(PlatformMetricsService $metrics): Response
    {
        return Inertia::render('Platform/Index', [
            'summary' => $metrics->summary(),
            'tenants' => array_slice($metrics->tenantRows(), 0, 8),
            'health' => $metrics->health(),
        ]);
    }
}
