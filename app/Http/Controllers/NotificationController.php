<?php
namespace App\Http\Controllers;
use App\Models\ParentGuardian;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
class NotificationController extends Controller {
 public function index(Request $r){
  $rows=$r->user()->notifications()->paginate(12);$guardianRows=collect();
  if($r->user()->isAdmin())$ids=ParentGuardian::pluck('id');
  elseif($r->user()->role==='teacher'){$classes=$r->user()->teacher->homeroomClasses->filter(fn($c)=>app(\App\Services\ClassAccessService::class)->unlocked($r->user(),$c))->pluck('id');$ids=ParentGuardian::whereHas('student',fn($q)=>$q->whereIn('school_class_id',$classes))->pluck('id');}
  else $ids=collect();
  if($ids->isNotEmpty())$guardianRows=DatabaseNotification::with('notifiable')->where('notifiable_type',ParentGuardian::class)->whereIn('notifiable_id',$ids)->when(!$r->user()->isAdmin(),fn($q)=>$q->whereIn('data->school_class_id',$classes))->latest()->paginate(12,['*'],'guardian_page');
  return view('notifications.index',compact('rows','guardianRows'));
 }
 public function read(Request $r,string $id){$r->user()->notifications()->where('id',$id)->firstOrFail()->markAsRead();return back()->with('success','Notifikasi ditandai dibaca.');}
 public function readAll(Request $r){$r->user()->unreadNotifications->markAsRead();return back()->with('success','Semua notifikasi ditandai dibaca.');}
}
