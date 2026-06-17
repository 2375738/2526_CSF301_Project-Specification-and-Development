<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\DashboardDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyWorkController extends Controller
{
    public function __invoke(Request $request, DashboardDataService $dashboardData): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isEmployee()) {
            return redirect()->route('home');
        }

        return view('my-work.index', $dashboardData->buildMyWorkData($request));
    }
}
