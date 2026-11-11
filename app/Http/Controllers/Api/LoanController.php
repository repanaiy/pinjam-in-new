<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ItemNotAvailableException;
use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Models\Item;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LoanController extends Controller
{
    public function __construct(private LoanService $loans) {}

    public function index(Request $request)
    {
        $loans = Loan::query()
            ->where('user_id', $request->user()->id)
            ->with(['item.cover'])
            ->latest()
            ->paginate(10);

        return LoanResource::collection($loans);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'purpose' => 'required|string|max:255',
            'student_card' => 'nullable|image|max:5120',
            'attachment' => 'nullable|file|max:5120',
        ]);

        $item = Item::findOrFail($data['item_id']);
        abort_unless($item->status->value === 'active', 403);

        try {
            $loan = $this->loans->submit(
                $request->user(),
                $item,
                $data,
                $request->file('student_card'),
                $request->file('attachment'),
            );
        } catch (ItemNotAvailableException $e) {
            return response()->json([
                'message' => 'Stok tidak tersedia.',
                'available' => $e->available,
            ], 422);
        }

        return new LoanResource($loan);
    }

    public function show(Loan $loan)
    {
        Gate::authorize('view', $loan);
        $loan->load(['item.cover', 'activityLogs.actor']);

        return new LoanResource($loan);
    }
}
