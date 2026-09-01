<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Dicek SEBELUM password di-update, supaya kita tahu apakah ini ganti
        // password wajib (pertama kali login) atau sekadar ganti password biasa
        // lewat halaman profil.
        $iniGantiPasswordWajib = $request->user()->mustChangePassword();

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
        ]);

        if ($iniGantiPasswordWajib) {
            return redirect()->route('dashboard')->with('status', 'password-updated');
        }

        return back()->with('status', 'password-updated');
    }
}
