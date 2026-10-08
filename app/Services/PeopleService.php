<?php
namespace App\Services;
use App\Models\{Student,Teacher,User,ClassStudent,AuditLog};
use Illuminate\Support\Facades\DB;
class PeopleService {
 public function saveStudent(array $data,?Student $student=null): Student {
  return DB::transaction(function()use($data,$student){
   $user=$this->saveUser($data,'student',$student?->user);
   $oldClass=$student?->school_class_id;
   $student??=new Student();$student->fill(collect($data)->only(['nisn','gender','birth_date','address','school_class_id'])->all());$student->user_id=$user->id;$student->enrolled_at??=today();$student->save();
   if((int)$oldClass!==(int)$student->school_class_id){ClassStudent::where('student_id',$student->id)->whereNull('left_at')->update(['left_at'=>today()]);$student->classes()->attach($student->school_class_id,['joined_at'=>today()]);}
   $student->parentGuardian()->updateOrCreate(['student_id'=>$student->id],['name'=>$data['guardian_name'],'relationship'=>$data['guardian_relationship'],'phone'=>$data['guardian_phone'],'email'=>$data['guardian_email']??null]);
   \App\Models\MonthlyReport::whereIn('school_class_id',array_filter([$oldClass,$student->school_class_id]))->update(['generated_at'=>null,'status'=>'PENDING','approved_at'=>null,'approved_by'=>null]);
   app(QRCodeService::class)->issue($user);AuditLog::record('student.save',$student);return $student;
  });
 }
 public function saveTeacher(array $data,?Teacher $teacher=null): Teacher {
  return DB::transaction(function()use($data,$teacher){
   $user=$this->saveUser([...$data,'login_id'=>$data['employee_id']],'teacher',$teacher?->user);
   $teacher??=new Teacher();$teacher->fill(collect($data)->only(['employee_id','position','address'])->all());$teacher->user_id=$user->id;$teacher->save();app(QRCodeService::class)->issue($user);AuditLog::record('teacher.save',$teacher);return $teacher;
  });
 }
 private function saveUser(array $data,string $role,?User $user): User {
  $user??=new User(['role'=>$role]);$user->fill(collect($data)->only(['name','email','phone','active','login_id'])->all());
  if(!empty($data['password']))$user->password=$data['password'];
  if(!empty($data['photo']))$user->photo_path=app(UploadService::class)->image($data['photo'],'profiles','public');
  $user->save();return $user;
 }
}
