<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoanStatus;
use App\Exports\LoansExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class LaporanController extends Controller
{
    private const PDF_LIMIT = 1000;

    public function __construct(private ReportService $reports) {}

    public function index(Request $request): View
    {
        $f = $this->reports->filters($request);

        return view('admin.laporan', [
            'filters' => $f,
            'summary' => $this->reports->summary($f),
            'loans' => $this->reports->query($f)->paginate(15)->withQueryString(),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
            'statuses' => LoanStatus::cases(),
            'exportQuery' => array_filter(Arr::only($f, ['dari', 'sampai', 'kategori', 'status']), fn ($v) => $v !== null && $v !== ''),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $f = $this->reports->filters($request);
        $loans = $this->reports->query($f)->limit(self::PDF_LIMIT)->get();
        $summary = $this->reports->summary($f);

        return Pdf::loadView('pdf.laporan', [
            'loans' => $loans,
            'summary' => $summary,
            'description' => $this->reports->describe($f),
            'truncated' => $summary['total'] > self::PDF_LIMIT,
            'limit' => self::PDF_LIMIT,
            'generatedAt' => now(),
            'officer' => $request->user()->name,
        ])->setPaper('a4', 'landscape')->download('laporan-peminjaman-'.today()->toDateString().'.pdf');
    }

    public function excel(Request $request): Response
    {
        return Excel::download(new LoansExport($this->reports->filters($request)), 'laporan-peminjaman-'.today()->toDateString().'.xlsx');
    }
}
