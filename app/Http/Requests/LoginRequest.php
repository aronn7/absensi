<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\{Auth,Hash,RateLimiter};
use Illuminate\Validation\ValidationException;
use App\Models\User;
class LoginRequest extends FormRequest {
 public function authorize(): bool{return true;}
 public function rules(): array{return ['identity'=>'required|string|max:255','password'=>'required|string|max:255','nisn'=>$this->route('role')==='student'?'required|digits:10':'nullable'];}
 public function authenticate(): void {
  $role=$this->route('role');$key=hash('sha256',mb_strtolower($this->string('identity')).'|'.$this->ip().'|'.$role);
  if(RateLimiter::tooManyAttempts($key,5))throw ValidationException::withMessages(['identity'=>'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.']);
  $users=User::where('role',$role)->where('active',true)->where(function($q)use($role){
   if($role==='student')$q->where('email',$this->input('identity'));
   else {$q->where('login_id',$this->input('identity'));if($role==='teacher')$q->orWhere('name',$this->input('identity'));}
  })->get();
  $user=$users->count()===1?$users->first():null;
  $valid=$user && Hash::check($this->input('password'),$user->password);
  if($role==='student')$valid=$valid && $user?->student?->nisn===$this->input('nisn');
  if(!$valid){RateLimiter::hit($key,60);throw ValidationException::withMessages(['identity'=>'Identitas atau password tidak sesuai.']);}
  RateLimiter::clear($key);Auth::login($user);$this->session()->regenerate();
 }
}
