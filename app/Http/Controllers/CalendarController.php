<?php
namespace App\Http\Controllers;
use App\Models\{AcademicCalendar,MonthlyReport,AuditLog};
use App\Http\Requests\CalendarRequest;
use Illuminate\Http\Request;
class CalendarController extends Controller {
 public function index(Request $r){$r->validate(['month'=>'nullable|date_format:Y-m']);$month=\Carbon\Carbon::createFromFormat('!Y-m',$r->input('month',today()->format('Y-m')));$events=AcademicCalendar::whereDate('starts_on','<=',$month->copy()->endOfMonth())->whereDate('ends_on','>=',$month)->orderBy('starts_on')->get();return view('calendar.index',compact('events','month'));}
 public function create(){return view('calendar.form',['event'=>null]);}
 public function store(CalendarRequest $r){$event=AcademicCalendar::create([...$r->validated(),'created_by'=>$r->user()->id]);$this->changed($event);return redirect()->route('calendar.index')->with('success','Agenda ditambahkan.');}
 public function edit(AcademicCalendar $event){return view('calendar.form',compact('event'));}
 public function update(CalendarRequest $r,AcademicCalendar $event){$event->update($r->validated());$this->changed($event);return redirect()->route('calendar.index')->with('success','Agenda diperbarui.');}
 public function destroy(AcademicCalendar $event){$event->delete();$this->changed($event);return back()->with('success','Agenda dihapus.');}
 private function changed(AcademicCalendar $event): void {app(\App\Services\CalendarReconciliationService::class)->reconcile();AuditLog::record('calendar.change',$event);}
}
