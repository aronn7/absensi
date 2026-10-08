<?php
namespace App\Http\Controllers\Student;
use App\Http\Controllers\Controller;
use App\Http\Requests\AbsenceStoreRequest;
use App\Models\AbsenceRequest;
use App\Services\{AbsenceService,UploadService};
use Illuminate\Support\Facades\{Gate,Storage};
use Illuminate\Http\Request;
class AbsenceController extends Controller {
 public function index(Request $r){$rows=$r->user()->absenceRequests()->when($r->status,fn($q)=>$q->where('status',$r->status))->latest()->paginate(10)->withQueryString();return view('student.absences',compact('rows'));}
 public function store(AbsenceStoreRequest $r,AbsenceService $service){$data=$r->safe()->except('evidence');$file=null;if($r->hasFile('evidence'))$data['evidence_path']=$file=app(UploadService::class)->image($r->file('evidence'),'evidence');try{$service->submit($r->user(),$data);}catch(\Throwable $e){if($file)Storage::disk('local')->delete($file);throw $e;}return back()->with('success','Permohonan dikirim. Menunggu pemeriksaan wali kelas.');}
 public function evidence(AbsenceRequest $absence){Gate::authorize('view',$absence);abort_unless($absence->evidence_path,404);return Storage::disk('local')->response($absence->evidence_path);}
 public function review(Request $r,AbsenceRequest $absence,AbsenceService $service){Gate::authorize('review',$absence);$data=$r->validate(['decision'=>'required|in:DISETUJUI,DITOLAK','review_notes'=>'nullable|required_if:decision,DITOLAK|string|max:1000']);$service->review($absence,$r->user(),$data['decision'],$data['review_notes']??null);return back()->with('success','Permohonan telah diperiksa.');}
}
