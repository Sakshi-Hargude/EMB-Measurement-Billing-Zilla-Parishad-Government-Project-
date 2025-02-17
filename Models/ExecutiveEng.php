<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExecutiveEng extends Model
{
    // use HasFactory;
    protected $fillable = [
        
        'id',
        'division_name',
        'exname_categary',
        'ex_name',
        'charge_from',
        'charge_upto',
        'phone_no',
        'email',
        'user_name',
        'pwd'
       

    ];


}
