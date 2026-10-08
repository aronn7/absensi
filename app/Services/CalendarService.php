<?php
namespace App\Services;
use App\Models\{AcademicCalendar,AttendanceSetting};
use Carbon\CarbonInterface;
class CalendarService {
 public function isSchoolDay(CarbonInterface $date): bool {
  if($date->isSunday())return false;
  $events=AcademicCalendar::whereDate('starts_on','<=',$date->toDateString())->whereDate('ends_on','>=',$date->toDateString())->get();
  if($events->contains('attendance_active',false))return false;
  if($events->contains('attendance_active',true))return true;
  return in_array($date->dayOfWeekIso,AttendanceSetting::current()->school_days);
 }
}
