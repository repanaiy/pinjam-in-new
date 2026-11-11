<?php

namespace App\Http\Controllers\Siswa;

use App\Enums\LoanStatus;
use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RiwayatController extends Controller
{
    public function index(Request $request): View
    {
        $status = LoanStatus::tryFrom((string) $request->query('status'));

        $loans = Loan::query()
            ->where('user_id', $request->user()->id)
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->with(['item'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('siswa.riwayat', [
            'loans' => $loans,
            'status' => $status,
            'statuses' => LoanStatus::cases(),
        ]);
    }
}
