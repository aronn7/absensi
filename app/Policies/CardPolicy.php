<?php
namespace App\Policies;
use App\Models\{Card,User};
class CardPolicy {public function view(User $user,Card $card): bool {return $user->isAdmin() || $user->id===$card->user_id;}}
