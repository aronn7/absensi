<?php
namespace App\Services;
use Carbon\CarbonInterface;
class LateDetectionService {
 public function calculate(CarbonInterface $time,string $lateAfter): array {
  $limit=$time->copy()->setTimeFromTimeString($lateAfter);
  $late=$time->greaterThan($limit);
  return ['status'=>$late?'TERLAMBAT':'HADIR','late_minutes'=>$late?(int)ceil($limit->diffInSeconds($time)/60):0];
 }
}
