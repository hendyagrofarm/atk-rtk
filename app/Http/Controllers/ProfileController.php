<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman profile user.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update informasi profile user.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated');
    }

    /**
     * Hapus akun sendiri dinonaktifkan.
     *
     * User tidak boleh menghapus akun sendiri.
     * Pengelolaan user hanya boleh dilakukan oleh admin
     * melalui menu Manajemen User.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort(403, 'User tidak boleh menghapus akun sendiri. Hubungi admin untuk pengelolaan akun.');
    }
}