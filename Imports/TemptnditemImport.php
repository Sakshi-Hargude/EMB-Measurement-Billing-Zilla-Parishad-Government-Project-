<?php

namespace App\Imports;

use App\Models\task;
use App\Models\Temptnditem;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TemptnditemImport implements ToModel,WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        dd($row);
        return new Temptnditem([
            'user_id'=> $row[0],
            'work_Id'=> $row[1],
            't_item_id'=> $row[2],
            't_item_no'=> $row[3],
            'sub_no'=> $row[4],
            'item_id'=> $row[5],
            'sch_item'=> $row[6],
            'item_desc'=> $row[7],
            'tnd_qty'=> $row[8],
            'tnd_rt'=> $row[9],
            'item_unit'=> $row[10],
            't_item_amt'=> $row[11],
            'short_nm'=> $row[12],
            'exs_nm'=> $row[13],
        ]);
    }
}
