<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class TeacherRequest extends FormRequest {
 public function authorize(): bool{return $this->user()->isAdmin();}
 public function rules(): array {
  $teacher=$this->route('teacher');
  return ['name'=>'required|string|max:100','employee_id'=>['required','string','max:50','regex:/^[A-Za-z0-9._-]+$/',Rule::unique('teachers')->ignore($teacher),Rule::unique('users','login_id')->ignore($teacher?->user_id)],'email'=>['required','email','max:255',Rule::unique('users')->ignore($teacher?->user_id)],'password'=>[$teacher?'nullable':'required','string','min:8','max:128'],'position'=>'required|string|max:100','address'=>'nullable|string|max:1000','phone'=>'nullable|string|max:30','active'=>'required|boolean','photo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4000,max_height=4000'];
 }
}
