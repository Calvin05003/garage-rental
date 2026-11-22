<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $periodReport = in_array($this->input('report', 'monthly'), ['monthly', 'daily'], true);

        return [
            'report' => ['sometimes', Rule::in(['monthly', 'daily', 'garage_type', 'tenant'])],
            'year' => [Rule::excludeIf(! $periodReport), 'sometimes', 'integer', 'between:1000,9999'],
            'month' => [Rule::excludeIf($this->input('report', 'monthly') !== 'daily'), 'sometimes', 'integer', 'between:1,12'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        $report = $this->input('report', 'monthly');
        $parameters = ['report' => in_array($report, ['monthly', 'daily', 'garage_type', 'tenant'], true) ? $report : 'monthly'];

        if ($this->has('year') && ! $this->validator->errors()->has('year')) {
            $parameters['year'] = $this->input('year');
        }

        return route('admin.reports.index', $parameters);
    }
}
