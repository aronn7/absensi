<?php
namespace App\Services;
use App\Models\{User,SchoolClass};
use App\Notifications\SchoolNotification;
class NotificationService {
 // Extension point: add an official email/WhatsApp channel to SchoolNotification::via.
 // No external messages are sent by this internal notification center.
 public function attendance(User $user,string $status,string $message,?SchoolClass $class=null): void {
  $data=['title'=>$status,'message'=>$user->name.' — '.$message,'user_id'=>$user->id,'school_class_id'=>$class?->id,'date'=>now()->toDateString()];
  $user->notify(new SchoolNotification($data));
  $user->student?->parentGuardian?->notify(new SchoolNotification($data));
  if(in_array($status,['TERLAMBAT','ALFA','SAKIT','IZIN']))$class?->homeroomTeacher?->user?->notify(new SchoolNotification($data));
 }
 public function request(User $user,SchoolClass $class,string $type): void {
  $notice=new SchoolNotification(['title'=>'Permohonan '.$type,'message'=>$user->name.' mengajukan '.$type.'.','school_class_id'=>$class->id,'user_id'=>$user->id]);
  if($teacher=$class->homeroomTeacher?->user)$teacher->notify($notice);
  else User::where('role','admin')->where('active',true)->each(fn($admin)=>$admin->notify($notice));
 }
}
