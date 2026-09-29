<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\Installer;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if (! Installer::installed()) {
            return redirect()->route('install.show');
        }

        return view('landing', [
            'company' => Setting::get('company_name', 'QEAI One'),
        ]);
    }
}
