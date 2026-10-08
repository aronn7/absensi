<?php
namespace App\Http\Controllers;
use App\Models\{Attendance,AttendanceSetting,SchoolClass};
use App\Services\{AttendanceService,QRCodeService};
use Illuminate\Http\Request;
class AttendanceController extends Controller {
 public function index(Request $r){return view('attendance.index',['today'=>$r->user()->attendances()->whereDate('date',today())->first(),'settings'=>AttendanceSetting::current(),'schoolDay'=>app(\App\Services\CalendarService::class)->isSchoolDay(today())]);}
 public function store(Request $r,AttendanceService $service){$data=$r->validate(['type'=>'required|in:in,out']);$service->record($r->user(),$data['type'],'manual',$r->user());return back()->with('success','Absensi '.($data['type']==='in'?'masuk':'pulang').' berhasil disimpan.');}
 public function scanner(){return view('scanner.index');}
 public function scan(Request $r,QRCodeService $qr,AttendanceService $service){$data=$r->validate(['qr'=>'required|string|max:100','type'=>'required|in:in,out']);$row=$service->record($qr->resolve($data['qr']),$data['type'],'qr',$r->user());return response()->json(['message'=>'ABSEN BERHASIL','name'=>$row->user->name,'identity'=>$row->user->student?->schoolClass->name??$row->user->teacher?->position,'date'=>$row->date->format('d/m/Y'),'time'=>($data['type']==='in'?$row->check_in_at:$row->check_out_at)->format('H:i:s'),'type'=>$data['type']==='in'?'Masuk':'Pulang','status'=>$row->status]);}
 public function history(Request $r){
  $r->validate(['date'=>'nullable|date_format:Y-m-d','status'=>'nullable|in:HADIR,TERLAMBAT,SAKIT,IZIN,ALFA','q'=>'nullable|string|max:100','class_id'=>'nullable|integer']);
  $rows=Attendance::with('user.student','user.teacher','schoolClass')->when(!$r->user()->isAdmin(),fn($q)=>$q->where('user_id',$r->user()->id))->when($r->date,fn($q)=>$q->whereDate('date',$r->date))->when($r->status,fn($q)=>$q->where('status',$r->status))->when($r->class_id && $r->user()->isAdmin(),fn($q)=>$q->where('school_class_id',$r->class_id))->when($r->q,fn($q)=>$q->whereHas('user',fn($q)=>$q->where('name','like','%'.$r->q.'%')))->orderByDesc('date')->latest('updated_at')->paginate(15)->withQueryString();
  return view('attendance.history',['rows'=>$rows,'classes'=>$r->user()->isAdmin()?SchoolClass::all():collect()]);
 }
}
