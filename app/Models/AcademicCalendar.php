<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class AcademicCalendar extends Model {
 use HasFactory;
 protected $fillable=['title','starts_on','ends_on','type','description','attendance_active','created_by'];
 protected function casts(): array {return ['starts_on'=>'date','ends_on'=>'date','attendance_active'=>'boolean'];}
 
}
