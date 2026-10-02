<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $stats,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user !== null && $user->role === UserRole::Admin;

        return Inertia::render('Dashboard', [
            'stats' => $isAdmin ? $this->stats->forAdmin() : null,
        ]);
    }
}
