<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MBIssueDiv extends Model
{
    protected $primarykey = "id";

    protected $table = "_m_b__issue__div";

    protected $fillable = ['id' , 'Div_Id' , 'AAO_Id' , 'EE_Id' , 'Dye_Id' , 'Dye_Nm' , 'MB_No' , 'Pg_from' , 'Pg_Upto' ,
                           'Issue_Dt' , 'Return_Dt' , 'Preserve_Yr' , 'Remark'];
    use HasFactory;
}
