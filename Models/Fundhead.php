<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;


class Fundhead extends Model
{

    protected $table='fundhdms';
   /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable=[
        'F_H_id',
        'fhcode',
        'fundhead',
        'fundhead_m'
    ];
    
}
