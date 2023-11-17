<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Mail\RegisterMail;
use App\Mail\ResetMail;
use App\Models\User;
use Mail;

class AuthController extends Controller
{
    public function index()
    {
        return view('login');
    }

    public function authLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if(Auth::attempt($credentials)) {
            if(auth()->user()->role == 1)
            {
                return redirect()->route('adminDashboard');
            }
            else
            {
                dd('Other Dashboards');
            }
        }
        return redirect()->back()->withError('Wrong Credentials');
        
    }

    public function store(Request $request)
    {
        $this->validate($request,[
            'name' =>'required',
            'email' =>'required|unique:users,email',
            'password' =>'required|confirmed|min:4'
        ]);

        $random = rand(100000, 999999);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'token' => $random
        ]);
        $mailData = [
            'title' => 'Email Verification',
            'body' => 'Click the below button to verify your email',
            'code' => $random,
            'email' => $request->email
        ];
         
        Mail::to($request->email)->send(new RegisterMail($mailData));

        return redirect()->back()->withSuccess('Check Your Email!');
    }

    public function verify($email, $code)
    {
        $user = User::whereEmail($email)->whereToken($code)->first();
        if(isset($user))
        {
            $user->update([
                'verified' => 1,
                'token' => null
            ]);
            return redirect()->route('loginForm')->withSuccess('Your Account Verified');
        }
        return redirect()->route('loginForm')->withError('Wrong Code');
    }

    public function reset(Request $request)
    {
        $mailData = [
            'title' => 'Change Password',
            'body' => 'Click the below button to change your password',
            'email' => $request->email
        ];
         
        Mail::to($request->email)->send(new ResetMail($mailData));
        return redirect()->back()->withSuccess('Check Your Email!');
    }

    public function UpdatePassword(Request $request)
    {
        $this->validate($request,[
            'email' =>'required|exists:users,email',
            'password' =>'required|confirmed|min:4'
        ]);

        User::whereEmail($request->email)->update([
            'password' => bcrypt($request->password)
        ]);

        return redirect()->route('loginForm')->withSuccess('Your Password Changed');
    }

    public function resetPassword($email)
    {
        return view('reset',compact('email'));
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('loginForm');
    }
}
