<?php

namespace App\Controllers\Core;

use App\Models\User;
use Sakuci\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('core.dashboard', ['user' => User::current()]);
    }

    public function admin()
    {
        return view('core.admin.dashboard', ['user' => User::current()]);
    }
}

