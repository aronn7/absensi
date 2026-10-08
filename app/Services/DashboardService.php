<?php
namespace App\Services;
use App\Models\{Attendance,User,SchoolClass};
class DashboardService {
 public const STATUSES=['HADIR','TERLAMBAT','SAKIT','IZIN','ALFA'];
 public function data(?User $user=null,?SchoolClass $class=null): array {
  $q=Attendance::query()->when($user,fn($q)=>$q->where('user_id',$user->id))->when($class,fn($q)=>$q->where('school_class_id',$class->id));
  $counts=(clone $q)->whereDate('date',today())->get()->countBy('status');$stats=[];foreach(self::STATUSES as $s)$stats[$s]=$counts[$s]??0;
  $month=(clone $q)->whereBetween('date',[now()->startOfMonth()->toDateString(),today()->toDateString()])->get();$monthStats=[];$grouped=$month->countBy('status');foreach(self::STATUSES as $s)$monthStats[$s]=$grouped[$s]??0;
  $weeklyRows=(clone $q)->whereBetween('date',[today()->subDays(6)->toDateString(),today()->toDateString()])->get();
  $weekly=['labels'=>[],'hadir'=>[],'terlambat'=>[]];for($i=6;$i>=0;$i--){$d=today()->subDays($i);$day=$weeklyRows->filter(fn($r)=>$r->date->isSameDay($d));$weekly['labels'][]=$d->translatedFormat('D');$weekly['hadir'][]=$day->where('status','HADIR')->count();$weekly['terlambat'][]=$day->where('status','TERLAMBAT')->count();}
  $monthly=['labels'=>[],'values'=>[]];for($i=1;$i<=today()->day;$i++){$monthly['labels'][]=$i;$monthly['values'][]=$month->filter(fn($a)=>$a->date->day===$i && in_array($a->status,['HADIR','TERLAMBAT']))->count();}
  $recent=(clone $q)->with('user.student.schoolClass','user.teacher')->whereDate('date',today())->latest('updated_at')->limit(6)->get();
  $total=$month->count();$percent=$total?round(($monthStats['HADIR']+$monthStats['TERLAMBAT'])/$total*100,1):0;
  return compact('stats','monthStats','weekly','monthly','recent','percent');
 }
}
