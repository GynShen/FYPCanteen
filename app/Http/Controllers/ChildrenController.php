<?php

namespace App\Http\Controllers;

use App\Models\Children;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Manoar\QrCode\Facades\QrCode;

class ChildrenController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeCustomer($request);
        $this->linkStudentsByGuardianPhone($request);

        return inertia('Customer/ChildrenManagement', [
            'children' => $request->user()->children()->with('student.school')->latest()->get(),
            'schools' => School::orderBy('name')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $validated = $this->validateDetails($request);
        DB::transaction(function () use ($request, $validated): void {
            $student = Student::create([
                ...$validated,
                'guardian_phone_number' => $request->user()->phone_number,
                'qr_code' => (string) Str::uuid(),
            ]);
            $request->user()->children()->create(['student_id' => $student->id]);
        });
        return back()->with('success', 'Child added and ready for meal ordering.');
    }

    public function update(Request $request, Children $child): RedirectResponse
    {
        $this->authorizeCustomer($request); $this->authorizeOwnership($request, $child);
        $child->student->update($this->validateDetails($request));
        return back()->with('success', 'Child details updated.');
    }

    public function destroy(Request $request, Children $child): RedirectResponse
    {
        $this->authorizeCustomer($request); $this->authorizeOwnership($request, $child);

        DB::transaction(function () use ($child): void {
            // Keep the official student record, QR code, and historical orders, but
            // remove the phone match so this relationship is not recreated on refresh.
            $child->student()->update(['guardian_phone_number' => null]);
            $child->delete();
        });

        return back()->with('success', 'Child removed from your account.');
    }

    public function qrCode(Request $request, Children $child): Response
    {
        $this->authorizeCustomer($request); $this->authorizeOwnership($request, $child);
        $svg = QrCode::format('svg')->size(360)->margin(2)->errorCorrection('H')->generate($child->student->qr_code);
        return response((string) $svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'private, no-store', 'Content-Disposition' => 'inline; filename="student-'.$child->student_id.'-qr.svg"']);
    }

    private function validateDetails(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:100'],
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
        ]);
    }

    private function authorizeCustomer(Request $request): void { abort_unless($request->user()?->hasRole('customer'), 403); }
    private function authorizeOwnership(Request $request, Children $child): void { abort_unless($child->user_id === $request->user()->id, 403); }

    private function linkStudentsByGuardianPhone(Request $request): void
    {
        $phoneNumbers = $this->phoneNumberVariants($request->user()->phone_number);

        if ($phoneNumbers === []) {
            return;
        }

        $normalizedGuardianPhone = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(guardian_phone_number, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '')";

        $studentIds = Student::query()
            ->whereNotNull('guardian_phone_number')
            ->whereRaw($normalizedGuardianPhone.' in ('.implode(', ', array_fill(0, count($phoneNumbers), '?')).')', $phoneNumbers)
            ->whereDoesntHave('child')
            ->pluck('id');

        foreach ($studentIds as $studentId) {
            Children::query()->firstOrCreate([
                'user_id' => $request->user()->id,
                'student_id' => $studentId,
            ]);
        }
    }

    private function phoneNumberVariants(?string $phoneNumber): array
    {
        $digits = preg_replace('/\D+/', '', (string) $phoneNumber);

        if ($digits === '') {
            return [];
        }

        if (str_starts_with($digits, '60')) {
            $local = '0'.substr($digits, 2);
            $international = $digits;
        } elseif (str_starts_with($digits, '0')) {
            $local = $digits;
            $international = '60'.substr($digits, 1);
        } else {
            $local = '0'.$digits;
            $international = '60'.$digits;
        }

        return array_values(array_unique([$local, $international]));
    }
}
