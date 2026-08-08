<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Manoar\QrCode\Facades\QrCode;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeOwner($request);

        return inertia('Owner/StudentManagement', [
            'students' => Student::with('school')->latest()->get(),
            'schools' => School::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);

        $validated = $this->validateStudent($request);

        Student::create([
            ...$validated,
            'qr_code' => (string) Str::uuid(),
        ]);

        return redirect()->route('owner.student')->with('success', 'Student added successfully.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeOwner($request);

        $student->update($this->validateStudent($request));

        return redirect()->route('owner.student')->with('success', 'Student updated successfully.');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeOwner($request);

        if ($student->child()->exists() || $student->orderItems()->exists()) {
            throw ValidationException::withMessages([
                'student' => 'This student cannot be deleted because they are linked to a customer or an order.',
            ]);
        }

        $student->delete();

        return redirect()->route('owner.student')->with('success', 'Student deleted successfully.');
    }

    public function qrCode(Request $request, Student $student): Response
    {
        $this->authorizeOwner($request);

        $svg = QrCode::format('svg')
            ->size(360)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($student->qr_code);

        return response((string) $svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline; filename="student-'.$student->id.'-qr.svg"',
        ]);
    }

    /**
     * @return array{school_id: int, name: string, class: string}
     */
    private function validateStudent(Request $request): array
    {
        return $request->validate([
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:100'],
        ]);
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->hasRole('owner'), 403);
    }
}
