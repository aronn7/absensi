<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
class Card extends Model {
 use HasFactory;
 protected $fillable=['user_id','number','qr_token','token_hash','qr_path','issued_at'];
 protected $hidden=['qr_token','token_hash'];
 protected function casts(): array {return ['qr_token'=>'encrypted','issued_at'=>'datetime'];}
 public function user(){return $this->belongsTo(User::class);}
}
