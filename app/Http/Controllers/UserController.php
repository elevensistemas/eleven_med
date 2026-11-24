<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Garantiza que todos los roles del consultorio (incluido técnico) existan en la base de datos.
     */
    protected function ensureRolesExist(): void
    {
        try {
            foreach (['administrador', 'médico', 'técnico', 'recepcionista', 'supervisor'] as $rName) {
                Role::firstOrCreate(['name' => $rName, 'guard_name' => 'web']);
            }
        } catch (\Throwable $e) {
            // Silencioso
        }
    }

    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $this->ensureRolesExist();
        $users = User::with('roles')->paginate(15);
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->ensureRolesExist();
        $roles = Role::orderBy('id')->get();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage (soporta múltiples roles simultáneos).
     */
    public function store(Request $request)
    {
        $this->ensureRolesExist();

        if (!$request->has('roles') && $request->filled('role_name')) {
            $request->merge(['roles' => [$request->input('role_name')]]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'roles.required' => 'Debes seleccionar al menos un rol para el usuario.',
        ]);

        $photoPath = null;
        if ($request->hasFile('profile_photo')) {
            $photoPath = $request->file('profile_photo')->store('avatars', 'public');
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_photo' => $photoPath,
        ]);

        $user->syncRoles($request->input('roles', []));

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente con sus roles asignados.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $this->ensureRolesExist();
        $roles = Role::orderBy('id')->get();
        $userRoles = $user->roles->pluck('name')->toArray();
        $userRole = $userRoles[0] ?? null;
        return view('users.edit', compact('user', 'roles', 'userRole', 'userRoles'));
    }

    /**
     * Update the specified user in storage (soporta múltiples roles simultáneos y cambio opcional de clave por admin).
     */
    public function update(Request $request, User $user)
    {
        $this->ensureRolesExist();

        if (!$request->has('roles') && $request->filled('role_name')) {
            $request->merge(['roles' => [$request->input('role_name')]]);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
            'password' => ['nullable', 'confirmed', 'min:6'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'roles.required' => 'Debes seleccionar al menos un rol para el usuario.',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password') && auth()->user() && auth()->user()->hasRole('administrador')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('avatars', 'public');
        }

        $user->update($data);
        $user->syncRoles($request->input('roles', []));

        return redirect()->route('users.index')->with('success', 'Perfil y roles de usuario actualizados correctamente.');
    }

    /**
     * Cambio de contraseña propia al ingresar al sistema — EXCLUSIVO para usuarios con rol Administrador.
     */
    public function changeOwnPassword(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('administrador')) {
            abort(403, 'Solo los usuarios Administradores tienen habilitado cambiar su contraseña de ingreso.');
        }

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Ingresá tu contraseña actual.',
            'password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual ingresada es incorrecta.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', '¡Tu contraseña de entrada fue actualizada correctamente!');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['Error' => 'No puedes inhabilitarte a ti mismo.']);
        }

        $user->delete();
        return back()->with('success', 'Usuario inhabilitado correctamente.');
    }

    /**
     * Quick Reset Password via default hash (elevenmed2026)
     */
    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('elevenmed2026')
        ]);

        return back()->with('success', 'Contraseña restablecida exitosamente a "elevenmed2026" para ' . $user->name);
    }
}
