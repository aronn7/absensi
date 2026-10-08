<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Hash,Storage,DB};
use Carbon\Carbon;
use App\Models\{User,Student,Teacher,SchoolClass,Attendance,AttendanceSetting,AcademicCalendar,AbsenceRequest,Card,MonthlyReport,ClassStudent};
use App\Services\{PeopleService,AttendanceService,QRCodeService,ReportService,CalendarService};
class SchoolFeatureTest extends TestCase {
 use RefreshDatabase;
 private User $admin;
 private Teacher $homeroom;
 private Teacher $regular;
 private SchoolClass $class;
 private Student $student;
 protected function setUp(): void {
  parent::setUp();$this->withoutVite();Carbon::setTestNow(Carbon::parse('2026-10-07 06:50:00','Asia/Jakarta'));
  if(DB::getDriverName()==='mysql')$this->assertStringEndsWith('_test',DB::connection()->getDatabaseName());
  Storage::fake('local');Storage::fake('public');AttendanceSetting::current();
  $this->admin=User::factory()->create(['role'=>'admin','login_id'=>'admin','password'=>'Sekolah123!']);
  $this->homeroom=app(PeopleService::class)->saveTeacher($this->teacherData());
  $this->regular=app(PeopleService::class)->saveTeacher($this->teacherData('G002'));
  $this->class=SchoolClass::create(['name'=>'XI RPL 1','grade'=>'XI','major'=>'RPL','academic_year'=>'2026/2027','homeroom_teacher_id'=>$this->homeroom->id,'pin'=>'246810','active'=>true]);
  $this->student=app(PeopleService::class)->saveStudent($this->studentData());
 }
 protected function tearDown(): void {Carbon::setTestNow();parent::tearDown();}
 private function teacherData(string $id='G001'): array {return ['name'=>'Guru '.$id,'employee_id'=>$id,'email'=>$id.'@test.example','position'=>'Guru','active'=>1,'password'=>'Sekolah123!'];}
 private function studentData(string $nisn='0000001001'): array {return ['name'=>'Murid '.$nisn,'nisn'=>$nisn,'email'=>$nisn.'@test.example','password'=>'Sekolah123!','gender'=>'L','active'=>1,'school_class_id'=>$this->class->id,'guardian_name'=>'Orang Tua','guardian_relationship'=>'Ayah','guardian_phone'=>'081234567890'];}
 private function unlock(): array {return ['class_unlock'=>[$this->class->id=>['expires'=>time()+7200,'version'=>hash('sha256',$this->class->pin)]]];}
 private function scan(string $type='in',?string $qr=null){return $this->actingAs($this->admin)->postJson('/scanner',['qr'=>$qr??'SAD:'.$this->student->user->card->qr_token,'type'=>$type]);}
 public function test_each_role_can_log_in_with_its_credentials(): void {
  foreach([['admin','admin',null,$this->admin],['teacher','G001',null,$this->homeroom->user],['student',$this->student->user->email,$this->student->nisn,$this->student->user]] as [$role,$identity,$nisn,$user]){
   $this->post('/login/'.$role,['identity'=>$identity,'nisn'=>$nisn,'password'=>'Sekolah123!'])->assertRedirect('/'.$role.'/dashboard');$this->assertAuthenticatedAs($user);$this->post('/logout')->assertRedirect('/');$this->assertGuest();
  }
 }
 public function test_invalid_nisn_and_password_are_rejected_and_login_is_limited(): void {
  for($i=0;$i<5;$i++)$this->post('/login/student',['identity'=>$this->student->user->email,'nisn'=>'9999999999','password'=>'wrong'])->assertSessionHasErrors('identity');
  $this->post('/login/student',['identity'=>$this->student->user->email,'nisn'=>$this->student->nisn,'password'=>'Sekolah123!'])->assertSessionHasErrors('identity');$this->assertGuest();
 }
 public function test_cross_role_urls_and_inactive_users_are_blocked(): void {
  $this->actingAs($this->student->user)->get('/admin/students')->assertForbidden();$this->get('/teacher/dashboard')->assertForbidden();$this->get('/scanner')->assertForbidden();$this->get('/reports')->assertForbidden();
  $this->actingAs($this->regular->user)->get('/admin/dashboard')->assertForbidden();
  $this->student->user->update(['active'=>false]);$this->actingAs($this->student->user)->get('/student/dashboard')->assertRedirect('/');
 }
 public function test_admin_can_create_edit_and_deactivate_student_with_card_and_guardian(): void {
  $this->actingAs($this->admin)->post('/admin/students',$this->studentData('0000001002'))->assertSessionHasNoErrors()->assertRedirect();
  $s=Student::where('nisn','0000001002')->firstOrFail();$this->assertNotNull($s->parentGuardian);$this->assertNotNull($s->user->card);$this->assertTrue(Hash::check('Sekolah123!',$s->user->password));
  $this->put('/admin/students/'.$s->id,[...$this->studentData('0000001002'),'name'=>'Nama Diperbarui','password'=>''])->assertSessionHasNoErrors();
  $this->assertEquals('Nama Diperbarui',$s->user->fresh()->name);$this->delete('/admin/students/'.$s->id)->assertRedirect();$this->assertFalse($s->user->fresh()->active);
 }
 public function test_admin_can_create_teacher_and_class_with_hashed_pin(): void {
  $this->actingAs($this->admin)->post('/admin/teachers',$this->teacherData('G003'))->assertSessionHasNoErrors()->assertRedirect();$teacher=Teacher::where('employee_id','G003')->firstOrFail();$this->assertNotNull($teacher->user->card);
  $this->post('/admin/classes',['name'=>'X RPL 1','grade'=>'X','major'=>'RPL','academic_year'=>'2026/2027','homeroom_teacher_id'=>$teacher->id,'pin'=>'123456','active'=>1])->assertSessionHasNoErrors()->assertRedirect();
  $this->assertTrue(Hash::check('123456',SchoolClass::where('name','X RPL 1')->firstOrFail()->pin));
 }
 public function test_unique_identifiers_and_bad_uploads_are_rejected(): void {
  $this->actingAs($this->admin)->post('/admin/students',$this->studentData())->assertSessionHasErrors(['nisn','email']);
  $this->post('/admin/students',[...$this->studentData('0000001002'),'photo'=>UploadedFile::fake()->create('shell.php',1,'application/x-php')])->assertSessionHasErrors('photo');
 }
 public function test_qr_tokens_are_unique_encrypted_and_not_identity_numbers(): void {
  $second=app(PeopleService::class)->saveStudent($this->studentData('0000001002'));
  $a=$this->student->user->card;$b=$second->user->card;$this->assertNotEquals($a->qr_token,$b->qr_token);$this->assertEquals(64,strlen($a->qr_token));$this->assertNotEquals($a->qr_token,$a->getRawOriginal('qr_token'));Storage::disk('local')->assertExists($a->qr_path);
 }
 public function test_valid_qr_scan_creates_attendance_and_parent_notification(): void {
  $this->scan()->assertOk()->assertJsonPath('status','HADIR');$this->assertDatabaseHas('attendances',['user_id'=>$this->student->user_id,'status'=>'HADIR','method'=>'qr']);$this->assertEquals(1,$this->student->parentGuardian->notifications()->count());
 }
 public function test_invalid_qr_and_inactive_accounts_are_rejected(): void {
  $this->scan('in','0000001001')->assertUnprocessable();$this->scan('in','SAD:'.str_repeat('a',64))->assertUnprocessable();$this->student->user->update(['active'=>false]);$this->scan()->assertUnprocessable();$this->assertDatabaseCount('attendances',0);
 }
 public function test_duplicate_check_in_is_rejected_without_changing_the_record(): void {
  $this->scan()->assertOk();$this->scan()->assertUnprocessable();$this->assertDatabaseCount('attendances',1);$this->assertEquals(1,$this->student->parentGuardian->notifications()->count());
 }
 public function test_late_minutes_are_eight_at_0723_and_zero_at_boundary(): void {
  Carbon::setTestNow('2026-10-07 07:23:00');$this->scan()->assertOk()->assertJsonPath('status','TERLAMBAT');$this->assertDatabaseHas('attendances',['late_minutes'=>8]);
  Carbon::setTestNow('2026-10-07 07:15:00');$row=app(AttendanceService::class)->record($this->regular->user,'in','manual',$this->regular->user);$this->assertSame('HADIR',$row->status);$this->assertSame(0,$row->late_minutes);
 }
 public function test_checkout_requires_checkin_and_correct_time_and_is_not_duplicated(): void {
  $this->scan('out')->assertUnprocessable();$this->scan()->assertOk();$this->scan('out')->assertUnprocessable();Carbon::setTestNow('2026-10-07 15:10:00');$this->scan('out')->assertOk()->assertJsonPath('type','Pulang');$this->scan('out')->assertUnprocessable();$this->assertNotNull(Attendance::first()->check_out_at);
 }
 public function test_manual_attendance_uses_authenticated_user_and_settings(): void {
  $this->actingAs($this->student->user)->post('/student/attendance',['type'=>'in','user_id'=>$this->regular->user_id,'status'=>'HADIR'])->assertSessionHasNoErrors();$this->assertDatabaseHas('attendances',['user_id'=>$this->student->user_id]);$this->assertDatabaseMissing('attendances',['user_id'=>$this->regular->user_id]);
  AttendanceSetting::current()->update(['manual_enabled'=>false]);$this->actingAs($this->regular->user)->post('/teacher/attendance',['type'=>'in'])->assertSessionHasErrors('attendance');
 }
 public function test_sick_request_requires_evidence_and_stores_private_image(): void {
  $payload=['type'=>'SAKIT','date'=>'2026-10-07','reason'=>'Demam dan sakit kepala'];$this->actingAs($this->student->user)->post('/student/absences',$payload)->assertSessionHasErrors('evidence');
  $this->post('/student/absences',[...$payload,'evidence'=>UploadedFile::fake()->image('bukti.jpg')])->assertSessionHasNoErrors();$absence=AbsenceRequest::firstOrFail();$this->assertSame('PENDING',$absence->status);Storage::disk('local')->assertExists($absence->evidence_path);Storage::disk('public')->assertMissing($absence->evidence_path);
 }
 public function test_permission_request_can_be_approved_by_unlocked_homeroom(): void {
  $this->actingAs($this->student->user)->post('/student/absences',['type'=>'IZIN','date'=>'2026-10-07','purpose'=>'Keluarga','reason'=>'Keperluan keluarga penting'])->assertSessionHasNoErrors();$absence=AbsenceRequest::firstOrFail();
  $this->actingAs($this->homeroom->user)->post('/absence/'.$absence->id.'/review',['decision'=>'DISETUJUI'])->assertForbidden();
  $this->withSession($this->unlock())->post('/absence/'.$absence->id.'/review',['decision'=>'DISETUJUI'])->assertSessionHasNoErrors();$this->assertDatabaseHas('attendances',['user_id'=>$this->student->user_id,'status'=>'IZIN']);$this->assertSame('DISETUJUI',$absence->fresh()->status);
 }
 public function test_homeroom_must_own_class_and_verify_pin(): void {
  $this->actingAs($this->regular->user)->get('/teacher/classes/'.$this->class->id.'/unlock')->assertForbidden();
  $this->actingAs($this->homeroom->user)->get('/teacher/classes/'.$this->class->id)->assertRedirect('/teacher/classes/'.$this->class->id.'/unlock');
  $this->post('/teacher/classes/'.$this->class->id.'/unlock',['pin'=>'111111'])->assertSessionHasErrors('pin');
  $this->post('/teacher/classes/'.$this->class->id.'/unlock',['pin'=>'246810'])->assertSessionHasNoErrors()->assertRedirect('/teacher/classes/'.$this->class->id);
  $this->get('/teacher/classes/'.$this->class->id)->assertOk();$this->class->update(['pin'=>'999999']);$this->get('/teacher/classes/'.$this->class->id)->assertRedirect('/teacher/classes/'.$this->class->id.'/unlock');
 }
 public function test_other_students_and_teachers_cannot_read_evidence_or_cards(): void {
  $this->actingAs($this->student->user)->post('/student/absences',['type'=>'SAKIT','date'=>'2026-10-07','reason'=>'Demam tinggi','evidence'=>UploadedFile::fake()->image('bukti.jpg')]);$absence=AbsenceRequest::firstOrFail();
  $this->actingAs($this->regular->user)->get('/absence/'.$absence->id.'/evidence')->assertForbidden();$this->get('/cards/'.$this->student->user->card->id)->assertForbidden();
  $other=app(PeopleService::class)->saveStudent($this->studentData('0000001002'));$this->actingAs($other->user)->get('/absence/'.$absence->id.'/evidence')->assertForbidden();$this->get('/cards/'.$this->student->user->card->id.'/download')->assertForbidden();
 }
 public function test_holiday_and_sunday_do_not_generate_alfa_or_allow_scans(): void {
  AcademicCalendar::create(['title'=>'Libur','starts_on'=>'2026-10-07','ends_on'=>'2026-10-07','type'=>'Hari libur','attendance_active'=>false]);$this->scan()->assertUnprocessable();Carbon::setTestNow('2026-10-07 19:00');$this->assertSame(0,app(AttendanceService::class)->markAbsent(today()));
  Carbon::setTestNow('2026-10-11 19:00');$this->assertSame(0,app(AttendanceService::class)->markAbsent(today()));$this->assertDatabaseCount('attendances',0);
 }
 public function test_alfa_runs_after_deadline_only_once_and_pending_requests_are_skipped(): void {
  $this->assertSame(0,app(AttendanceService::class)->markAbsent(today()));
  Carbon::setTestNow('2026-10-07 19:00');$this->assertSame(3,app(AttendanceService::class)->markAbsent(today()));$this->assertSame(0,app(AttendanceService::class)->markAbsent(today()));$this->assertDatabaseHas('attendances',['user_id'=>$this->student->user_id,'status'=>'ALFA']);
 }
 public function test_pending_leave_does_not_generate_alfa_and_rejection_finalizes_it(): void {
  $this->actingAs($this->student->user)->post('/student/absences',['type'=>'IZIN','date'=>'2026-10-07','purpose'=>'Keluarga','reason'=>'Keperluan keluarga']);$absence=AbsenceRequest::firstOrFail();Carbon::setTestNow('2026-10-07 19:00');app(AttendanceService::class)->markAbsent(today());$this->assertDatabaseMissing('attendances',['user_id'=>$this->student->user_id]);
  $this->actingAs($this->homeroom->user)->withSession($this->unlock())->post('/absence/'.$absence->id.'/review',['decision'=>'DITOLAK','review_notes'=>'Bukti tidak sesuai'])->assertSessionHasNoErrors();$this->assertDatabaseHas('attendances',['user_id'=>$this->student->user_id,'status'=>'ALFA']);
 }
 public function test_reports_export_real_xlsx_and_authorize_class_scope(): void {
  $this->scan()->assertOk();$report=app(ReportService::class)->generate($this->class,2026,10);Storage::disk('local')->assertExists($report->file_path);
  $book=\PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::disk('local')->path($report->file_path));$this->assertSame(4,$book->getSheetCount());$this->assertSame($this->student->nisn,$book->getSheet(0)->getCell('A2')->getValue());$this->assertSame(1,$book->getSheet(0)->getCell('C2')->getValue());$book->disconnectWorksheets();
  $this->actingAs($this->regular->user)->get('/reports/'.$report->id.'/download')->assertForbidden();
  $this->actingAs($this->homeroom->user)->withSession($this->unlock())->get('/reports/'.$report->id.'/download')->assertDownload();
  $this->post('/reports/'.$report->id.'/approve')->assertSessionHasNoErrors();$this->assertSame('DISETUJUI',$report->fresh()->status);
  Carbon::setTestNow('2026-10-07 15:05');$this->scan('out')->assertOk();$this->assertSame('PENDING',$report->fresh()->status);
 }
 public function test_excel_does_not_interpret_names_as_formulas(): void {
  $this->student->user->update(['name'=>'=HYPERLINK("https://invalid.test")']);$report=app(ReportService::class)->generate($this->class,2026,10);$book=\PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::disk('local')->path($report->file_path));$this->assertSame('s',$book->getSheet(0)->getCell('B2')->getDataType());$book->disconnectWorksheets();
 }
 public function test_profile_cannot_change_protected_identity_or_role(): void {
  $this->actingAs($this->student->user)->put('/profile',['phone'=>'08111','role'=>'admin','nisn'=>'9999999999','email'=>'attacker@test.example'])->assertSessionHasNoErrors();$this->assertSame('student',$this->student->user->fresh()->role);$this->assertSame('0000001001',$this->student->fresh()->nisn);
  $this->put('/profile',['password'=>'Changed123!','password_confirmation'=>'Changed123!','current_password'=>'wrong'])->assertSessionHasErrors('current_password');
 }
 public function test_card_pdf_is_a_real_download(): void {$response=$this->actingAs($this->student->user)->get('/cards/'.$this->student->user->card->id.'/download');$response->assertOk();$this->assertStringStartsWith('%PDF',$response->getContent());}
 public function test_monthly_command_is_idempotent(): void {$this->artisan('reports:monthly',['--month'=>'2026-10'])->assertSuccessful();$this->artisan('reports:monthly',['--month'=>'2026-10'])->assertSuccessful();$this->assertDatabaseCount('monthly_reports',1);}
 public function test_all_main_pages_render_for_each_role(): void {
  $this->get('/')->assertOk();$this->get('/login/student')->assertOk();$this->get('/login/teacher')->assertOk();$this->get('/login/admin')->assertOk();
  $this->actingAs($this->admin);foreach(['/admin/dashboard','/admin/students','/admin/students/create','/admin/students/'.$this->student->id,'/admin/students/'.$this->student->id.'/edit','/admin/teachers','/admin/teachers/create','/admin/teachers/'.$this->homeroom->id,'/admin/teachers/'.$this->homeroom->id.'/edit','/admin/classes','/admin/classes/create','/admin/classes/'.$this->class->id.'/edit','/admin/attendance','/scanner','/admin/requests','/calendar','/admin/calendar/create','/reports','/cards','/admin/settings','/notifications','/profile'] as $url)$this->get($url)->assertOk();
  $this->actingAs($this->student->user);foreach(['/student/dashboard','/student/attendance','/student/history','/student/absences','/cards','/cards/'.$this->student->user->card->id,'/calendar','/profile','/notifications'] as $url)$this->get($url)->assertOk();
  $this->actingAs($this->homeroom->user)->withSession($this->unlock());foreach(['/teacher/dashboard','/teacher/attendance','/teacher/history','/teacher/classes','/teacher/classes/'.$this->class->id,'/reports'] as $url)$this->get($url)->assertOk();
 }
}
