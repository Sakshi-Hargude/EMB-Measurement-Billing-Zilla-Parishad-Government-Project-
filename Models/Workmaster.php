<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class Workmaster extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'Div',
        'Work_Id',
        'Sub_Div',
        'F_H_Code',
        'Work_Nm',
        'Tnd_Amt',
        'Agency_Nm',
        'DSR_year',
        'WO_No',
        'Wo_Dt',
        'Above_Below',
        'A_B_Pc',
        'WO_Amt',
        'Period',
        'Perd_Unit',
        'Stip_Comp_Dt',
        // 'tm_lm_extension',
        // 'rs_dt_comp',
        'Tot_Exp',
        'work_comp',
        'actual_complete_date',
//excelfilesheet2//
'Sub_Div',
'Work_Type',
'Work_Nm',
    ];

    
}
