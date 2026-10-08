<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Models\{Student,SchoolClass,AuditLog};
use App\Services\PeopleService;
use Illuminate\Http\Request;
class StudentController extends Controller {
 public function index(Request $r){$rows=Student::with('user','schoolClass')->when($r->q,fn($q)=>$q->where(fn($q)=>$q->where('nisn','like','%'.$r->q.'%')->orWhereHas('user',fn($u)=>$u->where('name','like','%'.$r->q.'%'))))->when($r->class_id,fn($q)=>$q->where('school_class_id',$r->class_id))->when($r->filled('active'),fn($q)=>$q->whereHas('user',fn($u)=>$u->where('active',$r->active)))->latest()->paginate(12)->withQueryString();return view('admin.people.index',['rows'=>$rows,'kind'=>'students','classes'=>SchoolClass::all()]);}
 public function create(){return view('admin.people.form',['person'=>null,'kind'=>'students','classes'=>SchoolClass::where('active',true)->get()]);}
 public function store(StudentRequest $r,PeopleService $service){$student=$service->saveStudent($r->validated());return redirect()->route('admin.students.show',$student)->with('success','Murid, akun, QR, dan kartu berhasil dibuat.');}
 public function show(Student $student){return view('admin.people.show',['person'=>$student->load('user.card','schoolClass','parentGuardian'),'kind'=>'students']);}
 public function edit(Student $student){return view('admin.people.form',['person'=>$student->load('user','parentGuardian'),'kind'=>'students','classes'=>SchoolClass::where('active',true)->get()]);}
 public function update(StudentRequest $r,Student $student,PeopleService $service){$service->saveStudent($r->validated(),$student);return redirect()->route('admin.students.show',$student)->with('success','Data murid diperbarui.');}
 public function destroy(Student $student){$student->user->update(['active'=>false]);AuditLog::record('student.deactivate',$student);return back()->with('success','Murid dinonaktifkan. Riwayat tetap tersimpan.');}
}
