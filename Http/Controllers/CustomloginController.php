<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\Deputy;
use Illuminate\Http\Request;

use App\Mail\LoginNotification;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Hash;
use App\Models\User;



use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Controllers\CustomloginController;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class CustomloginController extends Controller
{
    //* Handle an authentication attempt.
    public function authenticate(Request $request)
    {
        // Check if the portal is EMB
    if ($request->isportal == "WMS") {
        // Decrypt the username and password
        $username = $request->input('Usernm');
        $password = $request->input('password'); // This is the plain text password

          // Store the flag in the session
          session(['isportal' => $request->isportal]);
          
          
        // Find the user by username
        $user = User::where('Usernm', $username)->first();

        // Check if the user exists
        if ($user && $password === $user->password) { // Verify the plain text password against the stored hash
            // Password is correct, proceed with authentication
            Auth::login($user);
            //dd($password, $user->password);
            return redirect()->intended('listworkmasters');
        } else {
            // Handle failed authentication (e.g., return an error message)
            return back()->withErrors([
                'Usernm' => 'The provided credentials do not match our records.',
            ])->onlyInput('Usernm');
        }
    }
    
    
        // Validate the login form data
        $request->validate([
            'Usernm' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
    
        // Attempt to authenticate the user
        $credentials = $request->only('Usernm', 'password');
    
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            
             // Check if DefaultUsrnmPass flag is set to 1
                if ($user->DefaultUnmPass == 1) {
                    
                    // Redirect to a route or page for updating username and password
                    return redirect()->route('update-credentials')->with('user_id', $user->id);
                }
    
            // Generate OTP (you can use any OTP generation logic)
            $otp = random_int(100000, 999999); // Example: 6-digit random OTP
    
            // Save OTP and credentials in session
            session(['otp' => $otp, 'otp_user' => $user->id, 'credentials' => $credentials]);
    //dd(session('password'));
            // Send OTP via email
            Mail::to($user->email)->queue(new OtpMail($otp));
    
            // Redirect to OTP form with username and password
            return redirect()->route('login')->with([
                'otp_required' => true,
                'Usernm' => $request->input('Usernm'),
                'password' => $request->input('password')
            ]);
        }
    
        // If authentication fails, redirect back with an error message
        return redirect()->route('login')->withErrors(['Usernm' => 'The provided credentials do not match our records.']);
    }
    

//Verify otp 
public function verifyOtp(Request $request)
{
    // Validate the OTP form data
    $request->validate([
        'otp' => ['required', 'string'],
    ]);

    // Retrieve OTP and user information from the session
    $otp = $request->otp;
    $sessionOtp = session('otp');
    $otpUserId = session('otp_user');
    $credentials = session('credentials');
//dd($credentials);


    if ($otp == $sessionOtp) {
        // Clear OTP from session
        session()->forget(['otp', 'otp_user', 'Usernm', 'password']);

        // Log in the user
        Auth::loginUsingId($otpUserId);

        // Send login notification email
        $user = Auth::user();
        Mail::to($user->email)->queue(new LoginNotification($user));

        // Redirect to intended page after successful OTP verification
        return redirect()->intended('listworkmasters');
    }

    // If OTP verification fails, redirect back with an error message
    return redirect()->route('login')->withErrors(['otp' => 'Invalid OTP.'])
        ->with([
            'otp_required' => true,
            'Usernm' => $credentials['Usernm'],
            'password' => $credentials['password']
        ]);
}
    

    public function loginview()
    {
        return view('login');
    }
    
    //First time login page open 
    public function showUpdateCredentialsForm(Request $request)
    {
        $user = User::find(session('user_id'));
    
        return view('auth.update_credentials', compact('user'));
    }

    
    //first time log in use data update
    public function updateCredentials(Request $request)
    {
         // Validate new username and password
         $request->validate([
            'Usernm' => 'required|string|unique:users,Usernm,' . $request->user_id,
            'password' => 'required|string|min:8|confirmed',
        ]);
    
    
             $user = User::find($request->user_id);
            
            // Check if entered credentials match existing default credentials
            if ($request->Usernm === $user->Usernm || Hash::check($request->password, $user->password)) {
                session()->flash('error', 'Default username and password cannot be reused. Please choose different credentials.');
                
                // Return the view directly, passing any necessary data
                return view('auth.update_credentials', ['user' => $user]);
            }
            
    
        // Generate OTP
        $otp = random_int(100000, 999999);
        session(['otp' => $otp, 'otp_user' => $request->user_id]);
    
        // Temporarily store credentials in the session
        session(['credentials' => [
            'Usernm' => $request->Usernm,
            'password' => $request->password,
        ]]);
    
        // Send OTP via email
        Mail::to(User::find($request->user_id)->email)->queue(new OtpMail($otp));
    
       
        // Redirect back to the form with OTP input enabled
        return redirect()->route('update-credentials')->with('otp_required', true)->with('status', 'OTP sent to your email. Please verify.')->with('user' , $user);
    }
    
    //verify otp
    public function verifyOtpupdatecred(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:6',
        ]);
    
        // Verify the OTP
        if ($request->otp == session('otp')) {
            $credentials = session('credentials');
            $user = User::find(session('otp_user'));
    
            //Update credentials in the database
            $user->Usernm = $credentials['Usernm'];
            $user->password = Hash::make($credentials['password']);
            $user->DefaultUnmPass = 0; // Reset flag
            $user->save();
    
            // Clear OTP and credentials from the session
            session()->forget(['otp', 'otp_user', 'credentials']);
    
            Auth::logout();
            return redirect()->route('login')->with('status', 'Credentials updated successfully. Please log in.');
        } else {
            return back()->withErrors(['otp' => 'The OTP entered is incorrect.']);
        }
    }

}