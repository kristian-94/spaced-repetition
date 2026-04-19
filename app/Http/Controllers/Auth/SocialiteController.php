<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class SocialiteController extends Controller
{
    /**
     * Providers we support. Keep this tight — anything not listed 404s.
     */
    private const PROVIDERS = ['google'];

    /**
     * Kick off the OAuth redirect to the provider.
     */
    public function redirect(string $provider): SymfonyRedirect
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider's callback: find or create the user, log them in.
     */
    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (InvalidStateException $e) {
            return redirect()->route('login')->withErrors([
                'oauth' => 'Sign-in session expired. Please try again.',
            ]);
        } catch (\Throwable $e) {
            Log::warning('OAuth callback failed', [
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'oauth' => 'Could not sign you in with '.$provider.'. Please try again.',
            ]);
        }

        if (! $oauthUser->getEmail()) {
            return redirect()->route('login')->withErrors([
                'oauth' => 'Your '.$provider.' account did not return an email address.',
            ]);
        }

        $user = DB::transaction(function () use ($provider, $oauthUser) {
            // 1) Existing social account? Use it.
            $account = SocialAccount::where('provider', $provider)
                ->where('provider_id', $oauthUser->getId())
                ->first();

            if ($account) {
                $user = $account->user;
                $user->fill([
                    'name' => $oauthUser->getName() ?: $user->name,
                    'avatar_url' => $oauthUser->getAvatar() ?: $user->avatar_url,
                ])->save();

                return $user;
            }

            // 2) User with this email already exists? Link.
            // Compare case-insensitively so mixed-case legacy emails still match.
            $email = strtolower($oauthUser->getEmail());
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if (! $user) {
                // 3) Brand new user.
                $user = User::create([
                    'name' => $oauthUser->getName() ?: $email,
                    'email' => $email,
                    'avatar_url' => $oauthUser->getAvatar(),
                ]);
                $user->email_verified_at = now();
                $user->save();
            } else {
                $user->fill([
                    'avatar_url' => $user->avatar_url ?: $oauthUser->getAvatar(),
                ])->save();
            }

            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $oauthUser->getId(),
            ]);

            return $user;
        });

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
