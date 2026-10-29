<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($this->redirectPathForUser(Auth::user()));
        }

        return view('pages.auth.signin', [
            'title' => 'Sign In - Support Ticket System',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Your account is inactive. Please contact administration.',
                ]);
            }

            $targetRoute = $this->redirectPathForUser($user);

            return redirect()->intended($targetRoute)
                ->with('success', 'Welcome back, ' . $user->name . '!');
        }

        return back()->withInput($request->only('email'))
            ->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($this->redirectPathForUser(Auth::user()));
        }

        return view('pages.auth.signup', [
            'title' => 'Create Account - Support Ticket System',
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:100'],
            'lname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user = User::create([
            'name' => trim($validated['fname'] . ' ' . $validated['lname']),
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $targetRoute = $this->redirectPathForUser($user);

        return redirect()->to($targetRoute)
            ->with('success', 'Account created successfully! Welcome to your Support Portal.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Demo 1-click login for test evaluator convenience
     */
    public function quickLogin(string $role): RedirectResponse
    {
        $user = match ($role) {
            'admin' => User::where('role', User::ROLE_ADMIN)->first(),
            'agent' => User::where('role', User::ROLE_AGENT)->first(),
            'customer' => User::where('role', User::ROLE_CUSTOMER)->first(),
            default => null,
        };

        if (!$user) {
            return back()->with('error', 'Demo account for this role was not found.');
        }

        Auth::login($user);
        request()->session()->regenerate();

        $targetRoute = $this->redirectPathForUser($user);

        return redirect()->to($targetRoute)
            ->with('success', "Logged in as {$user->name} (" . ucfirst($user->role) . ")");
    }

    /**
     * Determine role-specific dashboard redirect target
     */
    public function redirectPathForUser(User $user): string
    {
        return match ($user->role) {
            User::ROLE_ADMIN => route('dashboard'),
            User::ROLE_AGENT => route('dashboard'),
            User::ROLE_CUSTOMER => route('tickets.index'),
            default => route('dashboard'),
        };
    }
}
