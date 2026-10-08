<?php
namespace App\Http\Controllers;
use App\Models\{SchoolClass,MonthlyReport,AuditLog};
use App\Services\{ClassAccessService,ReportService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class ReportController extends Controller {
 public function index(Request $r){
  $r->validate(['month'=>'nullable|integer|between:1,12','year'=>'nullable|integer|between:2000,2100','class_id'=>'nullable|integer']);
  $classes=$r->user()->isAdmin()?SchoolClass::all():$r->user()->teacher->homeroomClasses;
  $reports=MonthlyReport::with('schoolClass','reviewer')->whereIn('school_class_id',$classes->pluck('id'))->when($r->class_id,fn($q)=>$q->where('school_class_id',$r->class_id))->when($r->month,fn($q)=>$q->where('month',$r->month))->when($r->year,fn($q)=>$q->where('year',$r->year))->latest()->paginate(12)->withQueryString();
  return view('reports.index',compact('reports','classes'));
 }
 public function generate(Request $r,ReportService $service){$data=$r->validate(['school_class_id'=>'required|exists:school_classes,id','month'=>'required|integer|between:1,12','year'=>'required|integer|between:2000,2100']);$class=SchoolClass::findOrFail($data['school_class_id']);app(ClassAccessService::class)->authorize($r->user(),$class);$report=$service->generate($class,(int)$data['year'],(int)$data['month']);return redirect()->route('reports.show',$report);}
 public function show(Request $r,MonthlyReport $report,ReportService $service){app(ClassAccessService::class)->authorize($r->user(),$report->schoolClass);$data=$service->data($report->schoolClass,$report->year,$report->month);return view('reports.show',['report'=>$report,...$data]);}
 public function download(Request $r,MonthlyReport $report,ReportService $service){app(ClassAccessService::class)->authorize($r->user(),$report->schoolClass);$report=$service->generate($report->schoolClass,$report->year,$report->month);return Storage::disk('local')->download($report->file_path,'Absensi-'.$report->schoolClass->id.'-'.$report->year.'-'.$report->month.'.xlsx');}
 public function approve(Request $r,MonthlyReport $report,ReportService $service){abort_unless(app(ClassAccessService::class)->unlocked($r->user(),$report->schoolClass),403);\Illuminate\Support\Facades\DB::transaction(function()use($r,$report,$service){SchoolClass::lockForUpdate()->findOrFail($report->school_class_id);$report=$service->generate($report->schoolClass,$report->year,$report->month);$report->update(['status'=>'DISETUJUI','approved_by'=>$r->user()->id,'approved_at'=>now()]);AuditLog::record('report.approve',$report);},3);return back()->with('success','Laporan disetujui dan tersedia untuk admin.');}
}
