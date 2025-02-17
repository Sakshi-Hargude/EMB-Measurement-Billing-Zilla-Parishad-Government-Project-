<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\LogoutController;
use League\Flysystem\Local\LocalFilesystemAdapter;


class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        Auth::logout(); // Log the user out

        $request->session()->invalidate();
        $request->session()->regenerateToken();
       // dd($request);

        return redirect()->intended('login');// Redirect to the log out
    
    }
    
}