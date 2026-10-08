<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StudentRequest extends FormRequest {
 public function authorize(): bool{return $this->user()->isAdmin();}
 public function rules(): array {
  $student=$this->route('student');
  return ['name'=>'required|string|max:100','nisn'=>['required','digits:10',Rule::unique('students')->ignore($student)],'email'=>['required','email','max:255',Rule::unique('users')->ignore($student?->user_id)],'password'=>[$student?'nullable':'required','string','min:8','max:128'],'gender'=>'required|in:L,P','birth_date'=>'nullable|date|before:today','address'=>'nullable|string|max:1000','phone'=>'nullable|string|max:30','school_class_id'=>['required',Rule::exists('school_classes','id')->where('active',true)],'active'=>'required|boolean','photo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4000,max_height=4000','guardian_name'=>'required|string|max:100','guardian_relationship'=>'required|string|max:50','guardian_phone'=>'required|string|max:30','guardian_email'=>'nullable|email|max:255'];
 }
}
