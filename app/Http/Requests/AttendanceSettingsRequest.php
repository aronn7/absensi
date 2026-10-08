<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AttendanceSettingsRequest extends FormRequest {
 public function authorize(): bool{return $this->user()->isAdmin();}
 public function rules(): array{return ['school_name'=>'required|string|max:150','school_address'=>'nullable|string|max:255','logo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=2000,max_height=2000','opens_at'=>'required|date_format:H:i','starts_at'=>'required|date_format:H:i|after:opens_at','late_after'=>'required|date_format:H:i|after_or_equal:starts_at','checkout_at'=>'required|date_format:H:i|after:late_after','closes_at'=>'required|date_format:H:i|after:checkout_at','school_days'=>'required|array|min:1','school_days.*'=>'required|integer|between:1,6|distinct','manual_enabled'=>'required|boolean','sick_evidence_required'=>'required|boolean'];}
}
