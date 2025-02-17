<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
 use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Agency extends Model
{
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'agency_nm',
        'Agency_Ad1',
        'Agency_Ad2',
        'Agency_Pl',
        'Agency_Mail',
        'Agency_Phone',
        'User_Name',
        'Password',
        'Regi_No_Local',
        'Gst_no',
        'Regi_Class',
        'Pan_no',
        'Regi_Dt_Local',
        'Bank_nm',
        'Ifsc_no',
        'Bank_br',
        'Micr_no',
        'Bank_acc_no',
        'Contact_Person1',
        'C_P1_Phone',
        'C_P1_Mail'
    ];
}
