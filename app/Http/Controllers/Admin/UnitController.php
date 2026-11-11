<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UnitRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function store(UnitRequest $request, Item $item): RedirectResponse
    {
        $unit = $this->inventory->addUnit($request->user(), $item, $request->validated());

        return back()->with('toast', ['type' => 'success', 'title' => 'Unit ditambahkan', 'message' => "Unit {$unit->unit_code} tersimpan."]);
    }

    public function update(UnitRequest $request, ItemUnit $unit): RedirectResponse
    {
        try {
            $this->inventory->updateUnit($request->user(), $unit, $request->validated());
        } catch (BusinessRuleException $e) {
            return back()->with('toast', ['type' => 'error', 'title' => 'Unit tidak diubah', 'message' => $e->getMessage()]);
        }

        return back()->with('toast', ['type' => 'success', 'title' => 'Unit diperbarui', 'message' => "Unit {$unit->unit_code} tersimpan."]);
    }

    public function destroy(Request $request, ItemUnit $unit): RedirectResponse
    {
        try {
            $this->inventory->deleteUnit($request->user(), $unit);
        } catch (BusinessRuleException $e) {
            return back()->with('toast', ['type' => 'error', 'title' => 'Tidak bisa dihapus', 'message' => $e->getMessage()]);
        }

        return back()->with('toast', ['type' => 'success', 'title' => 'Unit dihapus', 'message' => 'Unit dihapus (soft delete).']);
    }
}
