<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{User,SchoolClass,Attendance,AttendanceSetting,AcademicCalendar,ClassStudent,AbsenceRequest};
use App\Services\{PeopleService,NotificationService,ReportService,CalendarService};
class DatabaseSeeder extends Seeder {
 public function run(): void {
  AttendanceSetting::current();
  if(!app()->environment(['local','testing'])){$this->command?->warn('Akun demo hanya dibuat pada local/testing.');return;}
  if(User::where('login_id','admin')->exists()){$this->command?->info('Data demo sudah tersedia; tidak digandakan.');return;}
  DB::transaction(function(){
   $start=today()->subMonthNoOverflow()->startOfMonth();$people=app(PeopleService::class);$pass='Sekolah123!';
   User::create(['name'=>'Admin Sekolah','email'=>'admin@sekolah.test','login_id'=>'admin','role'=>'admin','password'=>$pass,'active'=>true]);
   $teacherNames=['Ratna Puspita','Budi Santoso','Dewi Lestari','Agus Setiawan','Siti Rahmawati','Dimas Pratama'];$teachers=[];
   foreach($teacherNames as $i=>$name){$teachers[]=$t=$people->saveTeacher(['name'=>$name,'email'=>'guru'.($i+1).'@sekolah.test','employee_id'=>'G00'.($i+1),'password'=>$pass,'position'=>$i<3?'Guru / Wali Kelas':'Guru Mata Pelajaran','phone'=>'08120000000'.($i+1),'address'=>'Jakarta','active'=>true]);$t->user->forceFill(['created_at'=>$start])->save();}
   $classes=[];foreach(['XI RPL 1','XI RPL 2','XII RPL 1'] as $i=>$name)$classes[]=SchoolClass::create(['name'=>$name,'grade'=>$i===2?'XII':'XI','major'=>'Rekayasa Perangkat Lunak','academic_year'=>'2026/2027','homeroom_teacher_id'=>$teachers[$i]->id,'pin'=>'246810','active'=>true]);
   $names=['Aditya Pratama','Aisyah Putri','Bagas Ramadhan','Citra Maharani','Daffa Saputra','Dina Amelia','Fajar Nugroho','Farah Aulia','Gilang Maulana','Hana Safitri','Ilham Kurniawan','Intan Permata','Joko Prasetyo','Kirana Dewi','Lutfi Hakim','Maya Anggraini','Naufal Fadillah','Nadia Zahra','Oki Firmansyah','Putri Anjani','Rafi Alfarizi','Rania Syakira','Rizky Febrianto','Salsa Nabila','Satria Wibowo','Sekar Ayuningtyas','Tegar Pamungkas','Tiara Melati','Umar Faruq','Vina Oktaviani','Wahyu Hidayat','Wulan Sari','Yoga Saputra','Yasmin Azizah','Zidan Akbar','Zahra Nuraini'];
   foreach($names as $i=>$name){$student=$people->saveStudent(['name'=>$name,'nisn'=>str_pad((string)(1001+$i),10,'0',STR_PAD_LEFT),'email'=>'murid'.($i+1).'@sekolah.test','password'=>$pass,'gender'=>$i%2?'P':'L','birth_date'=>'2009-05-15','address'=>'Jakarta','phone'=>'08130000'.str_pad((string)$i,4,'0',STR_PAD_LEFT),'school_class_id'=>$classes[intdiv($i,12)]->id,'active'=>true,'guardian_name'=>'Wali '.$name,'guardian_relationship'=>'Orang tua','guardian_phone'=>'08140000'.str_pad((string)$i,4,'0',STR_PAD_LEFT),'guardian_email'=>'wali'.($i+1).'@example.test']);$student->update(['enrolled_at'=>$start]);$student->user->forceFill(['created_at'=>$start])->save();ClassStudent::where('student_id',$student->id)->update(['joined_at'=>$start]);}
   $users=User::with('student')->whereIn('role',['student','teacher'])->get();$calendar=app(CalendarService::class);
   foreach(\Carbon\CarbonPeriod::create($start,today()) as $day){if(!$calendar->isSchoolDay($day))continue;
    foreach($users as $i=>$user){if($day->isToday() && $i%7===0)continue;$code=($i+$day->day)%23;$status=match(true){$code===0=>'ALFA',$code===1=>'SAKIT',$code===2=>'IZIN',$code<6=>'TERLAMBAT',default=>'HADIR'};$time=in_array($status,['HADIR','TERLAMBAT'])?$day->copy()->setTime(7,$status==='TERLAMBAT'?23:($i%14)):null;if($day->isToday() && $time?->isFuture())continue;
     Attendance::create(['user_id'=>$user->id,'school_class_id'=>$user->student?->school_class_id,'date'=>$day->toDateString(),'check_in_at'=>$time,'check_out_at'=>$time && !$day->isToday()?$day->copy()->setTime(15,5):null,'status'=>$status,'late_minutes'=>$status==='TERLAMBAT'?8:0,'method'=>'seed','created_at'=>$time??$day,'updated_at'=>$time??$day]);
    }
   }
   $day=today();while(!$calendar->isSchoolDay($day))$day->addDay();
   foreach(User::where('role','student')->whereDoesntHave('attendances',fn($q)=>$q->whereDate('date',$day))->limit(2)->get() as $student){AbsenceRequest::create(['user_id'=>$student->id,'school_class_id'=>$student->student->school_class_id,'date'=>$day,'type'=>'IZIN','purpose'=>'Keperluan keluarga','reason'=>'Mendampingi keluarga untuk keperluan penting.','status'=>'PENDING']);app(NotificationService::class)->request($student,$student->student->schoolClass,'IZIN');}
   AcademicCalendar::create(['title'=>'Asesmen Tengah Semester','starts_on'=>today()->addDays(5),'ends_on'=>today()->addDays(9),'type'=>'Ujian','attendance_active'=>true,'description'=>'Persiapkan diri dan perlengkapan ujian.']);
   AcademicCalendar::create(['title'=>'Pertemuan Orang Tua','starts_on'=>today()->addDays(12),'ends_on'=>today()->addDays(12),'type'=>'Acara sekolah','attendance_active'=>true]);
   AcademicCalendar::create(['title'=>'Libur Sekolah','starts_on'=>today()->addDays(18),'ends_on'=>today()->addDays(18),'type'=>'Hari libur','attendance_active'=>false]);
   $first=User::where('email','murid1@sekolah.test')->first();app(NotificationService::class)->attendance($first,'HADIR','Selamat datang di sistem absensi digital sekolah.',$first->student->schoolClass);
  });
  foreach(SchoolClass::all() as $class){$d=today()->subMonthNoOverflow();app(ReportService::class)->generate($class,$d->year,$d->month);}
 }
}
