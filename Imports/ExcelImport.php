<?php
namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use App\Http\Controllers\EmbController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PhpOffice\PhpSpreadsheet\Calculation\DateTime;
use App\Helpers\CommonHelper;

// ... your code

// ... your code

class ExcelImport
{
    // excel data insert in databse table individual b item id
    public static function process($file,$bitemId)
    {
          //$file=null;
        $measurements=null;
        $returnstlData=null;
        //dd($bitemId);
        // Load the Excel file
        $spreadsheet = IOFactory::load($file);
        $worksheet = $spreadsheet->getActiveSheet();

        //Item no
        $givenitemno=DB::table('bil_item')->where('b_item_id', '=', $bitemId)->get()->value('t_item_no');
        //sub no
        $givensubno=DB::table('bil_item')->where('b_item_id', '=', $bitemId)->get()->value('sub_no');
         //dd($givensubno);
         $givensubno=DB::table('bil_item')->where('b_item_id', '=', $bitemId)->get()->value('sub_no');
        // Get the highest row and column
        $tbillid =  DB::table('bil_item')->where('b_item_id', '=', $bitemId)->get()->value('t_bill_id');
        //dd($tbillid);
        $workid =  DB::table('bills')->where('t_bill_id', '=', $tbillid)->get()->value('work_id');
        //dd($workid);
        $billitem =  DB::table('bil_item')->where('b_item_id', '=', $bitemId)->get();

        
       
        //initiliase the varaibles
        $previousexecqty=null;
        $curqty = null;
        $execqty= null;
        $tndqty=null;
        $tndcostitem=null;
        //dd($tndqty);
        $percentage=null;
        //dd($percentage);
        $totlcostitem=null;

        $costdifference= null;
         // Initialize $measurements as an empty array before the loop
         
         //check measurement new insert or not in database
        $checkmeas=0;

    
   //item id of given bitem id related
    $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');

    $item = DB::table('bil_item')->where('b_item_id', $bitemId)->first();


    $t_item_no = $item->t_item_no;
    $sub_no = $item->sub_no ?? ''; // Default to empty string if sub_no doesn't exist

    //concatinate item no and sub no
    $concatenatedValue = $t_item_no . $sub_no;

     // Check if the 'itemid' ends with specific values
         if (
        in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])
            //in_array(substr($itemid, -6), ["001295", "001298", "002115", "003960", "003963", "004351", "003550", "003551", "002064", "002065", "002066", "002067", "002068", "002069", "003399", "003558", "004566", "004567"])
        ) {
            // Code to execute when there's a match

            //dd('ok');

            
           //get highest row in excel sheet
            $highestRow = $worksheet->getHighestRow();
            //get highest column of given excel sheet
            $highestColumn = $worksheet->getHighestColumn();
            //dd($highestColumn);
            $membersrno=null;
            //$itemno = $worksheet->getCell('A'. 3)->getValue();
            //dd($itemno);
            $processingStarted = false;

            //loop for excel rows and columns
            for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
                
                //cell vidse varaible declaration
                $itemtitle = $worksheet->getCell('A'. $rowIndex)->getValue();
                $itemno =  $worksheet->getCell('B'. $rowIndex)->getValue();

              //dd($itemno);
                // Check if the conditions are met to start or stop processing
    if (!$processingStarted && !empty($itemtitle) && !empty($itemno) && $itemtitle == 'Item No' && $itemno==$concatenatedValue) {
        // Start processing from this row
        $rowIndex=$rowIndex+2;
        $processingStarted = true;
    } elseif ($processingStarted && !empty($itemtitle) && !empty($itemno) && $itemtitle == 'Item No') {
        // Stop processing if the condition is met again
        $processingStarted = false;
        continue; // Skip this iteration to avoid double processing
    }
                 //dd($itemno);
                // if (!empty($itemtitle) && !empty($itemno) && $itemtitle == 'itemno') {

                    
                    // Process the current row
                    // ... your processing code ...

                       //dd($itemno);
                    //    $rowIndex=$rowIndex+2;
                       
                    //   for($Rowindex=$rowIndex; $Rowindex <= $highestRow; $Rowindex++)
                    //   {
                        if ($processingStarted) {
                      //append varaibles of excel cell
                       $membersrno = $worksheet->getCell('A'. $rowIndex)->getValue();
                       //dd($msrno);
                       $rccmember =  $worksheet->getCell('B'. $rowIndex)->getValue();
                       $meberparticulars =  $worksheet->getCell('C'. $rowIndex)->getValue();
                       //dd($meberparticulars);
                       $noofmemb =  $worksheet->getCell('D'. $rowIndex)->getValue();
                       $barsrno =  $worksheet->getCell('E'. $rowIndex)->getValue();
                      // dd($barsrno);
                       $barparticulars =  $worksheet->getCell('F'. $rowIndex)->getValue(); 
                       //dd($barparticulars);
                       $noofbars =  $worksheet->getCell('G'. $rowIndex)->getValue();
                      // dd();
                       $l6 =  $worksheet->getCell('H'. $rowIndex)->getValue();
                       $l8 = $worksheet->getCell('I'. $rowIndex)->getValue();
                       $l10 =  $worksheet->getCell('J'. $rowIndex)->getValue();
                       $l12 = $worksheet->getCell('K'. $rowIndex)->getValue();
                       //dd($l12);
                       $l16 =  $worksheet->getCell('L'. $rowIndex)->getValue();
                       $l20 = $worksheet->getCell('M'. $rowIndex)->getValue();
                       $l25 =  $worksheet->getCell('N'. $rowIndex)->getValue();
                       $l28 = $worksheet->getCell('O'. $rowIndex)->getValue();
                       $l32 =  $worksheet->getCell('P'. $rowIndex)->getValue();
                       $l36 = $worksheet->getCell('Q'. $rowIndex)->getValue();
                       $l40 =  $worksheet->getCell('R'. $rowIndex)->getValue();
                       $measdate = $worksheet->getCell('S'. $rowIndex)->getValue();
                       //dd($measdate);

                       //change the date format using instance create
                       $date= Date::excelToDateTimeObject(intval($measdate))->format('Y-m-d');

                       //get measurement date from and upto 
                       $measdtfrom=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_from');
                       //dd($measdtfrom);
                     $measdtupto=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_upto');
                              //dd($measdtupto);
                               // Assuming $dom is in a valid date format (e.g., 'YYYY-MM-DD')
                                 //$domDate = date_create_from_format('Y-m-d', $dom);
                                 //dd($domDate);
                       
                                 //apply date conditions
                     if ( $date >= $measdtfrom && $date <= $measdtupto) {       

                        // create steel id
            $previoussteelid=DB::table('stlmeas')->where('b_item_id', '=', $bitemId)->orderby('steelid', 'desc')->first('steelid');
            //dd( $previousmeasid);
            if ($previoussteelid) {
                $previousstld = $previoussteelid->steelid; // Convert object to string
                // Increment the last four digits of the previous meas_id
                 $lastFourDigits = intval(substr($previousstld, -4));
                 $newLastFourDigits = str_pad(($lastFourDigits + 1), 4, '0', STR_PAD_LEFT);
                 $newsteelid = $bitemId.$newLastFourDigits;
                 //dd($newmeasid);
           } else {
               // If no previous meas_id, start with bitemid.0001
               $newsteelid = $bitemId.'0001';
               //dd($newsteelid);
           }
    

              
            $rcmbrid = DB::table('bill_rcc_mbr')->where('b_item_id', '=', $bitemId)->where('rcc_member' , $rccmember)->where('member_particulars' , $meberparticulars)->first('rc_mbr_id');
            //dd($rcmbrid);

            if ($rcmbrid) {
                 // If no previous meas_id, start with bitemid.0001
                 $newrcmbrid = $rcmbrid->rc_mbr_id; // Access rc_mbr_id property
                 //dd($newmeasid);
           } else {
             

            $previousrcmbrid = DB::table('bill_rcc_mbr')->where('b_item_id', '=', $bitemId)->orderby('rc_mbr_id' , 'desc')->first('rc_mbr_id');
              
               // Increment the last four digits of the previous meas_id

               if($previousrcmbrid)
               {

                $previousrcid = $previousrcmbrid->rc_mbr_id; // Convert object to string

                $lastFiveDigits = intval(substr($previousrcid, -5));
                $newLastFiveDigits = str_pad(($lastFiveDigits + 1), 5, '0', STR_PAD_LEFT);
                $newrcmbrid = $bitemId.$newLastFiveDigits;
               }
               else
               {
                $newrcmbrid = $bitemId.'00001';
               }
                
               
           }
           //dd($newrcmbrid);
            //  $rcmbrid=$bitemId.$rcid;
             //dd($rcmbrid);

             //dd($date);

             //if member sr no insert in bill rcc member table
             if($membersrno)
             {
             if ($rcmbrid) {
                // If $rcmbrid is not null, do not insert 'rc_mbr_id' in the insert query

            } else {
                // If $rcmbrid is null, insert 'rc_mbr_id' in the insert query
               
                //dd($membersrno);
                DB::table('bill_rcc_mbr')->insert([
                    'work_id' => $workid,
                    't_bill_id' => $tbillid,
                    'b_item_id' => $bitemId,
                    'rc_mbr_id' => $newrcmbrid, // Insert 'rc_mbr_id'
                    'member_sr_no' => $membersrno,
                    'rcc_member' => $rccmember,
                    'member_particulars' => $meberparticulars,
                    'no_of_members' => $noofmemb,
                ]);
            }
            
          } 
            
            
            
            

           // Determine which length variable to consider first
                        $preferredLength = null;
                        
                        if ($l6 !== null) {
                            $preferredLength = $l6;
                        } elseif ($l8 !== null) {
                            $preferredLength = $l8;
                        } elseif ($l10 !== null) {
                            $preferredLength = $l10;
                        }  
                         elseif ($l12 !== null) {
                            $preferredLength = $l12;
                        }                      
                          elseif ($l16 !== null) {
                            $preferredLength = $l16;
                        } elseif ($l20 !== null) {
                            $preferredLength = $l20;
                        } elseif ($l25 !== null) {
                            $preferredLength = $l25;
                        }                        
                           elseif ($l32 !== null) {
                            $preferredLength = $l32;
                        }                      
                          elseif ($l36 !== null) {
                            $preferredLength = $l36;
                        } elseif ($l40 !== null) {
                            $preferredLength = $l40;
                         } 

                        //dd( $preferredLength);

                        if ($preferredLength !== null) {
                     // Calculate bar length using the preferred value
                            $barlength = $noofmemb * $noofbars * $preferredLength;
                            }

                        //dd($barlength);
                        if($barsrno)  {
                            
                              //measuerement added after chekmeas check new measurement added or not
                            $checkmeas=1;

                        DB::table('stlmeas')->insert([
                    

                            'work_id' => $workid,
                            't_bill_id' => $tbillid,
                            'b_item_id' => $bitemId,
                            'steelid' => $newsteelid,
                            'rc_mbr_id' => $newrcmbrid,
                            'bar_sr_no' => $barsrno,
                            'bar_particulars' => $barparticulars,
                            'no_of_bars' => $noofbars,
                            'ldiam6' => $l6,
                            'ldiam8' => $l8,
                             'ldiam10' => $l10,
                            'ldiam12' => $l12,
                            'ldiam16' => $l16,
                            'ldiam20' => $l20,
                            'ldiam25' => $l25,
                            'ldiam28' => $l28,
                            'ldiam32' => $l32,
                            'ldiam36' => $l36,
                            'ldiam40' => $l40,
                            'date_meas' => $date,
                            'bar_length' => $barlength,
                            'dyE_chk_dt' => $date,


                        ]);
        
                    }

                }   




                }
               // dd( $dom);
            }

          

   
    // $stldata = DB::table('stlmeas')
    // ->where('b_item_id', $bitemId)
    // ->get()
    // ->groupBy('rc_mbr_id');

 // Retrieve 'stlmeas' data associated with the 'b_item_id'
    $stldata = DB::table('stlmeas')
     ->where('b_item_id', $bitemId)
     ->get();
   // Retrieve all data from 'bill_rcc_mbr' table
    $bill_rc_data = DB::table('bill_rcc_mbr')->get();

   // dd($stldata , $bill_rc_data);
   // List of diameter columns
    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
      'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];
 // Swap bar length with diameter values if they are not equal
      foreach ($stldata as &$data) {
        if (is_object($data)) {
            foreach ($ldiamColumns as $ldiamColumn) {
                if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {
                    
                    $temp = $data->$ldiamColumn;
                    $data->$ldiamColumn = $data->bar_length;
                    $data->bar_length = $temp;
                   // dd($data->bar_length , $data->$ldiamColumn);
                    break; // Stop checking other ldiam columns if we found a match
                }
            }
        }
    }
    
   // Initialize sums for each diameter column
    $sums = array_fill_keys($ldiamColumns, 0);

     // Calculate the sum for each diameter column
    foreach ($stldata as $row) {
        foreach ($ldiamColumns as $ldiamColumn) {
            $sums[$ldiamColumn] += $row->$ldiamColumn;
        }
    }//dd($stldata);
//dd($sums);
// Retrieve 'bill_rcc_mbr' data where there exists a corresponding 'stlmeas' record
$bill_member = DB::table('bill_rcc_mbr')
      ->whereExists(function ($query) use ($bitemId) {
          $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemId);
      })
      ->get();          

//$bill_memberdata=DB::table('rcc_mbr')->get();
//dd($bill_member);
// Generate the HTML content
  // Generate the HTML content
    // Retrieve 'rc_mbr_id' values from 'bill_rcc_mbr' table for the given 'b_item_id'
$rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();
//d($rc_mbr_ids);



$html = '';

   
//dd($stldata);
    // Check if there is data for this rc_mbr_id
    // if ($stldata->isEmpty()) {
    //     continue; // Skip if there's no data
    // }

 // Generate HTML content for each member
    foreach ($bill_member as $index => $member) {
        $html .= '<div class="container-fluid">';
        $html .= '
        <div class="container-fluid">
    <div class="row">
        <div class="col-md-1">
            <div class="form-group">
                <label for="sr_no">Sr No</label>
                <input type="text" class="form-control" id="sr_no" name="sr_no" value="' . $member->member_sr_no . '" disabled>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="rcc_member">RCC Member</label>
                <select class="form-control" id="rcc_member" name="rcc_member" disabled>
                    <option value="' . $member->rc_mbr_id . '">' . $member->rcc_member . '</option>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label for="member_particular">Member Particular</label>
                <input type="text" class="form-control" id="member_particular" name="member_particular" value="' . $member->member_particulars . '" disabled>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                 <label for="no_of_members">No Of Members</label>
                 <input type="text" class="form-control" id="no_of_members" name="no_of_members" value="' . $member->no_of_members . '" disabled>
            </div>
       </div>
       <div class="col-md-1">
       <div class="form-group">
          <button type="button" class="btn btn-primary btn-sm editrcmbr-button" data-rcbillid="' . $member->rc_mbr_id . '" title="EDIT RCC MEMBER"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button>
       </div>
  </div>
</div>
     
    
<div class="container-fluid">
<div class="col-md-12">
            <table class="table table-striped">
            
                <thead>
                    <tr>
                    <th>Sr No</th>
                    <th>Bar Particulars</th>
                    <th>No of Bars</th>
                    <th>Length of Bars</th>
                    <th>6mm</th>
                    <th>8mm</th>
                    <th>10mm</th>
                    <th>12mm</th>
                    <th>16mm</th>
                    <th>20mm</th>
                    <th>25mm</th>
                    <th>28mm</th>
                    <th>32mm</th>
                    <th>36mm</th>
                    <th>40mm</th>
                    <th>Date</th>
                    <th>Action</th> 
                    </tr>
                </thead>
                <tbody>';
// Generate table rows for each bar associated with the current member
                foreach ($stldata as $bar) {
                    if ($bar->rc_mbr_id == $member->rc_mbr_id) {
                    //dd($bar);// Assuming the bar data is within a property like "bar_data"
                    $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                    $html .= '<tr>
                                <td>'. $bar->bar_sr_no .'</td>
                                <td>'. $bar->bar_particulars .'</td>
                                <td>'. $bar->no_of_bars .'</td>
                                <td>'. $bar->bar_length .'</td>
                                <td>'. $bar->ldiam6 .'</td>
                                <td>'. $bar->ldiam8 .'</td>
                                <td>'. $bar->ldiam10 .'</td>
                                <td>'. $bar->ldiam12 .'</td>
                                <td>'. $bar->ldiam16 .'</td>
                                <td>'. $bar->ldiam20 .'</td>
                                <td>'. $bar->ldiam25 .'</td>
                                <td>'. $bar->ldiam28 .'</td>
                                <td>'. $bar->ldiam32 .'</td>
                                <td>'. $bar->ldiam36 .'</td>
                                <td>'. $bar->ldiam40 .'</td>
                                <td>'. $formattedDateMeas .'</td>
                                <td>
                                <button type="button" class="btn btn-primary btn-sm edit-button" data-steelid="' . $bar->steelid . '" title="EDIT STEEL MEASUREMENT"> <i class="fa fa-pencil" style="color:white;"></i></button>
                                <button type="button" class="btn btn-danger btn-sm delete-button" data-steelid="' . $bar->steelid . '" title="DELETE STEEL MEASUREMENT"><i class="fa fa-trash" aria-hidden="true"></i></button>
                            </td>
                                </tr>';
                }
            }
            
            $html .= '
                </tbody>
            </table>
        </div>
    </div>
    </div>';

    // Add a row for the totals for the last member
    if ($index === count($bill_member) - 1) {
        $html .= '
        <div><h4>TOTAL LENGTH</h4></div> 
       <div class="container-fuid"> 
        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                <thead>
                    <tr>
                    <th></th>
                    <th colspan="13"></th>
                    <th>Length</th>
                    <th>6mm</th>
                    <th>8mm</th>
                    <th>10mm</th>
                    <th>12mm</th>
                    <th>16mm</th>
                    <th>20mm</th>
                    <th>25mm</th>
                    <th>28mm</th>
                    <th>32mm</th>
                    <th>36mm</th>
                    <th>40mm</th>
                    <th colspan="8"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                        <th>Total</th>
                        <td>' . $sums['ldiam6'] . '</td>
                        <td>' . $sums['ldiam8'] . '</td>
                        <td>' . $sums['ldiam10'] . '</td>
                        <td>' . $sums['ldiam12'] . '</td>
                        <td>' . $sums['ldiam16'] . '</td>
                        <td>' . $sums['ldiam20'] . '</td>
                        <td>' . $sums['ldiam25'] . '</td>
                        <td>' . $sums['ldiam28'] . '</td>
                        <td>' . $sums['ldiam32'] . '</td>
                        <td>' . $sums['ldiam36'] . '</td>
                        <td>' . $sums['ldiam40'] . '</td> 
                        <th></th>
                        <th></th>  
                        <th></th>                        
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        </div>';
    }

    $html .= '</div>'; // Close the container
   
}
// Check if this is the last member in the list
$itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('item_id');

      if (in_array(substr($itemid, -6), ["003351", "003878"])) 
      {
           $sec_type="HCRM/CRS Bar";
      }
   else{
           $sec_type="TMT Bar";
       }

       DB::table('embs')->where('b_item_id', '=' , $bitemId)->delete();
      

       $selectedlength = [];
       $size=null;
       $sr_no = 0; // Initialize the serial number
       $totalweight = 0; // Initialize the total weight

       $html .= '<div><h4>TOTAL WEIGHT</h4></div> <div class="container-fuid">  
<div class="row">
    <div class="col-md-12">
          <table class="table table-striped" style="width: 100%;">
            <thead>
                <tr>
                    <th>Sr No</th>
                    <th>Particulars</th>
                    <th>Formula</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>';
         //distinct steel dates
            $distinctStlDate = DB::table('stlmeas')
            ->select('date_meas') // Add other columns as needed
            ->where('b_item_id', $bitemId)
            ->groupBy('date_meas')
            ->orderBy('date_meas', 'asc') // Optional: Order by date in descending order
            ->get();

            
            DB::table('embs')->where('b_item_id', $bitemId)->delete();

         
            $Size=null;
           //dd($sums);
            foreach($distinctStlDate as $date)
           {
// //dd($date);
$barlenghtl6=0;
            $barlenghtl8=0;
            $barlenghtl10=0;
            $barlenghtl12=0;
            $barlenghtl16=0;
            $barlenghtl20=0;
            $barlenghtl25=0;
            $barlenghtl28=0;
            $barlenghtl32=0;
            $barlenghtl36=0;
            $barlenghtl40=0;
            $barlenghtl45=0;

            // Fetch steel measurement data for the given date and b_item_id
                                $steelmeasdata=DB::table('stlmeas')->where('b_item_id', $bitemId)->where('date_meas', $date->date_meas)->get();

                              //dd($steelmeasdata);

                                foreach ($steelmeasdata as $row) {
//dd($row);
                                  $measurement=DB::table('stlmeas')->select('ldiam6','ldiam8' , 'ldiam10' , 'ldiam12' , 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40', 'ldiam45')->where('steelid' , $row->steelid)->first();
                                   
                                   // Convert measurement object to key-value pairs excluding null values
                                  $keyValuePairs = (object)[];

                                  foreach ($measurement as $column => $value) {
                                      if (!is_null($value)) {
                                          $keyValuePairs->$column = $value;
                                      }
                                  }
                                  //dd(key($keyValuePairs));
                                //   foreach ($row as $key => $value) {
                                //     }
                                     // Determine the diameter size and accumulate bar lengths
                                    switch (key($keyValuePairs)) {
                                        case 'ldiam6':
                                            $Size = "6 mm dia";
                                            $barlenghtl6 += $row->bar_length;
                                            break;
                                        case 'ldiam8':
                                            $Size = "8 mm dia";
                                            $barlenghtl8 += $row->bar_length;
                                            break;
                                        case 'ldiam10':
                                            $Size = "10 mm dia";
                                            $barlenghtl10 += $row->bar_length;
                                            break;
                                        case 'ldiam12':
                                            $Size = "12 mm dia";
                                            $barlenghtl12 += $row->bar_length;
                                            break;
                                        case 'ldiam16':
                                            $Size = "16 mm dia";
                                            $barlenghtl16 += $row->bar_length;
                                            break;
                                        case 'ldiam20':
                                            $Size = "20 mm dia";
                                            $barlenghtl20 += $row->bar_length;
                                            break;
                                        case 'ldiam25':
                                            $Size = "25 mm dia";
                                            $barlenghtl25 += $row->bar_length;
                                            break;
                                        case 'ldiam28':
                                            $Size = "28 mm dia";
                                            $barlenghtl28 += $row->bar_length;
                                            break;
                                        case 'ldiam32':
                                            $Size = "32 mm dia";
                                            $barlenghtl32 += $row->bar_length;
                                            break;
                                        case 'ldiam36':
                                            $Size = "36 mm dia";
                                            $barlenghtl36 += $row->bar_length;
                                            break;
                                        case 'ldiam40':
                                            $Size = "40 mm dia";
                                            $barlenghtl40 += $row->bar_length;
                                            break;
                                        case 'ldiam45':
                                            $Size = "45 mm dia";
                                            $barlenghtl45 += $row->bar_length;
                                            break;
                                    }
                                }//dd($stldata);

                                // Instantiate ExcelImport class for processing
                                $excelimportclass = new ExcelImport();

                             // Process each diameter if length is greater than 0
                                if($barlenghtl6 > 0)
                                 {

                                    $sr_no++;
                                    $size="6 mm dia";
                                     
                                    //function is created 
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl6 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
     //dd($tmtdata);           
                                              
                                 }
 
 
 
 
 
                             
                            
                                 if($barlenghtl8 > 0)
                                 {
                                    $sr_no++;
                                         $size="8 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl8 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                        
                                              
 
                                 }
                              
                                 if($barlenghtl10 > 0)
                                 {
                                    $sr_no++;
                                         $size="10 mm dia";
                                        
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl10 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
                                              
 
                                 }
                                 if($barlenghtl12 > 0)
                                 {
                                    $sr_no++;
                                         $size="12 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl12 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
 
                                 }
                                 if($barlenghtl16 > 0)
                                 {
                                    $sr_no++;
                                         $size="16 mm dia";
                                          //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl16 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html                                                                                      
 
                                 }
 
                                
                               
                                 if($barlenghtl20 > 0)
                                 {
                                    $sr_no++;
                                         $size="20 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl20 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                 
                                 }

                                 if($barlenghtl25 > 0)
                                 {
                                    $sr_no++;
                                         $size="25 mm dia";
                                           //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl25 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                                                                   
                                 }
                                
                               
                                 if($barlenghtl28 > 0)
                                 {
                                    $sr_no++;
                                         $size="28 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl28 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                  $html .= $tmtdata['html']; // Accessing html
                                                  
                 
                                 }
                               
                                
                                 if($barlenghtl32 > 0)
                                 {
                                    $sr_no++;
                                         $size="32 mm dia";
                                             //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl32 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                            $html .= $tmtdata['html']; // Accessing html
                                                  
                 
                                 }
                               
                                
                                
                                 if($barlenghtl36 > 0)
                                 {
                                    $sr_no++;
                                         $size="36 mm dia";
                                            //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl36 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                 
                                 }


                                 if($barlenghtl40 > 0)
                                 {
                                    $sr_no++;
                                         $size="40 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $excelimportclass->steelinsertnormal($size , $barlenghtl40 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                                                                  
                                 }
                                // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];
 
 
                                
 

                               

                                
                            }
      // Add row for total weight after processing all diameters for the current date
       $html .= '<tr style="background-color: #333; color: #fff;">
       <td></td>
       <td><strong>Total Weight:</strong></td>
       <td></td>
       <td><strong>' . $totalweight . ' M.T</strong></td>
     </tr>';

     $html .= '</tbody>
        </table>
    </div>
</div>
</div>';      
    
   
     
      

       $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');
         
       $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');
   
       $previousTBillId = DB::table('bills')
       ->where('work_id' , $workid)
       ->where('t_bill_id', '<', $tbillid) // Add your condition here
       ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
       ->limit(1) // Limit the result to 1 row
       ->value('t_bill_id');
           //dd($previousTBillId);
   
           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

           $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');
           
           
           $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
           //dd($previousexecqty);
           
           if (is_null($previousexecqty)) {
               $previousexecqty = 0;
           }
           
             $curqty= round($totalweight ,$Qtydec);// Ensure previousexecqty is initialized to 0 if null
           
           
             $execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');// Format and round current quantity
             //dd($execqty);
   
           $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');// Fetch bill_rt for the given b_item_id

                $curamt=$curqty*$billrt;

           $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');// Fetch previous amount

               $bitemamt=$curamt+$previousamt;
 // Update bil_item table with calculated values
           DB::table('bil_item')->where('b_item_id' , $bitemId)->update([
   
               'exec_qty' => $execqty,
               'cur_qty' => $curqty,
               'prv_bill_qty' => $previousexecqty,
               'cur_amt' => $curamt,
               'b_item_amt' => $bitemamt,
           ]);
      

           $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
           $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
        //   $tndqty=round($tnditem->tnd_qty , 3);
         $tndqty=$tnditem->tnd_qty;
         
           $amountconvert=new CommonHelper();// Initialize CommonHelper for amount conversion
                

           $tndcostitem=$tnditem->t_item_amt;// Fetch t_item_amt from tnditems
           //dd($tndqty);
           $percentage=round(($execqty / $tndqty)*100 , 2);// Calculate percentage
           //dd($percentage);
           $totlcostitem=round($billrt*$execqty , 2);// Calculate total cost item

           $costdifference= round($tndcostitem-$totlcostitem , 2);// Calculate cost difference
           
             // Format amounts into Indian Rupees using CommonHelper
                            $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);

       // Initialize sums for matched and unmatched conditions
           $parta = 0; // Initialize the sum for matched conditions
           $partb = 0; // Initialize the sum for unmatched conditions
           
           $cparta = 0; // Initialize the sum for matched conditions
           $cpartb = 0; // Initialize the sum for unmatched conditions
        
           $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);
              
                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                        // Check conditions and sum up amounts accordingly
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          ) 
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                         
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions
              
              
                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }
        
                          
         // Calculate bill gross amount and format using CommonHelper
           $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

           $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
           // Fetch and calculate beloaboperc and beloAbo from workmasters table
           $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
           //dd($beloaboperc);
           $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');
        
           $bill_amt=0;
          $abeffect = $parta * ($beloaboperc / 100);
          $cabeffect = $cparta * ($beloaboperc / 100);
          
          // Calculate bill_amt based on Above or Below condition
                         if ($beloAbo === 'Above') {
                      
                            
                            $bill_amt = round(($parta + $abeffect), 2);
                            $cbill_amt = round(($cparta + $cabeffect), 2);
                      
                        } elseif ($beloAbo === 'Below') {
                      
                            $bill_amt = round(($parta - $abeffect), 2);
                            $cbill_amt = round(($cparta - $cabeffect), 2);
                      
                        }
                      
                         // Determine whether to add a minus sign
                         if ($beloAbo === 'Below') {
                             $abeffect = -$abeffect;
                             $cabeffect = -$cabeffect;
                             $beloaboperc = -$beloaboperc;
                            }
                            //dd($abeffect);
                           //$part_a_ab=($parta * $beloaboperc / 100);
                           //dd($partb);
                       
                      
                      
                                 
                             // Round the bill amounts to 2 decimal places
                           $Gstbase = round($bill_amt, 2);
                           $cGstbase = round($cbill_amt, 2);



                           
                                  // Calculate GST amounts at 18%
                      
                                  $Gstamt= round($Gstbase*(18 / 100), 2);
                                  $cGstamt= round($cGstbase*(18 / 100), 2);
                                   //dd($Gstamt);
        
                                   // Calculate part A GST amounts
                                   $part_A_gstamt=$Gstbase + $Gstamt;
                                   $cpart_A_gstamt=$cGstbase + $cGstamt;
        
                               // Calculate total bill amounts including part B
                                   $billamtgt = $partb + $part_A_gstamt;
                                   $cbillamtgt = $cpartb + $cpart_A_gstamt;
        
                                   // Extract integer and decimal parts
                     $integer_part = floor($billamtgt);  // Extract the integer part
                     $cinteger_part = floor($cbillamtgt);
        
        
                     $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                     $cdecimal_part = $cbillamtgt - $cinteger_part; 
                     //dd($decimal_part);
        
                     // Round the decimal parts
                     $billamtro = round($decimal_part, 2);
                     $cbillamtro = round($cdecimal_part, 2);
                     //dd($rounded_decimal_part);
        
                //     // Round the total bill amount
                //     $billamtro = round($billamtgt);
                //     //dd($rounded_billamtgt);
        
                //    // Calculate the difference
                //     //$billamtro = $rounded_billamtgt - $billamtgt;
                //     dd($billamtro);
                    //$billamtro=0.37;
                     // Adjust bill amount rounding to nearest integer if decimal part is greater than 0.50
                    if ($billamtro > 0.50) {
                        // Calculate the absolute difference
                        $abs_diff = abs($billamtro);
                        $billamtro = 1 - $abs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $billamtro = -$billamtro;
                        //dd($billamtro);
                    }
        
                    if ($cbillamtro > 0.50) {
                        // Calculate the absolute difference
                        $cabs_diff = abs($cbillamtro);
                        $cbillamtro = 1 - $cabs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $cbillamtro = -$cbillamtro;
                        //dd($billamtro);
                    }
                     //dd($billamtro);
                 // Calculate net amounts by adding rounded amounts to total bill amounts
                     $net_amt= $billamtgt + $billamtro;
                     $cnet_amt= $cbillamtgt + $cbillamtro;
                     //dd($net_amt);
        
                      // Determine whether to add a minus sign
                    
                      // Update the 'bills' table with computed values
                     DB::table('bills')->where('t_bill_id' , $tbillid)->update([
        
                        'part_a_amt' => $parta,
                        'part_a_gstamt' => $part_A_gstamt,
                        'part_b_amt' => $partb,
                        'gst_amt' => $Gstamt,
                        'gst_base' => $Gstbase,
                        'gross_amt' => $billgrossamt,
                        'a_b_effect' => $abeffect,
                        'bill_amt' => $bill_amt,
                        'bill_amt_gt' => $billamtgt,
                        'bill_amt_ro' => $billamtro,
                        'net_amt' => $net_amt,
        
                        'c_part_a_amt' => $cparta,
                        'c_part_a_gstamt' => $cpart_A_gstamt,
                        'c_part_b_amt' => $cpartb,
                        'curr_grossamt' => $cbillgrossamt,
                        'c_billamt' =>  $cbill_amt,
                        'c_gstamt' => $cGstamt,
                        'c_gstbase' => $cGstbase,
                        'c_abeffect' => $cabeffect,
                        'c_billamtgt' => $cbillamtgt,
                        'c_billamtro' => $cbillamtro,
                        'c_netamt' => $cnet_amt,
                        'mbstatus_so' => 0
                     ]);


                      // Construct HTML response with dynamic values

                     $html .= '
                     
                     <div class="row mt-3">
                          <div class="col-md-3 offset-md-9">
                              <div class="form-group">
                                  <label for="currentQty" class="font-weight-bold">Current Quantity:</label>
                                  <input type="text" class="form-control" id="currentQty{{ $emb3->b_item_id }}" name="currentQty" value="' .$curqty. '" disabled>
                              </div>
                          </div>
                      </div>
                      
                          
                     <div class="row mt-3">
                       <div class="col-md-3 offset-md-9">
                             <div class="form-group" >
                                 <label for="previousQty" class="font-weight-bold">Previous bill Quantity:</label>
                                 <input type="text" class="form-control" id="previousQty{{ $emb3->b_item_id }}" name="previousQty" value="' .$previousexecqty. '"  disabled>
                             </div>
                         </div>
                     </div> 
                          
                         
                        
                     <div class="row mt-3">
                     <div class="col-md-3 offset-md-3">
                         <div class="form-group">
                             <label for="tndqty" class="font-weight-bold">Tender Quantity Of Item:</label>
                             <input type="text" class="form-control" id="tndqty{{ $emb3->b_item_id }}" name="tndqty" value="' .$tndqty. '" disabled>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="form-group">
                             <label for="percentage" class="font-weight-bold">Percentage Utilised:</label>
                             <input type="text" class="form-control" id="percentage{{ $emb3->b_item_id }}" name="percentage" value="' .$percentage. '" disabled>
                         </div>
                     </div>
                     <div class="col-md-3">
                         <div class="form-group">
                             <label for="totalQty" class="font-weight-bold">Total Uptodate Quantity:</label>
                             <input type="text" class="form-control" id="totalQty{{ $emb3->b_item_id }}" name="totalQty" value="' .$execqty. '" disabled>
                         </div>
                     </div>
                 </div>
                 
                        <div class="row mt-3"  >
                        <div class="col-md-3 offset-md-3">
                            <div class="form-group">
                              <label for="tndcost" class="font-weight-bold">Tender Cost Of Item:</label>
                              <input type="text" class="form-control" id="tndcost{{ $emb3->b_item_id }}" name="tndcost" value="' .$tndcostitem. '" disabled>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="form-group">
                              <label for="costdifference" class="font-weight-bold">Excess/Saving:</label>
                              <input type="text" class="form-control" id="costdifference{{ $emb3->b_item_id }}" name="costdifference" value="' .$costdifference. '" disabled>
                            </div>
                          </div>
                          <div class="col-md-3">
                            <div class="form-group">
                              <label for="totalcost" class="font-weight-bold">Total Uptodate Amount:</label>
                              <input type="text" class="form-control" id="totalcost{{ $emb3->b_item_id }}" name="totalcost" value="' .$totlcostitem. '" disabled>
                            </div>
                          </div>
                        </div>';
     


$returnstlData = [
    'sums' => $sums,
    'stldata' => $stldata,
    'html' => $html,
     'checkmeas' => $checkmeas
];
          


        
        }
     else
     //Normal Measurement Add
         {


        //get a highest row and highest column of noremal measurement excel shett
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        //dd($highestColumn);


        //declare varaibles of excell cells
        $itemno = $worksheet->getCell('A'. 1)->getValue();
       // dd($itemno);
        $subno =  $worksheet->getCell('B'. 2)->getValue();
         //dd();
      // Loop through rows and extract cell values
    for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
       
        $itemno = $worksheet->getCell('A'. $rowIndex)->getValue();
        $subno =  $worksheet->getCell('B'. $rowIndex)->getValue();
        $srno =  $worksheet->getCell('D'. $rowIndex)->getValue();
        $particulars =  $worksheet->getCell('E'. $rowIndex)->getValue();
        //dd($particulars);
        $formula =  $worksheet->getCell('F'. $rowIndex)->getValue(); 
        $number =  $worksheet->getCell('G'. $rowIndex)->getValue();
        $length =  $worksheet->getCell('H'. $rowIndex)->getValue();
        $breadth = $worksheet->getCell('I'. $rowIndex)->getValue();
        $height =  $worksheet->getCell('J'. $rowIndex)->getValue();
        $dom = $worksheet->getCell('K'. $rowIndex)->getValue();

        $notforpayment = $worksheet->getCell('L'. $rowIndex)->getValue();

//check consdition of not for payment it or not
        if($notforpayment == 1)
    {
        $Particulars= $particulars . " (Not for payment)";
    }
    else{
        $Particulars= $particulars;
    }
        //dd($notforpayment);
        /// Create a DateTime object from the string

        $date= Date::excelToDateTimeObject(intval($dom))->format('Y-m-d');


       $measdtfrom=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_from');
//dd($measdtfrom);
       $measdtupto=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_upto');
       //dd($measdtupto);
        // Assuming $dom is in a valid date format (e.g., 'YYYY-MM-DD')
          //$domDate = date_create_from_format('Y-m-d', $dom);
          //dd($domDate);

          if ($itemno == $givenitemno && $subno == $givensubno && $date >= $measdtfrom && $date <= $measdtupto)
           {            
            
            // create measurement id
            $previousmeasuementid=DB::table('embs')->where('b_item_id', '=', $bitemId)->orderby('meas_id', 'desc')->first('meas_id');
            //dd( $previousmeasuementid);
            if ($previousmeasuementid) {
                $previousmeasid = $previousmeasuementid->meas_id; // Convert object to string
                // Increment the last four digits of the previous meas_id
                 $lastFourDigits = intval(substr($previousmeasid, -4));
                 $newLastFourDigits = str_pad(($lastFourDigits + 1), 4, '0', STR_PAD_LEFT);
                 $newmeasid = $bitemId.$newLastFourDigits;
                 //dd($newmeasid);
           } else {
               // If no previous meas_id, start with bitemid.0001
               $newmeasid = $bitemId.'0001';
               //dd($newmeasid);
           }
            //dd($newmeasid);
            // Check if a formula is provided and evaluate it
    if (!empty($formula)) {
        try {
            // Evaluate formula
            $evaluatedValue = eval("return $formula;");
            $qty = $evaluatedValue;

            // Ensure $qty is a numeric value
            if (!is_numeric($qty)) {
                $isQuantityValid = false;
            }
        } catch (Exception $error) {
            echo "Invalid formula: " . $error->getMessage();
            $isQuantityValid = false;
        }
    } elseif ($length !== null && $breadth !== null && $height !== null && $number !== null) {
        // Calculate based on length, breadth, height, and number
        // $number=2;
        // $length=1;
        // $breadth=7;
        // $height=5.52;
        $qty = ($number === 0 ? 1 : $number) *
               ($length === 0 ? 1 : $length) *
               ($breadth === 0 ? 1 : $breadth) *
               ($height === 0 ? 1 : $height);
        // $qty = $number*$length*$breadth*$height;
               //dd($qty);
    }

    // Convert $qty to 0 if it's 1, unless any input is 1
    if ($qty === 1 && ($number !== 1 || $length !== 1 || $breadth !== 1 || $height !== 1)) {
        
    }


     // Round $qty to 3 decimal points
     $qty = round($qty, 3);

     //dd($qty);

    
  //dd($date);


           //insert the data in embs table
           DB::table('embs')->insert([
            'Work_Id' => $workid,
            't_bill_id' => $tbillid,
            'b_item_id' => $bitemId,
            'meas_id' => $newmeasid,
            'sr_no' => $srno,
            'parti' => $Particulars,
            'number' => $number,
            'length' => $length,
            'breadth' => $breadth,
            'height' => $height,
            'formula' => $formula,
            'qty' => $qty,
            'measurment_dt' => $date,
            'dyE_chk_dt' => $date,
            'notforpayment' => $notforpayment
        ]);

        }else {
            // If the condition doesn't match, stop the loop
            continue;
        }
        
        
        //dd($itemno);
        // Now you can use $cellValue for each row
        // For example, you can save it to a database or perform other processing


         //get all data of measurements for bill
    $measurements=DB::table('embs')->where('t_bill_id', '=', $tbillid)->where('b_item_id' ,$bitemId)->get();

//dd($measurements);
    $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');

    $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');

    //get previous tbillid
    $previousTBillId = DB::table('bills')
    ->where('work_id' , $workid)
    ->where('t_bill_id', '<', $tbillid) // Add your condition here
    ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
    ->limit(1) // Limit the result to 1 row
    ->value('t_bill_id');
        //dd($previousTBillId);
        //titem id
        $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

          // Retrieve QtyDcml_Ro from tnditems based on t_item_id
        $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');
  // Retrieve previous executed quantity from bil_item based on b_item_id
        $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
        // dd($previousexecqty);

         // If previous executed quantity is null, set it to 0
        if (is_null($previousexecqty)) {
            $previousexecqty = 0;
        }

         // Calculate current quantity from embs table based on conditions and format it
        $curqty = number_format(round(DB::table('embs')->where('b_item_id', $bitemId)->where('notforpayment', '=', 0)->sum('qty'), $Qtydec), 3, '.', '');
        // dd($execqty)
        
        // Calculate executed quantity as sum of previous and current quantities, and format it
        $execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
        //dd($execqty);
      // Retrieve bill rate from bil_item based on b_item_id
        $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');
         
        // Calculate current amount based on current quantity and bill rate
             $curamt=$curqty*$billrt;
         // Retrieve previous amount from bil_item based on b_item_id
        $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

        // Calculate bill item amount as sum of current and previous amounts
            $bitemamt=$curamt+$previousamt;

             // Update bil_item with calculated values
        DB::table('bil_item')->where('b_item_id' , $bitemId)->update([

            'exec_qty' => $execqty,
            'cur_qty' => $curqty,
            'prv_bill_qty' => $previousexecqty,
            'cur_amt' => $curamt,
            'b_item_amt' => $bitemamt,
        ]);

          // Retrieve t_item_id again from bil_item
        $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
        $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
         // Retrieve tnd quantity
         $tndqty=$tnditem->tnd_qty;
         
          // Instantiate CommonHelper for amount formatting
           $amountconvert=new CommonHelper();
                
          // Retrieve and format tnd cost item amount
        $tndcostitem=$tnditem->t_item_amt;
         // Calculate percentage of executed quantity to tnd quantity
        $percentage=round(($execqty / $tndqty)*100 , 2);
         // Calculate total cost item based on bill rate and executed quantity
        $totlcostitem=round($billrt*$execqty , 2);
         // Calculate cost difference between tnd cost item and total cost item
        $costdifference= round($tndcostitem-$totlcostitem , 2);
        //dd($costdifference);
                    
        // Format amounts to Indian Rupees format using CommonHelper
                     $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);

                  // Initialize sums for matched and unmatched conditions
        $parta = 0; // Initialize the sum for matched conditions
        $partb = 0; // Initialize the sum for unmatched conditions
        
        $cparta = 0; // Initialize the sum for matched conditions
        $cpartb = 0; // Initialize the sum for unmatched conditions
     
        // Retrieve all item ids related to t_bill_id
        $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);
                  // Iterate through item ids to calculate sums based on conditions
                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                     // Check conditions for specific item ids or patterns
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          ) 
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                         
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions
              
              
                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }
                       
       // Calculate gross amount based on sum of b_item_amt for t_bill_id
        $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

        // Calculate current gross amount based on sum of cur_amt for t_bill_id
        $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
         // Retrieve A_B_Pc and Above_Below from workmasters based on work_id
        $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
        //dd($beloaboperc);
        $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');
     
        // Initialize bill_amt and calculate it based on conditions
        $bill_amt=0;
       $abeffect = $parta * ($beloaboperc / 100);
       $cabeffect = $cparta * ($beloaboperc / 100);
       
                      if ($beloAbo === 'Above') {
                   
                         
                         $bill_amt = round(($parta + $abeffect), 2);
                         $cbill_amt = round(($cparta + $cabeffect), 2);
                   
                     } elseif ($beloAbo === 'Below') {
                   
                         $bill_amt = round(($parta - $abeffect), 2);
                         $cbill_amt = round(($cparta - $cabeffect), 2);
                   
                     }
                   
                      // Determine whether to add a minus sign
                      if ($beloAbo === 'Below') {
                          $abeffect = -$abeffect;
                          $cabeffect = -$cabeffect;
                          $beloaboperc = -$beloaboperc;
                         }
                         //dd($abeffect);
                        //$part_a_ab=($parta * $beloaboperc / 100);
                        //dd($partb);
                    
                   
                   
                              
                        // Calculate GST base and GST amount based on bill_amt
                        $Gstbase = round($bill_amt, 2);
                        $cGstbase = round($cbill_amt, 2);
                               //dd($Gstbase);
                   
                               $Gstamt= round($Gstbase*(18 / 100), 2);
                               $cGstamt= round($cGstbase*(18 / 100), 2);
                                //dd($Gstamt);
     
                                $part_A_gstamt=$Gstbase + $Gstamt;
                                $cpart_A_gstamt=$cGstbase + $cGstamt;
     
     
                                $billamtgt = $partb + $part_A_gstamt;
                                $cbillamtgt = $cpartb + $cpart_A_gstamt;
     
                  $integer_part = floor($billamtgt);  // Extract the integer part
                  $cinteger_part = floor($cbillamtgt);
     
     
                  $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                  $cdecimal_part = $cbillamtgt - $cinteger_part; 
                  //dd($decimal_part);
     
                  $billamtro = round($decimal_part, 2);
                  $cbillamtro = round($cdecimal_part, 2);
                  //dd($rounded_decimal_part);
     
             //     // Round the total bill amount
             //     $billamtro = round($billamtgt);
             //     //dd($rounded_billamtgt);
     
             //    // Calculate the difference
             //     //$billamtro = $rounded_billamtgt - $billamtgt;
             //     dd($billamtro);

                // Adjust billamtro if greater than 0.50
                 if ($billamtro > 0.50) {
                     // Calculate the absolute difference
                     $abs_diff = abs($billamtro);
                     $billamtro = 1 - $abs_diff;
                     //dd($billamtro);
                 }
                 else {
                     // If it is, add a minus sign to the difference
                     $billamtro = -$billamtro;
                     //dd($billamtro);
                 }
     
                 if ($cbillamtro > 0.50) {
                     // Calculate the absolute difference
                     $cabs_diff = abs($cbillamtro);
                     $cbillamtro = 1 - $cabs_diff;
                     //dd($billamtro);
                 }
                 else {
                     // If it is, add a minus sign to the difference
                     $cbillamtro = -$cbillamtro;
                     //dd($billamtro);
                 }
                  //dd($billamtro);
     
                  $net_amt= $billamtgt + $billamtro;
                  $cnet_amt= $cbillamtgt + $cbillamtro;
                  //dd($net_amt);
     
                   // Determine whether to add a minus sign
                 
     
                  DB::table('bills')->where('t_bill_id' , $tbillid)->update([
     
                     'part_a_amt' => $parta,
                     'part_a_gstamt' => $part_A_gstamt,
                     'part_b_amt' => $partb,
                     'gst_amt' => $Gstamt,
                     'gst_base' => $Gstbase,
                     'gross_amt' => $billgrossamt,
                     'a_b_effect' => $abeffect,
                     'bill_amt' => $bill_amt,
                     'bill_amt_gt' => $billamtgt,
                     'bill_amt_ro' => $billamtro,
                     'net_amt' => $net_amt,
     
                     'c_part_a_amt' => $cparta,
                     'c_part_a_gstamt' => $cpart_A_gstamt,
                     'c_part_b_amt' => $cpartb,
                     'curr_grossamt' => $cbillgrossamt,
                     'c_billamt' =>  $cbill_amt,
                     'c_gstamt' => $cGstamt,
                     'c_gstbase' => $cGstbase,
                     'c_abeffect' => $cabeffect,
                     'c_billamtgt' => $cbillamtgt,
                     'c_billamtro' => $cbillamtro,
                     'c_netamt' => $cnet_amt,
                     'mbstatus_so' => 0
                  ]);

                 
   //dd($measurements);

    } 


    }

//dd($itemno);

 $billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
                  $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
                  $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();


                  //workdetails
    $billtemdata=DB::table('bil_item')->where('b_item_id', $bitemId)->first();
    
    $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');
   
    $tbilldata=DB::table('bills')->where('t_bill_id', $tbillid)->first();
   
    $workid=DB::table('bills')->where('t_bill_id', $tbillid)->value('work_id');
    
    $convert=new CommonHelper();
   
        // Format work details with currency and other specifics
      $workmasterdetail=DB::table('workmasters')->where('work_id' , $workid)->first();
      $workdetail = '<div class="container-fluid"><div><h5 style="color: darkblue;">Work Details</h5>' .
      '<strong>Work Name:</strong> ' . $workmasterdetail->Work_Nm . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order No:</strong> ' . $workmasterdetail->WO_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Date:</strong> ' . $workmasterdetail->Wo_Dt . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Work Order Amount:</strong> ' . $convert->formatIndianRupees($workmasterdetail->WO_Amt) . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Fund Head Code:</strong> ' . DB::table('fundhdms')->where('F_H_id', $workmasterdetail->F_H_id)->value('F_H_CODE')
 . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Bill No:</strong> ' . $tbilldata->t_bill_No . '&nbsp;&nbsp;&nbsp;' .
      '<strong>Total Uptodate Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">'. $convert->formatIndianRupees($billtemdata->b_item_amt) . '</span>&nbsp;&nbsp;&nbsp;' .
      '<strong>Current Bill Amount:</strong> <span style="color: #D21F3C; font-weight: bold;">' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</span>&nbsp;&nbsp;&nbsp;' . 
      '</div></div>';

     
   // Call a method from AnotherController
   //call([EmbController::class, 'methodName'], $workid); //dd($billdata);
    return [
        'measurements' => $measurements,
        'returnstlData' => $returnstlData,
        'billitem' => $billitem,
        'previousexecqty' => $previousexecqty,
        'curqty' => $curqty,
        'execqty' => $execqty,
        'tndqty' => $tndqty , 
        'tndcostitem' => $tndcostitem , 
        'percentage' => $percentage , 
        'totlcostitem' => $totlcostitem ,
        'costdifference' => $costdifference ,
        'billdata' => $billdata,
        'billitemdata' => $billitemdata,
        'lasttbillid' => $lasttbillid,
        'workdetail' => $workdetail,

    ];

    }



//Excel data add to all bill item ids
public function Allmeasexcel($excelfile , $tbillid)
{
    //intitial html varaible
    $html='';

    //intitialise varaibles
    $count=0;
    $totalcount=0;
   $InsertDataCount=0;
   
   $firstItemProcessed = false; // Flag to track if the first item has been processed
   $checkTotalRow = 0;
  
    //create instance of spreadsheet
      $spreadsheet = IOFactory::load($excelfile);
      $excelsheet = $spreadsheet->getActiveSheet();
          
      $name = $excelfile->getClientOriginalName();
       
      //get highest row
      $highestRow = $excelsheet->getHighestRow();

      //get highest column
      $highestColumn = $excelsheet->getHighestColumn();
     //dd($highestRow);
     $highestRowSpinner = 0; // Variable to store the highest row with data
  
   
     //declare varaible for excel cells
     $checkdata = $excelsheet->getCell('B'. 1)->getValue();
  
    
  
     // Start the loop from the second row (assuming the first row is the header)
  //    for ($row = 1; $row <= $highestRow; $row++) {
    
  //        $allCellsEmpty = true; // Assume all cells in the row are empty initially
     
  //        // Check all cells in the row
  //        foreach(range('A', $highestColumn) as $column) {
  //            $cellValue = $excelsheet->getCell($column . $row)->getValue();
  //           //  dd($cellValue);
             
  //            // If any cell in the row has data, mark allCellsEmpty as false
  //            if (!empty($cellValue)) {
  //                $allCellsEmpty = false;
  //                break; // Exit the loop as soon as data is found in any cell
  //            }
  //        }
     
  //        // If all cells in the row are empty, stop the loop
  //        if ($allCellsEmpty) {
  //            break;
  //        }
     
  //        // If any cell in the row has data, update the highestRowWithData
  //        $highestRowSpinner = $row-1 ;
  //    }
     
  //     //Display or echo the highest row count with data
  //     dd("Highest Row with Data: " . $highestRowSpinner);
    
  
    //work id
      $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');
  
        //bitemids related billid
      $bitemids=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get('b_item_id');
   

      //declare null varaibles
      $measurements=null;
      $returnstlData=null;
  
    
      $previousexecqty=null;
      $curqty = null;
      $execqty= null;
      $lastProcessedRowIndices = [];
      $currentrow=1;


      
$flag2=0;

          //loop for excel data row by bow
                    for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
                        $itemno = $excelsheet->getCell('A' . $rowIndex)->getValue();
                        
                        if (isset($itemno) && !empty($itemno)) {
                            $checkTotalRow++; // Increment count if 'Item No' is not found
                        }
                    }
                    

//dd($checkTotalRow);
                    // Check if the file name contains 'emb_rcc_stl' or 'emb_normal'
        if (strpos($name, 'emb_rcc_stl') !== false) {
            // Load steel Excel sheet
           // flag count for headings in steel excel sheet
            for ($rowIndex = 1; $rowIndex <= $highestRow; $rowIndex++) {
                $itemno = $excelsheet->getCell('A' . $rowIndex)->getValue();
                $A1=$excelsheet->getCell('A'. $rowIndex)->getValue();
                          
            if ($A1 == 'Item No') {
                $flag2++;
            }
            }
            //dd($name);
             $item_ids_to_check = ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"];
           

          
            // Initialize an empty array to collect the results
            $bitemisteelids = [];
            
            //loop in item ids
            foreach ($item_ids_to_check as $item_id_suffix) {
                // Fetch data from database based on the condition
                $bitem_data = DB::table('bil_item')
                                ->where('t_bill_id', $tbillid)
                                ->where('item_id', 'like', '%' . $item_id_suffix)
                                ->pluck('b_item_id')
                                ->toArray();
            
                // Merge the fetched b_item_ids into the result array
                $bitemisteelids = array_merge($bitemisteelids, $bitem_data);
            }
           
            //dd($bitemisteelids);
           //$bitemisteelids=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('item_id' , [])->get('b_item_id');

                    
    //loop in  bitemids of steel
    foreach ($bitemisteelids as $bitemId) 
    {
        //$bitemId=$bitemid->b_item_id;
         
          //item id get from bitem id
          $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
          $item = DB::table('bil_item')->where('b_item_id', $bitemId)->first();
 //dd($item);

 //item no and sub no
    $t_item_no = $item->t_item_no;
    $sub_no = $item->sub_no ?? ''; // Default to empty string if sub_no doesn't exist

    //conctenate item no and sub no
    $concatenatedValue = $t_item_no . $sub_no;
   //itemids in arrays 
    if (
        in_array(substr($itemid , -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])
            //in_array(substr($itemid, -6), ["001295", "001298", "002115", "003960", "003963", "004351", "003550", "003551", "002064", "002065", "002066", "002067", "002068", "002069", "003399", "003558", "004566", "004567"])
        ) {
            // Code to execute when there's a match
            //dd($bitemId);


           //highest row
            $highestRow = $excelsheet->getHighestRow();
            //get highest column
            $highestColumn = $excelsheet->getHighestColumn();
            //dd($highestRow);
            $membersrno=null;
            //$itemno = $worksheet->getCell('A'. 3)->getValue();
            //dd($itemno);
            $processingStarted = false;
            $previousItemNo = null;
          // $rowindexupdate=0;
           
          // dd($currentrow);
          // $rowIndex= $currentrow;
         
        //   $skipNextRow = false; // Flag to skip the next row
        //   if (!$firstItemProcessed) {
        //   for ($rowIndex = $currentrow; $rowIndex <= $highestRow; $rowIndex++) {
        //       if ($skipNextRow) {
        //           $skipNextRow = false; // Reset the flag
        //           continue; // Skip the current iteration
        //       }
          
        //       $itemno = $excelsheet->getCell('A'. $rowIndex)->getValue();
              
        //       if (isset($itemno) && !empty($itemno) && $itemno == 'Item No') {
        //           $skipNextRow = true; // Set the flag to skip the next row
        //           continue; // Skip the current iteration
        //       } else {
        //           $checkTotalRow++; // Increment count if 'Item No' is not found
        //       }
        //   }
        //   $firstItemProcessed = true; // Update flag to indicate the first item has been processed
        // }
        
          
         // dd($checkTotalRow); 
     
           //loop through all excel row 
            for ($rowIndex = $currentrow; $rowIndex <= $highestRow; $rowIndex++) {

               // dd($currentrow);

                //item title and item no
                $itemtitle = $excelsheet->getCell('A'. $rowIndex)->getValue();
                $itemno =  $excelsheet->getCell('B'. $rowIndex)->getValue();

              
              //dd($itemno);
                // Check if the conditions are met to start or stop processing
                if (!$processingStarted && !empty($itemtitle) && !empty($itemno) && $itemtitle == 'Item No' && $itemno==$concatenatedValue) {
                    // Start processing from this row
                    $rowIndex = $rowIndex + 2;
                    $processingStarted = true;
                    $previousItemNo = $itemno; // Store the first "Item No"
                    //dd($itemno , $concatenatedValue);
                  } elseif ($processingStarted && !empty($itemtitle) && !empty($itemno) && $itemtitle == 'Item No' && $itemno != $previousItemNo) {
                    // Restart processing from this row if a different "Item No" is encountered
                    $previousItemNo = $itemno;
                    $currentrow=$rowIndex; // Update the previous "Item No"
                    break; // Skip this iteration to avoid double processing
                    //dd($itemno , $concatenatedValue);
                    //$rowIndex = $rowIndex + 2;
                  //dd( $currentrow);
                    
                  } elseif ($processingStarted && !empty($itemtitle) && !empty($itemno) && $itemtitle == 'Item No') {
                    // Stop processing if the condition is met again
                    $processingStarted = false;
                    //dd($itemno , $concatenatedValue);
                    continue; // Skip this iteration to avoid double processing
                  } elseif (!$processingStarted && empty($itemtitle) && empty($itemno)) {
                    // If the data is empty and processing hasn't started, stop the iteration
                    break;
                    //dd($itemno , $concatenatedValue);
                  }
             
                  
                 //dd($itemno);
                // if (!empty($itemtitle) && !empty($itemno) && $itemtitle == 'itemno') {

                    
                    // Process the current row
                    // ... your processing code ...

                       //dd($itemno);
                    //    $rowIndex=$rowIndex+2;
                       
                    //   for($Rowindex=$rowIndex; $Rowindex <= $highestRow; $Rowindex++)
                    //   {
                        if ($processingStarted) {
                            
                            
                          
                       //declare all varaibles to excel row cell
                       $membersrno = $excelsheet->getCell('A'. $rowIndex)->getValue();
                       //dd($msrno);
                       $rccmember =  $excelsheet->getCell('B'. $rowIndex)->getValue();
                       $meberparticulars =  $excelsheet->getCell('C'. $rowIndex)->getValue();
                      //dd($rowIndex);
                       $noofmemb =  $excelsheet->getCell('D'. $rowIndex)->getValue();
                       $barsrno =  $excelsheet->getCell('E'. $rowIndex)->getValue();
                      // dd($barsrno);
                       $barparticulars =  $excelsheet->getCell('F'. $rowIndex)->getValue(); 
                       //dd($barparticulars);
                       $noofbars =  $excelsheet->getCell('G'. $rowIndex)->getValue();
                      // dd();
                       $l6 =  $excelsheet->getCell('H'. $rowIndex)->getValue();
                       $l8 = $excelsheet->getCell('I'. $rowIndex)->getValue();
                       $l10 =  $excelsheet->getCell('J'. $rowIndex)->getValue();
                       $l12 = $excelsheet->getCell('K'. $rowIndex)->getValue();
                       //dd($l12);
                       $l16 =  $excelsheet->getCell('L'. $rowIndex)->getValue();
                       $l20 = $excelsheet->getCell('M'. $rowIndex)->getValue();
                       $l25 =  $excelsheet->getCell('N'. $rowIndex)->getValue();
                       $l28 = $excelsheet->getCell('O'. $rowIndex)->getValue();
                       $l32 =  $excelsheet->getCell('P'. $rowIndex)->getValue();
                       $l36 = $excelsheet->getCell('Q'. $rowIndex)->getValue();
                       $l40 =  $excelsheet->getCell('R'. $rowIndex)->getValue();
                       $measdate = $excelsheet->getCell('S'. $rowIndex)->getValue();
                       //dd($measdate);

                       $date= Date::excelToDateTimeObject(intval($measdate))->format('Y-m-d');

                       $measdtfrom=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_from');
                       //dd($measdtfrom);
                     $measdtupto=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_upto');
                              //dd($measdtupto);
                               // Assuming $dom is in a valid date format (e.g., 'YYYY-MM-DD')
                                 //$domDate = date_create_from_format('Y-m-d', $dom);
                                 //dd($domDate);
                       
                                
                      //Apply datewise condition 
                     if ( $date >= $measdtfrom && $date <= $measdtupto) {       

                        // create steel id
            $previoussteelid=DB::table('stlmeas')->where('b_item_id', '=', $bitemId)->orderby('steelid', 'desc')->first('steelid');
            //dd( $previousmeasid);
            if ($previoussteelid) {
                $previousstld = $previoussteelid->steelid; // Convert object to string
                // Increment the last four digits of the previous meas_id
                 $lastFourDigits = intval(substr($previousstld, -4));
                 $newLastFourDigits = str_pad(($lastFourDigits + 1), 4, '0', STR_PAD_LEFT);
                 $newsteelid = $bitemId.$newLastFourDigits;
                 //dd($newsteelid);
           } else {
               // If no previous meas_id, start with bitemid.0001
               $newsteelid = $bitemId.'0001';
               //dd($newsteelid);
           }
    
          // dd($newsteelid);
              
            $rcmbrid = DB::table('bill_rcc_mbr')->where('b_item_id', '=', $bitemId)->where('rcc_member' , $rccmember)->where('member_particulars' , $meberparticulars)->first('rc_mbr_id');
            //dd($rcmbrid);

            if ($rcmbrid) {
                 // If no previous meas_id, start with bitemid.0001
                 $newrcmbrid = $rcmbrid->rc_mbr_id; // Access rc_mbr_id property
                 //dd($newmeasid);
           } else {
             

            $previousrcmbrid = DB::table('bill_rcc_mbr')->where('b_item_id', '=', $bitemId)->orderby('rc_mbr_id' , 'desc')->first('rc_mbr_id');
              
               // Increment the last four digits of the previous meas_id

               if($previousrcmbrid)
               {

                $previousrcid = $previousrcmbrid->rc_mbr_id; // Convert object to string

                $lastFiveDigits = intval(substr($previousrcid, -5));
                $newLastFiveDigits = str_pad(($lastFiveDigits + 1), 5, '0', STR_PAD_LEFT);
                $newrcmbrid = $bitemId.$newLastFiveDigits;
               }
               else
               {
                $newrcmbrid = $bitemId.'00001';
               }
                
               
           }
           //dd($newrcmbrid);
            //  $rcmbrid=$bitemId.$rcid;
             //dd($rcmbrid);

             //dd($date);

             //dd($membersrno);
             if($membersrno)
             {
             if ($rcmbrid) {
                // If $rcmbrid is not null, do not insert 'rc_mbr_id' in the insert query

            } else {
                // If $rcmbrid is null, insert 'rc_mbr_id' in the insert query
               
                //dd($membersrno);
                DB::table('bill_rcc_mbr')->insert([
                    'work_id' => $workid,
                    't_bill_id' => $tbillid,
                    'b_item_id' => $bitemId,
                    'rc_mbr_id' => $newrcmbrid, // Insert 'rc_mbr_id'
                    'member_sr_no' => $membersrno,
                    'rcc_member' => $rccmember,
                    'member_particulars' => $meberparticulars,
                    'no_of_members' => $noofmemb,
                ]);
            }
            
          } 
            
            
            
            

           // Determine which length variable to consider first
                        $preferredLength = null;
                        
                        if ($l6 !== null) {
                            $preferredLength = $l6;
                        } elseif ($l8 !== null) {
                            $preferredLength = $l8;
                        } elseif ($l10 !== null) {
                            $preferredLength = $l10;
                        }  
                         elseif ($l12 !== null) {
                            $preferredLength = $l12;
                        }                      
                          elseif ($l16 !== null) {
                            $preferredLength = $l16;
                        } elseif ($l20 !== null) {
                            $preferredLength = $l20;
                        } elseif ($l25 !== null) {
                            $preferredLength = $l25;
                        }                        
                           elseif ($l32 !== null) {
                            $preferredLength = $l32;
                        }                      
                          elseif ($l36 !== null) {
                            $preferredLength = $l36;
                        } elseif ($l40 !== null) {
                            $preferredLength = $l40;
                         } 

                        //dd( $preferredLength);

                        if ($preferredLength !== null) {
                     // Calculate bar length using the preferred value
                            $barlength = $noofmemb * $noofbars * $preferredLength;
                            // dd($barlength,$noofmemb,$noofbars,$preferredLength);

                            }

                        // dd($barlength);
                        if($barsrno)  {

                            //insert steel data in steel table
                        DB::table('stlmeas')->insert([
                    

                            'work_id' => $workid,
                            't_bill_id' => $tbillid,
                            'b_item_id' => $bitemId,
                            'steelid' => $newsteelid,
                            'rc_mbr_id' => $newrcmbrid,
                            'bar_sr_no' => $barsrno,
                            'bar_particulars' => $barparticulars,
                            'no_of_bars' => $noofbars,
                            'ldiam6' => $l6,
                            'ldiam8' => $l8,
                             'ldiam10' => $l10,
                            'ldiam12' => $l12,
                            'ldiam16' => $l16,
                            'ldiam20' => $l20,
                            'ldiam25' => $l25,
                            'ldiam28' => $l28,
                            'ldiam32' => $l32,
                            'ldiam36' => $l36,
                            'ldiam40' => $l40,
                            'date_meas' => $date,
                            'bar_length' => $barlength,
                            'dyE_chk_dt' => $date,

                        ]);
        
                        $InsertDataCount++;
                    $count++;
                    }

                }   




                }
                else{
                    continue;
                }
                
              
               // dd( $dom);
               $currentrow=$currentrow+1;
               //dd($currentrow);
            }

        


  // Retrieve stlmeas data based on b_item_id
        $stldata = DB::table('stlmeas')
     ->where('b_item_id', $bitemId)
     ->get();

      // Columns to check and adjust
            $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];
      
              // Adjust ldiam columns if necessary
            foreach ($stldata as &$data) {
              if (is_object($data)) {
                  foreach ($ldiamColumns as $ldiamColumn) {
                      if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {
                          
                          $temp = $data->$ldiamColumn;
                          $data->$ldiamColumn = $data->bar_length;
                          $data->bar_length = $temp;
                         // dd($data->bar_length , $data->$ldiamColumn);
                          break; // Stop checking other ldiam columns if we found a match
                      }
                  }
              }
          }
          
         // Calculate sums for ldiam columns
          $sums = array_fill_keys($ldiamColumns, 0);
      
          foreach ($stldata as $row) {
              foreach ($ldiamColumns as $ldiamColumn) {
                  $sums[$ldiamColumn] += $row->$ldiamColumn;
              }
          }//dd($stldata);
      //dd($sums);
        // Retrieve bill_rcc_mbr data where exists stlmeas data
      $bill_member = DB::table('bill_rcc_mbr')
            ->whereExists(function ($query) use ($bitemId) {
                $query->select(DB::raw(1))
                      ->from('stlmeas')
                      ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                      ->where('bill_rcc_mbr.b_item_id', $bitemId);
            })
            ->get();          
      
      //$bill_memberdata=DB::table('rcc_mbr')->get();
      //dd($bill_member);
      // Generate the HTML content
        // Retrieve rc_mbr_ids for the given b_item_id
      $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();
      //d($rc_mbr_ids);
      
    
            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('item_id');

            if (in_array(substr($itemid, -6), ["003351", "003878"])) 
            {
                 $sec_type="HCRM/CRS Bar";
            }
         else{
                 $sec_type="TMT Bar";
             }
      

             $selectedlength = [];
             $size=null;
             $sr_no = 0; // Initialize the serial number
             $totalweight = 0; // Initialize the total weight

            // Fetch distinct dates for measurement
             $distinctStlDate = DB::table('stlmeas')
             ->select('date_meas') // Add other columns as needed
             ->where('b_item_id', $bitemId)
             ->groupBy('date_meas')
             ->orderBy('date_meas', 'asc') // Optional: Order by date in descending order
             ->get();
 
             // Delete existing records in 'embs' table for current $bitemId
             DB::table('embs')->where('b_item_id', $bitemId)->delete();
 
          
             $Size=null;
            //dd($sums);
             foreach($distinctStlDate as $date)
            {
                // Initialize variables for different bar lengths
            // //dd($date);
            $barlenghtl6=0;
             $barlenghtl8=0;
             $barlenghtl10=0;
             $barlenghtl12=0;
             $barlenghtl16=0;
             $barlenghtl20=0;
             $barlenghtl25=0;
             $barlenghtl28=0;
             $barlenghtl32=0;
             $barlenghtl36=0;
             $barlenghtl40=0;
             $barlenghtl45=0;
                              // Fetch steel measurement data for the current date
                                 $steelmeasdata=DB::table('stlmeas')->where('b_item_id', $bitemId)->where('date_meas', $date->date_meas)->get();
 
                               //dd($steelmeasdata);
 
                                 foreach ($steelmeasdata as $row) {
 //dd($row);
                                   $measurement=DB::table('stlmeas')->select('ldiam6','ldiam8' , 'ldiam10' , 'ldiam12' , 'ldiam16' , 'ldiam20' , 'ldiam25' , 'ldiam28' , 'ldiam32' , 'ldiam36' , 'ldiam40', 'ldiam45')->where('steelid' , $row->steelid)->first();
                                    
                                     // Initialize an object to hold valid key-value pairs
                                   $keyValuePairs = (object)[];
 
                                    // Filter out null values from measurement and store in $keyValuePairs
                                   foreach ($measurement as $column => $value) {
                                       if (!is_null($value)) {
                                           $keyValuePairs->$column = $value;
                                       }
                                   }
                                   //dd(key($keyValuePairs));
                                 //   foreach ($row as $key => $value) {
                                 //     }
                                      // Determine bar size based on measurement type
                                     switch (key($keyValuePairs)) {
                                         case 'ldiam6':
                                             $Size = "6 mm dia";
                                             $barlenghtl6 += $row->bar_length;
                                             break;
                                         case 'ldiam8':
                                             $Size = "8 mm dia";
                                             $barlenghtl8 += $row->bar_length;
                                             break;
                                         case 'ldiam10':
                                             $Size = "10 mm dia";
                                             $barlenghtl10 += $row->bar_length;
                                             break;
                                         case 'ldiam12':
                                             $Size = "12 mm dia";
                                             $barlenghtl12 += $row->bar_length;
                                             break;
                                         case 'ldiam16':
                                             $Size = "16 mm dia";
                                             $barlenghtl16 += $row->bar_length;
                                             break;
                                         case 'ldiam20':
                                             $Size = "20 mm dia";
                                             $barlenghtl20 += $row->bar_length;
                                             break;
                                         case 'ldiam25':
                                             $Size = "25 mm dia";
                                             $barlenghtl25 += $row->bar_length;
                                             break;
                                         case 'ldiam28':
                                             $Size = "28 mm dia";
                                             $barlenghtl28 += $row->bar_length;
                                             break;
                                         case 'ldiam32':
                                             $Size = "32 mm dia";
                                             $barlenghtl32 += $row->bar_length;
                                             break;
                                         case 'ldiam36':
                                             $Size = "36 mm dia";
                                             $barlenghtl36 += $row->bar_length;
                                             break;
                                         case 'ldiam40':
                                             $Size = "40 mm dia";
                                             $barlenghtl40 += $row->bar_length;
                                             break;
                                         case 'ldiam45':
                                             $Size = "45 mm dia";
                                             $barlenghtl45 += $row->bar_length;
                                             break;
                                     }
                                 }//dd($stldata);
 



 
                         // Process each bar length if greater than 0 and update $html and $totalweight
                                 if($barlenghtl6 > 0)
                                 {

                                    $size="6 mm dia";
                                     
                                    //function is created 
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl6 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
     //dd($tmtdata);           
                                              
                                 }
 
 
 
 
 
                             
                            
                                 if($barlenghtl8 > 0)
                                 {
                                         $size="8 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl8 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                        
                                              
 
                                 }
                              
                                 if($barlenghtl10 > 0)
                                 {
                                         $size="10 mm dia";
                                        
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl10 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                $html .= $tmtdata['html']; // Accessing html
                                              
 
                                 }
                                 if($barlenghtl12 > 0)
                                 {
                                         $size="12 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl12 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
 
                                 }
                                 if($barlenghtl16 > 0)
                                 {
                                         $size="16 mm dia";
                                          //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl16 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html                                                                                      
 
                                 }
 
                                
                               
                                 if($barlenghtl20 > 0)
                                 {
                                         $size="20 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl20 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                 
                                 }

                                 if($barlenghtl25 > 0)
                                 {
                                         $size="25 mm dia";
                                           //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl25 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                                                                   
                                 }
                                
                               
                                 if($barlenghtl28 > 0)
                                 {
                                         $size="28 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl28 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                  $html .= $tmtdata['html']; // Accessing html
                                                  
                 
                                 }
                               
                                
                                 if($barlenghtl32 > 0)
                                 {
                                         $size="32 mm dia";
                                             //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl32 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                            $html .= $tmtdata['html']; // Accessing html
                                                  
                 
                                 }
                               
                                
                                
                                 if($barlenghtl36 > 0)
                                 {
                                         $size="36 mm dia";
                                            //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl36 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                 
                                 }


                                 if($barlenghtl40 > 0)
                                 {
                                         $size="40 mm dia";
                                         //function call for the total weight and emb table in that insert steel data
                                 $tmtdata = $this->steelinsertnormal($size , $barlenghtl40 , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no , $totalweight);
                                 $totalweight += round($tmtdata['singleweight'], 3); // Accessing singleweight
                                 $html .= $tmtdata['html']; // Accessing html
                                                                  
                                 }
                                // $barlengths = [$barlenghtl6, $barlenghtl8, $barlenghtl10, $barlenghtl12, $barlenghtl16, $barlenghtl20, $barlenghtl25, $barlenghtl28, $barlenghtl32, $barlenghtl36, $barlenghtl40, $barlenghtl45];
 
 
                                
 
                                 
                             }
 


             // Fetch the total bill ID using the bil_item ID
             $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');
         
             // Fetch the work ID using the fetched total bill ID
             $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');
         
              // Fetch the previous total bill ID based on the work ID and current total bill ID
             $previousTBillId = DB::table('bills')
             ->where('work_id' , $workid)
             ->where('t_bill_id', '<', $tbillid) // Add your condition here
             ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
             ->limit(1) // Limit the result to 1 row
             ->value('t_bill_id');
                 //dd($previousTBillId);
         
                  // Fetch the total item ID using the bil_item ID
                 $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');

                 // Fetch the quantity decimal places from tnditems table based on the total item ID
           $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');
           
           // Fetch the previous executed quantity rounded to 3 decimal places using bil_item ID
           $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
           //dd($previousexecqty);
           
           // Handle case where previous executed quantity is null
           if (is_null($previousexecqty)) {
               $previousexecqty = 0;
           }
           
           // Calculate the current quantity rounded to 3 decimal places
             $curqty= round($totalweight , $Qtydec);
           
             // Calculate the executed quantity as the sum of previous executed and current quantities
             $execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
             //dd($execqty);
         
               // Fetch the bill rate using the bil_item ID
                 $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                   // Calculate the current amount based on current quantity and bill rate
                      $curamt=$curqty*$billrt;

                        // Fetch the previous amount using the bil_item ID
                 $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

                 // Calculate the total bill item amount as the sum of current and previous amounts
                     $bitemamt=$curamt+$previousamt;

                     // Update the bil_item table with new values
                 DB::table('bil_item')->where('b_item_id' , $bitemId)->update([
         
                     'exec_qty' => $execqty,
                     'cur_qty' => $curqty,
                     'prv_bill_qty' => $previousexecqty,
                     'cur_amt' => $curamt,
                     'b_item_amt' => $bitemamt,
                 ]);

                  // Fetch the total item details using the bil_item ID
                 $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
           $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
        //   $tndqty=round($tnditem->tnd_qty , 3);
         $tndqty=$tnditem->tnd_qty;
         
          // Format the amount using the CommonHelper class
           $amountconvert=new CommonHelper();
                
          // Fetch and format the tender cost item amount from tnditems table
           $tndcostitem=$tnditem->t_item_amt;
            // Calculate and format the percentage of executed quantity to tender quantity
           $percentage=round(($execqty / $tndqty)*100 , 2);
           // Calculate and format the total cost item amount based on bill rate and executed quantity
           $totlcostitem=round($billrt*$execqty , 2);
             // Calculate and format the cost difference between tender cost and total cost item amount
           $costdifference= round($tndcostitem-$totlcostitem , 2);
           
               //convert amount in indian rs format
                $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);

            
           $parta = 0; // Initialize the sum for matched conditions
           $partb = 0; // Initialize the sum for unmatched conditions
           
           $cparta = 0; // Initialize the sum for matched conditions
           $cpartb = 0; // Initialize the sum for unmatched conditions
        
           // Fetch all item IDs associated with the total bill ID
           $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);
                
                 // Loop through each item ID and categorize based on conditions
                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                     //dd($bitemid);
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          ) 
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                         
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched condition
                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }
        
                          
        // Fetch and calculate total gross amount and current gross amount based on total bill ID
           $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

           $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
           // Fetch percentage and above/below values from workmasters table based on work ID
           $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
           //dd($beloaboperc);
           $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');
        
           // Initialize bill amount
           $bill_amt=0;

           // Calculate effect above/below based on conditions
          $abeffect = $parta * ($beloaboperc / 100);
          $cabeffect = $cparta * ($beloaboperc / 100);
          
          // Determine bill amount based on above/below conditions
                         if ($beloAbo === 'Above') {
                      
                            
                            $bill_amt = round(($parta + $abeffect), 2);
                            $cbill_amt = round(($cparta + $cabeffect), 2);
                      
                        } elseif ($beloAbo === 'Below') {
                      
                            $bill_amt = round(($parta - $abeffect), 2);
                            $cbill_amt = round(($cparta - $cabeffect), 2);
                      
                        }
                      
                         // Determine whether to add a minus sign
                         if ($beloAbo === 'Below') {
                             $abeffect = -$abeffect;
                             $cabeffect = -$cabeffect;
                             $beloaboperc = -$beloaboperc;
                            }
                            //dd($abeffect);
                           //$part_a_ab=($parta * $beloaboperc / 100);
                           //dd($partb);
                       
                      
                      
                                 
                        // Calculate GST base amount and GST amount based on bill amount
                           $Gstbase = round($bill_amt, 2);
                           $cGstbase = round($cbill_amt, 2);
                                  //dd($Gstbase);
                      
                                  $Gstamt= round($Gstbase*(18 / 100), 2);
                                  $cGstamt= round($cGstbase*(18 / 100), 2);
                                   //dd($Gstamt);
        
                                    // Calculate total GST amount including base amount
                                   $part_A_gstamt=$Gstbase + $Gstamt;
                                   $cpart_A_gstamt=$cGstbase + $cGstamt;
        
                                  // Calculate total bill amount and current bill amount including GST   
                                   $billamtgt = $partb + $part_A_gstamt;
                                   $cbillamtgt = $cpartb + $cpart_A_gstamt;
        
                    // Extract integer and decimal parts from bill amount
                     $integer_part = floor($billamtgt);  // Extract the integer part
                     $cinteger_part = floor($cbillamtgt);
        
        
                     $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                     $cdecimal_part = $cbillamtgt - $cinteger_part; 
                     //dd($decimal_part);

                    // Round and format the decimal part of bill amount
                     $billamtro = round($decimal_part, 2);
                     $cbillamtro = round($cdecimal_part, 2);
                     //dd($rounded_decimal_part);
        
                //     // Round the total bill amount
                //     $billamtro = round($billamtgt);
                //     //dd($rounded_billamtgt);
        
                //    // Calculate the difference
                //     //$billamtro = $rounded_billamtgt - $billamtgt;
                //     dd($billamtro);
                    //$billamtro=0.37;
                    if ($billamtro > 0.50) {
                        // Calculate the absolute difference
                        $abs_diff = abs($billamtro);
                        $billamtro = 1 - $abs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $billamtro = -$billamtro;
                        //dd($billamtro);
                    }
        
                    if ($cbillamtro > 0.50) {
                        // Calculate the absolute difference
                        $cabs_diff = abs($cbillamtro);
                        $cbillamtro = 1 - $cabs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $cbillamtro = -$cbillamtro;
                        //dd($billamtro);
                    }
                     //dd($billamtro);
        
                     $net_amt= $billamtgt + $billamtro;
                     $cnet_amt= $cbillamtgt + $cbillamtro;
                     //dd($net_amt);
        
                      // Determine whether to add a minus sign
                    
                     //update the bills realted all data
                     DB::table('bills')->where('t_bill_id' , $tbillid)->update([
        
                        'part_a_amt' => $parta,
                        'part_a_gstamt' => $part_A_gstamt,
                        'part_b_amt' => $partb,
                        'gst_amt' => $Gstamt,
                        'gst_base' => $Gstbase,
                        'gross_amt' => $billgrossamt,
                        'a_b_effect' => $abeffect,
                        'bill_amt' => $bill_amt,
                        'bill_amt_gt' => $billamtgt,
                        'bill_amt_ro' => $billamtro,
                        'net_amt' => $net_amt,
        
                        'c_part_a_amt' => $cparta,
                        'c_part_a_gstamt' => $cpart_A_gstamt,
                        'c_part_b_amt' => $cpartb,
                        'curr_grossamt' => $cbillgrossamt,
                        'c_billamt' =>  $cbill_amt,
                        'c_gstamt' => $cGstamt,
                        'c_gstbase' => $cGstbase,
                        'c_abeffect' => $cabeffect,
                        'c_billamtgt' => $cbillamtgt,
                        'c_billamtro' => $cbillamtro,
                        'c_netamt' => $cnet_amt,
                        'mbstatus_so' => 0
                     ]);
                                 
        
                    }
                    else{

                        continue;
                    }
}
          


        }
                        // if (!in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])) {

  //dd($bitemId);     $checkTotalRow = 0;

  //Normal measurement excel sheet 
  elseif (strpos($name, 'emb_normal') !== false) {
    // Load normal Excel sheet
  

   //declare varaible for excel row cell
    $itemno = $excelsheet->getCell('A'. 1)->getValue();
    //dd($itemno);
    $subno =  $excelsheet->getCell('B'. 2)->getValue();
     //dd();


  // Loop through rows and extract cell values
for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {

    //$checkTotalRow++;
    
    $itemno = $excelsheet->getCell('A'. $rowIndex)->getValue();

   // dd($itemno);
    $subno =  $excelsheet->getCell('B'. $rowIndex)->getValue();
    $srno =  $excelsheet->getCell('D'. $rowIndex)->getValue();
    $particulars =  $excelsheet->getCell('E'. $rowIndex)->getValue();
    //dd($particulars);
    $formula =  $excelsheet->getCell('F'. $rowIndex)->getValue(); 
    $number =  $excelsheet->getCell('G'. $rowIndex)->getValue();
    $length =  $excelsheet->getCell('H'. $rowIndex)->getValue();
    $breadth = $excelsheet->getCell('I'. $rowIndex)->getValue();
    $height =  $excelsheet->getCell('J'. $rowIndex)->getValue();
    $dom = $excelsheet->getCell('K'. $rowIndex)->getValue();

    $notforpayment = $excelsheet->getCell('L'. $rowIndex)->getValue();

    if($notforpayment == 1)
    {
        $Particulars= $particulars . " (Not for payment)";
    }
    else{
        $Particulars= $particulars;
    }
   
    /// Create a DateTime object from the string
    $date= Date::excelToDateTimeObject(intval($dom))->format('Y-m-d');
   //dd($date);

    //dd($tbillid);
    // $itemNo=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_no');
    // //
    
    // //dd($itemNo);
    // $subNo=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('sub_no');

    //measurement date from and upto
    $measdtfrom=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_from');
//dd($measdtfrom);
       $measdtupto=DB::table('bills')->where('t_bill_id', $tbillid)->value('meas_dt_upto');
       //dd($measdtupto);
        // Assuming $dom is in a valid date format (e.g., 'YYYY-MM-DD')
          //$domDate = date_create_from_format('Y-m-d', $dom);
          //dd($domDate);
       
        
        
        
        
          //check condition measurement dates
        if ( $date >= $measdtfrom && $date <= $measdtupto)
           {       
            //dd($itemno , $itemNo , $subno , $subNo , $bitemId);

             //insert the data in embs table
            DB::table('tempnormal')->insert([
                'tbillid' => $tbillid,
                'itemno' => $itemno,
                'subno' => $subno,
             'srno' => $srno,
             'particulars' => $Particulars,
             'number' => $number,
             'length' => $length,
             'breadth' => $breadth,
             'height' => $height,
             'formula' => $formula,
             'dateofmeasurement' => $date,
             'notforpayment' => $notforpayment

         ]);
            

        }

    }


    // Distinct data of the temperary data of normal measurements 



    $distinctnormaldata = DB::table('tempnormal')
    ->select('itemno', 'subno')
    ->where('tbillid', $tbillid)
    ->groupBy('itemno', 'subno')
    ->get();

    //dd($distinctnormaldata);
//lop through normal measurement data
    foreach($distinctnormaldata as $measdata)
    {

        //dd($measdata);
//bill item data check
        $bitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('t_item_no' , $measdata->itemno)->where('sub_no' , $measdata->subno)->first();
       // dd($bitemdata);

       

        // Check if any data exists in $bitemdata
    if ($bitemdata) {
        // Data exists, perform further actions
        $bitemId=$bitemdata->b_item_id;

      

     // get a temparory measurement data 
        $tempmeasdata = DB::table('tempnormal')->where('tbillid' , $tbillid)->where('itemno' , $measdata->itemno)->where('subno' , $measdata->subno)->get();

        //dd($tempmeasdata);
        foreach($tempmeasdata as $measdata)
        {
        //dd($measdata);
              $qty=0;
                     // create measurement id
             $previousmeasuementid=DB::table('embs')->where('b_item_id', '=', $bitemId)->orderby('meas_id', 'desc')->first('meas_id');
             //dd( $previousmeasuementid);
             if ($previousmeasuementid) {
                 $previousmeasid = $previousmeasuementid->meas_id; // Convert object to string
                 // Increment the last four digits of the previous meas_id
                  $lastFourDigits = intval(substr($previousmeasid, -4));
                  $newLastFourDigits = str_pad(($lastFourDigits + 1), 4, '0', STR_PAD_LEFT);
                  $newmeasid = $bitemId.$newLastFourDigits;
                  //dd($newmeasid);
            } else {
                // If no previous meas_id, start with bitemid.0001
                $newmeasid = $bitemId.'0001';
                //dd($newmeasid);
            }
             //dd($newmeasid);
             // Check if a formula is provided and evaluate it
     if (!empty($measdata->formula)) {
         try {
             // Evaluate formula
             $evaluatedValue = eval("return $measdata->formula;");
             $qty = $evaluatedValue;
 //dd($qty);
             // Ensure $qty is a numeric value
             if (!is_numeric($qty)) {
                 $isQuantityValid = false;
             }
         } catch (Exception $error) {
             echo "Invalid formula: " . $error->getMessage();
             $isQuantityValid = false;
         }
     } elseif ($measdata->length !== null && $measdata->breadth !== null && $measdata->height !== null && $measdata->number !== null) {
         // Calculate based on length, breadth, height, and number
         // $number=2;
         // $length=1;
         // $breadth=7;
         // $height=5.52;
         $qty = ($measdata->number === 0 ? 1 : $measdata->number) *
       ($measdata->length == 0 ? 1 : $measdata->length) *
       ($measdata->breadth == 0 ? 1 : $measdata->breadth) *
       ($measdata->height == 0 ? 1 : $measdata->height);

         // $qty = $number*$length*$breadth*$height;
                //dd($qty);
     }
 
     // Convert $qty to 0 if it's 1, unless any input is 1
    //  if ($qty === 1 && ($measdata->number !== 1 || $measdata->length !== 1 || $measdata->breadth !== 1 || $measdata->height !== 1)) {
         
    //  }
 
 
      // Round $qty to 3 decimal points
      $qty = round($qty, 3);
      //dd($qty);
 
     
   //dd($measdata);
  
            //insert the data in embs table
            DB::table('embs')->insert([
             'Work_Id' => $workid,
             't_bill_id' => $tbillid,
             'b_item_id' => $bitemId,
             'meas_id' => $newmeasid,
             'sr_no' => $measdata->srno,
             'parti' => $measdata->particulars,
             'number' => $measdata->number,
             'length' => $measdata->length,
             'breadth' => $measdata->breadth,
             'height' => $measdata->height,
             'formula' => $measdata->formula,
             'qty' => $qty,
             'measurment_dt' => $measdata->dateofmeasurement,
             'dyE_chk_dt' => $measdata->dateofmeasurement,
             'notforpayment' => $measdata->notforpayment

         ]);
         $InsertDataCount++;

         $count++;
        

        }
      //dd($tempmeasdata);
        // dd($bitemdata);
     
        // if($measdata->itemno)
   



         
         $measurements=DB::table('embs')->where('t_bill_id', '=', $tbillid)->where('b_item_id' ,$bitemId)->get();

         //find tbillid relayed bill items
             $tbillid=DB::table('bil_item')->where('b_item_id', $bitemId)->value('t_bill_id');
              //find wrokid related given bill (tbillid)
             $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');
         
             //previous bill no find
             $previousTBillId = DB::table('bills')
             ->where('work_id' , $workid)
             ->where('t_bill_id', '<', $tbillid) // Add your condition here
             ->orderBy('t_bill_id', 'desc') // Order by b_item_id in descending order
             ->limit(1) // Limit the result to 1 row
             ->value('t_bill_id');
                 //dd($previousTBillId);
         
                 // Retrieve the t_item_id associated with the b_item_id from the 'bil_item' table
  
        // Retrieve the QtyDcml_Ro value based on t_item_id from 'tnditems' table               
        $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
        $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

        // Retrieve and round previous bill quantity for the b_item_id from 'bil_item' table
        $previousexecqty=round(DB::table('bil_item')->where('b_item_id' , $bitemId)->value('prv_bill_qty') , 3);
//dd($previousexecqty);

 // Handle case where previous executed quantity is null
if (is_null($previousexecqty)) {
    $previousexecqty = 0;
}

// Calculate and format current quantity based on b_item_id and other conditions
$curqty=round(DB::table('embs')->where('b_item_id' , $bitemId)->where('notforpayment' , '=' , 0)->sum('qty'), $Qtydec);
//dd($previousexecqty);
//dd($curqty);


// Calculate executed quantity as sum of previous and current quantities
$execqty = number_format(round(($previousexecqty + $curqty), $Qtydec), 3, '.', '');
                 //dd($execqty);
                // Retrieve billing rate for the b_item_id
                 $billrt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('bill_rt');

                  // Calculate current amount based on current quantity and billing rate
                      $curamt=$curqty*$billrt;

                      // Retrieve previous amount for the b_item_id
                 $previousamt=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('previous_amt');

                 // Calculate total bill item amount as sum of current and previous amounts
                     $bitemamt=$curamt+$previousamt;

                      // Update 'bil_item' table with calculated and formatted values
                 DB::table('bil_item')->where('b_item_id' , $bitemId)->update([
         
                     'exec_qty' => $execqty,
                     'cur_qty' => $curqty,
                     'prv_bill_qty' => $previousexecqty,
                     'cur_amt' => $curamt,
                     'b_item_amt' => $bitemamt,
                 ]);
           

                 // Retrieve t_item_id for further processing
                 $titemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->value('t_item_id');
                 // Retrieve detailed information for the t_item_id from 'tnditems' table
                 $tnditem=DB::table('tnditems')->where('t_item_id' , $titemid)->first();
        //   $tndqty=round($tnditem->tnd_qty , 3);
          // Retrieve and format necessary values for display and calculation
        $tndqty=$tnditem->tnd_qty;
         
           $amountconvert=new CommonHelper();
                

           $tndcostitem=$tnditem->t_item_amt;
           //dd($tndqty);
           $percentage=round(($execqty / $tndqty)*100 , 2);
           //dd($percentage);
           $totlcostitem=round($billrt*$execqty , 2);

           $costdifference= round($tndcostitem-$totlcostitem , 2);
           
                            $tndcostitem=$amountconvert->formatIndianRupees($tndcostitem);
                 $totlcostitem=$amountconvert->formatIndianRupees($totlcostitem);
                 $costdifference=$amountconvert->formatIndianRupees($costdifference);


           $parta = 0; // Initialize the sum for matched conditions
           $partb = 0; // Initialize the sum for unmatched conditions
           
           $cparta = 0; // Initialize the sum for matched conditions
           $cpartb = 0; // Initialize the sum for unmatched conditions
        
           // Retrieve all item_ids associated with the t_bill_id
           $itemids=DB::table('bil_item')->where('t_bill_id' ,  $tbillid)->get();
                 //dd($itemids);
              
                 // Iterate through each item_id to calculate sums based on conditions
                 foreach($itemids as $itemId)
                 {
                     $itemid = $itemId->item_id;
                     $bitemid = $itemId->b_item_id;
                       // Check conditions and update sums accordingly
                    if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])
                        || (substr($itemid, 0, 4) === "TEST")                          ) 
                       {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                         
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $partb += $bitemamt; // Add to the sum for matched conditions
                          $cpartb += $citemamt; // Add to the sum for matched conditions
              
              
                       }
                       else {
                          $bitemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('b_item_amt');
                          // dd($bitemamt);
                          $citemamt=DB::table('bil_item')->where('item_id' , $itemid)->where('b_item_id' , $bitemid)->value('cur_amt');
                          $parta += $bitemamt; // Add to the sum for unmatched conditions
                          $cparta += $citemamt; // Add to the sum for matched conditions
                      }
                 }
                          
          // Calculate total gross amount and current gross amount
           $billgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('b_item_amt');

           $cbillgrossamt=DB::table('bil_item')->where('t_bill_id' , $tbillid)->sum('cur_amt');
          // Retrieve Above/Below percentage and value for the work id
           $beloaboperc=DB::table('workmasters')->where('Work_Id' , $workid)->value('A_B_Pc');
           //dd($beloaboperc);
           $beloAbo=DB::table('workmasters')->where('Work_Id' , $workid)->value('Above_Below');
        
           $bill_amt=0;
          $abeffect = $parta * ($beloaboperc / 100);
          $cabeffect = $cparta * ($beloaboperc / 100);
          
          // Adjust values based on Above/Below conditions
                         if ($beloAbo === 'Above') {
                      
                            
                            $bill_amt = round(($parta + $abeffect), 2);
                            $cbill_amt = round(($cparta + $cabeffect), 2);
                      
                        } elseif ($beloAbo === 'Below') {
                      
                            $bill_amt = round(($parta - $abeffect), 2);
                            $cbill_amt = round(($cparta - $cabeffect), 2);
                      
                        }
                      
                         // Determine whether to add a minus sign
                         if ($beloAbo === 'Below') {
                             $abeffect = -$abeffect;
                             $cabeffect = -$cabeffect;
                             $beloaboperc = -$beloaboperc;
                            }
                            //dd($abeffect);
                           //$part_a_ab=($parta * $beloaboperc / 100);
                           //dd($partb);
                       
                      
                      
                                 
                          // Calculate GST base amount and GST amount
                           $Gstbase = round($bill_amt, 2);
                           $cGstbase = round($cbill_amt, 2);
                                  //dd($Gstbase);
                      
                                  $Gstamt= round($Gstbase*(18 / 100), 2);
                                  $cGstamt= round($cGstbase*(18 / 100), 2);
                                   //dd($Gstamt);
        
                                   $part_A_gstamt=$Gstbase + $Gstamt;
                                   $cpart_A_gstamt=$cGstbase + $cGstamt;
        
                                  // Calculate total bill amount and current total bill amount
                                   $billamtgt = $partb + $part_A_gstamt;
                                   $cbillamtgt = $cpartb + $cpart_A_gstamt;
                    // Extract and round integer and decimal parts of total bill amount            
                     $integer_part = floor($billamtgt);  // Extract the integer part
                     $cinteger_part = floor($cbillamtgt);
        
        
                     $decimal_part = $billamtgt - $integer_part; // Extract the decimal part
                     $cdecimal_part = $cbillamtgt - $cinteger_part; 
                     //dd($decimal_part);
        
                     $billamtro = round($decimal_part, 2);
                     $cbillamtro = round($cdecimal_part, 2);
                     //dd($rounded_decimal_part);
        
                //     // Round the total bill amount
                //     $billamtro = round($billamtgt);
                //     //dd($rounded_billamtgt);
        
                //    // Calculate the difference
                //     //$billamtro = $rounded_billamtgt - $billamtgt;
                //     dd($billamtro);
                    //$billamtro=0.37;

                      // Handle rounding and adjustment based on decimal part
                    if ($billamtro > 0.50) {
                        // Calculate the absolute difference
                        $abs_diff = abs($billamtro);
                        $billamtro = 1 - $abs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $billamtro = -$billamtro;
                        //dd($billamtro);
                    }
        
                     // Handle rounding and adjustment based on decimal part for current bill amount
                    if ($cbillamtro > 0.50) {
                        // Calculate the absolute difference
                        $cabs_diff = abs($cbillamtro);
                        $cbillamtro = 1 - $cabs_diff;
                        //dd($billamtro);
                    }
                    else {
                        // If it is, add a minus sign to the difference
                        $cbillamtro = -$cbillamtro;
                        //dd($billamtro);
                    }
                     //dd($billamtro);
        
                     $net_amt= $billamtgt + $billamtro;
                     $cnet_amt= $cbillamtgt + $cbillamtro;
                     //dd($net_amt);
        
                      // Determine whether to add a minus sign
                    
                  // Update 'bills' table with calculated values
                     DB::table('bills')->where('t_bill_id' , $tbillid)->update([
        
                        'part_a_amt' => $parta,
                        'part_a_gstamt' => $part_A_gstamt,
                        'part_b_amt' => $partb,
                        'gst_amt' => $Gstamt,
                        'gst_base' => $Gstbase,
                        'gross_amt' => $billgrossamt,
                        'a_b_effect' => $abeffect,
                        'bill_amt' => $bill_amt,
                        'bill_amt_gt' => $billamtgt,
                        'bill_amt_ro' => $billamtro,
                        'net_amt' => $net_amt,
        
                        'c_part_a_amt' => $cparta, 
                        'c_part_a_gstamt' => $cpart_A_gstamt,
                        'c_part_b_amt' => $cpartb,
                        'curr_grossamt' => $cbillgrossamt,
                        'c_billamt' =>  $cbill_amt,
                        'c_gstamt' => $cGstamt,
                        'c_gstbase' => $cGstbase,
                        'c_abeffect' => $cabeffect,
                        'c_billamtgt' => $cbillamtgt,
                        'c_billamtro' => $cbillamtro,
                        'c_netamt' => $cnet_amt,
                        'mbstatus_so' => 0
                     ]);
                 
          


                    }


                    }

                    //temproray data deleted
                    DB::table('tempnormal')->where('tbillid' , $tbillid)->delete();
                }




// }


                  

//             }
   
//                 }

//billdata get
$billdata=DB::table('bills')->where('t_bill_id' , $tbillid)->get();
//dd($billdata);
                  $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
                  $lasttbillid=DB::table('bills')->where('work_id', $workid)->orderby('t_bill_id', 'desc')->first();


                  // Check if the file name contains 'emb_rcc_stl' or 'emb_normal'
if (strpos($name, 'emb_rcc_stl') !== false) {
    // Load steel Excel sheet
    // Assuming you have a function to load the steel Excel sheet
 
    $flag2=$flag2*2;
    $checkTotalRow=$checkTotalRow-$flag2;
    
} elseif (strpos($name, 'emb_normal') !== false) {
    // Load normal Excel sheet
    // Assuming you have a function to load the normal Excel sheet
    $checkTotalRow=$checkTotalRow-1;
}

 //dd($flagsteel , $flagnormal);
                //   if($flagsteel == 1)
                //   {
                    
                //    // dd($checkTotalRow , $flag2);


                //     $flag2=$flag2*2;
                //     $checkTotalRow=$checkTotalRow-$flag2;
                //     //dd($checkTotalRow , $flag2);

                //   }

                //   if($flagnormal == 1)
                //   {
                //     $checkTotalRow=$checkTotalRow-1;
                //     //dd($checkTotalRow);
                //   }
          //        call([EmbController::class, 'methodName'], $workid); //dd($billdata);

 //dd($count);

 //return the data 
                  return [
                    'billdata' => $billdata,
                    'billitemdata' => $billitemdata,
                    'lasttbillid' => $lasttbillid,
                    'InsertDataCount'=> $InsertDataCount,
                    'checkTotalRow'=>$checkTotalRow,
                ];

       
    }



// function is calculate the Total weight of steel measurement and steel data insert in normal measuremnt data
   public  function steelinsertnormal($size , $barlenght , $bitemId , $date , $sec_type , $workid , $tbillid , $sr_no ,$totalweight)
    {
     // $size="6 mm dia";
$html='';
     // dd($barlenghtl6 , $size);
      $weightquery=DB::table('stl_tbl')->where('size' , $size)->get('weight');

      $weight=$weightquery[1]->weight;
     // dd($weight);
      $unit= DB::table('stl_tbl')->where('size' , $size)->value('unit');

      $particulars = $sec_type . " - " . $size . " Total Length " . $barlenght ." " . $unit . "& Weight " . $weight . " Kg/R.Mt.";
//dd($particulars);          
      $formula =  $barlenght . " * " . $weight . " / " . 1000;
      //dd($formula);

      $singleweight = round(($barlenght * $weight) / 1000, 3);
      //dd($singleweight);

       // Add the singleweight to the total weight
       $totalweight += round($singleweight, 3);

     

         // Create the row for the current item
          $html .= '<tr>
          <td>' . $sr_no . '</td>
          <td>' . $particulars . '</td>
          <td>' . $formula . '</td>
          <td>' . $singleweight . '</td>
        </tr>';

     // Increment the serial number for the next iteration
       //$sr_no++;

      // $tbillid  $workid 

       $previousmeasidObj = DB::table('embs')->where('b_item_id', '=', $bitemId)->orderBy('meas_id', 'desc')->select('meas_id')->first();

       if ($previousmeasidObj) {
           $previousmeasid = $previousmeasidObj->meas_id; // Convert object to string
           // Increment the last four digits of the previous meas_id
            $lastFourDigits = intval(substr($previousmeasid, -4));
            $newLastFourDigits = str_pad(($lastFourDigits + 1), 4, '0', STR_PAD_LEFT);
            $newmeasid = $bitemId.$newLastFourDigits;
            //dd($newmeasid);
      } else {
          // If no previous meas_id, start with bitemid.0001
          $newmeasid = $bitemId.'0001';
      }

      $stldate = DB::table('stlmeas')->where('b_item_id', $bitemId)->orderBy('date_meas' , 'desc')->first();
      // dd($stldate->date_meas);
              
                 DB::table('embs')->insert([
                     'Work_Id' => $workid,
                     't_bill_id' => $tbillid,
                     'b_item_id' => $bitemId,
                     'meas_id' => $newmeasid,
                     'sr_no' => $sr_no,
                     'parti' => $particulars,
                     'formula' => $formula,
                     'qty' => $singleweight,
                     'measurment_dt' => $date->date_meas, // Insert the current date_meas value
                 ]);
               
      return ['html' => $html, 'singleweight' => $singleweight];         
    }

}