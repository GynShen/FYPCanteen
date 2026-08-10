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
        $filters = $request->only(['search', 'class', 'school_id', 'status']);
        $applyFilters = function ($query) use ($request, $operatorSchoolId): void {
            $query->when($operatorSchoolId, fn ($query) => $query->where('school_id', $operatorSchoolId))
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = '%'.$request->string('search')->trim().'%';
                    $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('student_code', 'like', $search));
                })
                ->when($request->filled('class'), fn ($query) => $query->where('class', $request->input('class')))
                ->when($request->filled('school_id') && ! $operatorSchoolId, fn ($query) => $query->where('school_id', $request->integer('school_id')))
                ->when($request->filled('status'), fn ($query) => $query->where('approval_status', $request->input('status')));
        };

        return inertia('Owner/StudentManagement', [
            'students' => Student::query()
                ->with('school')
                ->tap($applyFilters)
                ->latest()
                ->get(),
            'pendingStudents' => Student::query()
                ->where('approval_status', 'pending')
                ->tap($applyFilters)
                ->with(['school:id,name', 'child.user:id,name,email'])
                ->oldest()
                ->get(),
            'classes' => Student::query()->when($operatorSchoolId, fn ($query) => $query->where('school_id', $operatorSchoolId))->distinct()->orderBy('class')->pluck('class'),
            'filters' => $filters,
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
