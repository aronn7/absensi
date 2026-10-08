<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolClassRequest;
use App\Models\{SchoolClass,Teacher,AuditLog};
use Illuminate\Http\Request;
class SchoolClassController extends Controller {
 public function index(Request $r){$classes=SchoolClass::with('homeroomTeacher.user')->withCount('students')->when($r->q,fn($q)=>$q->where('name','like','%'.$r->q.'%'))->when($r->filled('active'),fn($q)=>$q->where('active',$r->active))->paginate(12)->withQueryString();return view('admin.classes.index',compact('classes'));}
 public function create(){return view('admin.classes.form',['schoolClass'=>null,'teachers'=>Teacher::with('user')->whereHas('user',fn($q)=>$q->where('active',true))->get()]);}
 public function store(SchoolClassRequest $r){$class=SchoolClass::create($r->validated());AuditLog::record('class.create',$class);return redirect()->route('admin.classes.index')->with('success','Kelas dibuat.');}
 public function edit(SchoolClass $schoolClass){return view('admin.classes.form',['schoolClass'=>$schoolClass,'teachers'=>Teacher::with('user')->whereHas('user',fn($q)=>$q->where('active',true))->get()]);}
 public function update(SchoolClassRequest $r,SchoolClass $schoolClass){$data=$r->validated();if(empty($data['pin']))unset($data['pin']);$schoolClass->update($data);AuditLog::record('class.update',$schoolClass);return redirect()->route('admin.classes.index')->with('success','Kelas diperbarui.');}
 public function destroy(SchoolClass $schoolClass){$schoolClass->update(['active'=>false]);AuditLog::record('class.deactivate',$schoolClass);return back()->with('success','Kelas dinonaktifkan.');}
}
