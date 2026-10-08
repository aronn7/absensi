<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class MonthlyReport extends Model {
 use HasFactory;
 protected $fillable=['school_class_id','year','month','status','file_path','generated_at','approved_by','approved_at'];
 protected function casts(): array {return ['generated_at'=>'datetime','approved_at'=>'datetime'];}
 public function schoolClass(){return $this->belongsTo(SchoolClass::class);}
 public function reviewer(){return $this->belongsTo(User::class,'approved_by');}
}
