<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Display the welcome page.
     */
    public function welcome(): Response
    {
        $data = $this->dashboardService->getWelcomeData();

        return Inertia::render('Welcome', $data);
    }

    /**
     * Display the user dashboard.
     */
    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        $data = $this->dashboardService->getDashboardData($user);

        return Inertia::render('Dashboard', $data);
    }
}
