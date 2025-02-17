<?php

namespace App\Helpers;

use DateTime;
use Exception;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\Subdivms;
use App\Models\Workmaster;
use App\Helpers\ExcelReader;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Http\Request; // Import Request class
use Illuminate\Support\Facades\Response as FacadeResponse; // Import Response facade



class ExcelReader
{
public function reader($file)
{
    $workId = session('workId');
    // dd($workId);
    // dd($DBWork_Id);
    // Load the Excel file using IOFactory
    $excel = IOFactory::load($file);
    // Initialize an array to store all validation errors
    // $rowErrors = [];
    $validationErrors = [];
    $rowsWithWorkId = [];
    $columnName=[];
// Get the first sheet (Sheet 0 in this case, as you specified)
    $sheet = $excel->getSheet(0);
    // Get the highest row and column in the worksheet
    $highestRow = $sheet->getHighestRow();
//  dd($highestRow);
    $highestColumn = $sheet->getHighestColumn();
    // Convert the highest column letter to column index
    $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);   

    // Loop through the rows
    for ($row = 2; $row <= $highestRow; $row++) 
    {
        $rowErrors =[];

        // $workId = DB::table('workmasters')
        // ->orderBy('Work_Id', 'desc')
        // ->orderBy('created_at', 'desc')
        // ->value('Work_Id');
    
    // dd($DBWork_Id);
    // $workId =$DBWork_Id;
        // $workId = DB::table('workmasters')->max('Work_Id');

        // dd($workId);
        $lastTItemIdSQL = DB::table('tnditems')
        ->select('t_item_id',)
        ->where('work_Id', '=', $workId)
        ->orderBy('t_item_id', 'desc')
        ->first();
        // dd($lastTItemIdSQL);
        // get T_Item_id
        if (isset($lastTItemIdSQL->t_item_id)) {
            $lastFourDigits = substr($lastTItemIdSQL->t_item_id, -4);
            $incrementedLastFourDigits = str_pad(intval($lastFourDigits) + 1, 4, '0', STR_PAD_LEFT);
            $FinalTItemId = $workId . $incrementedLastFourDigits;
            // dd($FinalTItemId);
        } else {
            // If no records exist, set the t_item_id to '0001'
            $FinalTItemId = $workId . '0001';
        }

// dd($FinalTItemId);

         // Access and store cell values for columns A to G

    $A2TItem_id = $sheet->getCell('A' . $row)->getValue();
    $A2TItem_id = is_null($A2TItem_id) ? ' ' : $A2TItem_id;

        if (!is_numeric($A2TItem_id)) 
        {

            $rowErrors[] = "Cell:A$row: Incorrect Item No.";
        }

    $B2subno = $sheet->getCell('B' . $row)->getValue();
    $B2subno = is_null($B2subno) ? ' ' : $B2subno;


                if ($B2subno) 
            { 
                // dd(empty($B2subno));
                // dd($B2subno);

                if (!preg_match('/^[a-z ]?$/', $B2subno))
                                {
                                    $rowErrors[] = "Cell:B$row: Incorrect SubNO No.";
                                }
            }

    $C2Description = $sheet->getCell('C' . $row)->getValue();
    $C2Description = is_null($C2Description) ? ' ' : $C2Description;


                // dd($C2Description);

                if ($C2Description === '' || is_numeric($C2Description))         
                {
                    $rowErrors[] = "Cell:C$row: Incorrect Descroption of Item.";
                }
                else
                {
                    $trimmedValue = trim($C2Description);
                    if ($trimmedValue === '')
                    {
                        $rowErrors[] = "Cell:C$row: Incorrect Descroption of Item.";
                    }
                }

    $D2TndQty = $sheet->getCell('D' . $row)->getValue();
    $D2TndQty = is_null($D2TndQty) ? ' ' : $D2TndQty;

                // dd($D2TndQty);

                if (!is_numeric($D2TndQty) || !preg_match('/^\d{1,10}(\.\d{1,3})?$/', $D2TndQty)) 
                    {
                    $rowErrors[] = "Cell:D$row: Incorrect Tendered Quantity.";
                    }

    $E2Unit = $sheet->getCell('E' . $row)->getValue();
    $E2Unit = is_null($E2Unit) ? ' ' : $E2Unit;

                // dd($E2Unit);

                if (!is_null($E2Unit) && is_string($E2Unit) && (strlen($E2Unit) < 1 || strlen($E2Unit) > 100)) 
                {
                    $rowErrors[] = "Cell:E$row: Incorrect Tendere Unit.";
                }

    $F2TenderRate = $sheet->getCell('F' . $row)->getValue();
    $F2TenderRate = is_null($F2TenderRate) ? ' ' : $F2TenderRate;

                // dd($F2TenderRate);

                if (!is_null($F2TenderRate) && preg_match('/^\d{1,8}(\.\d{1,2})?$/', $F2TenderRate) !== 1)             
                   {
                    $rowErrors[] = "Cell:F$row: Incorrect Tendered Rate.";
                    }

    $G2Amount = $sheet->getCell('G' . $row)->getValue();
    $G2Amount = is_null($G2Amount) ? ' ' : $G2Amount;

                // dd($G2Amount);

                if (!is_null($G2Amount) && preg_match('/^\d{1,11}(\.\d{1,2})?$/', $G2Amount) !== 1)
                {
                    $rowErrors[] = "Cell:G$row Incorrect Amount.";
                }
    // dd($G1);
     // Access and store cell values for columns CA to CJ
     $CA2SchItem = $sheet->getCell('CA' . $row)->getValue();
     $CA2SchItem = is_null($CA2SchItem) ? ' ' : $CA2SchItem;

                // dd($CA2SchItem);

                // Column CA (Column index 79)
                if (!is_numeric($CA2SchItem)) 
                {
                    $rowErrors[] = "Cell: CA$row Incorrect Sch Item.";
                }


                $CB2ItemId = $sheet->getCell('CB' . $row)->getValue();
                $CB2ItemId = is_null($CB2ItemId) ? ' ' : $CB2ItemId;
                // dd($CB2ItemId);
                
                $pattern = '/^\d{1,10}$/';
                $patternWithChars = '/^[A-Za-z\d]{1,10}$/'; // Pattern to allow characters and digits
                
                if (!preg_match($pattern, $CB2ItemId) && !preg_match($patternWithChars, $CB2ItemId) || empty($CB2ItemId))
                {
                    $rowErrors[] = "Cell: CB$row Incorrect Item Id.";
                }
                
     $CC2ShortItem = $sheet->getCell('CC' . $row)->getValue();
     $CC2ShortItem = is_null($CC2ShortItem) ? ' ' : $CC2ShortItem;

                // dd($CC2ShortItem);
                $CC2ShortItem = trim($sheet->getCellByColumnAndRow(81, $row)->getValue());
                // dd($CC2ShortItem);

                if (empty($CC2ShortItem) || strlen($CC2ShortItem) < 1 || strlen($CC2ShortItem) > 4000)              
                {
                    $rowErrors[] = "Cell: CC$row Incorrect short Discription Item .";
                }
            

     $CD2ExtraItem = $sheet->getCell('CD' . $row)->getValue();
     $CD2ExtraItem = is_null($CD2ExtraItem) ? ' ' : $CD2ExtraItem;

                // dd($cellValue);
                $CD2ExtraItem = trim($sheet->getCellByColumnAndRow(82,$row)->getValue());
                // dd($CD2ExtraItem);
                if (empty($CD2ExtraItem) || strlen($CD2ExtraItem) < 1 || strlen($CD2ExtraItem) > 4000)              
                  {
                    $rowErrors[] = "Cell: CD$row Incorrect Extra Discription Item .";
                    }


     $CE2ModConsum= $sheet->getCell('CE' . $row)->getValue();
     $CE2ModConsum = is_null($CE2ModConsum) ? ' ' : $CE2ModConsum;
                // dd($cellValue);
                if (strlen($CE2ModConsum) < -1 || strlen($CE2ModConsum) > 4)                
                  {
                    $rowErrors[] = "Cell: CE$row Incorrect Mod Consumption.";
                  }


     $CF2AddDed = $sheet->getCell('CF' . $row)->getValue();
     $CF2AddDed = is_null($CF2AddDed) ? ' ' : $CF2AddDed;

                // dd($cellValue);
                // Column CA (Column index 79)
                if (is_numeric($CF2AddDed))              
                  {
                    $rowErrors[] ="Cell: CF$row Incorrect Add Ded.";
                  }

     $CG2AddDedT = $sheet->getCell('CG' . $row)->getValue();
     $CG2AddDedT = is_null($CG2AddDedT) ? ' ' : $CG2AddDedT;
                // dd($CG2AddDedT);
                // Column CA (Column index 79)
                // if (!is_numeric($CG2AddDedT))
                //  {
                //     $rowErrors[] = "Cell: CG$row Incorrect Add Ded T .";
                //  }
                

                  $CH2DecimalQty = $sheet->getCell('CH' . $row)->getValue();
                  $CH2DecimalQty = is_null($CH2DecimalQty) ? '' : trim($CH2DecimalQty);
                  
                  $allowedValues = ['0', '1', '2', '3'];

                  if ($CH2DecimalQty === '') {
                      $rowErrors[] = "Cell: CH$row Decimal Qty cannot be empty.";
                  } elseif (!in_array($CH2DecimalQty, $allowedValues)) {
                      $rowErrors[] = "Cell: CH$row Incorrect Decimal Qty.";
                  }                  
     $CI2Floor = $sheet->getCell('CI' . $row)->getValue();
     $CI2Floor = is_null($CI2Floor) ? ' ' : $CI2Floor;

                // dd($cellValue);
                $CI2Floor = trim($CI2Floor);
                if (strlen($CI2Floor) !== 1 && strlen($CI2Floor) !== 2 )            
                           {
                    $rowErrors[] = "Cell: CI$row Incorrect Floor .";
                }


     $CJ2CL = $sheet->getCell('CJ' . $row)->getValue();
     $CJ2CL = is_null($CJ2CL) ? ' ' : $CJ2CL;

                // dd($CJ2CL);
                $CJ2CL = trim($CJ2CL);
                if (strlen($CJ2CL) > 1)         
                  {
                    $rowErrors[] = "Cell: CJ$row Incorrect Comp_Lab .";
                   }
        if (!empty($rowErrors)) 
            {
        //  dd($rowErrors);
         $validationErrors[$row] = $rowErrors; 
        //  dd($validationErrors);
                }       
         if (!empty($validationErrors))
        {

            header('Content-Type: application/json');
            echo json_encode(['errorssheet1' => $validationErrors]);
            exit;
        
        // dd($validationErrors);
        // return response()->json(['errors' => $validationErrors]);
        }
        
    
    $data=[
        'work_Id'=>$workId,
        't_item_id'=>$FinalTItemId,
        't_item_no' => $A2TItem_id,
        'sub_no' => $B2subno,
        'item_id' => $CB2ItemId,
        'sch_item' => $CA2SchItem,
        'item_desc' => $C2Description,
        'tnd_qty' => $D2TndQty,
        'tnd_rt' => $F2TenderRate,
        'item_unit' => $E2Unit,
        't_item_amt' => $G2Amount,
        'short_nm' => $CC2ShortItem,
        'exs_nm' => $CD2ExtraItem,
        'Add_Ded' => $CF2AddDed,
        'Add_Ded_T' => $CG2AddDedT,
        'Floor' => $CI2Floor,
        'Def_Cons' => $CE2ModConsum,
        'Comp_Lab' => $CJ2CL,
        'QtyDcml_Ro' => $CH2DecimalQty,


        // ... Repeat for other columns CA to CJ ...
    ];
// dd($data);
    DB::table('temptnditems')->insert($data);
    DB::table('tnditems')->insert($data);
    
}
    //  dd($data);
    // dd($data);

    DB::table('temptnditems')->delete();

    // return response()->json(array('rowsWithWorkId' => $data),200);

    $importedData = DB::table('tnditems')
    ->where('work_Id','=' , $workId)->get();
    // dd($importedData); // Fetch the imported data from the database

return $importedData;
}



  public function readersheet2($file)
    {
        $excel = IOFactory::load($file);
        $sheet = $excel->getSheet(1);
        $highestRow = $sheet->getHighestRow();
        // dd($highestRow);
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $rowsWithWorkId = [];
    
        // Define the specific cell coordinates for date columns
        $dateCells = [
            ['row' => 8, 'col' => 2],
            ['row' => 12, 'col' => 2],
            ['row' => 20, 'col' => 2],
            ['row' => 23, 'col' => 2],
        ];
        // dd($dateCells);

//validation array
        $allErrors = [];
//validation array
            for ($row = 1; $row <= 36; $row++) 
        {
                    // dd($highestRow);

            $firstCellValue = $sheet->getCellByColumnAndRow(1, $row)->getValue();
    
            // Check if the first cell in the row is empty or not set
            if (empty($firstCellValue) || is_null($firstCellValue)) 
            {
                // Log a warning or show an error message if needed
                // Log::warning("Row $row: First cell value is empty or not set.");
                // Or
                // throw new \Exception("Row $row: First cell value is empty or not set.");
                continue;
            }
    
            $rowData = [];
    
            for ($column = 1; $column <= 2; $column++) 
            {
                $cellValue = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                $cellValue = is_null($cellValue) ? '' : $cellValue;
    
                // Check if the current cell coordinates match any of the date cells
                // foreach ($dateCells as $dateCell) 
                // {
                //     if ($row === $dateCell['row'] && $column === $dateCell['col']) 
                //     {
                //         $cellValue = Date::excelToDateTimeObject(intval($cellValue))->format('d-m-Y');
                //         // dd($dateCells);
                //         // Convert the numeric date value to an exact date with the format "d-m-Y"
                //         break;
                //     }
                // }

                // Validation for row[0], column[1]

                $errors = [];

    if ($row === 1 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (empty($cellValue) || is_numeric($cellValue)) 
        {
            $errors[] = "in Row $row, Column $column Incorrect Division.";
        }
    }   
    // Validation for row[1], column[1]
    if ($row === 2 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (empty($cellValue) || is_numeric($cellValue)) 
        {
            $errors[] = "Incorrect Sub-Division.";
        }
    }     
    
    if ($row === 3 && $column === 2)
    {
        $cellValue = trim($cellValue);
        if (empty($cellValue) || is_numeric($cellValue))
        {
            $errors[] ="Incorrect Type Of Work "; 
        }
    }             

    if ($row === 4 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (empty($cellValue) || is_numeric($cellValue)) 
        {
            $errors[] ="Incorrect Name of Work";
        }
    }             


    if ($row === 6 && $column === 2) {
        $cellValue = trim($cellValue);
        if (empty($cellValue) || (!is_string($cellValue) && !is_numeric($cellValue))) {
            $errors[] ="Incorrect Fund Head";
        }
    }
    
    if ($row === 7 && $column === 2) {

        $cellValue = trim($cellValue);
        if (empty($cellValue) )
         {
            
            $errors[] ="Incorrect AA. No ";
        }

    }             

    if ($row === 8 && $column === 2) {
            // dd($cellValue);
        // $cellValue = trim($cellValue);
        if (empty($cellValue) || $cellValue === "01-01-1970") {
            $errors[] = "Incorrect Date";
        }
        // dd($cellValue);
    }

    if ($row === 9 && $column === 2) {
        $cellValue = trim($cellValue);
        if (!is_numeric($cellValue) || empty($cellValue)) {
            $errors[] ="Incorrect AA Amount";
        }
    }
        

    if ($row === 10 && $column === 2)
     {
        $cellValue = trim($cellValue);
        if (empty($cellValue) || is_numeric($cellValue)) 
        {
            $errors[] ="Incorrect Authority";
        }
    }
    

    if ($row === 11 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        // if (empty($cellValue)) 
        // {
        //     $errors[] ="Incorrect TS. No ";
        // }
    }             

    if ($row === 12 && $column === 2) 
    {
    
    //     if (empty($cellValue) || $cellValue === "01-01-1970") 
    //     {
    //         $errors[] =" InCorrect TS Date";
    //     }
    //         // dd($cellValue);

    }    
    
    if ($row === 13 && $column === 2) {
        $cellValue = trim($cellValue);
        // if (!is_numeric($cellValue) || empty($cellValue)) {
        //     $errors[] ="Incorrect TS Amount";
        // }
    }

    if ($row === 14 && $column === 2) 
    {
        $cellValue = trim($cellValue);
    // if (empty($cellValue) || is_numeric($cellValue)) 
    //     {
    //         $errors[] ="Incorrect TS. Authority ";
    //     }
    }             

    if ($row === 15 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (empty($cellValue) || is_numeric($cellValue)) 
        {
            $errors[] ="Incorrect Agency Name ";
        }
    }             

    if ($row === 16 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (!empty($cellValue) && !is_numeric($cellValue))
         {
            $errors[] = "Incorrect Amount put to Tender";
        }
            }             

    if ($row === 17 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (empty($cellValue) || is_numeric($cellValue)) 
        {
             $errors[] ="Incorrect Above / Below ";
        }
    }             

    if ($row === 18 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        // dd($cellValue);
        if ($cellValue === '' || !is_numeric($cellValue)) 
        {
            $errors[] = "Incorrect Above / Below Percent";
        }
            }             

    if ($row === 19 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (empty($cellValue)) 
        {
             $errors[] ="Incorrect WO. No ";
        }
    }             

    if ($row === 20 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (empty($cellValue) || $cellValue === "01-01-1970") 
        {
            $errors[] ="Incorrect WO. Date ";
        }
    }             

    if ($row === 23 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if ($cellValue === "02-01-1970") 
        {
            $errors[] ="Incorrect Agree. Date ";
        }
    }             


    if ($row === 24 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
            $errors[] ="Incorrect Taluka ";
        }
    }             

    if ($row === 25 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
             $errors[] ="Incorrect Ps constituency ";
        }
    }        
    
    

    if ($row === 26 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
            $errors[] ="Incorrect Zp constituency ";
        }
    }             

    if ($row === 27 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
            $errors[] ="Incorrect Village ";
        }
    }             

    if ($row === 28 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
             $errors[] ="Incorrect E.E. Name ";
        }
    }             

    if ($row === 29 && $column === 2) 
    {
        $cellValue = trim($cellValue);  
        if (is_numeric($cellValue))  
        {   
             $errors[] ="Incorrect Dy.E. Name ";
        }    
    }             

    if ($row === 30 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
             $errors[] ="Incorrect S.O. Name ";
        }
    }             

    if ($row === 31 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
             $errors[] ="Incorrect S.D.C. Name ";
        }
    }             

    if ($row === 32 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
             $errors[] ="Incorrect P.O. Name ";
        }
    }             

    if ($row === 33 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
             $errors[] ="Incorrect Auditor Name ";
        }
    }             

    if ($row === 34 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        if (is_numeric($cellValue)) 
        {
             $errors[] ="Incorrect Accountant Name ";
        }
    }             

    if ($row === 35 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        // if (empty($cellValue) || is_numeric($cellValue)) 
        // {
        //      $errors[] ="Incorrect SSR Year ";
        // }
    }             

    if ($row === 36 && $column === 2) 
    {
        $cellValue = trim($cellValue);
        // if (empty($cellValue) || is_numeric($cellValue)) 
        // {
        //      $errors[] ="Incorrect Area of Work ";
        // }
    }       
    
    if (!empty($errors)) {
        $allErrors = array_merge($allErrors, $errors);
    }

    $rowData[] = $cellValue;
}

$dataSheet2[] = $rowData;
}
// dd($allErrors);

// Display all accumulated errors in a single alert
if (!empty($allErrors)) {
    // Return errors as JSON response
    header('Content-Type: application/json');
    echo json_encode(['errorssheet2' => $allErrors,]);
    exit;
}
$MDivision= $dataSheet2[0][1];
// dd($MDivision);
$MSub_Division= $dataSheet2[1][1];
// dd($MSub_Division);
$MType_of_Work= $dataSheet2[2][1];
// dd($MType_of_Work);
$MName_of_Work= $dataSheet2[3][1];
$MName_work_marathi = $dataSheet2[4][1];
$Mfund_head = $dataSheet2[5][1];
$MAANO = $dataSheet2[6][1];
// dd($MAANO);
$MAAdate = $dataSheet2[7][1];
// dd($MAAdate);
if (is_numeric($MAAdate)) { // Replace with your actual Excel date serial number
    $excelBaseDate = new DateTime('1900-01-01');
    // Calculate the number of days from the Excel base date
    $daysFromBaseDate = $MAAdate - 1; // Excel is 1-based, so subtract 1
    // Create a new DateTime object using the calculated date
    $dateTimeObject = clone $excelBaseDate;
    $dateTimeObject->modify("+$daysFromBaseDate days");
    // Subtract one day to account for the Excel quirk
    $dateTimeObject->modify("-1 day");
    // Format the date as needed
    $MAAdateFormatted = $dateTimeObject->format('Y-m-d');
    // dd($MAAdateFormatted);
} 
else 
{
    // Attempt to create a DateTime object using the format 'd/m/Y'
    $dateTimeObject = DateTime::createFromFormat('d/m/Y', $MAAdate);
    // Check if the conversion was successful
    if ($dateTimeObject !== false) {
        // Format the date as 'Y-m-d'
        $MAAdateFormatted = $dateTimeObject->format('Y-m-d');
        // dd($MAAdateFormatted);
    } else {
        // Handle the case where the conversion failed
        // dd("Invalid date format");
    }
}
// dd($MAAdateFormatted);
$MAAAmount = $dataSheet2[8][1];
// dd($MAAAmount);
$MAAAuthority = $dataSheet2[9][1];
// dd($MAAAuthority);
$MTS_NO = $dataSheet2[10][1];
$MTS_date = $dataSheet2[11][1];
// dd($MTS_date);

if (!empty($MTS_date)) {

    if (is_numeric($MTS_date)) { 
        // Replace with your actual Excel date serial number
        $excelBaseDate = new DateTime('1900-01-01');
        // Calculate the number of days from the Excel base date
        $daysFromBaseDate = $MTS_date - 1; // Excel is 1-based, so subtract 1
        // Create a new DateTime object using the calculated date
        $dateTimeObject = clone $excelBaseDate;
        $dateTimeObject->modify("+$daysFromBaseDate days");
        // Subtract one day to account for the Excel quirk
        $dateTimeObject->modify("-1 day");
        // Format the date as needed
        $MTSdateFormatted = $dateTimeObject->format('Y-m-d');
    } 
    else {
        // Attempt to create a DateTime object using the format 'd/m/Y'
        $dateTimeObject = DateTime::createFromFormat('d/m/Y', $MTS_date);
        // Check if the conversion was successful
        if ($dateTimeObject !== false) {
            // Format the date as 'Y-m-d'
            $MTSdateFormatted = $dateTimeObject->format('Y-m-d');
        } else {
            // If conversion fails, set $MTSdateFormatted to null
            $MTSdateFormatted = null;
        }
    }
} else {
    // If $MTS_date is empty, set $MTSdateFormatted to null
    $MTSdateFormatted = null;
}

                // dd($MTSdateFormatted);


                
// dd($MTSdateFormatted);
$MTS_Amount = $dataSheet2[12][1];
if (!empty($MTS_Amount)) {
    $MTS_Amount = $MTS_Amount; // Assign $MTS_date to $MAAdate
} else {
    $MTS_Amount = '0.00'; // Assign an empty string to $MAAdate if $MTS_date is empty or null
}

$MTS_Authority = $dataSheet2[13][1];
// dd($MTS_Amount,$MTS_Authority);
$MAgency = $dataSheet2[14][1];
$MAmount_Put_Tender = $dataSheet2[15][1];
$MAbove_Below= $dataSheet2[16][1];
$MAbove_Below_percent = $dataSheet2[17][1];
$MWO_no = $dataSheet2[18][1];
$MWO_date = $dataSheet2[19][1];
if (is_numeric($MWO_date)) { // Replace with your actual Excel date serial number
    $excelBaseDate = new DateTime('1900-01-01');
    // Calculate the number of days from the Excel base date
    $daysFromBaseDate = $MWO_date - 1; // Excel is 1-based, so subtract 1
    // Create a new DateTime object using the calculated date
    $dateTimeObject = clone $excelBaseDate;
    $dateTimeObject->modify("+$daysFromBaseDate days");
    // Subtract one day to account for the Excel quirk
    $dateTimeObject->modify("-1 day");
    // Format the date as needed
    $MWOdateFormatted = $dateTimeObject->format('Y-m-d');
    // dd($MAAdateFormatted);
} 
else 
{
    // Attempt to create a DateTime object using the format 'd/m/Y'
    $dateTimeObject = DateTime::createFromFormat('d/m/Y', $MWO_date);
    // Check if the conversion was successful
    if ($dateTimeObject !== false) {
        // Format the date as 'Y-m-d'
        $MWOdateFormatted = $dateTimeObject->format('Y-m-d');
        // dd($MAAdateFormatted);
    } else {
        // Handle the case where the conversion failed
        // dd("Invalid date format");
    }
}

// dd($MTS_date,$MAAdate,$MWO_date);
// $MWOdateFormatted = date('Y-m-d', strtotime($MWO_date));
// $originalDatedddd = \DateTime::createFromFormat('d/m/Y', $MWO_date);
// $MWOdateFormatted = $originalDatedddd->format('Y-m-d');
// dd($MWOdateFormatted);

// dd($MWOdateFormatted);
$MWO_Amounts = $dataSheet2[20][1];
//dd($MWO_Amounts);
$MWO_Amount = ($MWO_Amounts === "" || $MWO_Amounts === null) ? 0.00 : $MWO_Amounts;

$MAgree_no=$dataSheet2[21][1];
// dd($MAgree_no);
$MAgree_Dt=$dataSheet2[22][1];
//  dd($MAgree_Dt);
$MAgreedateFormatted = date('Y-m-d', strtotime($MAgree_Dt));
// dd($MAgreedateFormatted);
$MTaluka = $dataSheet2[23][1];
$MPS_consti=$dataSheet2[24][1];
// dd($MPS_consti);
$MZP_consti=$dataSheet2[25][1];
// dd($MZP_consti);

$Mvillage = $dataSheet2[26][1];
// dd($Mvillage);
$MEE_Name = $dataSheet2[27][1];
$MDy_E_Name = $dataSheet2[28][1];
$MJE_Name = $dataSheet2[29][1];
// dd($MJE_Name);
$MSDC_Name = $dataSheet2[30][1];
$MPO_Name = $dataSheet2[31][1];
$MAuditor_Name = $dataSheet2[32][1];
// dd($MAuditor_Name);
$MAccountant_Name = $dataSheet2[33][1];
// dd($MAccountant_Name);
$MSSR_Year = $dataSheet2[34][1];
// dd($MSSR_Year);
$MArea_Work = $dataSheet2[35][1];
// dd($MArea_Work);

//comparison of $MSub_Division and Subdivms(table)Sub_Div_Id field
    $DBDiv_Id = DB::table('divisions')->where('div', $MDivision)->value('div_id');
    // dd($DBDiv_Id);
// dd($MSub_Division);
//creating Subdiv_id
    $DBSub_Div_Id = DB::table('subdivms')->where('Sub_Div', $MSub_Division)
   ->where('Div_Id',$DBDiv_Id)
   ->value('Sub_Div_Id');
    // dd($DBSub_Div_Id);


// dd($MAAdate) this date have in dd-mm-yy that convert into yy-mm-dd then query run
// $MAAdateFormatted = date('Y-m-d', strtotime($MAAdate));
//  dd($MAAdateFormatted);
$DBacYrId = DB::table('acyrms')
    ->whereDate('Yr_St', '<=', $MAAdateFormatted)
    ->whereDate('Yr_End', '>=', $MAAdateFormatted)
    ->value('Ac_Yr_Id');
// dd($DBacYrId);

//concatinate subdiv and acYrId//
$concatenatedValue = $DBSub_Div_Id . $DBacYrId;
// dd($concatenatedValue);

// creating WorkId //
        // $maxWorkId = DB::table('workmasters')
        // ->orderBy('Work_Id', 'desc')
        // ->orderBy('created_at', 'desc')
        // ->value('Work_Id');
    

        $maxWorkId = DB::table('workmasters')
        ->where('Sub_Div_Id', $DBSub_Div_Id)
        ->where('Ac_Yr_Id', $DBacYrId)
        ->max('Work_Id');
    // dd($maxWorkId);

// $DBWork_Id = $concatenatedValue . str_pad((int)substr($maxWorkId, -6) + 1, 6, '0', STR_PAD_LEFT);
if ($maxWorkId !== null) 
{
    // If max value is found, increment it
    $DBWork_Id = $concatenatedValue . str_pad((int)substr($maxWorkId, -6) + 1, 6, '0', STR_PAD_LEFT);
} else {
    // If max value is not found, set a default value
    $DBWork_Id = $concatenatedValue . '000001';
}
// dd($maxWorkId,$DBWork_Id);
// dd($DBWork_Id);

session(['workId' => $DBWork_Id]);
// $this->reader($file, $DBWork_Id);



//craeting Agency_Id on agencies table
$MAgency = trim($MAgency);
//  dd($MAgency);
$DBAgency_Id=DB::table('agencies')
->where('agency_nm',$MAgency)
->value('id');
//dd($MAgency,$DBAgency_Id);
$DBAgency_Id = $DBAgency_Id !== null ? $DBAgency_Id :  0;
// dd($DBAgency_Id);                       
//craeting Tal_Id 
$MTaluka = trim($MTaluka); // Apply trim() to $MTaluka
// dd($MTaluka);
$DBDist_ID= DB::table('divisions')
->where('div', $MDivision)
->value('dist_id');
// dd($DBDist_ID);
$DBTal_Id = DB::table('talms')
->where('Tal', $MTaluka)
->where('Dist_Id',$DBDist_ID)
->value('Tal_Id');
$DBTal_Id = $DBTal_Id !== null ? $DBTal_Id : '';
// dd($DBTal_Id);

$MPS_consti=$dataSheet2[24][1];
//  dd($MPS_consti);
$DBpsId=DB::table('psconsts')
->where('PS_Con',$MPS_consti)
->value('PS_Con_Id');
//  dd($DBpsId);
 $DBpsId=$DBpsId !==null ? $DBpsId : '';
//  dd($DBpsId);

$MZP_consti=$dataSheet2[25][1];
// dd($MZP_consti);
$DBzpId=DB::table('zpconsts')
->where('ZP_Con',$MZP_consti)
->value('ZP_Con_Id');
// dd($DBzpId);
$DBzpId=$DBzpId !== null ? $DBzpId  :'';
// dd($DBzpId);



//creating Village id//
$Mvillage = trim($Mvillage); // Apply trim() to $Mvillage
// dd($Mvillage);
$DBVillage_id = DB::table('villagemasters')
    ->where('Village', $Mvillage)
    ->value('Village_Id');
$DBVillage_id = $DBVillage_id !== null ? $DBVillage_id : '';
// dd($DBVillage_id);

//create EE_id
// dd($MEE_Name);
$MEE_Name = trim($MEE_Name);
$DBEE_id = null;

// Check if $MEE_Name is not empty
if (!empty($MEE_Name)) {
    $DBEE_id = DB::table('eemasters')
        ->where('name', $MEE_Name)
        ->value('eeid');
}

// Check if $DBEE_id is null and set it to an empty string if needed
if ($DBEE_id === null) 
{
    $DBEE_id = ''; // Set it to an empty string or any other default value as needed
}
// dd($DBEE_id);


//create DYE_id 
$MDy_E_Name = trim($MDy_E_Name); // Apply trim() to $MDy_E_Name
// dd($MDy_E_Name);
$DBDYE_id = null;

// Check if $MDy_E_Name is not empty
if (!empty($MDy_E_Name)) {
    $DBDYE_id = DB::table('dyemasters')
        ->where('name', $MDy_E_Name)
        ->value('dye_id');
}

// Check if $DBDYE_id is null and set it to an empty string if needed
if ($DBDYE_id === null) {
    $DBDYE_id = ''; // Set it to an empty string or any other default value as needed
}// dd($DBDYE_id);

//create SO_id in jemaster table

$MJE_Name=trim($MJE_Name);
$DBJE_id = null;

// Check if $MJE_Name is not empty
if (!empty($MJE_Name)) {
    $DBJE_id = DB::table('jemasters')
        ->where('name', $MJE_Name)
        ->value('jeid');
}

// Check if $DBJE_id is null and set it to an empty string if needed
if ($DBJE_id === null) {
    $DBJE_id = ''; // Set it to an empty string or any other default value as needed
}
// dd($DBJE_id);

//create SDC_ID in sdcmasters table

$MSDC_Name=trim($MSDC_Name);
$DBSDC_Id = null;

// Check if $MSDC_Name is not empty
if (!empty($MSDC_Name)) {
    $DBSDC_Id = DB::table('sdcmasters')
        ->where('name', $MSDC_Name)
        ->value('SDC_id');
}
// Check if $DBSDC_Id is null and set it to an empty string if needed
if ($DBSDC_Id === null) {
    $DBSDC_Id = ''; // Set it to an empty string or any other default value as needed
}

//create PB_ID in pbmasters table

$MPO_Name=trim($MPO_Name);
$DBPB_Id = null;

// Check if $MPO_Name is not empty
if (!empty($MPO_Name))
 {
    $DBPB_Id = DB::table('pbmasters')
        ->where('name', $MPO_Name)
        ->value('PB_Id');
}
// Check if $DBPB_Id is null and set it to an empty string if needed
if ($DBPB_Id === null) 
{
    $DBPB_Id = ''; // Set it to an empty string or any other default value as needed
}//   dd($DBPB_Id);

//create AB_ID in abmasters table
// dd($MAuditor_Name);
$MAuditor_Name=trim($MAuditor_Name);
$DBAB_Id = null;

// dd($MAuditor_Name);
if (!empty($MAuditor_Name)) 
{
    $DBAB_Id = DB::table('abmasters')
        ->where('name', $MAuditor_Name)
        ->value('AB_Id');
}

// Check if $DBAB_Id is null and set it to an empty string if needed
if ($DBAB_Id === null) {
    $DBAB_Id = ''; // Set it to an empty string or any other default value as needed
}
// dd($DBAB_Id);
//create DAO_ID in daomasters table
// dd($MAccountant_Name);
$MAccountant_Name=trim($MAccountant_Name);
$DBDAO_Id=DB::table('daomasters')
->where('name',$MAccountant_Name)
->value('DAO_id');
// dd($DBDAO_Id);
$DBDAO_Id = $DBDAO_Id !== null ? $DBDAO_Id : '';
// dd($Mfund_head);
// dd($DBWork_Id);
$insertData = [
'Work_Id'=>$DBWork_Id,
'Div_Id'=>$DBDiv_Id,
'Div'=>$MDivision,
'Sub_Div_Id'=>$DBSub_Div_Id,
'Sub_Div' => $MSub_Division,
'Work_Type' => $MType_of_Work,
'Work_Nm' => $MName_of_Work,
'Work_Nm_M' => $MName_work_marathi,
'F_H_Code' => $Mfund_head,
'AA_No' => $MAANO,
'AA_Dt' => $MAAdateFormatted, // Use the formatted date
'AA_Amt' => $MAAAmount,
'AA_Authority' => $MAAAuthority,
'TS_No'=>$MTS_NO,
'TS_Dt'=>$MTSdateFormatted,
'TS_Amt'=> $MTS_Amount,
'TS_Authority'=>$MTS_Authority,
'Agency_Id'=>$DBAgency_Id,
'Agency_Nm'=>$MAgency,
'Tnd_Amt'=>$MAmount_Put_Tender,
'Above_Below'=>$MAbove_Below,
'A_B_Pc'=>$MAbove_Below_percent,
'WO_No'=> $MWO_no,
'Wo_Dt'=>$MWOdateFormatted,
'WO_Amt'=> $MWO_Amount,
'Agree_No'=>$MAgree_no,
'Agree_Dt'=>$MAgreedateFormatted,
'Tal_Id' => $DBTal_Id, 
'Tal'=> $MTaluka,
'Ps_Consti'=>$DBpsId,
'Zp_Consti'=>$DBzpId,
'Village_ID' => $DBVillage_id,
// 'Village'=>$Mvillage,
'EE_id' => $DBEE_id,
// 'EE_name'=> $MEE_Name,
'DYE_id' => $DBDYE_id,
// 'DYE_name'=>$MDy_E_Name,
'jeid' => $DBJE_id,
// 'JE_name'=> $MJE_Name,
'SDC_id' => $DBSDC_Id,
// 'SDC_name' => $MSDC_Name,
'PB_Id' => $DBPB_Id,
// 'PB_name'=>$MPO_Name,
'AB_Id' => $DBAB_Id,
// 'AB_Name'=> $MAuditor_Name,
'DAO_Id' => $DBDAO_Id, 
// 'DAO_Name'=>$MAccountant_Name,
'SSR_Year'=>$MSSR_Year,
'Work_Area'=>$MArea_Work,
'Ac_Yr_Id'=>$DBacYrId
];
 //dd($insertData);
// Insert the data into the 'workmasters' table
DB::table('tempsheet2excels')->insert($insertData);  


DB::table('workmasters')->insert($insertData);       
// Delete the inserted data from the 'tempsheet2excels' table
DB::table('tempsheet2excels')->delete();
// Return the processed data as JSON response
// header('Content-Type: application/json');
// echo json_encode(['sheet2' => $insertData]);
// exit;

// return response()->json(['Sheet2' => $insertData]);  

// $insertDatasheet1=$this->reader($file);
// $insertDatasheet2=$this->readersheet2($file);
// dd($insertDatasheet1);
//header('Content-Type: application/json');
// echo json_encode([
//     'Sheet2' => $insertData
// ]);
// exit;
return $insertData;
// return ['insertData' => $insertData, 'DBWork_Id' => $DBWork_Id];
}




public function readersheet4($file)
{
    // dd('ok');
    $excel = IOFactory::load($file);
    $sheet = $excel->getSheet(3);

    // Define the range of columns from CA (column index 79) to CH (column index 87)
    $startColumnIndex = 79;
    $endColumnIndex = 87;

    // Get the highest row in the worksheet
    $highestRow = $sheet->getHighestRow();

    // Initialize an array to store all rows of data
    $allRowsData = [];

    // Loop through all the rows (starting from row 1)
    for ($row = 2; $row <= $highestRow; $row++) {
        $rowData = [];

        // Loop through the selected columns (CA to CH)
        for ($column = $startColumnIndex; $column <= $endColumnIndex; $column++) {
            // Get the cell value and replace null with a space
            $cellValue = $sheet->getCellByColumnAndRow($column, $row)->getValue();
            $cellValue = is_null($cellValue) ? ' ' : $cellValue;
            $rowData[] = $cellValue; // Add the cell value to the $rowData array

        }

        // Add the rowData array to the allRowsData array
        $allRowsData[] = $rowData;
        // dd($allRowsData);
    }

    // dd($allRowsData); // Uncomment if you want to check the content of the $allRowsData array
    // return $allRowsData;
    // $workId = DB::table('workmasters')->max('Work_Id');
    // $workId = DB::table('workmasters')
    // ->orderBy('Work_Id', 'desc')
    // ->orderBy('created_at', 'desc')
    // ->value('Work_Id');
    
        $workId = session('workId');


    $dbItems = DB::table('tnditems')
    ->where('work_Id',$workId)
    ->get(['t_item_id', 't_item_no', 'sub_no']); // Get data from database
// dd($dbItems);
    // dd($WorkId);

    $matchedItems = [];

    foreach ($allRowsData as $row) {
        $itemNumberCA = $row[0];
        $subNumberCB = trim($row[1]); // Remove leading and trailing spaces
        $Item_id=$row[2];
        // dd($Item_id);
        $Mat_id=$row[3];
        $Material=$row[4];
        $Pc_Qty=$row[5];
        $Lead=$row[6];
        $Leadcharge=$row[7];
        $Mat_unit=$row[8];


        
        // dd("Comparing ItemNo: $itemNumberCA with SubNo: $subNumberCB");

        // Check if the values are not empty or spaces
        if (!empty($itemNumberCA))
         {
            $matchingItem = $dbItems->first(function ($item) use ($itemNumberCA, $subNumberCB) 
            {
                return $item->t_item_no == $itemNumberCA && ($subNumberCB === '' || $item->sub_no == $subNumberCB);
            });

            if ($matchingItem) {
                $matchedItemId = $matchingItem->t_item_id;

                $matchedItems[] = [
                    'Work_Id'=>$workId,
                    't_item_id' => $matchedItemId,
                    't_item_no' => $itemNumberCA,
                    'sub_no' => $subNumberCB,
                    'item_id' => $Item_id,
                    'mat_id' =>  $Mat_id,
                    'material' => $Material,
                    'pc_qty' => $Pc_Qty,
                    'lead' => $Lead,
                    'lead_charge' => $Leadcharge,
                    'mat_unit' => $Mat_unit,

                    
                ];
            }
            
            }
        }

    // Insert data from matchedItems array into itemcons table
    foreach ($matchedItems as $matchedItem) {
    DB::table('tempitemcons')->insert($matchedItem);
    DB::table('itemcons')->insert($matchedItem);
    DB::table('tempitemcons')->delete();
    }
     //dd($matchedItems);
    // return $matchedItems; 
    
    return response()->json(['dataSheet4' => $matchedItems]);
    //insert data in table itemcons sheet4                               

}

    
    }



    // public function jsondata()
    // {
    //     $insertDatasheet1=$this->reader($file);
    //     $insertDatasheet2=$this->readersheet2($file);
    //     $insertedDatasheet4=$this->readersheet4($file);
    //     header('Content-Type: application/json');
    //     echo json_encode([
    //         'Sheet1' => $insertDatasheet1,
    //         'Sheet2' => $insertDatasheet2,
    //         'sheet4'=>$insertedDatasheet4
    //     ]);
    //     exit;           
    // }






