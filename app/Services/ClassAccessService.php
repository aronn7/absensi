<?php
namespace App\Services;
use App\Models\{SchoolClass,User};
class ClassAccessService {
 public function owns(User $user, SchoolClass $class): bool {return $user->active && $user->role==='teacher' && $class->active && $user->teacher?->id===$class->homeroom_teacher_id;}
 public function unlocked(User $user, SchoolClass $class): bool {
  $key=session('class_unlock.'.$class->id);
  return $this->owns($user,$class) && is_array($key) && ($key['expires']??0)>time() && hash_equals(hash('sha256',$class->pin),$key['version']??'');
 }
 public function authorize(User $user, SchoolClass $class): void {abort_unless($user->isAdmin() || $this->unlocked($user,$class),403);}
}
