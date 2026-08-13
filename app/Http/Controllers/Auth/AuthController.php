<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Afficher le formulaire de connexion
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Traiter la connexion
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $remember = $request->boolean('remember');

        if (!$this->authService->login($credentials['phone'], $credentials['password'], $remember)) {
            return back()
                ->withInput($request->only('phone'))
                ->with('error', __('messages.auth.invalid_credentials'));
        }

        $locale = $request->session()->get('locale');
        if ($locale && array_key_exists($locale, config('app.supported_locales', []))) {
            $request->user()?->forceFill(['locale' => $locale])->save();
        }

        return redirect()->route('dashboard');
    }

    /**
     * Afficher le formulaire d'enregistrement
     */
    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    /**
     * Traiter l'enregistrement
     */
    public function register(RegisterRequest $request)
    {
        $this->authService->register($request->validated());

        return redirect()->route('login')
            ->with('success', __('messages.auth.account_created'));
    }

    /**
     * Se déconnecter
     */
    public function logout(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('login');
    }

    public function showForgotPasswordForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }

    public function showResetPasswordForm(string $token, Request $request): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email' => 'L\'adresse email doit être valide.',
            'password.required' => 'Le nouveau mot de passe est obligatoire.',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }

    /**
     * Afficher le formulaire de changement de mot de passe
     */
    public function showChangePasswordForm(): View
    {
        return view('auth.profile', [
            'user' => auth()->user(),
            'activeProfileTab' => 'password',
        ]);
    }

    /**
     * Afficher le profil de l'utilisateur connecté
     */
    public function showProfileForm(): View
    {
        return view('auth.profile', [
            'user' => auth()->user(),
            'activeProfileTab' => request('tab', old('profile_tab', session('profile_tab', 'info'))),
        ]);
    }

    /**
     * Mettre à jour les informations personnelles de l'utilisateur connecté
     */
    public function updateProfile(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $filename = 'avatar_' . time() . '_' . uniqid() . '.' . $request->file('avatar')->getClientOriginalExtension();
            $data['avatar'] = $request->file('avatar')->storeAs('users/avatars', $filename, 'public');
        }

        $user->update($data);

        return back()
            ->with('success', __('messages.auth.profile_updated'))
            ->with('profile_tab', 'info');
    }

    /**
     * Traiter le changement de mot de passe
     */
    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $credentials = $request->validated();

        if (!$this->authService->changePassword($user, $credentials['old_password'], $credentials['new_password'])) {
            return back()
                ->with('error', __('messages.auth.old_password_wrong'))
                ->with('profile_tab', 'password');
        }

        return back()
            ->with('success', __('messages.auth.password_changed'))
            ->with('profile_tab', 'password');
    }
}
