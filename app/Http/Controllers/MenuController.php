<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('operator'), 403);

        return inertia('Operator/MenuManagement', [
            'menuItems' => MenuItem::with('menuCategories')->get(),
            'categories' => MenuCategory::orderBy('name')->get(),
        ]);
    }
}
