<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $items = Item::with('category')
            ->when($request->query('search'), function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return ItemResource::collection($items);
    }

    public function show(Item $item): ItemResource
    {
        return new ItemResource($item->load('category'));
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        $item = Item::create($request->validated());

        return (new ItemResource($item->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateItemRequest $request, Item $item): ItemResource
    {
        $item->update($request->validated());

        return new ItemResource($item->load('category'));
    }

    public function destroy(Item $item): JsonResponse
    {
        $item->delete();

        return response()->json(['message' => 'Item deleted successfully.']);
    }
}