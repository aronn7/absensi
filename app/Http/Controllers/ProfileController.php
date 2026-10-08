<?php
namespace App\Http\Controllers;
use App\Services\UploadService;
use Illuminate\Http\Request;
class ProfileController extends Controller {
 public function edit(Request $r){return view('profile.edit',['user'=>$r->user()]);}
 public function update(Request $r){$data=$r->validate(['phone'=>'nullable|string|max:30','photo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4000,max_height=4000','current_password'=>'nullable|required_with:password|current_password','password'=>'nullable|string|min:8|max:128|confirmed']);$user=$r->user();$user->phone=$data['phone']??null;if($r->hasFile('photo'))$user->photo_path=app(UploadService::class)->image($r->file('photo'),'profiles','public');if(!empty($data['password'])){$user->password=$data['password'];$r->session()->regenerate();}$user->save();\App\Models\AuditLog::record('profile.update',$user);return back()->with('success','Profil diperbarui.');}
}
