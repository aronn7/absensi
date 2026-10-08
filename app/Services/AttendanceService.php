<?php
namespace App\Services;
use App\Models\{Attendance,AttendanceSetting,AbsenceRequest,AuditLog,User,ClassStudent};
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class AttendanceService {
 public function __construct(private CalendarService $calendar,private LateDetectionService $late,private NotificationService $notifications){}
 public function record(User $user,string $type,string $method,User $actor): Attendance {
  return DB::transaction(function()use($user,$type,$method,$actor){
   $user=User::lockForUpdate()->findOrFail($user->id);$now=now();$settings=AttendanceSetting::current();
   $this->ensure($user->active && in_array($user->role,['student','teacher']),'Akun tidak aktif atau tidak dapat melakukan absensi.');
   if($user->role==='student')$this->ensure((bool)$user->student?->schoolClass?->active,'Kelas tidak aktif.');
   $this->ensure($actor->isAdmin() || $actor->id===$user->id,'Akses absensi ditolak.');
   $this->ensure($method!=='manual' || $settings->manual_enabled,'Absensi manual dinonaktifkan. Gunakan terminal QR.');
   $this->ensure($this->calendar->isSchoolDay($now),'Absensi tidak aktif pada hari ini.');
   $date=$now->toDateString();$time=$now->format('H:i:s');
   $row=Attendance::where('user_id',$user->id)->whereDate('date',$date)->first();
   $this->ensure(in_array($type,['in','out']),'Jenis absensi tidak valid.');
   if($type==='in'){
    $this->ensure(!$row,'Anda sudah melakukan absensi masuk hari ini atau memiliki catatan ketidakhadiran.');
    $this->ensure($time>=$settings->opens_at,'Absensi masuk belum dibuka.');
    $this->ensure($time<$settings->checkout_at,'Batas absensi masuk sudah berakhir.');
    $this->ensure(!AbsenceRequest::where('user_id',$user->id)->whereDate('date',$date)->whereIn('status',['PENDING','DISETUJUI'])->exists(),'Anda memiliki permohonan izin/sakit untuk hari ini.');
    $row=Attendance::create(array_merge(['user_id'=>$user->id,'school_class_id'=>$user->student?->school_class_id,'date'=>$date,'check_in_at'=>$now,'method'=>$method,'created_by'=>$actor->id],$this->late->calculate($now,$settings->late_after)));
   }else{
    $this->ensure($row && $row->check_in_at,'Lakukan absensi masuk terlebih dahulu.');
    $this->ensure(!$row->check_out_at,'Anda sudah melakukan absensi pulang hari ini.');
    $this->ensure($time>=$settings->checkout_at,'Absensi pulang belum dibuka.');
    $this->ensure($time<=$settings->closes_at,'Batas absensi pulang sudah berakhir.');
    $row->update(['check_out_at'=>$now]);
   }
   ReportService::invalidate($row->school_class_id,$now);
   AuditLog::record('attendance.'.$type,$row,['method'=>$method]);
   $this->notifications->attendance($user,$type==='in'?$row->status:'PULANG',($type==='in'?'Masuk':'Pulang').' pada '.$now->format('d/m/Y H:i').'.',$row->schoolClass);
   return $row;
  },3);
 }
 public function markAbsent(CarbonInterface $date): int {
  $date=$date->copy()->startOfDay();$settings=AttendanceSetting::current();
  if(!$this->calendar->isSchoolDay($date) || $date->isFuture() || ($date->isToday() && now()->format('H:i:s')<=$settings->closes_at))return 0;
  $count=0;
  User::where('active',true)->whereIn('role',['student','teacher'])->whereDate('created_at','<=',$date)->chunkById(100,function($users)use($date,&$count){
   foreach($users as $user)DB::transaction(function()use($user,$date,&$count){
    $user=User::lockForUpdate()->find($user->id);
    if(!$user->active)return;
    $class=null;
    if($user->role==='student'){
     $enrollment=ClassStudent::where('student_id',$user->student?->id)->whereDate('joined_at','<=',$date)->where(fn($q)=>$q->whereNull('left_at')->orWhereDate('left_at','>',$date))->first();
     if(!$enrollment || !$enrollment->schoolClass->active)return;
     $class=$enrollment->schoolClass;
    }
    if(Attendance::where('user_id',$user->id)->whereDate('date',$date)->exists())return;
    if(AbsenceRequest::where('user_id',$user->id)->whereDate('date',$date)->whereIn('status',['PENDING','DISETUJUI'])->exists())return;
    $row=Attendance::create(['user_id'=>$user->id,'school_class_id'=>$class?->id,'date'=>$date,'status'=>'ALFA','method'=>'system']);
    ReportService::invalidate($class?->id,$date);$count++;
    $this->notifications->attendance($user,'ALFA','Tidak tercatat hadir pada '.$date->format('d/m/Y').'.',$class);
   },3);
  });
  return $count;
 }
 private function ensure(bool $condition,string $message): void {if(!$condition)throw ValidationException::withMessages(['attendance'=>$message]);}
}
