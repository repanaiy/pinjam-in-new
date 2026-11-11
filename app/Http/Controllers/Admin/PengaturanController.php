<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HolidayRequest;
use App\Http\Requests\Admin\PengaturanRequest;
use App\Models\Holiday;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\ServiceCalendar;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function edit(ServiceCalendar $calendar): View
    {
        return view('admin.pengaturan', [
            'serviceDays' => $calendar->serviceDays(),
            'values' => [
                'service_hour_start' => Setting::get('service_hour_start', '07:00'),
                'service_hour_end' => Setting::get('service_hour_end', '15:00'),
                'max_loan_days' => Setting::get('max_loan_days', 5),
                'review_sla_workdays' => Setting::get('review_sla_workdays', 1),
                'pickup_grace_workdays' => Setting::get('pickup_grace_workdays', 1),
                'late_warning_threshold' => Setting::get('late_warning_threshold', 3),
                'academic_year' => Setting::get('academic_year', now()->year.'/'.(now()->year + 1)),
                'semester_label' => Setting::get('semester_label', ''),
            ],
            'holidays' => Holiday::query()->orderByDesc('date')->paginate(12, ['*'], 'libur')->fragment('s-libur'),
        ]);
    }

    public function update(PengaturanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $days = collect($data['service_days'])->map(fn ($d) => (int) $d)->unique()->sort()->values()->implode(',');

        Setting::put('service_days', $days);
        foreach (['service_hour_start', 'service_hour_end', 'max_loan_days', 'review_sla_workdays', 'pickup_grace_workdays', 'late_warning_threshold', 'academic_year', 'semester_label'] as $key) {
            Setting::put($key, (string) $data[$key]);
        }

        $this->audit->log(null, $request->user(), 'settings_updated', 'Pengaturan sistem diperbarui', ['service_days' => $days]);

        return back()->with('toast', ['type' => 'success', 'title' => 'Pengaturan disimpan', 'message' => 'Perubahan langsung berlaku.']);
    }

    public function storeHoliday(HolidayRequest $request): RedirectResponse
    {
        try {
            $holiday = Holiday::create($request->validated());
            $this->audit->log(null, $request->user(), 'holiday_created', "Hari libur {$holiday->name} ({$holiday->date->toDateString()}) ditambahkan");

            return back()->with('toast', ['type' => 'success', 'title' => 'Hari libur ditambahkan', 'message' => $holiday->name]);
        } catch (UniqueConstraintViolationException $e) {
            return back()->withErrors(['date' => 'Tanggal ini sudah terdaftar sebagai hari libur.'])->withInput();
        }
    }

    public function destroyHoliday(Request $request, Holiday $holiday): RedirectResponse
    {
        $holiday->delete();
        $this->audit->log(null, $request->user(), 'holiday_deleted', "Hari libur {$holiday->name} dihapus");

        return back()->with('toast', ['type' => 'success', 'title' => 'Hari libur dihapus', 'message' => $holiday->name]);
    }
}
