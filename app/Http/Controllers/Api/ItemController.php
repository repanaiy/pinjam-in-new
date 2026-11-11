<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::query()
            ->active()
            ->with(['category', 'cover'])
            ->search($request->query('q'))
            ->when($request->query('kategori'), fn ($b) => $b->whereHas('category', fn ($c) => $c->where('slug', $request->query('kategori'))))
            ->paginate(20);

        return ItemResource::collection($items);
    }

    public function show(Item $item)
    {
        abort_unless($item->status->value === 'active', 404);
        $item->load(['category', 'photos', 'cover']);

        return new ItemResource($item);
    }
}
