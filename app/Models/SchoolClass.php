<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class SchoolClass extends Model {
 use HasFactory;
 protected $fillable=['name','grade','major','academic_year','homeroom_teacher_id','pin','active'];
 protected $hidden=['pin'];
 protected function casts(): array {return ['pin'=>'hashed','active'=>'boolean'];}
 public function homeroomTeacher(){return $this->belongsTo(Teacher::class,'homeroom_teacher_id');}
 public function students(){return $this->hasMany(Student::class);}
 public function enrollments(){return $this->hasMany(ClassStudent::class);}
 public function attendances(){return $this->hasMany(Attendance::class);}
 public function monthlyReports(){return $this->hasMany(MonthlyReport::class);}
}
