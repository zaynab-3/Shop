<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse|SymfonyRedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()
                ->route('login')
                ->with('google_error', 'Google login is not configured yet. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to .env.');
        }

        $state = Str::random(40);

        session(['google_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('login')
                ->with('google_error', 'Google login was cancelled or could not be completed.');
        }

        $expectedState = (string) $request->session()->pull('google_oauth_state');
        $returnedState = (string) $request->query('state');

        if (! $expectedState || ! hash_equals($expectedState, $returnedState)) {
            return redirect()
                ->route('login')
                ->with('google_error', 'Google login session expired. Please try again.');
        }

        $code = (string) $request->query('code');

        if (! $code) {
            return redirect()
                ->route('login')
                ->with('google_error', 'Google did not return an authorization code.');
        }

        try {
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            if ($tokenResponse->failed() || ! $tokenResponse->json('access_token')) {
                throw new \RuntimeException('Google token exchange failed.');
            }

            $profileResponse = Http::withToken($tokenResponse->json('access_token'))
                ->get('https://openidconnect.googleapis.com/v1/userinfo');

            if ($profileResponse->failed()) {
                throw new \RuntimeException('Google user profile request failed.');
            }

            $googleUser = $profileResponse->json();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->with('google_error', 'Google login was cancelled or could not be completed.');
        }

        $googleId = (string) ($googleUser['sub'] ?? '');
        $email = Str::lower((string) ($googleUser['email'] ?? ''));

        if (! $googleId || ! $email) {
            return redirect()
                ->route('login')
                ->with('google_error', 'Google did not return an email address for this account.');
        }

        if (($googleUser['email_verified'] ?? false) !== true) {
            return redirect()
                ->route('login')
                ->with('google_error', 'Google did not verify the email address for this account.');
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => ($googleUser['name'] ?? null) ?: Str::before($email, '@'),
                'email' => $email,
                'email_verified_at' => now(),
                'google_id' => $googleId,
                'google_avatar' => $googleUser['picture'] ?? null,
                'password' => Hash::make(Str::password(48)),
                'password_set_at' => null,
            ]);
        } else {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleId,
                'google_avatar' => $googleUser['picture'] ?? null,
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('profile.edit', absolute: false));
    }

    private function redirectUri(): string
    {
        return config('services.google.redirect') ?: route('auth.google.callback');
    }
}
