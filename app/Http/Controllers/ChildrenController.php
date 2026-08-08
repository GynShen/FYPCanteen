<?php

namespace App\Http\Controllers;

use App\Models\Children;
use App\Models\Student;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Manoar\QrCode\Facades\QrCode;

class ChildrenController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeCustomer($request);

        return inertia('Customer/ChildrenManagement', [
            'children' => $request->user()
                ->children()
                ->with('student.school')
                ->latest()
                ->get(),
            'schools' => School::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCustomer($request);

        $validated = $this->validateStudent($request);

        $request->user()->children()->create($validated);

        return redirect()->route('customer.children')->with('success', 'Child added successfully.');
    }

    public function update(Request $request, Children $child): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $this->authorizeOwnership($request, $child);

        $child->update($this->validateStudent($request, $child));

        return redirect()->route('customer.children')->with('success', 'Child updated successfully.');
    }

    public function requestStudent(Request $request): RedirectResponse
    {
        $this->authorizeCustomer($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:100'],
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')->where('is_active', true)],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $student = Student::create([
                ...$validated,
                'approval_status' => 'pending',
                'qr_code' => (string) Str::uuid(),
            ]);

            $request->user()->children()->create(['student_id' => $student->id]);
        });

        return back()->with('success', 'Student submitted for school approval. Meal ordering will be enabled after approval.');
    }

    public function destroy(Request $request, Children $child): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $this->authorizeOwnership($request, $child);

        $child->delete();

        return redirect()->route('customer.children')->with('success', 'Child removed successfully.');
    }

    public function qrCode(Request $request, Children $child): Response
    {
        $this->authorizeCustomer($request);
        $this->authorizeOwnership($request, $child);

        $student = $child->student;
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
     * @return array{student_id: int}
     */
    private function validateStudent(Request $request, ?Children $child = null): array
    {
        $request->merge([
            'student_code' => strtoupper(trim((string) $request->input('student_code'))),
        ]);

        $validated = $request->validate([
            'student_code' => [
                'required',
                'string',
                'max:255',
                Rule::exists('students', 'student_code'),
            ],
        ], [
            'student_code.exists' => 'No student was found with this Student ID.',
        ]);

        $student = Student::query()
            ->where('student_code', strtoupper($validated['student_code']))
            ->where('approval_status', 'approved')
            ->whereHas('school', fn ($query) => $query->where('is_active', true))
            ->first();

        if (! $student) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'student_code' => 'This student’s school is currently inactive.',
            ]);
        }

        $alreadyClaimed = Children::query()
            ->where('student_id', $student->id)
            ->when($child, fn ($query) => $query->where('id', '!=', $child->id))
            ->exists();

        if ($alreadyClaimed) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'student_code' => 'This student is already linked to a customer account.',
            ]);
        }

        return ['student_id' => $student->id];
    }

    private function authorizeCustomer(Request $request): void
    {
        abort_unless($request->user()?->hasRole('customer'), 403);
    }

    private function authorizeOwnership(Request $request, Children $child): void
    {
        abort_unless($child->user_id === $request->user()->id, 403);
    }
}
