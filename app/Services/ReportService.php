<?php
namespace App\Services;
use App\Models\{SchoolClass,MonthlyReport,Attendance,AbsenceRequest,Student,ClassStudent};
use App\Exports\AttendanceExport;
use Carbon\{Carbon,CarbonInterface,CarbonPeriod};
use Illuminate\Support\Facades\{Storage,DB};
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class ReportService {
 public static function invalidate(?int $classId,CarbonInterface $date): void {
  if(!$classId)return;
  DB::transaction(function()use($classId,$date){
  SchoolClass::lockForUpdate()->findOrFail($classId);
  MonthlyReport::where('school_class_id',$classId)->where('year',$date->year)->where('month',$date->month)->update(['status'=>'PENDING','approved_by'=>null,'approved_at'=>null,'generated_at'=>null]);
  },3);
 }
 public function data(SchoolClass $class,int $year,int $month): array {
  $start=Carbon::create($year,$month,1)->startOfDay();$end=$start->copy()->endOfMonth();$cutoff=$end->min(now());
  $rows=Attendance::with('user.student','user.teacher')->where('school_class_id',$class->id)->whereBetween('date',[$start->toDateString(),$end->toDateString()])->orderBy('date')->get();
  $enrollments=ClassStudent::with('student.user')->where('school_class_id',$class->id)->whereDate('joined_at','<=',$end)->where(fn($q)=>$q->whereNull('left_at')->orWhereDate('left_at','>',$start))->get();
  $students=Student::with('user')->whereIn('id',$enrollments->pluck('student_id'))->orWhereIn('user_id',$rows->pluck('user_id'))->get();
  $schoolDays=[];if($cutoff->gte($start))foreach(CarbonPeriod::create($start,$cutoff) as $day)if(app(CalendarService::class)->isSchoolDay($day))$schoolDays[]=$day->toDateString();
  $summary=[];
  foreach($students as $student){$mine=$rows->where('user_id',$student->user_id);$counts=$mine->countBy('status');$eligible=0;
   foreach($schoolDays as $day){if($enrollments->where('student_id',$student->id)->contains(fn($e)=>$e->joined_at->toDateString()<=$day && (!$e->left_at || $e->left_at->toDateString()>$day)))$eligible++;}
   $attended=($counts['HADIR']??0)+($counts['TERLAMBAT']??0);
   $summary[]=[$student->nisn,$student->user->name,$counts['HADIR']??0,$counts['TERLAMBAT']??0,(int)$mine->sum('late_minutes'),$counts['SAKIT']??0,$counts['IZIN']??0,$counts['ALFA']??0,$eligible?round($attended/$eligible*100,2):0,$eligible];
  }
  $convert=fn($a)=>[$a->date->toDateString(),$a->user->student?->nisn??$a->user->teacher?->employee_id,$a->user->name,$a->status,$a->check_in_at?->format('H:i:s'),$a->check_out_at?->format('H:i:s'),$a->late_minutes];
  $details=$rows->map($convert)->all();$late=$rows->where('status','TERLAMBAT')->map($convert)->values()->all();
  $requests=AbsenceRequest::with('user','reviewer')->where('school_class_id',$class->id)->whereBetween('date',[$start->toDateString(),$end->toDateString()])->get()->map(fn($a)=>[$a->date->toDateString(),$a->user->name,$a->type,$a->reason,$a->status,$a->reviewer?->name])->all();
  return compact('summary','details','requests','late');
 }
 public function generate(SchoolClass $class,int $year,int $month): MonthlyReport {
  return DB::transaction(function()use($class,$year,$month){
  SchoolClass::lockForUpdate()->findOrFail($class->id);
  $report=MonthlyReport::firstOrCreate(['school_class_id'=>$class->id,'year'=>$year,'month'=>$month]);
  if($report->generated_at && !($year===today()->year && $month===today()->month && !$report->generated_at->isToday()) && $report->file_path && Storage::disk('local')->exists($report->file_path))return $report;
  $data=$this->data($class,$year,$month);$book=(new AttendanceExport())->workbook(...$data);$file='reports/'.$class->id.'/'.$year.'-'.str_pad((string)$month,2,'0',STR_PAD_LEFT).'.xlsx';
  Storage::disk('local')->makeDirectory(dirname($file));$tmp=Storage::disk('local')->path($file).'.'.bin2hex(random_bytes(8)).'.tmp';
  try{(new Xlsx($book))->save($tmp);Storage::disk('local')->put($file,file_get_contents($tmp));}finally{if(is_file($tmp))unlink($tmp);$book->disconnectWorksheets();}
  $report->update(['file_path'=>$file,'generated_at'=>now()]);return $report;
  },3);
 }
}
