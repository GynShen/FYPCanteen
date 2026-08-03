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
            'schools' => School::all(),
        ]);
    }
}
