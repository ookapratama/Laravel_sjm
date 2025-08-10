<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BonusTransaction;
class BonusController extends Controller
{
   public function index()
    {
        $bonuses = BonusTransaction::where('user_id', auth()->id())
           
            ->latest()
            ->get();

        return view('bonus.index', compact('bonuses'));
    }
}
