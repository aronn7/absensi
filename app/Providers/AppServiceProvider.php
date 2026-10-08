<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{Gate,View};
use App\Models\{User,AttendanceSetting};
class AppServiceProvider extends ServiceProvider {
 public function register(): void {}
 public function boot(): void {
  \Carbon\Carbon::setLocale('id');
  Gate::define('reports',fn(User $user)=>$user->isAdmin() || ($user->role==='teacher' && $user->teacher?->homeroomClasses()->where('active',true)->exists()));
  View::composer(['layouts.app','layouts.guest'],function($view){$view->with('schoolSettings',AttendanceSetting::current());});
 }
}
