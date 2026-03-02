<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterPasswordValidationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_register_requires_password(): void
    {
        $response = $this->post(route('register.post'), [
            'username' => 'reg_no_pass_'.uniqid(),
            'nama' => 'Register No Pass',
            'role' => 'karyawan',
            'id_karyawan' => 'EMP-'.uniqid(),
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_register_requires_password_confirmation_match(): void
    {
        $response = $this->post(route('register.post'), [
            'username' => 'reg_bad_confirm_'.uniqid(),
            'nama' => 'Register Bad Confirm',
            'role' => 'karyawan',
            'id_karyawan' => 'EMP-'.uniqid(),
            'password' => 'secret123',
            'password_confirmation' => 'secret456',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_register_stores_hashed_password(): void
    {
        $username = 'reg_hash_'.uniqid();
        $password = 'secret123';

        $response = $this->post(route('register.post'), [
            'username' => $username,
            'nama' => 'Register Hash',
            'role' => 'karyawan',
            'id_karyawan' => 'EMP-'.uniqid(),
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertRedirect(route('login'));

        $user = DB::table('users')->where('username', $username)->first();
        $this->assertNotNull($user);
        $this->assertNotSame($password, $user->password_hash);
        $this->assertTrue(Hash::check($password, $user->password_hash));
    }
}
