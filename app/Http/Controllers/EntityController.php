<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EntityController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');

        $entities = Entity::query()
            ->withCount([
                'users',
                'accessUsers',
                'itemStocks',
                'stockIns',
                'requests',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('entities.index', compact(
            'entities',
            'search',
            'status'
        ));
    }

    public function create()
    {
        return view('entities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                Rule::unique('entities', 'code'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ], [
            'code.required' => 'Kode entitas wajib diisi.',
            'code.alpha_dash' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'code.unique' => 'Kode entitas sudah digunakan.',
            'name.required' => 'Nama entitas wajib diisi.',
        ]);

        Entity::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('entities.index')
            ->with('success', 'Entitas berhasil ditambahkan.');
    }

    public function edit(Entity $entity)
    {
        return view('entities.edit', compact('entity'));
    }

    public function update(Request $request, Entity $entity)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                Rule::unique('entities', 'code')->ignore($entity->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ], [
            'code.required' => 'Kode entitas wajib diisi.',
            'code.alpha_dash' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'code.unique' => 'Kode entitas sudah digunakan.',
            'name.required' => 'Nama entitas wajib diisi.',
        ]);

        $entity->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('entities.index')
            ->with('success', 'Entitas berhasil diperbarui.');
    }

    public function toggleStatus(Entity $entity)
    {
        $entity->update([
            'is_active' => ! $entity->is_active,
        ]);

        return back()->with(
            'success',
            $entity->is_active
                ? 'Entitas berhasil diaktifkan.'
                : 'Entitas berhasil dinonaktifkan.'
        );
    }

    public function destroy(Entity $entity)
    {
        $isUsed = $entity->users()->exists()
            || $entity->accessUsers()->exists()
            || $entity->itemStocks()->exists()
            || $entity->stockIns()->exists()
            || $entity->requests()->exists();

        if ($isUsed) {
            return back()->with(
                'error',
                'Entitas tidak dapat dihapus karena sudah digunakan. Silakan nonaktifkan entitas.'
            );
        }

        $entity->delete();

        return redirect()
            ->route('entities.index')
            ->with('success', 'Entitas berhasil dihapus.');
    }
}
