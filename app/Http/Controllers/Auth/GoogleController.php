<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirect user ke halaman OAuth Google.
     * Nama method disesuaikan menjadi redirectToGoogle agar sesuai dengan Route.
     */
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')
                ->scopes(['email', 'profile'])
                ->redirect();
        } catch (Exception $e) {
            return redirect()->route('login')->with('error', 'Gagal terhubung ke Google.');
        }
    }

    /**
     * Handle callback dari Google.
     * Nama method disesuaikan menjadi handleGoogleCallback agar sesuai dengan Route.
     */
    public function handleGoogleCallback()
    {
        // Cek jika user membatalkan login
        if (request()->has('error')) {
            return redirect()->route('login')->with('info', 'Login dibatalkan.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();

            // Cari atau buat user di database
            $user = $this->findOrCreateUser($googleUser);

            // Login user
            Auth::login($user, true);

            // Keamanan: Regenerate session
            session()->regenerate();

            return redirect()->intended(route('home'))->with('success', 'Berhasil login!');

        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
            return redirect()->route('login')->with('error', 'Session expired, silakan coba lagi.');
        } catch (Exception $e) {
            logger()->error('Google Auth Error: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Terjadi kesalahan sistem.');
        }
    }

    /**
     * Logika untuk mencari user lama atau mendaftarkan user baru.
     */
    protected function findOrCreateUser($googleUser): User
    {
        // 1. Cek berdasarkan google_id
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            // Update foto jika berubah
            if ($user->avatar !== $googleUser->getAvatar()) {
                $user->update(['avatar' => $googleUser->getAvatar()]);
            }
            return $user;
        }

        // 2. Cek berdasarkan email (jika sebelumnya daftar manual)
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar() ?? $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
            return $user;
        }

        // 3. Buat User Baru
        return User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
            'password' => Hash::make(Str::random(24)),
            'role' => 'customer', // Sesuaikan dengan sistem role Anda
        ]);
    }
}