<?php

namespace App\Http\Controllers;

use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('operator'), 403);

        return inertia('Operator/SchoolManagement', [
            'schools' => School::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('operator'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        School::create($validated);

        return redirect()->route('operator.school');
    }

    public function update(Request $request, School $school) 
    {
        abort_unless($request->user()->hasRole('operator'), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        $school->update($validated);

        return redirect()->route('operator.school');
    }
}
