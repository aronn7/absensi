<?php
namespace App\Http\Controllers;
use App\Models\Card;
use App\Services\CardGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
class CardController extends Controller {
 public function index(Request $r){$rows=Card::with('user.student.schoolClass','user.teacher')->when(!$r->user()->isAdmin(),fn($q)=>$q->where('user_id',$r->user()->id))->when($r->q,fn($q)=>$q->whereHas('user',fn($q)=>$q->where('name','like','%'.$r->q.'%')))->paginate(12)->withQueryString();return view('cards.index',compact('rows'));}
 public function show(Card $card,CardGeneratorService $service){Gate::authorize('view',$card);return view('cards.show',$service->data($card));}
 public function download(Card $card,CardGeneratorService $service){Gate::authorize('view',$card);return $service->download($card);}
}
