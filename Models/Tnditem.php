<?php

namespace App\Models;

use App\Models\Tnditem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tnditem extends Model
{
    use HasFactory;
    protected $fillable = [
        
        't_item_no',
        'sub_no',
        'item_desc',
        'tnd_qty',
        'item_unit',
        'tnd_rt',
        't_item_amt',
    ];

}
