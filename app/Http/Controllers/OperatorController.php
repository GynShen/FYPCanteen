<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\School;
use App\Rules\MalaysianPhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class OperatorController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeOwner($request);
        $filters = $request->only(['search', 'school_id', 'status']);

        return inertia('Owner/OperatorManagement', [
            'operators' => User::query()
                ->where('role', 'operator')
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = '%'.$request->string('search')->trim().'%';
                    $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('username', 'like', $search)->orWhere('phone_number', 'like', $search)->orWhere('id', $request->input('search')));
                })
                ->when($request->filled('school_id'), fn ($query) => $query->where('school_id', $request->integer('school_id')))
                ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->input('status') === 'active'))
                ->orderBy('name')
                ->with('school:id,name')
                ->get(['id', 'name', 'username', 'phone_number', 'school_id', 'is_active', 'created_at']),
            'schools' => School::orderBy('name')->get(['id', 'name', 'is_active']),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeOwner($request);
        $request->merge([
            'phone_number' => MalaysianPhoneNumber::normalize($request->input('phone_number')),
        ]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique(User::class)],
            'phone_number' => ['required', new MalaysianPhoneNumber, Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
        ]);

        User::create([
            ...$validated,
            'email' => null,
            'role' => 'operator',
            'is_active' => true,
        ]);

        return back()->with('success', 'Operator account created successfully.');
    }

    public function update(Request $request, User $operator): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureOperator($operator);
        $request->merge([
            'phone_number' => MalaysianPhoneNumber::normalize($request->input('phone_number')),
        ]);
        $operator->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', Rule::unique(User::class)->ignore($operator->id)],
            'phone_number' => ['required', new MalaysianPhoneNumber, Rule::unique(User::class)->ignore($operator->id)],
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
        ]) + ['email' => null]);
        $this->clearSessions($operator);

        return back()->with('success', 'Operator account updated. The operator must sign in again.');
    }

    public function toggleActive(Request $request, User $operator): RedirectResponse
    {
        $this->authorizeOwner($request);
        $this->ensureOperator($operator);
        $operator->update(['is_active' => ! $operator->is_active]);
        $this->clearSessions($operator);

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
        $operator->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
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
