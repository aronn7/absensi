<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class ClassStudent extends Model {
 use HasFactory;
 protected $fillable=['school_class_id','student_id','joined_at','left_at'];
 protected $table='class_students';
 protected function casts(): array {return ['joined_at'=>'date','left_at'=>'date'];}
 public function student(){return $this->belongsTo(Student::class);}
 public function schoolClass(){return $this->belongsTo(SchoolClass::class);}
}
