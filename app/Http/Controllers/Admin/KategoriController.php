<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KategoriRequest;
use App\Models\Category;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KategoriController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(): View
    {
        return view('admin.inventaris.kategori', [
            'categories' => Category::query()->withCount('items')->orderBy('sort_order')->orderBy('name')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(KategoriRequest $request): RedirectResponse
    {
        $category = $this->inventory->createCategory($request->user(), $request->validated());

        return back()->with('toast', ['type' => 'success', 'title' => 'Kategori ditambahkan', 'message' => "{$category->name} tersimpan."]);
    }

    public function update(KategoriRequest $request, Category $category): RedirectResponse
    {
        $this->inventory->updateCategory($request->user(), $category, $request->validated());

        return back()->with('toast', ['type' => 'success', 'title' => 'Kategori diperbarui', 'message' => "{$category->name} tersimpan."]);
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        try {
            $this->inventory->deleteCategory($request->user(), $category);
        } catch (BusinessRuleException $e) {
            return back()->with('toast', ['type' => 'error', 'title' => 'Tidak bisa dihapus', 'message' => $e->getMessage()]);
        }

        return back()->with('toast', ['type' => 'success', 'title' => 'Kategori dihapus', 'message' => 'Kategori dihapus.']);
    }
}
