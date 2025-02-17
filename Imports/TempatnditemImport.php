<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TempatnditemImport implements ToCollection,WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach($rows as $row)
        {
            Temptnditem::create([
                'user_id'=>$row[user_id],
                'work_Id'=>$row[work_Id],
                't_item_id'=>$row[t_item_id],
                't_item_no'=>$row[t_item_no],
                'sub_no'=>$row[sub_no],
                'item_id'=>$row[item_id],
                'sch_item'=>$row[sch_item],
                'item_desc'=>$row['item_desc'],
                'tnd_qty'=>$row[tnd_qty],
                'tnd_rt'=>$row[tnd_rt],
                'item_unit'=>$row[item_unit],
                't_item_amt'=>$row[t_item_amt],
                'short_nm'=>$row['short_nm'],
                'exs_nm'=>$row['exs_nm'],
                'Add_Ded'=>$row['Add_Ded'],
                'Add_Ded_T'=>$row['Add_Ded_T'],
                'Floor'=>$row[Floor],
                'Def_Cons'=>$row['Def_Cons'],
                'Comp_Lab'=>$row['Comp_Lab'],
                'QtyDcml_Ro'=>$row[QtyDcml_Ro],
        
        


            ]);
        }
    }
}
