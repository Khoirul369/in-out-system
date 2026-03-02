<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function getAuthUser(): User
    {
        $userId = session('user.id');
        if (!$userId) {
            abort(401, 'Sesi tidak valid. Silakan login ulang.');
        }

        $user = User::find($userId);
        if (!$user) {
            session()->forget('user');
            abort(401, 'Sesi tidak valid. Silakan login ulang.');
        }

        return $user;
    }
}
