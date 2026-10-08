<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class ParentGuardian extends Model {
 use HasFactory, Notifiable;
 protected $fillable=['student_id','name','relationship','phone','email'];
 public function student(){return $this->belongsTo(Student::class);}
}
