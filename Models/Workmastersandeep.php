<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;


class Workmaster extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable=[
        'workid',
        'Sub_Div',
        'Agency_Nm',
        'worknm'
     ];
}
