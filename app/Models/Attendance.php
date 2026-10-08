<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class Attendance extends Model {
 use HasFactory;
 protected $fillable=['user_id','school_class_id','date','check_in_at','check_out_at','status','late_minutes','method','created_by'];
 protected function casts(): array {return ['date'=>'date','check_in_at'=>'datetime','check_out_at'=>'datetime'];}
 public function user(){return $this->belongsTo(User::class);}
 public function schoolClass(){return $this->belongsTo(SchoolClass::class);}
}
