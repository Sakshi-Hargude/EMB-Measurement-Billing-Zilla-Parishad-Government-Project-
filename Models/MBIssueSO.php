<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MBIssueSO extends Model
{
    use HasFactory;

    protected $primarykey = "id";

    protected $table = "_m_b__issue__s_o";

    protected $fillable = ['id' , 'Div_Id' , 'Sub_Div_Id' , 'Dye_Id' , 'JE_Id' , 'JE_Nm' , 'MB_No' ,'Pg_from' , 'Pg_Upto' , 
                             'Issue_Dt' , 'Return_Dt' , 'Preserve_Yr' , 'Remark'];
}
