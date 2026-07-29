<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-sm font-semibold text-white">Edit User</h1>
                <p class="text-[10px] text-white/80">
                    Perbarui role, lokasi default, dan seluruh lokasi yang boleh diakses.
                </p>
            </div>

            <a href="{{ route('users.index') }}"
               class="rounded-md border border-white/30 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-white/20">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="space-y-3">
        @if (session('error'))
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-[11px] text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2">
                <p class="text-[11px] font-semibold text-red-800">Data belum lengkap:</p>
                <ul class="mt-1 list-inside list-disc text-[10px] text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('users.update', $user) }}"
              class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
            @csrf
            @method('PUT')

            <div class="space-y-5 p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">Nama User *</label>
                        <input name="name" value="{{ old('name', $user->name) }}" required
                               class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs"
                               placeholder="Contoh: Admin Pabrik">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs"
                               placeholder="contoh@agrofarm.id">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">Role *</label>
                        <select name="role" id="role" required
                                class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs">
                            <option value="">-- Pilih Role --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>
                                    {{ match ($role) {
                                        'super_admin' => 'Super Admin',
                                        'admin' => 'Admin',
                                        'approver' => 'Approver',
                                        default => 'Staff',
                                    } }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="location-access" class="space-y-4">
                    <div class="rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-[10px] text-sky-700">
                        Centang semua entitas dan gudang yang boleh diakses. Setelah itu pilih satu lokasi default.
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div class="rounded-md border border-slate-200 p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-[11px] font-bold text-slate-800">Akses Entitas *</p>
                                <button type="button" onclick="toggleAll('entity-checkbox', true)"
                                        class="text-[10px] font-semibold text-sky-600">Pilih Semua</button>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($entities as $entity)
                                    <label class="flex items-start gap-2 rounded-md border border-slate-200 px-3 py-2">
                                        <input type="checkbox" name="entity_ids[]" value="{{ $entity->id }}"
                                               class="entity-checkbox mt-0.5 rounded border-slate-300 text-emerald-600"
                                               @checked(in_array((string) $entity->id, array_map('strval', old('entity_ids', $selectedEntityIds)), true))>
                                        <span>
                                            <span class="block text-[11px] font-bold text-slate-800">{{ $entity->code }}</span>
                                            <span class="block text-[9px] text-slate-500">{{ $entity->name }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-md border border-slate-200 p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-[11px] font-bold text-slate-800">Akses Gudang *</p>
                                <button type="button" onclick="toggleAll('warehouse-checkbox', true)"
                                        class="text-[10px] font-semibold text-sky-600">Pilih Semua</button>
                            </div>

                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($warehouses as $warehouse)
                                    <label class="flex items-start gap-2 rounded-md border border-slate-200 px-3 py-2">
                                        <input type="checkbox" name="warehouse_ids[]" value="{{ $warehouse->id }}"
                                               class="warehouse-checkbox mt-0.5 rounded border-slate-300 text-emerald-600"
                                               @checked(in_array((string) $warehouse->id, array_map('strval', old('warehouse_ids', $selectedWarehouseIds)), true))>
                                        <span>
                                            <span class="block text-[11px] font-bold text-slate-800">{{ $warehouse->name }}</span>
                                            <span class="block text-[9px] text-slate-500">{{ $warehouse->code }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold text-slate-700">Entitas Default *</label>
                            <select name="entity_id" id="entity_id"
                                    class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs">
                                <option value="">-- Pilih Entitas Default --</option>
                                @foreach ($entities as $entity)
                                    <option value="{{ $entity->id }}" @selected((string) old('entity_id', $user->entity_id) === (string) $entity->id)>
                                        {{ $entity->code }} - {{ $entity->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-semibold text-slate-700">Gudang Default *</label>
                            <select name="warehouse_id" id="warehouse_id"
                                    class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs">
                                <option value="">-- Pilih Gudang Default --</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $user->warehouse_id) === (string) $warehouse->id)>
                                        {{ $warehouse->code }} - {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div id="super-admin-info" class="hidden rounded-md border border-violet-200 bg-violet-50 px-3 py-3">
                    <p class="text-[11px] font-bold text-violet-800">Super Admin memiliki akses penuh.</p>
                    <p class="mt-1 text-[10px] text-violet-700">
                        Entitas dan gudang tidak perlu dipilih karena otomatis dapat mengakses semua lokasi.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">Password Baru</label>
                        <input type="password" name="password" minlength="8"
                               class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs">
                    </div>

                    <div>
                        <label class="mb-1 block text-[11px] font-semibold text-slate-700">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" minlength="8"
                               class="block w-full rounded-md border-slate-300 px-3 py-2 text-xs">
                    </div>
                </div>

                <label class="flex items-start gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           class="mt-0.5 rounded border-slate-300 text-emerald-600"
                           @checked(old('is_active', $user->is_active))>
                    <span>
                        <span class="block text-[11px] font-semibold text-slate-800">User Aktif</span>
                        <span class="text-[10px] text-slate-500">User dapat login dan menggunakan aplikasi.</span>
                    </span>
                </label>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                <a href="{{ route('users.index') }}"
                   class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                    Batal
                </a>
                <button class="rounded-md bg-emerald-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-emerald-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleAll(className, checked) {
            document.querySelectorAll('.' + className).forEach((checkbox) => {
                checkbox.checked = checked;
            });
        }

        function updateLocationFields() {
            const isSuperAdmin = document.getElementById('role').value === 'super_admin';
            const access = document.getElementById('location-access');
            const info = document.getElementById('super-admin-info');
            const entityDefault = document.getElementById('entity_id');
            const warehouseDefault = document.getElementById('warehouse_id');

            access.classList.toggle('hidden', isSuperAdmin);
            info.classList.toggle('hidden', !isSuperAdmin);

            entityDefault.disabled = isSuperAdmin;
            warehouseDefault.disabled = isSuperAdmin;

            document.querySelectorAll('.entity-checkbox, .warehouse-checkbox').forEach((input) => {
                input.disabled = isSuperAdmin;
            });
        }

        document.getElementById('role').addEventListener('change', updateLocationFields);
        updateLocationFields();
    </script>
</x-app-layout>
