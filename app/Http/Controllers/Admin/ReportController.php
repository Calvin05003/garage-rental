<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(ReportRequest $request, ReportService $service): View
    {
        $data = $request->validated();
        $report = $data['report'] ?? 'monthly';
        $year = (int) ($data['year'] ?? today()->year);
        $month = (int) ($data['month'] ?? today()->month);
        $rows = DB::transaction(fn () => match ($report) {
            'daily' => $service->daily($year, $month),
            'garage_type' => $service->byGarageType(),
            'tenant' => $service->byTenant(),
            default => $service->monthly($year),
        });

        return view('admin.reports', [
            'report' => $report,
            'year' => $year,
            'month' => $month,
            'period' => match ($report) {
                'monthly' => (string) $year,
                'daily' => CarbonImmutable::create($year, $month, 1)->format('F Y'),
                default => 'All time',
            },
            'groupLabel' => match ($report) {
                'daily' => 'Date', 'garage_type' => 'Garage type', 'tenant' => 'Tenant', default => 'Month',
            },
            'rows' => $rows,
            'totals' => $service->totals($rows),
            'formatAmount' => $service->formatAmount(...),
        ]);
    }
}
