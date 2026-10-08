<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\AttendanceSetting;
class AbsenceStoreRequest extends FormRequest {
 public function authorize(): bool{return $this->user()->role==='student';}
 public function rules(): array {return ['date'=>'required|date_format:Y-m-d|after_or_equal:'.today()->subDays(7)->toDateString().'|before_or_equal:'.today()->addDays(30)->toDateString(),'type'=>'required|in:SAKIT,IZIN','purpose'=>'nullable|required_if:type,IZIN|string|max:100','reason'=>'required|string|min:5|max:1000','notes'=>'nullable|string|max:2000','evidence'=>[$this->input('type')==='SAKIT' && AttendanceSetting::current()->sick_evidence_required?'required':'nullable','image','mimes:jpg,jpeg,png,webp','max:4096','dimensions:max_width=4000,max_height=4000']];}
}
