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

 public function adminGate(Request $request, ?string $secret = null){
  $expected = config('services.admin_gate.secret');
  if ($secret === null && $request->session()->get('admin_gate_ok') === true) {
   return view('auth.admin-gate');
  }
  if (!is_string($expected) || $expected === '' || !hash_equals($expected, (string) $secret)) {
   abort(404);
  }
  $request->session()->put('admin_gate_ok', true);
  return view('auth.admin-gate');
 }

 public function adminGateVerify(Request $request){
  if ($request->session()->get('admin_gate_ok') !== true) abort(404);
  $request->validate(['pin' => 'required|digits_between:1,12']);
  $hash = config('services.admin_gate.pin_hash');
  if (!is_string($hash) || $hash === '' || !\Illuminate\Support\Facades\Hash::check($request->input('pin'), $hash)) {
   return back()->withErrors(['pin' => 'PIN tidak sesuai.'])->onlyInput('pin');
  }
  $request->session()->forget('admin_gate_ok');
  return redirect()->route('login.show', 'admin');
 }
}
