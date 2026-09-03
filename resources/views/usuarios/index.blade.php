<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-sans font-semibold text-xl text-dark-text leading-tight">Usuarios</h2>
            @can('usuarios.crear')
                <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-corp hover:bg-corp-dim text-dark-bg font-medium px-4 py-2 rounded text-sm transition-colors">+ Nuevo</button>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 px-4 py-3 rounded bg-green-900/40 border border-green-700/50 text-green-300 text-sm">{{ session('success') }}</div>
            @endif

            <div class="bg-dark-panel border border-dark-border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-dark-border text-dark-muted text-xs uppercase tracking-wider">
                            <th class="text-left px-5 py-3 font-medium">Nombre</th>
                            <th class="text-left px-5 py-3 font-medium">{{ __('Email') }}</th>
                            <th class="text-left px-5 py-3 font-medium">Rol</th>
                            <th class="text-center px-5 py-3 font-medium">Activo</th>
                            <th class="text-center px-5 py-3 font-medium">Bloqueado</th>
                            <th class="text-left px-5 py-3 font-medium">Último Acceso</th>
                            <th class="text-right px-5 py-3 font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-border">
                        @foreach($usuarios as $u)
                            <tr class="hover:bg-dark-card transition-colors">
                                <td class="px-5 py-3.5">
                                    <span class="text-dark-text font-medium">{{ $u->nombre }} {{ $u->apellido }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-dark-muted font-mono text-xs">{{ $u->email }}</td>
                                <td class="px-5 py-3.5">
                                    @foreach($u->roles as $role)
                                        <span class="text-xs px-2 py-0.5 rounded bg-dark-card border border-dark-border text-dark-muted">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-block w-2 h-2 rounded-full {{ $u->activo ? 'bg-green-400' : 'bg-red-400' }}"></span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if($u->bloqueado)
                                        <span class="text-xs text-red-400 font-semibold">SÍ</span>
                                    @else
                                        <span class="text-xs text-dark-muted">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-dark-muted text-xs font-mono">{{ $u->ultimo_acceso?->format('d/m H:i') ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex gap-1 justify-end items-center">
                                        @can('usuarios.editar')
                                            <button onclick="editUser({{ $u->id }}, '{{ $u->nombre }}', '{{ $u->apellido ?? '' }}', '{{ $u->email }}', '{{ $u->roles->first()?->name ?? '' }}')" title="Editar" class="w-8 h-7 flex items-center justify-center rounded bg-dark-card border border-dark-border text-dark-muted hover:text-corp transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <form method="POST" action="{{ route('usuarios.toggle-activo', $u) }}" class="inline">
                                                @csrf
                                                <button title="{{ $u->activo ? 'Desactivar' : 'Activar' }}" class="w-8 h-7 flex items-center justify-center rounded bg-dark-card border border-dark-border {{ $u->activo ? 'text-green-400 hover:text-red-400' : 'text-red-400 hover:text-green-400' }} transition-colors">
                                                    @if($u->activo)
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                    @else
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                                    @endif
                                                </button>
                                            </form>
                                            @can('configuracion.editar')
                                                <form method="POST" action="{{ route('usuarios.toggle-bloqueo', $u) }}" class="inline">
                                                    @csrf
                                                    <button title="{{ $u->bloqueado ? 'Desbloquear' : 'Bloquear' }}" class="w-8 h-7 flex items-center justify-center rounded bg-dark-card border border-dark-border {{ $u->bloqueado ? 'text-green-400 hover:text-dark-text' : 'text-dark-text hover:text-green-400' }} transition-colors">
                                                        @if($u->bloqueado)
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                                                        @else
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                        @endif
                                                    </button>
                                                </form>
                                            @endcan
                                        @endcan
                                        @can('usuarios.eliminar')
                                            @if($u->id !== auth()->id())
                                                <form method="POST" action="{{ route('usuarios.destroy', $u) }}" class="inline" onsubmit="event.preventDefault(); confirmDelete(this, '{{ $u->nombre }}');">
                                                    @csrf @method('DELETE')
                                                    <button title="Eliminar" class="w-8 h-7 flex items-center justify-center rounded bg-dark-card border border-dark-border text-red-400 hover:text-red-300 transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

    <script>
    function confirmDelete(form, nombre) {
        Swal.fire({
            title: '¿Eliminar usuario?',
            text: 'Se eliminará ' + nombre + '. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#E60012',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
    </script>

            <div class="mt-6">{{ $usuarios->links() }}</div>

        </div>
    </div>

    {{-- Create Modal --}}
    <div id="createModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 hidden" onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-dark-panel border border-dark-border rounded-lg w-full max-w-md p-6" onclick="event.stopPropagation()">
            <h3 class="font-sans text-lg font-semibold text-dark-text mb-4">Nuevo Usuario</h3>
            <form method="POST" action="{{ route('usuarios.store') }}">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">Nombre *</label>
                        <input type="text" name="nombre" required class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">Apellido</label>
                        <input type="text" name="apellido" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">{{ __('Email') }} *</label>
                        <input type="email" name="email" required class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">Contraseña *</label>
                        <input type="password" name="password" required minlength="8" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">Rol *</label>
                        <select name="rol" required class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-4 py-2 text-sm text-dark-muted hover:text-dark-text">Cancelar</button>
                    <button type="submit" class="bg-corp hover:bg-corp-dim text-dark-bg font-medium px-4 py-2 rounded text-sm transition-colors">Crear</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="editModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 hidden" onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-dark-panel border border-dark-border rounded-lg w-full max-w-md p-6" onclick="event.stopPropagation()">
            <h3 class="font-sans text-lg font-semibold text-dark-text mb-4">Editar Usuario</h3>
            <form method="POST" action="" id="editForm">
                @csrf @method('PATCH')
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">Nombre *</label>
                        <input type="text" name="nombre" id="edit_nombre" required class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">Apellido</label>
                        <input type="text" name="apellido" id="edit_apellido" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">{{ __('Email') }} *</label>
                        <input type="email" name="email" id="edit_email" required class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">Nueva contraseña (dejar vacío para mantener)</label>
                        <input type="password" name="password" minlength="8" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                    </div>
                    <div>
                        <label class="block text-xs text-dark-muted mb-1">Rol</label>
                        <select name="rol" id="edit_rol" class="w-full bg-dark-card border border-dark-border rounded px-3 py-2 text-sm text-dark-text focus:border-corp focus:ring-1 focus:ring-corp outline-none">
                            <option value="">Sin cambio</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-4 py-2 text-sm text-dark-muted hover:text-dark-text">Cancelar</button>
                    <button type="submit" class="bg-corp hover:bg-corp-dim text-dark-bg font-medium px-4 py-2 rounded text-sm transition-colors">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editUser(id, nombre, apellido, email, rol) {
        document.getElementById('editForm').action = '/usuarios/' + id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_apellido').value = apellido;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_rol').value = rol;
        document.getElementById('editModal').classList.remove('hidden');
    }
    </script>
</x-app-layout>
