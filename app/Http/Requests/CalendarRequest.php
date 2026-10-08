<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class CalendarRequest extends FormRequest {
 public function authorize(): bool{return $this->user()->isAdmin();}
 public function rules(): array{return ['title'=>'required|string|max:150','starts_on'=>'required|date_format:Y-m-d','ends_on'=>'required|date_format:Y-m-d|after_or_equal:starts_on','type'=>'required|in:Hari sekolah,Hari libur,Ujian,Kegiatan sekolah,Libur semester,Class meeting,Acara sekolah,Hari khusus','description'=>'nullable|string|max:2000','attendance_active'=>'required|boolean'];}
}
