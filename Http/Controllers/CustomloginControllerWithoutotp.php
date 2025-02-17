<?php

namespace App\Http\Controllers;

use App\Models\Deputy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Controllers\CustomloginController;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Support\Facades\Mail;
use App\Mail\LoginNotification;

class CustomloginController extends Controller
{
    //* Handle an authentication attempt.
    public function authenticate(Request $request)
    {
        //dd($request);
         // Validate the login form data
        $request->validate([
            'Usernm' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
   
        // Attempt to authenticate the user
        $credentials = $request->only('Usernm', 'password');
           // dd($credentials);
           if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            $userid=$user->id;
            //dd($userid);

            //dd($user->email);

            // Send login notification email
             Mail::to($user->email)->queue(new LoginNotification($user));

            // Redirect to the intended page after login
            return redirect()->intended('listworkmasters');
        }
 
          // If authentication fails, redirect back with an error message
        return back()->withErrors([
            'Usernm' => 'The provided credentials do not match our records.',
        ])->onlyInput('Usernm');
    }


    public function loginview()
    {
        return view('login');
    }
}