<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
 use HasFactory, Notifiable;
 protected $attributes=['active'=>true,'role'=>'student'];
 protected $fillable=['name','email','login_id','password','role','active','phone','photo_path'];
 protected $hidden=['password','remember_token'];
 protected function casts(): array {return ['password'=>'hashed','active'=>'boolean','email_verified_at'=>'datetime'];}
 public function student(){return $this->hasOne(Student::class);}
 public function teacher(){return $this->hasOne(Teacher::class);}
 public function card(){return $this->hasOne(Card::class);}
 public function attendances(){return $this->hasMany(Attendance::class);}
 public function absenceRequests(){return $this->hasMany(AbsenceRequest::class);}
 public function isAdmin(): bool {return $this->role==='admin';}
 public function homeRoute(): string {return $this->role.'.dashboard';}
}
