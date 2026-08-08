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
        $this->authorizeManager($request);
        $operatorSchoolId = $request->user()->hasRole('operator')
            ? $request->user()->school_id
            : null;

        return inertia('Owner/StudentManagement', [
            'students' => Student::query()
                ->with('school')
                ->when($operatorSchoolId, fn ($query) => $query->where('school_id', $operatorSchoolId))
                ->latest()
                ->get(),
            'pendingStudents' => Student::query()
                ->where('approval_status', 'pending')
                ->when($operatorSchoolId, fn ($query) => $query->where('school_id', $operatorSchoolId))
                ->with(['school:id,name', 'child.user:id,name,email'])
                ->oldest()
                ->get(),
            'schools' => School::query()
                ->where('is_active', true)
                ->when($operatorSchoolId, fn ($query) => $query->whereKey($operatorSchoolId))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);

        $validated = $this->validateStudent($request);

        Student::create([
            ...$validated,
            'qr_code' => (string) Str::uuid(),
        ]);

        return redirect()->route('owner.student')->with('success', 'Student added successfully.');
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeManager($request);
        $this->authorizeStudentAccess($request, $student);

        $student->update($this->validateStudent($request));

        return redirect()->route('owner.student')->with('success', 'Student updated successfully.');
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeManager($request);
        $this->authorizeStudentAccess($request, $student);

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
        $this->authorizeManager($request);
        $this->authorizeStudentAccess($request, $student);

        $svg = QrCode::format('svg')
            ->size(360)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($student->qr_code);

        return response((string) $svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline; filename="student-'.$student->student_code.'-qr.svg"',
        ]);
    }

    /**
     * @return array{school_id: int, name: string, class: string}
     */
    private function validateStudent(Request $request): array
    {
        $validated = $request->validate([
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:100'],
        ]);

        if ($request->user()->hasRole('operator')) {
            abort_unless((int) $request->user()->school_id === (int) $validated['school_id'], 403);
        }

        return $validated;
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['owner', 'operator'], true), 403);
        abort_if($request->user()->hasRole('operator') && ! $request->user()->school_id, 403);
    }

    private function authorizeStudentAccess(Request $request, Student $student): void
    {
        if ($request->user()->hasRole('operator')) {
            abort_unless((int) $request->user()->school_id === (int) $student->school_id, 403);
        }
    }
}
