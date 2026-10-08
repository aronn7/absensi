<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;



return new class extends Migration {
 public function up(): void {
  Schema::table('users', function(Blueprint $t){
   $t->string('login_id')->unique()->nullable(); $t->string('role',20)->default('student')->index();
   $t->boolean('active')->default(true)->index(); $t->string('phone',30)->nullable(); $t->string('photo_path')->nullable();
  });
  Schema::create('teachers',function(Blueprint $t){$t->id();$t->foreignId('user_id')->unique()->constrained()->restrictOnDelete();$t->string('employee_id',50)->unique();$t->string('position');$t->text('address')->nullable();$t->timestamps();});
  Schema::create('school_classes',function(Blueprint $t){$t->id();$t->string('name');$t->string('grade',10);$t->string('major');$t->string('academic_year',9);$t->foreignId('homeroom_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();$t->string('pin');$t->boolean('active')->default(true);$t->timestamps();$t->unique(['name','academic_year']);});
  Schema::create('students',function(Blueprint $t){$t->id();$t->foreignId('user_id')->unique()->constrained()->restrictOnDelete();$t->string('nisn',10)->unique();$t->string('gender',1);$t->date('birth_date')->nullable();$t->text('address')->nullable();$t->foreignId('school_class_id')->constrained()->restrictOnDelete();$t->date('enrolled_at');$t->timestamps();});
  Schema::create('class_students',function(Blueprint $t){$t->id();$t->foreignId('school_class_id')->constrained()->restrictOnDelete();$t->foreignId('student_id')->constrained()->restrictOnDelete();$t->date('joined_at');$t->date('left_at')->nullable();$t->timestamps();$t->index(['student_id','joined_at','left_at']);});
  Schema::create('parent_guardians',function(Blueprint $t){$t->id();$t->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();$t->string('name');$t->string('relationship',50);$t->string('phone',30);$t->string('email')->nullable();$t->timestamps();});
  Schema::create('attendance_settings',function(Blueprint $t){$t->id();$t->string('school_name')->default('SMKN 20');$t->string('school_address')->nullable();$t->string('logo_path')->nullable();$t->time('opens_at')->default('06:00');$t->time('starts_at')->default('07:00');$t->time('late_after')->default('07:15');$t->time('checkout_at')->default('15:00');$t->time('closes_at')->default('18:00');$t->json('school_days');$t->boolean('manual_enabled')->default(true);$t->boolean('sick_evidence_required')->default(true);$t->timestamps();});
  Schema::create('academic_calendars',function(Blueprint $t){$t->id();$t->string('title');$t->date('starts_on');$t->date('ends_on');$t->string('type',30);$t->text('description')->nullable();$t->boolean('attendance_active')->default(false);$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->index(['starts_on','ends_on']);});
  Schema::create('attendances',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->foreignId('school_class_id')->nullable()->constrained()->restrictOnDelete();$t->date('date');$t->timestamp('check_in_at')->nullable();$t->timestamp('check_out_at')->nullable();$t->string('status',15)->index();$t->unsignedInteger('late_minutes')->default(0);$t->string('method',15);$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->unique(['user_id','date']);$t->index(['school_class_id','date']);});
  Schema::create('absence_requests',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->restrictOnDelete();$t->foreignId('school_class_id')->constrained()->restrictOnDelete();$t->date('date');$t->string('type',10);$t->string('purpose')->nullable();$t->text('reason');$t->text('notes')->nullable();$t->string('evidence_path')->nullable();$t->string('status',15)->default('PENDING');$t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamp('approved_at')->nullable();$t->text('review_notes')->nullable();$t->timestamps();$t->unique(['user_id','date']);$t->index(['school_class_id','status']);});
  Schema::create('cards',function(Blueprint $t){$t->id();$t->foreignId('user_id')->unique()->constrained()->restrictOnDelete();$t->string('number')->unique();$t->text('qr_token');$t->string('token_hash',64)->unique();$t->string('qr_path');$t->timestamp('issued_at');$t->timestamps();});
  Schema::create('notifications',function(Blueprint $t){$t->uuid('id')->primary();$t->string('type');$t->morphs('notifiable');$t->text('data');$t->timestamp('read_at')->nullable();$t->timestamps();});
  Schema::create('monthly_reports',function(Blueprint $t){$t->id();$t->foreignId('school_class_id')->constrained()->restrictOnDelete();$t->unsignedSmallInteger('year');$t->unsignedTinyInteger('month');$t->string('status',15)->default('PENDING');$t->string('file_path')->nullable();$t->timestamp('generated_at')->nullable();$t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamp('approved_at')->nullable();$t->timestamps();$t->unique(['school_class_id','year','month']);});
  Schema::create('audit_logs',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('action');$t->string('subject_type');$t->unsignedBigInteger('subject_id');$t->json('metadata')->nullable();$t->timestamps();});
 }
 public function down(): void {
  foreach(['audit_logs','monthly_reports','notifications','cards','absence_requests','attendances','academic_calendars','attendance_settings','parent_guardians','class_students','students','school_classes','teachers'] as $name) Schema::dropIfExists($name);
  Schema::table('users',fn(Blueprint $t)=>$t->dropColumn(['login_id','role','active','phone','photo_path']));
 }
};
