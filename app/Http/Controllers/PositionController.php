<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Master jabatan, dipakai untuk menentukan siapa yang berwenang
 * pada tiap tahap approval pengajuan custom. Khusus super admin.
 */
class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::query()
            ->withCount(['users', 'approvalSteps'])
            ->orderBy('name')
            ->get();

        return view('positions.index', compact('positions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('positions', 'name')],
        ], [
            'name.required' => 'Nama jabatan wajib diisi.',
            'name.unique' => 'Nama jabatan sudah ada.',
        ]);

        Position::create([
            'name' => trim($validated['name']),
            'is_active' => true,
        ]);

        return redirect()
            ->route('positions.index')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('positions', 'name')->ignore($position->id),
            ],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama jabatan wajib diisi.',
            'name.unique' => 'Nama jabatan sudah ada.',
        ]);

        $isActive = $request->boolean('is_active');

        if (! $isActive && $position->approvalSteps()->where('is_active', true)->exists()) {
            return redirect()
                ->route('positions.index')
                ->with('error', 'Jabatan masih dipakai pada alur approval aktif, jadi tidak bisa dinonaktifkan.');
        }

        $position->update([
            'name' => trim($validated['name']),
            'is_active' => $isActive,
        ]);

        return redirect()
            ->route('positions.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Position $position)
    {
        if ($position->users()->exists() || $position->approvalSteps()->exists()) {
            return redirect()
                ->route('positions.index')
                ->with('error', 'Jabatan tidak dapat dihapus karena masih dipakai user atau alur approval.');
        }

        $position->delete();

        return redirect()
            ->route('positions.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }
}
