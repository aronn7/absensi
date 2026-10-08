<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class SchoolClassRequest extends FormRequest {
 public function authorize(): bool{return $this->user()->isAdmin();}
 public function rules(): array {
  return ['name'=>['required','string','max:100',Rule::unique('school_classes')->where('academic_year',$this->input('academic_year'))->ignore($this->route('schoolClass'))],'grade'=>'required|in:X,XI,XII','major'=>'required|string|max:100','academic_year'=>'required|regex:/^20[0-9]{2}\/20[0-9]{2}$/','homeroom_teacher_id'=>['nullable',Rule::exists('teachers','id')->whereIn('user_id',\App\Models\User::where('active',true)->pluck('id')->all())],'pin'=>[$this->route('schoolClass')?'nullable':'required','digits_between:6,12'],'active'=>'required|boolean'];
 }
}
