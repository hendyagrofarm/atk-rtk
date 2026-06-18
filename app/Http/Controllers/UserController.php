<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Role yang boleh digunakan di aplikasi.
     */
    private array $roles = [
        'admin',
        'staff',
        'approver',
    ];

    /**
     * Menampilkan daftar user.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $status = $request->input('status');

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($role, function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => $this->roles,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    /**
     * Menampilkan form tambah user.
     */
    public function create()
    {
        return view('users.create', [
            'roles' => $this->roles,
        ]);
    }

    /**
     * Menyimpan user baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($this->roles)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama user wajib diisi.',
            'email.required' => 'Email user wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh user lain.',
            'role.required' => 'Role user wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'password.required' => 'Password wajib diisi saat membuat user baru.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User baru berhasil dibuat.');
    }

    /**
     * Menampilkan form edit user.
     */
    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => $this->roles,
        ]);
    }

    /**
     * Update data user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in($this->roles)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama user wajib diisi.',
            'email.required' => 'Email user wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah digunakan oleh user lain.',
            'role.required' => 'Role user wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if ($user->id === Auth::id() && $validated['role'] !== $user->role) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak boleh mengubah role akun Anda sendiri.');
        }

        if ($user->id === Auth::id() && ! $request->boolean('is_active')) {
            return back()
                ->withInput()
                ->with('error', 'Anda tidak boleh menonaktifkan akun Anda sendiri.');
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        return redirect()
            ->route('users.index')
            ->with('success', 'Data user berhasil diperbarui.');
    }

    /**
     * Menonaktifkan user.
     *
     * Catatan:
     * Tidak menghapus permanen dari database agar histori data tetap aman.
     */
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()
                ->with('error', 'Anda tidak boleh menonaktifkan akun Anda sendiri.');
        }

        $user->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil dinonaktifkan.');
    }

    /**
     * Mengaktifkan kembali user.
     */
    public function activate(User $user)
    {
        $user->update([
            'is_active' => true,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User berhasil diaktifkan kembali.');
    }
}