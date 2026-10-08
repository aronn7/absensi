<?php
namespace App\Policies;
use App\Models\{AbsenceRequest,User};
use App\Services\ClassAccessService;
class AbsenceRequestPolicy {
 public function view(User $user,AbsenceRequest $absence): bool {return $user->isAdmin() || $user->id===$absence->user_id || app(ClassAccessService::class)->unlocked($user,$absence->schoolClass);}
 public function review(User $user,AbsenceRequest $absence): bool {return $user->isAdmin() || app(ClassAccessService::class)->unlocked($user,$absence->schoolClass);}
}
