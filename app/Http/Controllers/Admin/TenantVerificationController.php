<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyTenantRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ]);
        $status = $validated['status'] ?? 'pending';

        return view('admin.tenants', [
            'status' => $status,
            'tenants' => User::where('role', 'penyewa')
                ->where('verification_status', $status)
                ->oldest()->orderBy('user_id')->paginate(10)->withQueryString(),
            'counts' => User::where('role', 'penyewa')
                ->selectRaw('verification_status, COUNT(*) as total')
                ->groupBy('verification_status')->pluck('total', 'verification_status'),
        ]);
    }

    public function update(VerifyTenantRequest $request, User $tenant): RedirectResponse
    {
        abort_unless($tenant->role === 'penyewa', 404);

        $decision = $request->validated('verification_status');
        $updated = User::whereKey($tenant->getKey())
            ->where('role', 'penyewa')
            ->where('verification_status', 'pending')
            ->update(['verification_status' => $decision]);

        if (! $updated) {
            return to_route('admin.tenants.index')
                ->with('status', 'This registration has already been processed.');
        }

        return to_route('admin.tenants.index')->with(
            'status',
            $decision === 'approved' ? 'Tenant registration approved.' : 'Tenant registration rejected.',
        );
    }
}
