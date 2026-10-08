<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class Teacher extends Model {
 use HasFactory;
 protected $fillable=['user_id','employee_id','position','address'];
 public function user(){return $this->belongsTo(User::class);}
 public function homeroomClasses(){return $this->hasMany(SchoolClass::class,'homeroom_teacher_id');}
 public function attendances(){return $this->hasMany(Attendance::class,'user_id','user_id');}
 public function card(){return $this->hasOne(Card::class,'user_id','user_id');}
}
