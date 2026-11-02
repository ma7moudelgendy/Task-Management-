<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    /**
     * Display personal dashboard metrics for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $data = $this->dashboardService->getUserDashboard($request->user());

            return $this->jsonResponse(
                data: DashboardResource::make($data),
                msg: 'Dashboard metrics retrieved successfully.'
            );
        });
    }
}
