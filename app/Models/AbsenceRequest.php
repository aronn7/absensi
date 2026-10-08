<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class AbsenceRequest extends Model {
 use HasFactory;
 protected $fillable=['user_id','school_class_id','date','type','purpose','reason','notes','evidence_path','status','approved_by','approved_at','review_notes'];
 protected function casts(): array {return ['date'=>'date','approved_at'=>'datetime'];}
 public function user(){return $this->belongsTo(User::class);}
 public function schoolClass(){return $this->belongsTo(SchoolClass::class);}
 public function reviewer(){return $this->belongsTo(User::class,'approved_by');}
}
