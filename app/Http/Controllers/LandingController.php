<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index() {
        return view('frontend.index');
    }

    public function produk() {
        return view('frontend.produk');
    }

    public function tentang() {
        return view('frontend.tentang');
    }
    public function register() {
        return view('frontend.tentang');
    }
}
