<?php
namespace App\Http\Controllers;
use App\Services\DashboardService;
use App\Models\{User,SchoolClass,AcademicCalendar,Attendance,AbsenceRequest};
use Illuminate\Http\Request;
class DashboardController extends Controller {
 public function __invoke(Request $r,DashboardService $service){
  $admin=$r->user()->isAdmin();$data=$service->data($admin?null:$r->user());
  $data['totals']=['students'=>User::where('role','student')->where('active',true)->count(),'teachers'=>User::where('role','teacher')->where('active',true)->count(),'classes'=>SchoolClass::where('active',true)->count()];
  $data['today']=Attendance::where('user_id',$r->user()->id)->whereDate('date',today())->first();
  $data['events']=AcademicCalendar::whereDate('ends_on','>=',today())->orderBy('starts_on')->limit(3)->get();
  $data['pending']=$admin?AbsenceRequest::where('status','PENDING')->count():$r->user()->absenceRequests()->where('status','PENDING')->count();
  return view($admin?'admin.dashboard':$r->user()->role.'.dashboard',$data);
 }
}
