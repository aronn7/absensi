<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class AuditLog extends Model {
 use HasFactory;
 protected $fillable=['user_id','action','subject_type','subject_id','metadata'];
 protected function casts(): array {return ['metadata'=>'array'];}
 public static function record(string $action, \Illuminate\Database\Eloquent\Model $subject, array $metadata=[]): void {static::create(['user_id'=>auth()->id(),'action'=>$action,'subject_type'=>get_class($subject),'subject_id'=>$subject->getKey(),'metadata'=>$metadata]);}
}
