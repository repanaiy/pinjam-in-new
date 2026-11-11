<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UnitStatus;
use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ItemRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Setting;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventarisController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $kategori = $request->query('kategori');
        $status = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : null;

        $items = Item::query()
            ->with(['category', 'cover'])
            ->withCount([
                'units as total_units',
                'units as avail_units' => fn ($u) => $u->where('status', UnitStatus::Available->value),
            ])
            ->search($q)
            ->when($kategori, fn ($b) => $b->where('category_id', $kategori))
            ->when($status, fn ($b) => $b->where('status', $status))
            ->orderBy('name')
            ->paginate(12)->withQueryString();

        return view('admin.inventaris.index', [
            'items' => $items,
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
            'q' => $q,
            'kategori' => $kategori,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.inventaris.form', [
            'item' => new Item([
                'max_loan_days' => (int) Setting::get('max_loan_days', 5),
                'max_qty_per_loan' => 1,
                'min_stock' => 1,
                'status' => 'active',
            ]),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
            'inUse' => [],
        ]);
    }

    public function store(ItemRequest $request): RedirectResponse
    {
        $item = $this->inventory->createItem(
            $request->user(),
            $request->itemData(),
            $request->file('photos', []),
            (int) $request->input('initial_units', 0),
        );

        return redirect()->route('admin.inventaris.edit', $item)->with('toast', [
            'type' => 'success', 'title' => 'Alat ditambahkan', 'message' => "{$item->name} ({$item->code}) siap diatur unit dan fotonya.",
        ]);
    }

    public function edit(Item $item): View
    {
        $item->load(['category', 'photos']);

        return view('admin.inventaris.form', [
            'item' => $item,
            'units' => ItemUnit::query()->where('item_id', $item->id)->orderBy('unit_code')->paginate(10, ['*'], 'unit')->fragment('u-title'),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
            'inUse' => ItemUnit::query()->where('item_id', $item->id)->inUse()->pluck('id')->all(),
        ]);
    }

    public function update(ItemRequest $request, Item $item): RedirectResponse
    {
        $this->inventory->updateItem($request->user(), $item, $request->itemData(), $request->file('photos', []));

        return redirect()->route('admin.inventaris.edit', $item)->with('toast', [
            'type' => 'success', 'title' => 'Alat diperbarui', 'message' => 'Perubahan tersimpan.',
        ]);
    }

    public function destroy(Request $request, Item $item): RedirectResponse
    {
        try {
            $this->inventory->deleteItem($request->user(), $item);
        } catch (BusinessRuleException $e) {
            return back()->with('toast', ['type' => 'error', 'title' => 'Tidak bisa dihapus', 'message' => $e->getMessage()]);
        }

        return redirect()->route('admin.inventaris.index')->with('toast', [
            'type' => 'success', 'title' => 'Alat dihapus', 'message' => 'Alat disembunyikan dari katalog; riwayat transaksi tetap tersimpan.',
        ]);
    }
}
