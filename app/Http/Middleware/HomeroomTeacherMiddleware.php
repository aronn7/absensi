<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use App\Models\SchoolClass;
use App\Services\ClassAccessService;
class HomeroomTeacherMiddleware {
 public function handle(Request $request,Closure $next){
  $class=$request->route('schoolClass');
  abort_unless($class instanceof SchoolClass && app(ClassAccessService::class)->owns($request->user(),$class),403);
  if(!app(ClassAccessService::class)->unlocked($request->user(),$class))return redirect()->route('teacher.classes.unlock',$class);
  return $next($request);
 }
}
