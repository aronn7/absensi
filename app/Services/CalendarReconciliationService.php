<?php
namespace App\Services;
use App\Models\{Attendance,AuditLog,MonthlyReport};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class CalendarReconciliationService {
 public function reconcile(): void {
  // Only automatic Alfa is removed. Explicit attended or approved records are preserved.
  DB::transaction(function(){
   Attendance::where('status','ALFA')->select('date')->distinct()->get()->each(function($row){
    if(!app(CalendarService::class)->isSchoolDay($row->date))Attendance::whereDate('date',$row->date)->where('status','ALFA')->get()->each(function($attendance){AuditLog::record('attendance.remove_holiday_alfa',$attendance,['date'=>$attendance->date->toDateString()]);$attendance->delete();});
   });
   MonthlyReport::query()->update(['generated_at'=>null,'status'=>'PENDING','approved_at'=>null,'approved_by'=>null]);
  });
 }
}
