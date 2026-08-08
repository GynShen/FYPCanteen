<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class OperatorController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeOwner($request);

        return inertia('Owner/OperatorManagement', [
            'operators' => User::query()
                ->where('role', 'operator')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone_number', 'is_active', 'created_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'phone_number' => ['required', 'string', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            ...$validated,
            'role' => 'operator',
            'is_active' => true,
        ]);

        return back()->with('success', 'Operator account created successfully.');
    }

    public function update(Request $request, User $operator): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureOperator($operator);
        $operator->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($operator->id)],
            'phone_number' => ['required', 'string', 'max:255', Rule::unique(User::class)->ignore($operator->id)],
        ]));

        return back()->with('success', 'Operator account updated successfully.');
    }

    public function toggleActive(Request $request, User $operator): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureOperator($operator);
        $operator->update(['is_active' => ! $operator->is_active]);

        if (! $operator->is_active) {
            $this->clearSessions($operator);
        }

        return back()->with('success', $operator->is_active ? 'Operator activated.' : 'Operator deactivated.');
    }

    public function resetPassword(Request $request, User $operator): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureOperator($operator);
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $operator->update(['password' => $validated['password']]);
        $this->clearSessions($operator);

        return back()->with('success', 'Password assigned. The operator must sign in again.');
    }

    public function destroy(Request $request, User $operator): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureOperator($operator);
        $this->clearSessions($operator);
        $operator->delete();

        return back()->with('success', 'Operator account deleted.');
    }

    private function clearSessions(User $operator): void
    {
        DB::table('sessions')->where('user_id', $operator->id)->delete();
    }

    private function ensureOperator(User $operator): void
    {
        abort_unless($operator->role === 'operator', 404);
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->hasRole('owner'), 403);
    }
}
