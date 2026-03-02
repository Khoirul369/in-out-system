<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi.',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return back()->withErrors(['username' => 'Username tidak ditemukan.'])->withInput();
        }

        // Simpan user ke session
        session([
            'user' => [
                'id'           => $user->id,
                'username'     => $user->username,
                'role'         => $user->role,
                'nama'         => $user->nama,
                'id_karyawan'  => $user->id_karyawan,
                'divisi_posisi'=> $user->divisi_posisi,
                'pm_id'        => $user->pm_id,
                'is_pm'        => (bool) $user->is_pm,
            ]
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('user');
        return redirect()->route('login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username'     => 'required|string|unique:users,username',
            'nama'         => 'required|string',
            'role'         => 'required|in:karyawan,hc,it,doc,ga,finance',
            'password'     => 'required|string|min:6|confirmed',
            'id_karyawan'  => 'required_if:role,karyawan',
            'admin_code'   => 'required_if:role,hc,it,doc,ga,finance',
        ], [
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if ($request->role !== 'karyawan') {
            if ($request->admin_code !== config('app.admin_code', 'MUCIT2024')) {
                return back()->withErrors(['admin_code' => 'Kode admin tidak valid.'])->withInput();
            }
        }

        User::create([
            'username'      => $request->username,
            'password_hash' => bcrypt($request->password),
            'role'          => $request->role,
            'nama'          => $request->nama,
            'id_karyawan'   => $request->id_karyawan,
            'divisi_posisi' => $request->divisi_posisi,
            'created_at'    => now(),
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil. Silakan login.');
    }

}
