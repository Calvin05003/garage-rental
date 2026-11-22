<?php

namespace App\Http\Controllers;

use App\Models\Garage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        if ($request->user()->isAdmin()) {
            return view('admin.dashboard', [
                'garageCount' => Garage::count(),
                'tenantCount' => User::where('role', 'penyewa')->count(),
                'pendingCount' => User::where('role', 'penyewa')
                    ->where('verification_status', 'pending')->count(),
                'pendingTenants' => User::where('role', 'penyewa')
                    ->where('verification_status', 'pending')
                    ->oldest()->limit(5)->get(),
            ]);
        }

        return view('tenant.dashboard', ['tenant' => $request->user()]);
    }
}
