<?php

namespace App\Imports;

use App\Models\Boq;
use App\Models\User;
use App\Models\TndItem;
use App\Imports\UsersImport;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new TndItem([
            't_item_no'=> $row[0],
            'sub_no'=> $row[1],
            'item_desc'=> $row[2],
            'tnd_qty'=> $row[3], 
            'item_unit'=> $row[4], 
            'tnd_rt'=> $row[5], 
            't_item_amt'=> $row[6], 
        ]);
    }
}
