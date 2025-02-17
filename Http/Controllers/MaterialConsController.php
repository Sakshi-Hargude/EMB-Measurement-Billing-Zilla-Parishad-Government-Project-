<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Emb;
use App\Models\Workmaster;
use App\Imports\ExcelImport;
use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Illuminate\Support\Facades\Log;

// Your code that uses LocalFilesystemAdapter

// ... your code


// ... your code

// Material consumption controller
class MaterialConsController extends Controller
{
     //material consumption page open
    public function materialcon(Request $request)
    {

    // Retrieve the 'workid' from the request
    $workid = $request->workid;
    // Debugging line to dump and die the value of 'workid'
    // dd($workid);

    // Retrieve the 't_bill_Id' from the request
    $tbillid = $request->t_bill_Id;

    // Return the 'materialcon' view
    return view('materialcon');
  }
    

public function royaltycons(Request $request)
{
    // DB::beginTransaction();

    try{

     // Retrieve 'workid' and 't_bill_Id' from the request
    $workid=$request->workid;
    //dd($workid);
    $tbillid=$request->t_bill_Id;
    //dd($tbillid);
    
     // Fetch 'mbstatus_so' from the 'bills' table
        $mbstatusSo=DB::table('bills')->where('t_bill_Id',$tbillid)->value('mbstatus_so');

    // Update 'mbstatus_so' if the condition is met
     if ($mbstatusSo <= 5) {

    $UpdatembstatusSO=DB::table('bills')
    ->where('work_id',$workid)->update(['mbstatus_so'=>5]);
    // dd($UpdatembstatusSO);
    }

    // Delete existing records from 'royal_d' and 'royal_m' tables
    DB::table('royal_d')->where('t_bill_id' , $tbillid)->delete();
    DB::table('royal_m')->where('t_bill_id' , $tbillid)->delete();

 //dd($matconsd);
  

   // Fetch bill items
   $Billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();

    /// material consumption data added 

     // declared sr no 
    $srno=1;
    // Filter bill items based on 't_item_id' from 'itemcons'
    $filteredBillItems = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->whereIn('t_item_id', function ($query) {
        $query->select('t_item_id')
            ->from('itemcons');
    })
   ->get();
    
   //dd($Billitems , $filteredBillItems);

   $uniquebillitems=$filteredBillItems = DB::table('bil_item')
   ->where('t_bill_id', $tbillid)
   ->whereIn('t_item_id', function ($query) {
       $query->select('t_item_id')
           ->from('itemcons');
   })
  ->get();

   //dd($uniquebillitems);

 $lastSixBillItemId='';
 
 $billrt=null;

 $act_rt = null; // Initialize act_rt variable
 $id=00;
 $Id=00;
 
 //loop data of dsr and billitems data
 foreach($filteredBillItems as $billitem)
{
    //dd($filteredBillItems);
    //$itemid= "0190004340";

    $firstfouricode = '';
    $firsttwoicode = '';
    
     // Fetch dsr data
    $dsrdata = DB::table('dsr')->where('item_id', $billitem->item_id)->first();
    $schitem = $billitem->sch_item;
    
    //echo $billitem->item_id;
    //take sheduled item first four and first two code
    if ($dsrdata) {
        if ($schitem == 1) {
            $firstfouricode = substr($dsrdata->i_code, 0, 4);
            $firsttwoicode = substr($dsrdata->i_code, 0, 2);
        }

        $dsrdata->item = strtoupper($dsrdata->item);
//dd($dsrdata->item);
$firstTwentyChars = substr($dsrdata->item, 0, 20);//dd($leftTwentyChars);

    }
    



  // Check conditions for 'EXCAVATION' and break the loop if met
    if((($firstfouricode == 'BD-A' || $firstfouricode == 'MST1' || $firstfouricode == 'MOST' || $firsttwoicode == 'RD' || $firsttwoicode == 'BR') && strpos($firstTwentyChars, 'EXCAVATION') !== false)
    || (($firsttwoicode == 'RD' || $firstfouricode == 'MST1' || $firstfouricode == 'MOST') && strpos($dsrdata->item, 'EXCAVATION') !== false && strpos($firstTwentyChars, 'CONVEYING') !== false)
    )
    {
    // dd($firstfouricode , $firsttwoicode);
    //echo('ghhfggh');
    break; // Breaks the loop when the condition is met
    }
    else{
    // Fetch item consumption data
    $itemconsdata=DB::table('itemcons')->where('t_item_id' , $billitem->t_item_id)->get();
    //dd($itemconsdata , $billitem->t_item_id);
    //dd($firstfouricode , $dsrdata->i_code);

    //dd($itemconsdata , $billitem->t_item_id);
    $bmatid='';

    //loop in itemconsumption data
    foreach($itemconsdata as $itemacon)
    {


    $matdata=DB::table('mat_mast')->where('mat_id' , $itemacon->mat_id)->first();
    
    if($matdata)
    {
        
     //matquantity calculated
     $matqty = $billitem->exec_qty * $itemacon->pc_qty;
   // dd($matdata);
   //check condition mat_gr,royal rate,Actual rate
   if (($matdata->mat_gr  == 'Quarrying Material') && ($matdata->royal_rt >= 0 && $matdata->act_royal >= 0)) {
   
   //last four mat id
    $lastFourMatId = substr($matdata->mat_id, -4);
   //dd($lastFourMatId);
 //dd($lastSixBillItemId);
//$billrt=null;
if (in_array($lastFourMatId, ['0002', '0895', '1886', '1887'])) {

      
    $firstSixBillItemId = '004349';
    $secondSixBillItemId = '001992';
    
    $firstBillRt = DB::table('bil_item')
        ->where('t_bill_id', $tbillid)
        ->whereRaw("RIGHT(item_id, 6) = ?", [$firstSixBillItemId])
        ->value('bill_rt');
    
    if ($firstBillRt !== null) {
        $billrt = $firstBillRt; // Use the first bill_rt value
        if ($billrt !== null) {
            // Check if 'tmp_mat' table has data for the current 'mat_id'
            $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();

           // $newbmatid=$billitem->b_item_id.$itemacon->mat_id;

          


        //$bmatid=$billitem->b_item_id.$Id;

            if ($tmpmatdata->isEmpty()) {
               
                $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatid=$tbillid.$Id;

                // Insert into 'tmp_mat' table
                DB::table('tmp_mat')->insert([
                    'mat_id' => $matdata->mat_id,
                    'material' => $itemacon->material,
                    't_mat_qty' => $matqty,
                    'mat_rt' =>  $billrt,
                    'mat_unit' => $itemacon->mat_unit,
                    'b_mat_id' => $bmatid
                ]);
        
             // Insert into 'royal_m' table
                DB::table('royal_m')->insert([
                        
                    'work_id' => $workid,
                    't_bill_id' =>  $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    'mat_id' => $itemacon->mat_id,
                    'b_mat_id' => $tbillid.$Id,
                    'material' => $itemacon->material,
                    'royal_m' =>  'R',
                    'sr_no' =>  $srno,
                    'mat_unit' =>$itemacon->mat_unit,
                    'royal_rt' =>$billrt,
                    'royal_amt' => null,
                    'tot_m_qty' =>null,
                ]);
                $srno++;
        
        
            }
            else{
                $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();
    
                $bmatid=$bmatdata->b_mat_id;
               }
        
                // Insert into 'loc_roy' table
            DB::table('loc_roy')->insert([
                'mat_id' => $itemacon->mat_id,
                'material' => $itemacon->material,
                'mat_qty' => $matqty,
                'pc_qty' => $itemacon->pc_qty,
                't_item_id' => $itemacon->t_item_id,
            ]);
        
                //$bmatid=$billitem->b_item_id.$itemacon->mat_id;
                $pcqty=$itemacon->pc_qty;
        
                $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatdid=$bmatid.$id;

                 // Insert into 'royal_d' table
                DB::table('royal_d')->insert([
                    'b_mat_d_id' => $bmatdid,
                    'b_mat_id' => $bmatid,
                    't_bill_id' => $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    't_item_no' => $billitem->t_item_no,
                    'mat_id' =>  $itemacon->mat_id,
                    'sub_no'  => $billitem->sub_no || '',
                    'exs_nm' => $billitem->exs_nm,
                    'exec_qty' => $billitem->exec_qty,
                    'pc_qty' => $itemacon->pc_qty,
                    'mat_qty' => $matqty,
        
                ]);
        
        
                
               
              }
    } else {
          // Fetch 'bill_rt' for the second condition
        $secondBillRt = DB::table('bil_item')
            ->where('t_bill_id', $tbillid)
            ->whereRaw("RIGHT(item_id, 6) = ?", [$secondSixBillItemId])
            ->value('bill_rt');
    
        if ($secondBillRt !== null) {
            $billrt = $secondBillRt; // Use the second bill_rt value
            if ($billrt !== null) {
                // Check if 'tmp_mat' table has data for the current 'mat_id'
                $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();
            
                
            if ($tmpmatdata->isEmpty()) {
               
                $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatid=$tbillid.$Id;

                 // Insert into 'tmp_mat' table
                DB::table('tmp_mat')->insert([
                    'mat_id' => $matdata->mat_id,
                    'material' => $itemacon->material,
                    't_mat_qty' => $matqty,
                    'mat_rt' =>  $billrt,
                    'mat_unit' => $itemacon->mat_unit,
                    'b_mat_id' => $bmatid
                ]);
        
              // Insert into 'royal_m' table
                DB::table('royal_m')->insert([
                        
                    'work_id' => $workid,
                    't_bill_id' =>  $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    'mat_id' => $itemacon->mat_id,
                    'b_mat_id' => $tbillid.$Id,
                    'material' => $itemacon->material,
                    'royal_m' =>  'R',
                    'sr_no' =>  $srno,
                    'mat_unit' =>$itemacon->mat_unit,
                    'royal_rt' =>$billrt,
                    'royal_amt' => null,
                    'tot_m_qty' =>null,
                ]);
                $srno++;
        
        
            }
            else{
                $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();
    
                $bmatid=$bmatdata->b_mat_id;
               }
        
            // Insert into 'loc_roy' table
            DB::table('loc_roy')->insert([
                'mat_id' => $itemacon->mat_id,
                'material' => $itemacon->material,
                'mat_qty' => $matqty,
                'pc_qty' => $itemacon->pc_qty,
                't_item_id' => $itemacon->t_item_id,
            ]);
        
                //$bmatid=$billitem->b_item_id.$itemacon->mat_id;
                $pcqty=$itemacon->pc_qty;
        
                $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatdid=$bmatid.$id;

                // Insert into 'royal_d' table
                DB::table('royal_d')->insert([
                    'b_mat_d_id' => $bmatdid,
                    'b_mat_id' => $bmatid,
                    't_bill_id' => $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    't_item_no' => $billitem->t_item_no,
                    'mat_id' =>  $itemacon->mat_id,
                    'sub_no'  => $billitem->sub_no || '',
                    'exs_nm' => $billitem->exs_nm,
                    'exec_qty' => $billitem->exec_qty,
                    'pc_qty' => $itemacon->pc_qty,
                    'mat_qty' => $matqty,
        
                ]);
        

            
                

            
                    
                   
                  }
        } else {
            // Both conditions failed, handle the scenario here if needed
           break; // Or set a default value
        }
    }
    

} elseif (in_array($lastFourMatId, ['1883', '1884'])) {
     
    $firstSixBillItemId = '003229';// The last six characters to match for item_id
    
    $firstBillRt = DB::table('bil_item')
        ->where('t_bill_id', $tbillid)// Filter by t_bill_id
        ->whereRaw("RIGHT(item_id, 6) = ?", [$firstSixBillItemId])// Filter where the last 6 characters of item_id match $firstSixBillItemId
        ->value('bill_rt'); // Retrieve the bill_rt value
    
    if ($firstBillRt !== null) { // Check if a bill_rt value was found
        $billrt = $firstBillRt; // Use the first bill_rt value
        if ($billrt !== null) {
             // Retrieve all records from tmp_mat where mat_id matches
            $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();
        
           


            $bmatid=$billitem->b_item_id.$Id;
    
          
            if ($tmpmatdata->isEmpty()) { // Check if tmp_mat has no records with the given mat_id
               
                $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT);  // Increment and pad Id
                $bmatid=$tbillid.$Id; // Generate new bmatid

                // Insert a new record into tmp_mat
                DB::table('tmp_mat')->insert([
                    'mat_id' => $matdata->mat_id,
                    'material' => $itemacon->material,
                    't_mat_qty' => $matqty,
                    'mat_rt' =>  $billrt,
                    'mat_unit' => $itemacon->mat_unit,
                    'b_mat_id' => $bmatid
                ]);
        
                // Insert a new record into royal_m
                DB::table('royal_m')->insert([
                        
                    'work_id' => $workid,
                    't_bill_id' =>  $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    'mat_id' => $itemacon->mat_id,
                    'b_mat_id' => $tbillid.$Id,
                    'material' => $itemacon->material,
                    'royal_m' =>  'R',
                    'sr_no' =>  $srno,
                    'mat_unit' =>$itemacon->mat_unit,
                    'royal_rt' =>$billrt,
                    'royal_amt' => null,
                    'tot_m_qty' =>null,
                ]);
                $srno++;
        
        
            }
            else{
                 // Retrieve the first record from tmp_mat where mat_id matches
                $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();
    
                $bmatid=$bmatdata->b_mat_id;
               }
        
                 // Insert a new record into loc_roy
            DB::table('loc_roy')->insert([
                'mat_id' => $itemacon->mat_id,
                'material' => $itemacon->material,
                'mat_qty' => $matqty,
                'pc_qty' => $itemacon->pc_qty,
                't_item_id' => $itemacon->t_item_id,
            ]);
        
                //$bmatid=$billitem->b_item_id.$itemacon->mat_id;
                $pcqty=$itemacon->pc_qty;// Set pcqty
        
                $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatdid=$bmatid.$id;

                // Insert a new record into royal_d
                DB::table('royal_d')->insert([
                    'b_mat_d_id' => $bmatdid,
                    'b_mat_id' => $bmatid,
                    't_bill_id' => $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    't_item_no' => $billitem->t_item_no,
                    'mat_id' =>  $itemacon->mat_id,
                    'sub_no'  => $billitem->sub_no || '',
                    'exs_nm' => $billitem->exs_nm,
                    'exec_qty' => $billitem->exec_qty,
                    'pc_qty' => $itemacon->pc_qty,
                    'mat_qty' => $matqty,
        
                ]);
        

            
        

        
                
               
              }
    } else {
        // Exit if no bill_rt is found
           break; // Or set a default value
        
    }
    
  // Additional condition to check for specific last four characters in mat_id
} elseif (in_array($lastFourMatId, ['0001', '0013', '0905', '0012', '0018', '0014', '1396', '1402', '1410', '1415'])) {
//dd($uniquebillitems);
$firstSixBillItemId = '001992';// Update the last six characters to match for item_id
    
$firstBillRt = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->whereRaw("RIGHT(item_id, 6) = ?", [$firstSixBillItemId])// Filter where the last 6 characters of item_id match $firstSixBillItemId
    ->value('bill_rt');// Retrieve the bill_rt value

if ($firstBillRt !== null) {// Check if a bill_rt value was found
    $billrt = $firstBillRt; // Use the first bill_rt value
    if ($billrt !== null) {
          // Retrieve all records from tmp_mat where mat_id matches
        $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();
    

        $bmatid=$billitem->b_item_id.$Id; // Generate bmatid

      
        if ($tmpmatdata->isEmpty()) { // Check if tmp_mat has no records with the given mat_id
               
            $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT); // Increment and pad Id
            $bmatid=$tbillid.$Id; // Generate new bmatid

              // Insert a new record into tmp_mat
            DB::table('tmp_mat')->insert([
                'mat_id' => $matdata->mat_id,
                'material' => $itemacon->material,
                't_mat_qty' => $matqty,
                'mat_rt' =>  $billrt,
                'mat_unit' => $itemacon->mat_unit,
                'b_mat_id' => $bmatid
            ]);
    
    
             // Insert a new record into royal_m
            DB::table('royal_m')->insert([
                    
                'work_id' => $workid,
                't_bill_id' =>  $tbillid,
                'b_item_id' => $billitem->b_item_id,
                'mat_id' => $itemacon->mat_id,
                'b_mat_id' => $tbillid.$Id,
                'material' => $itemacon->material,
                'royal_m' =>  'R',
                'sr_no' =>  $srno,
                'mat_unit' =>$itemacon->mat_unit,
                'royal_rt' =>$billrt,
                'royal_amt' => null,
                'tot_m_qty' =>null,
            ]);
            $srno++; // Increment srno
    
    
        }
        else{
            // Retrieve the first record from tmp_mat where mat_id matches
            $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();

            $bmatid=$bmatdata->b_mat_id;
           }
    
            // Insert a new record into loc_roy
        DB::table('loc_roy')->insert([
            'mat_id' => $itemacon->mat_id,
            'material' => $itemacon->material,
            'mat_qty' => $matqty,
            'pc_qty' => $itemacon->pc_qty,
            't_item_id' => $itemacon->t_item_id,
        ]);
    
            //$bmatid=$billitem->b_item_id.$itemacon->mat_id;
            $pcqty=$itemacon->pc_qty;  // Set pcqty
    
            $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); // Increment and pad id
            $bmatdid=$bmatid.$id; // Generate bmatdid

            // Insert a new record into royal_d
            DB::table('royal_d')->insert([
                'b_mat_d_id' => $bmatdid,
                'b_mat_id' => $bmatid,
                't_bill_id' => $tbillid,
                'b_item_id' => $billitem->b_item_id,
                't_item_no' => $billitem->t_item_no,
                'mat_id' =>  $itemacon->mat_id,
                'sub_no'  => $billitem->sub_no || '',
                'exs_nm' => $billitem->exs_nm,
                'exec_qty' => $billitem->exec_qty,
                'pc_qty' => $itemacon->pc_qty,
                'mat_qty' => $matqty,
    
            ]);
    

    
    
            
           
          }
} else {
   
       break; // Or set a default value
    
}

}


    //dd($matdata);
  } 


 }
    //$matdata=DB::table('mat_mast')->where('t_item_id' , $billitem->t_item_id)

}

}
}

// Summarize material quantities and round them off
$roundof = DB::table('loc_roy')
    ->select('mat_id', DB::raw('SUM(mat_qty) as total_qty'))
    ->groupBy('mat_id')
    ->get();
//dd($roundof);

foreach($roundof as $sum) {
    
    $roundedQty = round($sum->total_qty, 2);// Round the total quantity to 2 decimal places
//dd($roundedQty);
    // Update t_mat_qty in tmp_mat with rounded quantity
    DB::table('tmp_mat')->where('mat_id' , $sum->mat_id)->update([
        't_mat_qty' => $roundedQty,
    ]);

//dd($sum->mat_id , $roundedQty);
    // Update tot_m_qty in royal_m with rounded quantity
    DB::table('royal_m')->where('mat_id' , $sum->mat_id)->update([
        'tot_m_qty' => $roundedQty,
        // You can calculate 'royal_amt' here if needed
    ]);
}

// Delete all records from tmp_mat and loc_roy tables
    DB::table('tmp_mat')->delete();
    DB::table('loc_roy')->delete();
//dd($summedQty);



// //---------------------------------------------------------------------------------------------------------------------------
// //check all process for surcharge royalty items









// Array to store the last 6 digits of item IDs
$lastSixDigits = [];

// Extracting the last 6 digits of each item ID
foreach ($Billitems as $item) {
    $itemId = substr($item->item_id, -6); // Assuming 'itemid' is the column name
    $lastSixDigits[] = $itemId;
}

// Array containing the required last 6 digit values to check
$requiredDigits = ['004346', '004347', '004348'];

// Checking if any required digit is present
$anyDigitPresent = false;
foreach ($requiredDigits as $digit) {
    if (in_array($digit, $lastSixDigits)) {
        $anyDigitPresent = true;
        break;
    }
}

// Outputting the result based on the check



$billrate=null;

$billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();



//dd($anyDigitPresent);


if ($anyDigitPresent) {
 


foreach($filteredBillItems as $billitem)
{

    
    //dd($Billitems);
    //$itemid= "0190004340";
    $firstfouricode = '';
    $firsttwoicode = '';
    
      
        // Retrieve the dsr data for the current bill item
    $dsrdata = DB::table('dsr')->where('item_id', $billitem->item_id)->first();
    $schitem = $billitem->sch_item;
    
   // echo $billitem->item_id;
    
    if ($dsrdata) {
        if ($schitem == 1) {
            // Extract the first four and two characters of i_code
            $firstfouricode = substr($dsrdata->i_code, 0, 4);
            $firsttwoicode = substr($dsrdata->i_code, 0, 2);
        }

          // Convert item to uppercase
        $dsrdata->item = strtoupper($dsrdata->item);
        //dd($dsrdata->item);
        $firstTwentyChars = substr($dsrdata->item, 0, 20);//dd($leftTwentyChars);

    }
    
    
     // Check various conditions on the extracted codes and item description
    if((($firstfouricode == 'BD-A' || $firstfouricode == 'MST1' || $firstfouricode == 'MOST' || $firsttwoicode == 'RD' || $firsttwoicode == 'BR') && strpos($firstTwentyChars, 'EXCAVATION') !== false)
    || (($firsttwoicode == 'RD' || $firstfouricode == 'MST1' || $firstfouricode == 'MOST') && strpos($dsrdata->item, 'EXCAVATION') !== false && strpos($firstTwentyChars, 'CONVEYING') !== false)
    )
  {
    //dd($firstfouricode , $firsttwoicode);
   //echo('ghhfggh');
   break; // Breaks the loop when the condition is met
}
else{
  // Retrieve item consumption data
$itemconsdata=DB::table('itemcons')->where('t_item_id' , $billitem->t_item_id)->get();
//dd($itemconsdata , $billitem->t_item_id);
//dd($firstfouricode , $dsrdata->i_code);

 //dd($itemconsdata , $billitem->t_item_id);
 $bmatid='';

 foreach($itemconsdata as $itemacon)
 {
    $matdata=DB::table('mat_mast')->where('mat_id' , $itemacon->mat_id)->first();

                    $matqty = $billitem->exec_qty * $itemacon->pc_qty;

   // dd($matdata);
   
   if($matdata)
   {
     // Check if the material is of a certain type and has valid rates
   if (($matdata->mat_gr == 'Quarrying Material') && ($matdata->royal_rt >= 0 && $matdata->act_royal >= 0)) {
   
  
    //last four mat id
    $lastFourMatId = substr($matdata->mat_id, -4);
           //dd($lastFourMatId);
         //dd($lastSixBillItemId);
        //$billrt=null;

        //check last four mat id in array last id
    if (in_array($lastFourMatId, ['0002', '0895', '1886', '1887'])) {


   
    $firstSixBillItemId = '004347';
    $secondSixBillItemId = '004346';
    
     // Retrieve the bill rate for the first item ID
    $firstBillRt = DB::table('bil_item')
        ->where('t_bill_id', $tbillid)
        ->whereRaw("RIGHT(item_id, 6) = ?", [$firstSixBillItemId])
        ->value('bill_rt');
    
    if ($firstBillRt !== null) {
        $billrate = $firstBillRt; // Use the first bill_rt value
        if ($billrate !== null) {
            $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();
        
             if ($tmpmatdata->isEmpty()) {
               
                $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatid=$tbillid.$Id;

                 // Insert new material data
                DB::table('tmp_mat')->insert([
                    'mat_id' => $matdata->mat_id,
                    'material' => $itemacon->material,
                    't_mat_qty' => $matqty,
                    'mat_rt' =>  $billrate,
                    'mat_unit' => $itemacon->mat_unit,
                    'b_mat_id' => $bmatid
                ]);
        
                 // Insert new royalty data
                DB::table('royal_m')->insert([
                        
                    'work_id' => $workid,
                    't_bill_id' =>  $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    'mat_id' => $itemacon->mat_id,
                    'b_mat_id' => $tbillid.$Id,
                    'material' => $itemacon->material,
                    'royal_m' =>  'S',
                    'sr_no' =>  $srno,
                    'mat_unit' =>$itemacon->mat_unit,
                    'royal_rt' =>$billrate,
                    'royal_amt' => null,
                    'tot_m_qty' =>null,
                ]);
                $srno++;
        
        
            }
            else{
                $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();
    
                $bmatid=$bmatdata->b_mat_id;
               }
             // Insert new location royalty data
            DB::table('loc_roy')->insert([
                'mat_id' => $itemacon->mat_id,
                'material' => $itemacon->material,
                'mat_qty' => $matqty,
                'pc_qty' => $itemacon->pc_qty,
                't_item_id' => $itemacon->t_item_id,
            ]);
        
                //$bmatid=$billitem->b_item_id.$itemacon->mat_id;
                $pcqty=$itemacon->pc_qty;
        
                $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatdid=$bmatid.$id;
                  // Insert new detailed royalty data
                DB::table('royal_d')->insert([
                    'b_mat_d_id' => $bmatdid,
                    'b_mat_id' => $bmatid,
                    't_bill_id' => $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    't_item_no' => $billitem->t_item_no,
                    'mat_id' =>  $itemacon->mat_id,
                    'sub_no'  => $billitem->sub_no || '',
                    'exs_nm' => $billitem->exs_nm,
                    'exec_qty' => $billitem->exec_qty,
                    'pc_qty' => $itemacon->pc_qty,
                    'mat_qty' => $matqty,
        
                ]);
        
                
               
              }
    } else {
         // Retrieve the bill rate for the second item ID
        $secondBillRt = DB::table('bil_item')
            ->where('t_bill_id', $tbillid)
            ->whereRaw("RIGHT(item_id, 6) = ?", [$secondSixBillItemId])
            ->value('bill_rt');
    
        if ($secondBillRt !== null) {
            $billrate = $secondBillRt; // Use the second bill_rt value
            if ($billrate !== null) {
                $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();
            
                if ($tmpmatdata->isEmpty()) {
               
                    $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT); 
                    $bmatid=$tbillid.$Id;
    
                     // Insert new material data
                    DB::table('tmp_mat')->insert([
                        'mat_id' => $matdata->mat_id,
                        'material' => $itemacon->material,
                        't_mat_qty' => $matqty,
                        'mat_rt' =>  $billrate,
                        'mat_unit' => $itemacon->mat_unit,
                        'b_mat_id' => $bmatid
                    ]);
            
                  // Insert new royalty data
                    DB::table('royal_m')->insert([
                            
                        'work_id' => $workid,
                        't_bill_id' =>  $tbillid,
                        'b_item_id' => $billitem->b_item_id,
                        'mat_id' => $itemacon->mat_id,
                        'b_mat_id' => $tbillid.$Id,
                        'material' => $itemacon->material,
                        'royal_m' =>  'S',
                        'sr_no' =>  $srno,
                        'mat_unit' =>$itemacon->mat_unit,
                        'royal_rt' =>$billrate,
                        'royal_amt' => null,
                        'tot_m_qty' =>null,
                    ]);
                    $srno++;
            
            
                }
                else{
                    $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();
        
                    $bmatid=$bmatdata->b_mat_id;
                   }
             // Insert new location royalty data
                DB::table('loc_roy')->insert([
                    'mat_id' => $itemacon->mat_id,
                    'material' => $itemacon->material,
                    'mat_qty' => $matqty,
                    'pc_qty' => $itemacon->pc_qty,
                    't_item_id' => $itemacon->t_item_id,
                ]);
            
                    //$bmatid=$billitem->b_item_id.$itemacon->mat_id;
                    $pcqty=$itemacon->pc_qty;
            
                    $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
                    $bmatdid=$bmatid.$id;

                      // Insert new detailed royalty data
                    DB::table('royal_d')->insert([
                        'b_mat_d_id' => $bmatdid,
                        'b_mat_id' => $bmatid,
                        't_bill_id' => $tbillid,
                        'b_item_id' => $billitem->b_item_id,
                        't_item_no' => $billitem->t_item_no,
                        'mat_id' =>  $itemacon->mat_id,
                        'sub_no'  => $billitem->sub_no || '',
                        'exs_nm' => $billitem->exs_nm,
                        'exec_qty' => $billitem->exec_qty,
                        'pc_qty' => $itemacon->pc_qty,
                        'mat_qty' => $matqty,
            
                    ]);
            
            
                    
                   
                  }
        } else {
            // Both conditions failed, handle the scenario here if needed
           break; // Or set a default value
        }
    }
    
//check last four mat id in given array id
} elseif (in_array($lastFourMatId, ['1883', '1884'])) {
     
  
   // Check if a condition is met (assumed to be set earlier in the code)  
    $firstSixBillItemId = '004348';
    
    //first six bill item id no item id right 6 match then that get bill rate
    $firstBillRt = DB::table('bil_item')
        ->where('t_bill_id', $tbillid)
        ->whereRaw("RIGHT(item_id, 6) = ?", [$firstSixBillItemId])
        ->value('bill_rt');
    
          // Check if a valid bill rate was retrieved
    if ($firstBillRt !== null) {
        $billrate = $firstBillRt; // Use the first bill_rt value

         // Ensure that the bill rate is not null
        if ($billrate !== null) {
            $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();
        
             // Check if the material is already in the temporary materials table
            if ($tmpmatdata->isEmpty()) {
                  // Generate a unique material ID
                $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatid=$tbillid.$Id;
                // Insert new material data into tmp_mat
                DB::table('tmp_mat')->insert([
                    'mat_id' => $matdata->mat_id,
                    'material' => $itemacon->material,
                    't_mat_qty' => $matqty,
                    'mat_rt' =>  $billrate,
                    'mat_unit' => $itemacon->mat_unit,
                    'b_mat_id' => $bmatid
                ]);
        
                 // Insert new royalty data into royal_m
                DB::table('royal_m')->insert([
                        
                    'work_id' => $workid,
                    't_bill_id' =>  $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    'mat_id' => $itemacon->mat_id,
                    'b_mat_id' => $tbillid.$Id,
                    'material' => $itemacon->material,
                    'royal_m' =>  'S',
                    'sr_no' =>  $srno,
                    'mat_unit' =>$itemacon->mat_unit,
                    'royal_rt' =>$billrate,
                    'royal_amt' => null,
                    'tot_m_qty' =>null,
                ]);
                $srno++;
        
        
            }
            else{
                 // Retrieve existing material data from tmp_mat
                $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();
    
                $bmatid=$bmatdata->b_mat_id;
               }
             // Insert new location royalty data into loc_roy
            DB::table('loc_roy')->insert([
                'mat_id' => $itemacon->mat_id,
                'material' => $itemacon->material,
                'mat_qty' => $matqty,
                'pc_qty' => $itemacon->pc_qty,
                't_item_id' => $itemacon->t_item_id,
            ]);
        
                // Generate a unique ID for detailed royalty data
                $pcqty=$itemacon->pc_qty;
        
                $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatdid=$bmatid.$id;
                // Insert new detailed royalty data into royal_d
                DB::table('royal_d')->insert([
                    'b_mat_d_id' => $bmatdid,
                    'b_mat_id' => $bmatid,
                    't_bill_id' => $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    't_item_no' => $billitem->t_item_no,
                    'mat_id' =>  $itemacon->mat_id,
                    'sub_no'  => $billitem->sub_no || '',
                    'exs_nm' => $billitem->exs_nm,
                    'exec_qty' => $billitem->exec_qty,
                    'pc_qty' => $itemacon->pc_qty,
                    'mat_qty' => $matqty,
        
                ]);
        
                
               
              }
    } else {
       
           break; // Or set a default value
        
    }
    
} elseif (in_array($lastFourMatId, ['0001', '0013', '0905', '0012', '0018', '0014', '1396', '1402', '1410', '1415'])) {
  // If the last four digits of material ID match specific values
$firstSixBillItemId = '004346';

     // Retrieve bill rate based on the last 6 digits of item_id
$firstBillRt = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->whereRaw("RIGHT(item_id, 6) = ?", [$firstSixBillItemId])
    ->value('bill_rt');

      // Check if a valid bill rate was retrieved
if ($firstBillRt !== null) {
    $billrate = $firstBillRt; // Use the first bill_rt value

      // Ensure that the bill rate is not null
    if ($billrate !== null) {
        $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();
         // Check if the material is already in the temporary materials table
        if ($tmpmatdata->isEmpty()) {
               
              // Generate a unique material ID
            $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT); 
            $bmatid=$tbillid.$Id;

              // Insert new material data into tmp_mat
            DB::table('tmp_mat')->insert([
                'mat_id' => $matdata->mat_id,
                'material' => $itemacon->material,
                't_mat_qty' => $matqty,
                'mat_rt' =>  $billrate,
                'mat_unit' => $itemacon->mat_unit,
                'b_mat_id' => $bmatid
            ]);
    
           // Insert new royalty data into royal_m
            DB::table('royal_m')->insert([
                    
                'work_id' => $workid,
                't_bill_id' =>  $tbillid,
                'b_item_id' => $billitem->b_item_id,
                'mat_id' => $itemacon->mat_id,
                'b_mat_id' => $tbillid.$Id,
                'material' => $itemacon->material,
                'royal_m' =>  'S',
                'sr_no' =>  $srno,
                'mat_unit' =>$itemacon->mat_unit,
                'royal_rt' =>$billrate,
                'royal_amt' => null,
                'tot_m_qty' =>null,
            ]);
            $srno++;
    
    
        }
        else{
               // Retrieve existing material data from tmp_mat
            $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();

            $bmatid=$bmatdata->b_mat_id;
           }
    
            // Insert new location royalty data into loc_roy
        DB::table('loc_roy')->insert([
            'mat_id' => $itemacon->mat_id,
            'material' => $itemacon->material,
            'mat_qty' => $matqty,
            'pc_qty' => $itemacon->pc_qty,
            't_item_id' => $itemacon->t_item_id,
        ]);
    
             // Generate a unique ID for detailed royalty data
            $pcqty=$itemacon->pc_qty;
    
            $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
            $bmatdid=$bmatid.$id;

            // Insert new detailed royalty data into royal_d
            DB::table('royal_d')->insert([
                'b_mat_d_id' => $bmatdid,
                'b_mat_id' => $bmatid,
                't_bill_id' => $tbillid,
                'b_item_id' => $billitem->b_item_id,
                't_item_no' => $billitem->t_item_no,
                'mat_id' =>  $itemacon->mat_id,
                'sub_no'  => $billitem->sub_no || '',
                'exs_nm' => $billitem->exs_nm,
                'exec_qty' => $billitem->exec_qty,
                'pc_qty' => $itemacon->pc_qty,
                'mat_qty' => $matqty,
    
            ]);
    
    
            
           
          }
} else {
   
       break; // Or set a default value
    
}

}


    //dd($matdata);
  } 


 }
    //$matdata=DB::table('mat_mast')->where('t_item_id' , $billitem->t_item_id)


  }
  
}

}

  // Final rounding and cleanup
$roundof = DB::table('loc_roy')
    ->select('mat_id', DB::raw('SUM(mat_qty) as total_qty'))
    ->groupBy('mat_id')
    ->get();

foreach($roundof as $sum) {
    $roundedQty = round($sum->total_qty, 2); // Round quantity to 2 decimal places

      // Update the quantity in tmp_mat
    DB::table('tmp_mat')->where('mat_id' , $sum->mat_id)->update([
        't_mat_qty' => $roundedQty,
    ]);

      // Update the quantity in royal_m
    DB::table('royal_m')->where('mat_id' , $sum->mat_id)->update([
        'tot_m_qty' => $roundedQty,
        // You can calculate 'royal_amt' here if needed
    ]);
}
   //delete temproary data
    DB::table('tmp_mat')->delete();
    DB::table('loc_roy')->delete();


} 


////DMF royalty items rate----------------------------------------------------------------------------------------------------------------------------------------------------------------------------


// Array to store the last 6 digits of item IDs
$SixDigits = [];

// Extracting the last 6 digits of each item ID
foreach ($Billitems as $item) {
    $itemId = substr($item->item_id, -6); // Assuming 'itemid' is the column name
    $SixDigits[] = $itemId;
}

// Array containing the required last 6 digit values to check
$REQUIREDDigits = ['003940', '003941', '004350'];

// Checking if any required digit is present
$ANYDigitPresent = false;
foreach ($REQUIREDDigits as $digit) {
    if (in_array($digit, $lastSixDigits)) {
        $ANYDigitPresent = true;
        break;
    }
}

// Fetch all bill items for the given bill ID from the 'bil_item' table
$billitemdataDMF=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();



//dd($billitemdataDMF , $filteredBillItems);

// Initialize bill rate variable
$billRt=null;
if ($ANYDigitPresent) {
 

 // Iterate over each filtered bill item
foreach($filteredBillItems as $billitem)
{

    
    //dd($filteredBillItems);
     // Initialize variables to store code substrings
    $firstfouricode = '';
    $firsttwoicode = '';
    
     // Fetch item data from 'dsr' table based on item_id from the bill item
    $dsrdata = DB::table('dsr')->where('item_id', $billitem->item_id)->first();
    $schitem = $billitem->sch_item;
    
   // echo $billitem->item_id;
    // Process 'dsrdata' if it exists
    if ($dsrdata) {
          // Get the first four and two characters of the item code if 'schitem' equals 1
        if ($schitem == 1) {
            $firstfouricode = substr($dsrdata->i_code, 0, 4);
            $firsttwoicode = substr($dsrdata->i_code, 0, 2);
        }

         // Convert item name to uppercase and get the first 20 characters
        $dsrdata->item = strtoupper($dsrdata->item);
//dd($dsrdata->item);
$firstTwentyChars = substr($dsrdata->item, 0, 20);//dd($leftTwentyChars);

    }
    
     // Check conditions to determine if the current bill item should be processed
    if (!(
        ($firstfouricode == 'BD-A' || $firstfouricode == 'MST1' || $firstfouricode == 'MOST' || $firsttwoicode == 'RD' || $firsttwoicode == 'BR')
        && strpos($firstTwentyChars, 'EXCAVATION') !== false
    ) && !(
        ($firsttwoicode == 'RD' || $firstfouricode == 'MST1' || $firstfouricode == 'MOST')
        && strpos($dsrdata->item, 'EXCAVATION') !== false
        && strpos($firstTwentyChars, 'CONVEYING') !== false
    )) {
        // Code for the exact opposite scenario
     // Fetch associated item cons data
    $itemconsdata=DB::table('itemcons')->where('t_item_id' , $billitem->t_item_id)->get();
    //dd($itemconsdata , $billitem->t_item_id);
    //dd($firstfouricode , $dsrdata->i_code);

    //dd($itemconsdata , $billitem->t_item_id);
    $bmatid='';

     // Iterate over each item cons data
    foreach($itemconsdata as $itemacon)
    {
    $matdata=DB::table('mat_mast')->where('mat_id' , $itemacon->mat_id)->first();
    
       // Process material data if it exists
    if($matdata)
    {
        
    
     // Check if the material is 'Quarrying Material' and royalty values are non-negative
   if (($matdata->mat_gr == 'Quarrying Material') && ($matdata->royal_rt >= 0 && $matdata->act_royal >= 0)) {
   
    $matqty = $billitem->exec_qty * $itemacon->pc_qty;
    //dd($matdata);
    $lastFourMatId = substr($matdata->mat_id, -4);
  
    // Determine the bill rate based on material ID
    if (in_array($lastFourMatId, ['0002', '0895', '1886', '1887'])) {

    // echo($lastFourMatId);
        
    //echo($matdata->mat_id);

   
    $firstSixBillItemId = '004350';
    $secondSixBillItemId = '003940';
    
     // Get bill rate for the first condition
    $firstBillRt = DB::table('bil_item')
        ->where('t_bill_id', $tbillid)
        ->whereRaw("RIGHT(item_id, 6) = ?", [$firstSixBillItemId])
        ->value('bill_rt');
    
        
        // echo($firstSixBillItemId);

    if ($firstBillRt !== null) {
        $billRt = $firstBillRt; // Use the first bill_rt value
        if ($billRt !== null) {
            $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();
        
            if ($tmpmatdata->isEmpty()) {
                  // Generate a new material ID
                $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatid=$tbillid.$Id;
               
                 // Insert new material data
                DB::table('tmp_mat')->insert([
                    'mat_id' => $matdata->mat_id,
                    'material' => $itemacon->material,
                    't_mat_qty' => $matqty,
                    'mat_rt' =>  $billRt,
                    'mat_unit' => $itemacon->mat_unit,
                    'b_mat_id' => $bmatid
                ]);
        
                // Insert royalty material data
                DB::table('royal_m')->insert([
                        
                    'work_id' => $workid,
                    't_bill_id' =>  $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    'mat_id' => $itemacon->mat_id,
                    'b_mat_id' => $tbillid.$Id,
                    'material' => $itemacon->material,
                    'royal_m' =>  'D',
                    'sr_no' =>  $srno,
                    'mat_unit' =>$itemacon->mat_unit,
                    'royal_rt' =>$billRt,
                    'royal_amt' => null,
                    'tot_m_qty' =>null,
                ]);
                $srno++;
        
        
            }
            else{
                // Fetch existing temporary material data
                $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();
    
                $bmatid=$bmatdata->b_mat_id;
               }
               // Insert location royalty data
            DB::table('loc_roy')->insert([
                'mat_id' => $itemacon->mat_id,
                'material' => $itemacon->material,
                'mat_qty' => $matqty,
                'pc_qty' => $itemacon->pc_qty,
                't_item_id' => $itemacon->t_item_id,
            ]);
        
                  // Generate a new detail ID and insert royalty detail data
                $pcqty=$itemacon->pc_qty;
        
                $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
                $bmatdid=$bmatid.$id;
                DB::table('royal_d')->insert([
                    'b_mat_d_id' => $bmatdid,
                    'b_mat_id' => $bmatid,
                    't_bill_id' => $tbillid,
                    'b_item_id' => $billitem->b_item_id,
                    't_item_no' => $billitem->t_item_no,
                    'mat_id' =>  $itemacon->mat_id,
                    'sub_no'  => $billitem->sub_no || '',
                    'exs_nm' => $billitem->exs_nm,
                    'exec_qty' => $billitem->exec_qty,
                    'pc_qty' => $itemacon->pc_qty,
                    'mat_qty' => $matqty,
        
                ]);
        
                
               
              }
        
    } else {
           // Get bill rate for the second condition
        $secondBillRt = DB::table('bil_item')
            ->where('t_bill_id', $tbillid)
            ->whereRaw("RIGHT(item_id, 6) = ?", [$secondSixBillItemId])
            ->value('bill_rt');
        if ($secondBillRt !== null) {
            $billRt = $secondBillRt; // Use the second bill_rt value

            //echo($secondSixBillItemId);
            if ($billRt !== null) {

                // Check if temporary material data exists
                $tmpmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->get();
            
              

                if ($tmpmatdata->isEmpty()) {
                // Generate a new material ID
                    $Id = str_pad(intval($Id) + 1, 2, '0', STR_PAD_LEFT); 
                    $bmatid=$tbillid.$Id;
    
                    // Insert new material data
                    DB::table('tmp_mat')->insert([
                        'mat_id' => $matdata->mat_id,
                        'material' => $itemacon->material,
                        't_mat_qty' => $matqty,
                        'mat_rt' =>  $billRt,
                        'mat_unit' => $itemacon->mat_unit,
                        'b_mat_id' => $bmatid
                    ]);
            
                     // Insert royalty material data
                    DB::table('royal_m')->insert([
                            
                        'work_id' => $workid,
                        't_bill_id' =>  $tbillid,
                        'b_item_id' => $billitem->b_item_id,
                        'mat_id' => $itemacon->mat_id,
                        'b_mat_id' => $tbillid.$Id,
                        'material' => $itemacon->material,
                        'royal_m' =>  'D',
                        'sr_no' =>  $srno,
                        'mat_unit' =>$itemacon->mat_unit,
                        'royal_rt' =>$billRt,
                        'royal_amt' => null,
                        'tot_m_qty' =>null,
                    ]);
                    $srno++;
            
            
                }
                else{
                       // Retrieve existing record for the given material ID from 'tmp_mat'
                    $bmatdata = DB::table('tmp_mat')->where('mat_id', $matdata->mat_id)->first();
                      // Assign the existing 'b_mat_id' to $bmatid
                    $bmatid=$bmatdata->b_mat_id;
                   }
                
                   // Insert data into 'loc_roy' table with material details
                DB::table('loc_roy')->insert([
                    'mat_id' => $itemacon->mat_id,
                    'material' => $itemacon->material,
                    'mat_qty' => $matqty,
                    'pc_qty' => $itemacon->pc_qty,
                    't_item_id' => $itemacon->t_item_id,
                ]);
            
                   // Prepare unique ID for 'royal_d' table by incrementing $id and formatting
                    $pcqty=$itemacon->pc_qty;
            
                    $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
                    $bmatdid=$bmatid.$id;
                    // Insert data into 'royal_d' table with material details and quantities
                    DB::table('royal_d')->insert([
                        'b_mat_d_id' => $bmatdid,
                        'b_mat_id' => $bmatid,
                        't_bill_id' => $tbillid,
                        'b_item_id' => $billitem->b_item_id,
                        't_item_no' => $billitem->t_item_no,
                        'mat_id' =>  $itemacon->mat_id,
                        'sub_no'  => $billitem->sub_no || '',
                        'exs_nm' => $billitem->exs_nm,
                        'exec_qty' => $billitem->exec_qty,
                        'pc_qty' => $itemacon->pc_qty,
                        'mat_qty' => $matqty,
            
                    ]);
            
                    
                   
                  }
            

        } else {
            // Both conditions failed, handle the scenario here if needed
           break; // Or set a default value
        }
    }
    
 
    


} 
 

    //dd($matdata);
  } 


 }
    //$matdata=DB::table('mat_mast')->where('t_item_id' , $billitem->t_item_id)
}

}
}
// Processing continues outside the loop
// Aggregates material quantities and updates rounded quantities in 'tmp_mat' and 'royal_m' tables
$roundof = DB::table('loc_roy')
    ->select('mat_id', DB::raw('SUM(mat_qty) as total_qty'))
    ->groupBy('mat_id')
    ->get();

foreach($roundof as $sum) {
    $roundedQty = round($sum->total_qty, 2);

      // Update quantities in 'tmp_mat' and 'royal_m' tables
    DB::table('tmp_mat')->where('mat_id' , $sum->mat_id)->update([
        't_mat_qty' => $roundedQty,
    ]);

    DB::table('royal_m')->where('mat_id' , $sum->mat_id)->update([
        'tot_m_qty' => $roundedQty,
        // You can calculate 'royal_amt' here if needed
    ]);
}

// Clean up temporary tables after processing
    DB::table('tmp_mat')->delete();
    DB::table('loc_roy')->delete();


} 

// Calculate and update royalty amounts in 'royal_m'
$royalamts=DB::table('royal_m')->where('t_bill_id' , $tbillid)->get();
foreach($royalamts as $royalamt)
{
$royamt=$royalamt->tot_m_qty*$royalamt->royal_rt;

 // Update royalty amount for each entry in 'royal_m'
DB::table('royal_m')->where('b_mat_id' , $royalamt->b_mat_id)->update([
    'royal_amt' => $royamt
]);
}

// Retrieve royalty data for view
$royalm=DB::table('royal_m')->where('t_bill_id' , $tbillid)->get();

if ($royalm->isEmpty()) 
{
   // dd($royalm);
    // If $royalm is empty, display an alert and return the view
    alert()->info('No Royalty Data Found', 'No royalty item is present in the list.');
    return back();
}

// Retrieve the first record from 'royal_m' and related data from 'royal_d'
$royalmfirst=DB::table('royal_m')->where('t_bill_id' , $tbillid)->first();


$royald=DB::table('royal_d')->where('t_bill_id' , $tbillid)->where('b_mat_id' , $royalmfirst->b_mat_id)->first();
//dd($royald);

$royaldfirst=DB::table('royal_d')->where('b_mat_id' , $royald->b_mat_id)->get();

// DB::commit();

return view('Royalconsumption' , compact('royalm' , 'royald' , 'royalmfirst' , 'royaldfirst' , 'workid','tbillid'));

 }
 // Exception handling for errors during processing
 catch(\Exception $e)
    {
        // Log the error and redirect with an error message
        Log::error('An error Occurr during Create royalty consumption' . $e->getMessage());

        return redirect()->back()->with(['error' => 'An error Occurr during Create royalty consumption']);
    }
}

      




     //Update Material consumption data
    public function updatematerialcon(Request $request)
    {
        // DB::beginTransaction();
         try{

             // Retrieve work ID and bill ID from the request
        $workid=$request->workid;
        //dd($workid);
        $tbillid=$request->t_bill_Id;
        //dd($tbillid);

        // Fetch the current 'mbstatus_so' value from the 'bills' table for the given bill ID
        $mbstatusSo=DB::table('bills')->where('t_bill_Id',$tbillid)->value('mbstatus_so');

        // If 'mbstatus_so' is less than or equal to 1, update it to 1 for the given work ID and bill ID
        if ($mbstatusSo <= 2) 
        {
        $updatembstatusSO=DB::table('bills')->where('work_id',$workid)->where('t_bill_id',$tbillid)
        ->update(['mbstatus_so' =>2]);
        // dd($updatembstatusSO);
        }

        

        // Fetch material consumption data for the given bill ID
        $matconsdata=DB::table('mat_cons_m')->where('t_bill_id' , $tbillid)->get();


        // DB::table('mat_cons_d')->where('t_bill_id' , $tbillid)->delete();
        // DB::table('mat_cons_m')->where('t_bill_id' , $tbillid)->delete();

        //material consumption data insert function
       $this->commonmaterialconsumption($tbillid , $workid);

       // Fetch updated material consumption data
        $cons_mdata=DB::table('mat_cons_m')->where('t_bill_id' , $tbillid)->get();

         // If no data is found, alert the user and redirect back
        if ($cons_mdata->isEmpty()) {
            // dd($royalm);
         
             // If $royalm is empty, display an alert and return the view
             alert()->info('No material consumption Data Found', 'No material consumption item is present in the list.');
             return back();
         }
         
        // Fetch the first record of material consumption data
        $cons_mdatafirst=DB::table('mat_cons_m')->where('t_bill_id' , $tbillid)->first();
          // Fetch additional material data based on the first record's 'b_mat_id'
        $additionalMaterialData = DB::table('mat_cons_d')->where('b_mat_id', $cons_mdatafirst->b_mat_id)->get();


        
        
        //DB::commit();
       // Return the view with the relevant data
    return view('updatematerial' , compact('workid' , 'cons_mdata' , 'cons_mdatafirst' , 'additionalMaterialData','tbillid'));

        }catch(\Exception $e)
        {
             // Rollback the transaction in case of an error (commented out in this code)
        // DB::rollback();
        // Log the error and return an error message
            Log::error('An error Occurr during Create Material consumption' . $e->getMessage());

           return redirect()->back()->with(['error' => 'An error occurr during create Material consumption' . $e->getMessage()]);
        }
    }



    //materiak consumption data common function
    public function commonmaterialconsumption($tbillid , $workid)
    {

                            $datacons=[];
                    // Fetch bill items for the given bill ID
                    $Billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
                    $id=00;
                    //dd($Billitems);
                    foreach($Billitems as $billitem)
                    {

                       // Check for existing material consumption data
                        $matconsddata=DB::table('mat_cons_d')->where('t_bill_id' , $tbillid)->where('b_item_id' , $billitem->b_item_id)->first();
                            
                       
                            if ($matconsddata !== null) {
                                // Existing data found, update the record
                                if ($matconsddata->b_item_id == $billitem->b_item_id) {
                                    $Matqty = $matconsddata->pc_qty * $billitem->exec_qty;
                                    //dd($billitem);
                                   // dd($matconsddata->A_mat_qty , $billitem->exec_qty);
                                    // Check if exec_qty is greater than 0 to avoid division by zero
                                        if ($billitem->exec_qty > 0) {
                                            $apcqty = $matconsddata->A_mat_qty / $billitem->exec_qty;
                                        } else {
                                            // Handle the case where exec_qty is 0
                                            $apcqty = 0; // or any default value you want to use
                                        }
                        
                                    $apcqty = number_format($apcqty, 6);
                                   // Update existing record in 'mat_cons_d'
                                    DB::table('mat_cons_d')
                                        ->where('b_mat_d_id', $matconsddata->b_mat_d_id)
                                        ->update([
                                            'exec_qty' => $billitem->exec_qty,
                                            'mat_qty' => $Matqty,
                                            'A_pc_qty' => $apcqty
                                        ]);
                                }
                            }
                       
                        else{
                        
                             // If no existing data, insert new record if 'exec_qty' is not zero
                        if($billitem->exec_qty != 0)
                        {
                         // Fetch distinct material data
                        $distinctData = DB::table('itemcons')
                        ->leftjoin('mat_mast', 'itemcons.mat_id', '=', 'mat_mast.mat_id')
                        ->where('itemcons.t_item_id', $billitem->t_item_id)
                        ->where('mat_mast.sch_a', 'Yes')
                        ->first();
                        //dd($distinctData);
                        if($distinctData !== null)
                        {
                            //dd($distinctData);
                            $APCQTY=$distinctData->pc_qty;
                            $bmatid=$tbillid.$distinctData->mat_id;
                            $pcqty=$distinctData->pc_qty;
                            $matqty=$billitem->exec_qty* $pcqty;
                            $Amatqty=$billitem->exec_qty* $pcqty;
                            $id = str_pad(intval($id) + 1, 2, '0', STR_PAD_LEFT); 
                            
                             // Check for special case for material ID ending in '0008'
                            $lastFourDigits = substr($distinctData->mat_id, -4);
                            // dd($lastFourDigits);
                            if ($lastFourDigits == '0008')        
                            {
                                // dd($lastFourDigits);

                                    $Step1 = round($matqty * 20, 0);
                                    $Amatqty= $Step1 / 20;
                                    // dd($Step1,$ActualMaterialQty);
                                    $APCQTY= $Amatqty / $billitem->exec_qty;
                                
                            }


                            $bmatdid=$bmatid.$id;
                            // Insert new record into 'mat_cons_d'
                            DB::table('mat_cons_d')->insert([
                                'b_mat_d_id' => $bmatdid,
                                'b_mat_id' => $bmatid,
                                't_bill_id' => $tbillid,
                                'b_item_id' => $billitem->b_item_id,
                                't_item_no' => $billitem->t_item_no,
                                'mat_id' =>  $distinctData->mat_id,
                                'sub_no'  => $billitem->sub_no,
                                'exs_nm' => $billitem->exs_nm,
                                'exec_qty' => $billitem->exec_qty,
                                'pc_qty' => $distinctData->pc_qty,
                                'mat_qty' => $matqty,
                                'A_pc_qty' => $APCQTY,
                                'A_mat_qty' => $Amatqty,
                                'remark' => null

                            ]);

                            //dd($pcqty , $distinctData->t_item_no);

                            $datacons[]=$distinctData;

                        }

                        }
                    }

                }

               // Delete previous material consumption data for the given bill ID
                DB::table('mat_cons_m')->where('t_bill_id' , $tbillid)->delete();


                 // Fetch distinct material IDs from 'mat_cons_d' for the given bill ID
                    $matconsd = DB::table('mat_cons_d')
                        ->select('b_mat_id')
                        ->where('t_bill_id', $tbillid)
                        ->distinct()
                        ->get();
                    //dd($matconsd);
                        $Srno=1;
                        foreach($matconsd as $matid)
                        {
                   // Fetch material consumption data for the current material ID
                        $matdatad=DB::table('mat_cons_d')->where('b_mat_id' , $matid->b_mat_id)->first();
                        //dd($matdatad);
                        
                        $sumMatQty = DB::table('mat_cons_d')
                        ->where('t_bill_id', $tbillid)
                        ->where('b_mat_id' , $matid->b_mat_id)
                        ->sum('mat_qty');

                    $sumAMatQty = DB::table('mat_cons_d')
                        ->where('t_bill_id', $tbillid)
                        ->where('b_mat_id' , $matid->b_mat_id)
                        ->sum('A_mat_qty');

                         // Fetch item consumption data for the given work ID and material ID
                        $itemconsdata=DB::table('itemcons')->where('work_id' , $workid)->where('mat_id' , $matdatad->mat_id)->first();

                        ///dd($itemconsdata);
                        
                        if ($itemconsdata !== null) {
                   // Insert updated material consumption data into 'mat_cons_m'
                    DB::table('mat_cons_m')->insert([

                        'work_id' => $workid,
                        't_bill_id' =>  $tbillid,
                        'b_item_id' => $billitem->b_item_id,
                        'mat_id' => $matdatad->mat_id,
                        'b_mat_id' => $matdatad->b_mat_id,
                        'material' => $itemconsdata->material,
                        'sr_no' =>  $Srno,
                        'mat_unit' =>$itemconsdata->mat_unit,
                        'tot_t_qty' =>$sumMatQty,
                        'tot_a_qty' =>$sumAMatQty,
                    ]);
                    $Srno++;

                        }
                        //dd($itemconsdata);
                        }

       return 1;
    }




//material data for edit
    public function fetchMaterialData(Request $request)
    {
         // Retrieve material ID from the request
        $materialId = $request->input('material_id');
       // dd($materialId);
 // Fetch master material data
 $masterMaterialData = DB::table('mat_cons_m')->where('b_mat_id', $materialId)->first();

 // Fetch additional material data
 $additionalMaterialData = DB::table('mat_cons_d')->where('b_mat_id', $materialId)->get();

        // Prepare and return the data as JSON
        //dd($materialData);

        return response()->json(['master_material_data' => $masterMaterialData ,
        'additional_material_data' => $additionalMaterialData]);
    }


    //update material quantity 
    public function updatematqty(Request $request)
    {
       // Begin a database transaction
         DB::beginTransaction();

        try{
            
         // Retrieve values from the request     
        $amatqty = $request->input('A_mat_qty');
        $execqty = $request->input('exec_qty');
        //dd($execqty);
        $remark = $request->input('remark');
        $bMatDId = $request->input('b_mat_d_id');
    
          // Get the bill ID and material ID
        $tbillid=DB::table('mat_cons_d')->where('b_mat_d_id' , $bMatDId)->value('t_bill_id');
        $bmatid=DB::table('mat_cons_d')->where('b_mat_d_id' , $bMatDId)->value('b_mat_id');

        // Check if $execqty is not zero to avoid division by zero
        if ($execqty != 0) {
            $apcqty = $amatqty / $execqty;

             // Format result to three decimal places
    $apcqty = number_format($apcqty, 6);
        } else {
            // Handle the division by zero scenario (if needed)
            $apcqty = 0; // Set a default value or handle the scenario accordingly
        }       // dd($apcqty);
         // Retrieve b_mat_d_id specifically

         //update the mat_cons_d
    $bMatDId = $request->input('b_mat_d_id');
    DB::table('mat_cons_d')->where('b_mat_d_id' , $bMatDId)
    ->update([

        'A_pc_qty'=> $apcqty,
        'A_mat_qty'=>$amatqty,
        'remark'=>$remark,
    ]);


    // Recalculate total quantities and update summary
    $sumMatQty = DB::table('mat_cons_d')
    ->where('b_mat_id', $bmatid)
    ->sum('mat_qty');

  $sumAMatQty = DB::table('mat_cons_d')
    ->where('b_mat_id', $bmatid)
    ->sum('A_mat_qty');


    DB::table('mat_cons_m')->where('b_mat_id' , $bmatid)
    ->update([

        'tot_t_qty' =>$sumMatQty,
    'tot_a_qty' =>$sumAMatQty,
    ]);

         // Fetch updated data
        $matdata= DB::table('mat_cons_m')->where('b_mat_id' , $bmatid)->first();
        $mateditdata=DB::table('mat_cons_d')->where('b_mat_id' , $bmatid)->get();
        
        DB::commit(); //commit transaction
     // Return JSON response
    return response()->json(['matdata' => $matdata, 'mateditdata' => $mateditdata]); // Return your response
    
     
   }catch(\Exception $e)
   {
    DB::rollback(); // Rollback the transaction in case of an error
    Log::error('An error Occurr during Update Material Quantity');
    return response()->json(['error' => 'An error Occurr during Update Material Quantity']);

   }
   
   
    }






   //fetch royalty data
    public function fetchroyaldata(Request $request)
    {

        $materialId = $request->input('material_id');
        // dd($materialId);
  // Fetch master material data
  $masterMaterialData = DB::table('royal_m')->where('b_mat_id', $materialId)->first();
 
  // Fetch additional material data
  $additionalMaterialData = DB::table('royal_d')->where('b_mat_id', $materialId)->get();
 
         // Prepare and return the data as JSON
         //dd($materialData);
 
         return response()->json(['master_material_data' => $masterMaterialData ,
         'additional_material_data' => $additionalMaterialData]);
    }
    
    // update the progress bar so using close button of  material consumption
    public function FunCloseMaterial(Request $request)
    {
           // Retrieve work ID and action from the request
            $workid=$request->input('workid');
            // dd($request,$workid);
            $action = $request->input('action');

           // Check if the action is to close material
        if($action ==='CloseMaterial')
        {
            $tbillid = $request->input('tbillid');
              
        // Fetch the associated work ID from the bills table based on the provided bill ID
            $workid=DB::table('bills')->where('t_bill_Id',$tbillid)->value('work_id');

            // Retrieve the current status of the material consumption
            $mbstatusSo=DB::table('bills')->where('t_bill_Id',$tbillid)->value('mbstatus_so');

             // Update the status only if it is less than or equal to 1
             if ($mbstatusSo <= 2) {
            $updatembstatusSO=DB::table('bills')->where('work_id',$workid)->where('t_bill_id',$tbillid)
            ->update(['mbstatus_so' =>2]);
            // dd($updatembstatusSO);
            }
        }

                // Retrieve values from the form
    // Display values for debugging
    // Check if the action is to close royalty
    if($action === 'CloseRoyalty')
    {
        $workid = $request->input('workid');
        $tbillid = $request->input('tbillid'); // Corrected name
        $mbstatusSo=DB::table('bills')->where('t_bill_Id',$tbillid)->value('mbstatus_so');

         // Fetch the current status of the material consumption
        if ($mbstatusSo <= 5) 
        {
        $updatembstatusSO=DB::table('bills')->where('work_id',$workid)->where('t_bill_id',$tbillid)
        ->update(['mbstatus_so' =>5]);
        // dd($updatembstatusSO);
        }
        // dd('Royaltypage');
    }
     // Redirect back to the bill list route with the work ID as a parameter
            return redirect()->route('billlist', ['workid' => $workid]);
    }



}