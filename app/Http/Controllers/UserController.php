<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('usuarios.ver'), 403);
        $usuarios = User::with('roles')->latest()->paginate(20);
        $roles = Role::when(
            ! auth()->user()->can('roles.administrar'),
            fn ($query) => $query->where('name', '<>', 'superadministrador')
        )->get();
        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('usuarios.crear'), 403);

        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'rol' => 'required|exists:roles,name',
        ]);
        $this->autorizarAsignacionRol($data['rol']);

        $user = User::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'activo' => true,
            'creado_por' => auth()->id(),
        ]);

        $user->assignRole($data['rol']);

        return redirect()->route('usuarios.index')->with('success', "Usuario {$user->nombre} creado.");
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->can('usuarios.editar'), 403);
        $this->autorizarGestionUsuario($user);

        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'apellido' => 'nullable|string|max:100',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'rol' => 'nullable|exists:roles,name',
        ]);
        if (! empty($data['rol'])) {
            $this->autorizarAsignacionRol($data['rol']);
        }

        $user->nombre = $data['nombre'];
        $user->apellido = $data['apellido'];
        $user->email = $data['email'];
        $user->actualizado_por = auth()->id();

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if ($request->filled('rol')) {
            $user->syncRoles([$data['rol']]);
        }

        return redirect()->route('usuarios.index')->with('success', "Usuario {$user->nombre} actualizado.");
    }

    public function toggleActivo(User $user)
    {
        abort_unless(auth()->user()->can('usuarios.editar'), 403);
        $this->autorizarGestionUsuario($user);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes desactivarte a ti mismo.']);
        }

        $user->update([
            'activo' => !$user->activo,
            'actualizado_por' => auth()->id(),
        ]);

        $estado = $user->activo ? 'activado' : 'desactivado';
        return back()->with('success', "Usuario {$user->nombre} {$estado}.");
    }

    public function toggleBloqueo(User $user)
    {
        abort_unless(auth()->user()->can('configuracion.editar'), 403);
        $this->autorizarGestionUsuario($user);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes bloquearte a ti mismo.']);
        }

        $user->update([
            'bloqueado' => !$user->bloqueado,
            'intentos_fallidos' => 0,
            'actualizado_por' => auth()->id(),
        ]);

        $estado = $user->bloqueado ? 'bloqueado' : 'desbloqueado';
        return back()->with('success', "Usuario {$user->nombre} {$estado}.");
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->can('usuarios.eliminar'), 403);
        $this->autorizarGestionUsuario($user);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes eliminarte a ti mismo.']);
        }

        $user->delete();
        return redirect()->route('usuarios.index')->with('success', "Usuario {$user->nombre} eliminado.");
    }

    private function autorizarAsignacionRol(string $rol): void
    {
        if ($rol === 'superadministrador') {
            abort_unless(auth()->user()->can('roles.administrar'), 403, 'No puedes asignar el rol superadministrador.');
        }
    }

    private function autorizarGestionUsuario(User $user): void
    {
        if ($user->hasRole('superadministrador')) {
            abort_unless(auth()->user()->can('roles.administrar'), 403, 'No puedes modificar una cuenta superadministradora.');
        }
    }
}
