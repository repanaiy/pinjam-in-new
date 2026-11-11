<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemPhoto;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FotoController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function store(Request $request, Item $item): RedirectResponse
    {
        $request->validateWithBag('photos', [
            'photos' => ['required', 'array', 'max:8'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'photos.required' => 'Pilih minimal satu foto.',
            'photos.*.image' => 'Berkas harus berupa gambar.',
            'photos.*.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'photos.*.max' => 'Ukuran tiap foto maksimal 3 MB.',
        ]);

        $this->inventory->addPhotos($item, $request->file('photos'));

        return back()->with('toast', ['type' => 'success', 'title' => 'Foto ditambahkan', 'message' => 'Foto alat tersimpan.']);
    }

    public function destroy(ItemPhoto $photo): RedirectResponse
    {
        $this->inventory->deletePhoto($photo);

        return back()->with('toast', ['type' => 'success', 'title' => 'Foto dihapus', 'message' => 'Foto alat dihapus.']);
    }

    public function cover(ItemPhoto $photo): RedirectResponse
    {
        $this->inventory->setCover($photo);

        return back()->with('toast', ['type' => 'success', 'title' => 'Sampul diganti', 'message' => 'Foto ini menjadi sampul di katalog.']);
    }
}
