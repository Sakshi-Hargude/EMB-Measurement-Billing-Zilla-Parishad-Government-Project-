<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temptnditem extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'work_Id',
        't_item_id',
        't_item_no',
        'sub_no',
        'item_id',
        'sch_item',
        'item_desc',
        'tnd_qty',
        'tnd_rt',
        'item_unit',
        't_item_amt',
        'short_nm',
        'exs_nm',
        'Add_Ded',
        'Add_Ded_T',
        'Floor',
        'Def_Cons',
        'Comp_Lab',
        'QtyDcml_Ro',

    ];


}
