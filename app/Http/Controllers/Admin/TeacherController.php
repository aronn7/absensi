<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherRequest;
use App\Models\{Teacher,AuditLog};
use App\Services\PeopleService;
use Illuminate\Http\Request;
class TeacherController extends Controller {
 public function index(Request $r){$rows=Teacher::with('user')->when($r->q,fn($q)=>$q->where(fn($q)=>$q->where('employee_id','like','%'.$r->q.'%')->orWhereHas('user',fn($u)=>$u->where('name','like','%'.$r->q.'%'))))->when($r->filled('active'),fn($q)=>$q->whereHas('user',fn($u)=>$u->where('active',$r->active)))->latest()->paginate(12)->withQueryString();return view('admin.people.index',['rows'=>$rows,'kind'=>'teachers']);}
 public function create(){return view('admin.people.form',['person'=>null,'kind'=>'teachers']);}
 public function store(TeacherRequest $r,PeopleService $service){$teacher=$service->saveTeacher($r->validated());return redirect()->route('admin.teachers.show',$teacher)->with('success','Guru, akun, QR, dan kartu berhasil dibuat.');}
 public function show(Teacher $teacher){return view('admin.people.show',['person'=>$teacher->load('user.card','homeroomClasses'),'kind'=>'teachers']);}
 public function edit(Teacher $teacher){return view('admin.people.form',['person'=>$teacher->load('user'),'kind'=>'teachers']);}
 public function update(TeacherRequest $r,Teacher $teacher,PeopleService $service){$service->saveTeacher($r->validated(),$teacher);return redirect()->route('admin.teachers.show',$teacher)->with('success','Data guru diperbarui.');}
 public function destroy(Teacher $teacher){$teacher->user->update(['active'=>false]);AuditLog::record('teacher.deactivate',$teacher);return back()->with('success','Guru dinonaktifkan.');}
}
