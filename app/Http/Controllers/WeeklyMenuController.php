<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\School;
use Illuminate\Http\Request;

class WeeklyMenuController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()?->role, ['owner', 'operator'], true), 403);
        $operatorSchoolId = $request->user()->hasRole('operator') ? $request->user()->school_id : null;
        abort_if($request->user()->hasRole('operator') && ! $operatorSchoolId, 403);

        return inertia('Owner/WeeklyMenu', [
            'menuItems' => MenuItem::query()
                ->with(['menuCategories', 'days', 'schools'])
                ->when($operatorSchoolId, fn ($query) => $query->whereHas('schools', fn ($query) => $query->whereKey($operatorSchoolId)))
                ->latest()
                ->get(),
            'schools' => School::query()
                ->when($operatorSchoolId, fn ($query) => $query->whereKey($operatorSchoolId))
                ->orderBy('name')
                ->get(),
            'canEdit' => $request->user()->hasRole('owner'),
        ]);
    }
}
