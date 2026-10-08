<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class LoginController extends Controller {
 public function choose(){return view('auth.choose');}
 public function show(string $role){return view('auth.login',compact('role'));}
 public function store(LoginRequest $request,string $role){$request->authenticate();return redirect()->route($request->user()->homeRoute());}
 public function destroy(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('login');}
}
