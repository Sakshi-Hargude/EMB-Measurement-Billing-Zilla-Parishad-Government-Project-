<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use DB;

class DivisionViewController extends Controller
{
    public function index1() {
        $users = DB::select('select * from divisions');
        return view('division_view',['users'=>$users]);
     }
}
