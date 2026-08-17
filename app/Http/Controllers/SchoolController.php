<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        return inertia('Owner/SchoolManagement', [
            'schools' => School::query()->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('address', 'like', $search));
            })->latest()->get(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:schools,name',
            'address' => 'required|string|max:255|unique:schools,address',
            'ordering_deadline_day' => ['required', Rule::in([
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
            ])],
        ]);

        School::create($validated);

        return redirect()->route('owner.school');
    }

    public function update(Request $request, School $school)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:schools,name,'.$school->id,
            'address' => 'required|string|max:255|unique:schools,address,'.$school->id,
            'ordering_deadline_day' => ['required', Rule::in([
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
            ])],
        ]);

        $school->update($validated);

        return redirect()->route('owner.school');
    }

    public function toggleActive(Request $request, School $school)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $school->update(['is_active' => ! $school->is_active]);

        return back()->with(
            'success',
            $school->is_active ? 'School activated.' : 'School deactivated.',
        );
    }

}
