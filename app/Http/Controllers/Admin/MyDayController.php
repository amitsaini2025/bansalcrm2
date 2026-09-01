<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class MyDayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard', [], 302)->withFragment('my-day');
    }
}
