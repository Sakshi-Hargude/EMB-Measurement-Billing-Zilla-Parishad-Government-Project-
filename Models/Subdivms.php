<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use  Illuminate\Database\Eloquent\Model;

class Subdivms extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        
        'Reg_Id',
        'Cir_Id',
        'Div_Id',
        'Sub_Div_Id',
        'Sub_Div',
        'Sub_Div_M',
        'address1',
        'address2',
        'place',
        'email',
        'phone_no',
        'designation'
       

    ];

   
}
