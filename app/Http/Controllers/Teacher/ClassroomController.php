<?php
namespace App\Http\Controllers\Teacher;
use App\Http\Controllers\Controller;
use App\Models\{SchoolClass,Attendance,AbsenceRequest};
use App\Services\{ClassAccessService,DashboardService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class ClassroomController extends Controller {
 public function index(Request $r){return view('teacher.classes',['classes'=>$r->user()->teacher->homeroomClasses()->where('active',true)->withCount('students')->get()]);}
 public function unlock(Request $r,SchoolClass $schoolClass,ClassAccessService $access){abort_unless($access->owns($r->user(),$schoolClass),403);return view('teacher.unlock',compact('schoolClass'));}
 public function verify(Request $r,SchoolClass $schoolClass,ClassAccessService $access){abort_unless($access->owns($r->user(),$schoolClass),403);$r->validate(['pin'=>'required|string|max:12']);if(!Hash::check($r->pin,$schoolClass->pin))throw ValidationException::withMessages(['pin'=>'PIN kelas salah. Akses ditolak.']);$r->session()->put('class_unlock.'.$schoolClass->id,['expires'=>time()+7200,'version'=>hash('sha256',$schoolClass->pin)]);return redirect()->route('teacher.classes.show',$schoolClass);}
 public function show(Request $r,SchoolClass $schoolClass,DashboardService $service){
  $r->validate(['date'=>'nullable|date_format:Y-m-d','status'=>'nullable|in:PENDING,DISETUJUI,DITOLAK','q'=>'nullable|string|max:100']);
  $data=$service->data(null,$schoolClass);$data['schoolClass']=$schoolClass;
  $data['students']=$schoolClass->students()->with('user')->when($r->q,fn($q)=>$q->whereHas('user',fn($u)=>$u->where('name','like','%'.$r->q.'%')))->paginate(10,['*'],'students_page')->withQueryString();
  $data['attendances']=$schoolClass->attendances()->with('user')->when($r->date,fn($q)=>$q->whereDate('date',$r->date))->orderByDesc('date')->paginate(10,['*'],'attendance_page')->withQueryString();
  $data['requests']=AbsenceRequest::with('user')->where('school_class_id',$schoolClass->id)->when($r->status,fn($q)=>$q->where('status',$r->status))->latest()->paginate(10,['*'],'requests_page')->withQueryString();
  return view('teacher.classroom',$data);
 }
}
