<?php

namespace App\Http\Controllers;

use App\Models\Item;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::query()->orderBy('name')->paginate(20);

        return view('items.index', [
            'items' => $items,
        ]);
    }
}
