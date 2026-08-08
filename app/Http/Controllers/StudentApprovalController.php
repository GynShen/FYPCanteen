<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentApprovalController extends Controller
{
    public function approve(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeReviewer($request);
        abort_unless($student->approval_status === 'pending', 422, 'This student has already been reviewed.');

        if ($request->user()->hasRole('operator')) {
            abort_unless((int) $request->user()->school_id === (int) $student->school_id, 403);
        }

        $student->update(['approval_status' => 'approved']);

        return back()->with('success', 'Student approved successfully.');
    }

    private function authorizeReviewer(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['owner', 'operator'], true), 403);
        abort_if($request->user()->hasRole('operator') && ! $request->user()->school_id, 403);
    }
}
