<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        return inertia('Owner/SchoolManagement', [
            'schools' => School::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:schools,name',
            'address' => 'required|string|max:255|unique:schools,address',
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
        ]);

        $school->update($validated);

        return redirect()->route('owner.school');
    }

    public function destroy(Request $request, School $school)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $school->delete();

        return redirect()->route('owner.school');
    }
}
