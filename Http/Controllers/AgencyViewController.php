<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class AgencyViewController extends Controller
{
    public function index() {
        $users = DB::select('select * from agencies');
        return view('agency_view',['users'=>$users]);
     }
}
