<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use Closure;
use Illuminate\Http\Request;

class MenuCategoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        return inertia('Owner/MenuCategoryManagement', [
            'menuCategories' => MenuCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) {
                    $exists = MenuCategory::query()
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($exists) {
                        $fail('The menu category name already exists.');
                    }
                },
            ],
        ]);

        MenuCategory::create($validated);

        return redirect()->route('owner.menuCategory');
    }

    public function update(Request $request, MenuCategory $menuCategory)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) use ($menuCategory) {
                    $exists = MenuCategory::query()
                        ->whereKeyNot($menuCategory->id)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])
                        ->exists();

                    if ($exists) {
                        $fail('The menu category name already exists.');
                    }
                },
            ],
        ]);

        $menuCategory->update($validated);

        return redirect()->route('owner.menuCategory');
    }

    public function destroy(Request $request, MenuCategory $menuCategory)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $menuCategory->delete();

        return redirect()->route('owner.menuCategory');
    }
}
