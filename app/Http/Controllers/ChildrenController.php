<?php

namespace App\Http\Controllers;

use App\Models\Children;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function destroy(Request $request, Children $child): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $this->authorizeOwnership($request, $child);

        $child->delete();

        return redirect()->route('customer.children')->with('success', 'Child removed successfully.');
    }

    /**
     * @return array{student_id: int}
     */
    private function validateStudent(Request $request, ?Children $child = null): array
    {
        $uniqueStudent = Rule::unique('children', 'student_id');

        if ($child) {
            $uniqueStudent->ignore($child->id);
        }

        return $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id'),
                $uniqueStudent,
            ],
        ], [
            'student_id.exists' => 'No student was found with this ID.',
            'student_id.unique' => 'This student is already linked to a customer account.',
        ]);
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
