<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceSettingsRequest;
use App\Models\{AttendanceSetting,MonthlyReport,AuditLog};
use App\Services\UploadService;
class SettingsController extends Controller {
 public function edit(){return view('admin.settings',['settings'=>AttendanceSetting::current()]);}
 public function update(AttendanceSettingsRequest $r){$data=$r->safe()->except('logo');$data['school_days']=array_map('intval',$data['school_days']);if($r->hasFile('logo'))$data['logo_path']=app(UploadService::class)->image($r->file('logo'),'school','public');$settings=AttendanceSetting::current();$settings->update($data);app(\App\Services\CalendarReconciliationService::class)->reconcile();AuditLog::record('settings.update',$settings);return back()->with('success','Pengaturan disimpan.');}
}
