<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-sm font-semibold leading-5 text-slate-800">
                Master Kategori Barang
            </h1>
            <p class="text-[10px] leading-4 text-slate-500">
                Kelola kategori untuk data barang ATK dan RTK.
            </p>
        </div>
    </x-slot>

    <div class="space-y-3">
        <!-- Alert Success -->
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-emerald-800 shadow-sm">
                <div class="flex gap-2">
                    <svg class="mt-0.5 h-4 w-4 flex-none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-5M12 3.5l7 3v5.5c0 4.5-2.8 7.6-7 8.5-4.2-.9-7-4-7-8.5V6.5l7-3Z" />
                    </svg>
                    <div>
                        <p class="text-xs font-semibold leading-4">Berhasil</p>
                        <p class="text-[11px] leading-4">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Alert Error -->
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-red-800 shadow-sm">
                <div class="flex gap-2">
                    <svg class="mt-0.5 h-4 w-4 flex-none" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    </svg>
                    <div>
                        <p class="text-xs font-semibold leading-4">Terjadi Kesalahan</p>
                        <p class="text-[11px] leading-4">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Card -->
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xs font-semibold leading-5 text-slate-800">
                            Data Kategori
                        </h2>
                        <p class="text-[10px] leading-4 text-slate-500">
                            Daftar kategori barang yang tersedia di sistem.
                        </p>
                    </div>

                    <a href="{{ route('categories.create') }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                        </svg>
                        Tambah Kategori
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                No
                            </th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Nama Kategori
                            </th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Deskripsi
                            </th>
                            <th class="whitespace-nowrap px-4 py-2.5 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($categories as $category)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-2.5 text-[11px] text-slate-600">
                                    {{ $categories->firstItem() + $loop->index }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-indigo-100 text-indigo-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h2A2.5 2.5 0 0 1 11 6.5v2A2.5 2.5 0 0 1 8.5 11h-2A2.5 2.5 0 0 1 4 8.5v-2ZM13 6.5A2.5 2.5 0 0 1 15.5 4h2A2.5 2.5 0 0 1 20 6.5v2a2.5 2.5 0 0 1-2.5 2.5h-2A2.5 2.5 0 0 1 13 8.5v-2ZM4 15.5A2.5 2.5 0 0 1 6.5 13h2a2.5 2.5 0 0 1 2.5 2.5v2A2.5 2.5 0 0 1 8.5 20h-2A2.5 2.5 0 0 1 4 17.5v-2ZM13 15.5a2.5 2.5 0 0 1 2.5-2.5h2a2.5 2.5 0 0 1 2.5 2.5v2a2.5 2.5 0 0 1-2.5 2.5h-2a2.5 2.5 0 0 1-2.5-2.5v-2Z" />
                                            </svg>
                                        </div>

                                        <div>
                                            <p class="text-xs font-semibold leading-5 text-slate-800">
                                                {{ $category->name }}
                                            </p>
                                            <p class="text-[10px] leading-3 text-slate-500">
                                                Kategori Barang
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-2.5 text-[11px] leading-4 text-slate-600">
                                    {{ $category->description ?: '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('categories.edit', $category) }}"
                                           class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-1.5 text-[10px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200 hover:bg-amber-100">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L8.5 18.152 4 19.5l1.348-4.5L16.862 4.487Z" />
                                            </svg>
                                            Edit
                                        </a>

                                        <form action="{{ route('categories.destroy', $category) }}"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-1.5 text-[10px] font-semibold text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9M19 6h-4.5l-.9-1.5h-3.2L9.5 6H5m2 0v13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V6" />
                                                </svg>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5v-11Z" />
                                        </svg>
                                    </div>

                                    <h3 class="mt-3 text-xs font-semibold text-slate-800">
                                        Belum ada data kategori
                                    </h3>

                                    <p class="mt-1 text-[11px] text-slate-500">
                                        Silakan tambah kategori barang terlebih dahulu.
                                    </p>

                                    <a href="{{ route('categories.create') }}"
                                       class="mt-4 inline-flex items-center justify-center gap-1.5 rounded-md bg-emerald-700 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-indigo-700">
                                        Tambah Kategori
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($categories->hasPages())
                <div class="border-t border-slate-200 px-4 py-3 text-xs">
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>