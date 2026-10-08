<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class AttendanceSetting extends Model {
 use HasFactory;
 protected $fillable=['school_name','school_address','logo_path','opens_at','starts_at','late_after','checkout_at','closes_at','school_days','manual_enabled','sick_evidence_required'];
 protected function casts(): array {return ['school_days'=>'array','manual_enabled'=>'boolean','sick_evidence_required'=>'boolean'];}
 public static function current(): self {return static::unguarded(fn()=>static::firstOrCreate(['id'=>1],['school_days'=>[1,2,3,4,5]]))->refresh();}
}
