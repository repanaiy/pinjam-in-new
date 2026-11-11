<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\Request;

class FineController extends Controller
{
    public function __construct(private LoanService $service) {}

    public function store(Request $request, Loan $loan)
    {
        $this->service->payFine($loan, $request->user(), $request->input('payment_method'));

        return back()->with('success', 'Denda berhasil dilunasi.');
    }
}
