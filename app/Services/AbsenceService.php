<?php
namespace App\Services;
use App\Models\{AbsenceRequest,Attendance,User,AuditLog};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class AbsenceService {
 public function submit(User $user,array $data): AbsenceRequest {
  return DB::transaction(function()use($user,$data){
   $user=User::lockForUpdate()->findOrFail($user->id);$date=\Carbon\Carbon::parse($data['date']);
   if(!$user->student?->schoolClass->active || !app(CalendarService::class)->isSchoolDay($date))throw ValidationException::withMessages(['date'=>'Tanggal bukan hari sekolah aktif.']);
   if(Attendance::where('user_id',$user->id)->whereDate('date',$date)->where('status','!=','ALFA')->exists())throw ValidationException::withMessages(['date'=>'Sudah ada absensi pada tanggal ini.']);
   if(AbsenceRequest::where('user_id',$user->id)->whereDate('date',$date)->exists())throw ValidationException::withMessages(['date'=>'Permohonan pada tanggal ini sudah pernah dikirim.']);
   $request=AbsenceRequest::create([...$data,'user_id'=>$user->id,'school_class_id'=>$user->student->school_class_id,'status'=>'PENDING']);
   app(NotificationService::class)->request($user,$request->schoolClass,$request->type);
   AuditLog::record('absence.submit',$request);return $request;
  },3);
 }
 public function review(AbsenceRequest $absence,User $reviewer,string $decision,?string $notes): void {
  DB::transaction(function()use($absence,$reviewer,$decision,$notes){
   User::lockForUpdate()->findOrFail($absence->user_id);
   $absence=AbsenceRequest::lockForUpdate()->findOrFail($absence->id);
   if($absence->status!=='PENDING')throw ValidationException::withMessages(['decision'=>'Permohonan telah diperiksa.']);
   if($decision==='DISETUJUI'){
    $row=Attendance::where('user_id',$absence->user_id)->whereDate('date',$absence->date)->first();
    if($row && $row->status!=='ALFA')throw ValidationException::withMessages(['decision'=>'Terdapat absensi lain pada tanggal ini.']);
    Attendance::updateOrCreate(['user_id'=>$absence->user_id,'date'=>$absence->date->toDateString()],['school_class_id'=>$absence->school_class_id,'status'=>$absence->type,'method'=>'approved','created_by'=>$reviewer->id,'late_minutes'=>0]);
   }
   $absence->update(['status'=>$decision,'approved_by'=>$reviewer->id,'approved_at'=>now(),'review_notes'=>$notes]);
   ReportService::invalidate($absence->school_class_id,$absence->date);
   app(NotificationService::class)->attendance($absence->user,$absence->type,'Permohonan '.$absence->date->format('d/m/Y').' '.$decision.'.',$absence->schoolClass);
   AuditLog::record('absence.review',$absence,['decision'=>$decision]);
  },3);
  if($decision==='DITOLAK')app(AttendanceService::class)->markAbsent($absence->date);
 }
}
