<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class Student extends Model {
 use HasFactory;
 protected $fillable=['user_id','nisn','gender','birth_date','address','school_class_id','enrolled_at'];
 protected function casts(): array {return ['birth_date'=>'date','enrolled_at'=>'date'];}
 public function user(){return $this->belongsTo(User::class);}
 public function schoolClass(){return $this->belongsTo(SchoolClass::class);}
 public function parentGuardian(){return $this->hasOne(ParentGuardian::class);}
 public function classes(){return $this->belongsToMany(SchoolClass::class,'class_students')->withPivot('joined_at','left_at')->withTimestamps();}
 public function attendances(){return $this->hasMany(Attendance::class,'user_id','user_id');}
 public function absenceRequests(){return $this->hasMany(AbsenceRequest::class,'user_id','user_id');}
 public function card(){return $this->hasOne(Card::class,'user_id','user_id');}
}
