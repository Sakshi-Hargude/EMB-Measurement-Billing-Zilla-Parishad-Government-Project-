<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deputy extends Model
{
    use HasFactory;
    protected $fillable = [
        
        // 'id',
        'division_name',
        'subdivision_name',
        'dename_categary',
        'dpt_name',
        'designation',
        'charge_from',
        'charge_upto',
        'phone_no',
        'email',
        'user_name',
        'pwd'
       

    ];



}
