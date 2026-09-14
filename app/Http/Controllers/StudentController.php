<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Rules\MalaysianPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Manoar\QrCode\Facades\QrCode;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeManager($request);
        $operatorSchoolId = $request->user()->school_id;
        $filters = $request->only(['search', 'class']);
        $applyFilters = function ($query) use ($request, $operatorSchoolId): void {
            $query->when($operatorSchoolId, fn ($query) => $query->where('school_id', $operatorSchoolId))
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = '%'.$request->string('search')->trim().'%';
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', $search)
                            ->orWhere('class', 'like', $search)
                            ->orWhere('guardian_phone_number', 'like', $search)
                            ->orWhereHas('child.user', fn ($query) => $query
                                ->where('name', 'like', $search)
                                ->orWhere('phone_number', 'like', $search));
                    });
                })
                ->when($request->filled('class'), fn ($query) => $query->where('class', $request->input('class')));
        };

        return inertia('Owner/StudentManagement', [
            'students' => Student::query()
                ->with([
                    'school',
                    'child.user' => fn ($query) => $query->select('id', 'name', 'username'),
                ])
                ->tap($applyFilters)
                ->latest()
                ->get(),
            'classes' => Student::query()->when($operatorSchoolId, fn ($query) => $query->where('school_id', $operatorSchoolId))->distinct()->orderBy('class')->pluck('class'),
            'filters' => $filters,
            'schools' => School::query()
                ->whereKey($operatorSchoolId)
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

        $validated = $this->validateStudent($request);
        $phoneChanged = $this->normalizePhoneNumber($student->guardian_phone_number)
            !== $validated['guardian_phone_number'];

        DB::transaction(function () use ($student, $validated, $phoneChanged): void {
            if ($phoneChanged) {
                $student->child()->delete();
            }

            $student->update($validated);
        });

        $message = $phoneChanged
            ? 'Student updated. The previous parent link was removed because the guardian phone changed.'
            : 'Student updated successfully.';

        return redirect()->route('owner.student')->with('success', $message);
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
            'Content-Disposition' => 'inline; filename="student-'.$student->id.'-qr.svg"',
        ]);
    }

    /**
     * @return array{school_id: int, name: string, class: string, guardian_phone_number: ?string}
     */
    private function validateStudent(Request $request): array
    {
        $request->merge([
            'guardian_phone_number' => MalaysianPhoneNumber::normalize($request->input('guardian_phone_number')),
        ]);

        $validated = $request->validate([
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:100'],
            'guardian_phone_number' => ['nullable', new MalaysianPhoneNumber],
        ]);

        abort_unless((int) $request->user()->school_id === (int) $validated['school_id'], 403);

        return $validated;
    }

    private function normalizePhoneNumber(?string $phoneNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phoneNumber);

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '60')
            ? '0'.substr($digits, 2)
            : $digits;
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->role === 'operator' && $request->user()->school_id, 403);
    }

    private function authorizeStudentAccess(Request $request, Student $student): void
    {
        abort_unless((int) $request->user()->school_id === (int) $student->school_id, 403);
    }
}
