<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivationPin;   // <-- tambahkan
use App\Models\PinRequest;      // <-- tambahkan
use Illuminate\Support\Facades\DB;
class PinCtrl extends Controller
{
    public function index(){
        return view('finance.pin_request', ['list'=> PinRequest::with('requester')->latest()->get()]);
    }

    public function approve(Request $r, $id){
        $data = $r->validate([
            'payment_method'=>'required|string|max:50',
            'payment_reference'=>'required|string|max:100',
            'finance_notes'=>'nullable|string|max:500',
        ]);

        DB::transaction(function() use($id,$data){
            $req = PinRequest::lockForUpdate()->findOrFail($id);
            if ($req->status !== 'requested') throw ValidationException::withMessages(['status'=>'Invalid.']);
            $req->update([
                'status'=>'finance_approved',
                'payment_method'=>$data['payment_method'],
                'payment_reference'=>$data['payment_reference'],
                'finance_notes'=>$data['finance_notes']??null,
                'finance_id'=>auth()->id(),'finance_at'=>now(),
            ]);
        });

        return back()->with('success','Approved. Menunggu Admin generate.');
    }

    public function reject(Request $r, $id){
        $notes = $r->validate(['finance_notes'=>'required|string|max:500'])['finance_notes'];
        $req = PinRequest::findOrFail($id);
        if ($req->status !== 'requested') return back()->with('error','Invalid.');
        $req->update(['status'=>'finance_rejected','finance_notes'=>$notes,'finance_id'=>auth()->id(),'finance_at'=>now()]);
        return back()->with('success','Ditolak.');
    }
}

