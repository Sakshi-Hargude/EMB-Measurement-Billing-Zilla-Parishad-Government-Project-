<?php

namespace App\Http\Controllers;

use PDF;
use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Models\Emb;
use Dompdf\Options;
use App\Models\Workmaster;
use Illuminate\Support\Str;
use App\Imports\ExcelImport;
use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Mpdf\Mpdf;
use Dompdf\Image;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;

use chillerlan\QRCode\QROptions;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class ReportController extends Controller
{

    public $latestRecordData = [];
    public $lastupdatedRecordData = [];
    
public function reportbill(Request $request , $tbillid)
{

    //dd($tbillid);

     // Store the dynamic $tbillid value in the session
     $request->session()->put('global_tbillid', $tbillid);
   
$embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

$newmeasdtfrformat = $embsection2->meas_dt_from ?? null;
$newmeasdtfr = date('d-m-Y', strtotime($newmeasdtfrformat));
$newmessuptoformat=$embsection2->meas_dt_upto ?? null; 
$newmessupto = date('d-m-Y', strtotime($newmessuptoformat));
$formatpreviousbilldt=$embsection2->previousbilldt ?? null; 
$previousbilldt = date('d-m-Y', strtotime($formatpreviousbilldt));
//dd($embsection2);

 return view('Report', compact('embsection2' , 'newmeasdtfr' , 'newmessupto' , 'previousbilldt'));
 }

//common header ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
 public function commonheader($tbillid , $headercheck)
{

    $html='';

    $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
$workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($recordentrynos);
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

$division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
//dd($tbillid);
$html .= '<table style="width: 100%; margin-left: 22px; margin-right: 17px;"><tbody>';

$html .= '<tr style="margin-bottom: 10px;">';
$html .= '<td colspan="" style="width: 100%; padding: 8px; text-align: right;"><h3><strong>' . $division . '</strong></h3></td>';
$html .= '<td colspan="" style="width: 100%; padding: 8px; text-align: right;"><h3><strong>MB NO: ' . $workid . '</strong></h3></td>';
$html .= '<td colspan="" style="width: 100%; padding: 8px; text-align: right;"><h3><strong>' . $workdata->Sub_Div . '</strong></h3></td>';
$html .= '</tr>';



switch ($headercheck) {
                    case 'MB':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="padding: 8px; text-align: center;"><h2><strong>MEASUREMENT BOOK</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                    case 'Abstract':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>ABSTRACT </strong></h2></td>';
                    $html .= '</tr>';
                    break;

                    case 'Excess':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>EXCESS SAVING STATEMENT</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                    case 'Royalty':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>ROYALTY STATEMENT</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                    case 'materialcons':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>MATERIAL CONSUMPTION STATEMENT</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                     case 'Subdivisionchecklist':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>SubdivisionChecklist</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                     case 'divchecklist':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h3><strong>Division Checklist Report</strong></h3></td>';
                    $html .= '</tr>';
                    break;

                    case 'MB':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>MEASUREMENT BOOK</strong></h2></td>';
                    $html .= '</tr>';
                    break;
                        
     }


     $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
     $billType = CommonHelper::getBillType($tbilldata->final_bill);
//dd($formattedTItemNo , $billType);

if($headercheck != 'MB')
{
$html .= '<tr>';
$html .= '<td  style="width: 50%;"></td>';
$html .= '<td  style="width: 50%; padding: 8px; text-align: center;"><h5><strong>Bill No : ' . $formattedTItemNo . ' ' . $billType . '</strong></h5></td>';
$html .= '</tr>';
}

$html .= '<tr>';
$html .= '<td style=""><strong>Name of Work:</strong></td>';
$html .= '<td colspan="2">' . $workdata->Work_Nm . '</td>';
$html .= '</tr>';

$html .= '<tr>';
$html .= '<td  style="width: 20%;"><strong>Agency:</strong></td>';
$html .= '<td  style="width: 80%;">' . $workdata->Agency_Nm . '</td>';
$html .= '</tr>';

$agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';


$html .= '<tr>';
$html .= '<td colspan="2" style="width: 50%;"><strong>Authority:</strong>'.$workdata->Agree_No.'</td>';
if(!empty($agreementDate))
{
$html .= '<td colspan="" style="width: 50%; text-align: right;"><strong>Date:</strong>' . $agreementDate . '</td>';
}
else{
    $html .= '<td colspan="" style="width: 40%;"></td>';

}
$html .= '</tr>';


$workdate=$workdata->Wo_Dt ?? null;
$workorderdt = date('d-m-Y', strtotime($workdate));

$html .= '<tr>';
$html .= '<td colspan="2" style="width: 60%;"><strong>WorkOrder No:</strong>' . $workdata->WO_No . '</td>';
$html .= '<td colspan="" style="width: 40%; text-align: right;"><strong>WorkOrder Date:</strong>' . $workorderdt . '</td>';
$html .= '</tr>';


$normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

$combinedDates = $normalmeas->merge($steelmeas);
$maxDate = $combinedDates->max();
$maxdate = date('d-m-Y', strtotime($maxDate));


if ($tbilldata->final_bill === 1) {
 $date = $workdata->actual_complete_date ?? null;
 $workcompletiondate = date('d-m-Y', strtotime($date));

 $html .= '<tr>';
 $html .= '<td colspan="2" style="width: 60%;"><strong>Actual Date of Completion:</strong>' . $workcompletiondate . '</td>';
 $html .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
 $html .= '</tr>';
 


} else {
 $date = $workdata->Stip_Comp_Dt ?? null;
 $workcompletiondate = date('d-m-Y', strtotime($date));

 $html .= '<tr>';
 $html .= '<td colspan="2" style="width: 60%;"><strong>Stipulated Date of Completion:</strong>' . $workcompletiondate . '</td>';
 $html .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
 $html .= '</tr>';
 

}



$html .= '</tbody></table>';

return $html;
}

public function commonheaderview($tbillid , $headercheck)
{

    $html='';

    $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
$workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($recordentrynos);
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

$division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
//dd($tbillid);
$html .= '<div class="table-responsive">';

$html .= '<table style="width: 100%; margin-left: 22px; margin-right: 17px;">';
$html .= '<tbody>';

$html .= '<tr  margin-bottom: 10px;">';
$html .= '<td colspan="7"><h3><strong>' . $division . '</strong></h3></td>';
$html .= '<td colspan="4" ><h3><strong>MB NO: ' . $workid . '</strong></h3></td>';
$html .= '<td colspan="5" ><h3><strong>' . $workdata->Sub_Div . '</strong></h3></td>';
$html .= '</tr>';



switch ($headercheck) {
                    case 'MB':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>MEASUREMENT BOOK</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                    case 'Abstract':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>ABSTRACT </strong></h2></td>';
                    $html .= '</tr>';
                    break;

                    case 'Excess':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>EXCESS SAVING STATEMENT</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                    case 'Royalty':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>ROYALTY STATEMENT</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                    case 'materialcons':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>MATERIAL CONSUMPTION STATEMENT</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                     case 'Subdivisionchecklist':
                    $html .= '<tr>';
                    $html .= '<td colspan="14" style="text-align: center;"><h2><strong>Sub Division Checklist</strong></h2></td>';
                    $html .= '</tr>';
                    break;

                     case 'divchecklist':
                    $html .= '<tr>';
                    $html .= '<td colspan="12" style="text-align: center;"><h3><strong>Division Checklist Report</strong></h3></td>';
                    $html .= '</tr>';
                    break;

                    case 'MB':
                    $html .= '<tr>';
                    $html .= '<td colspan="12" style="text-align: center;"><h2><strong>MEASUREMENT BOOK</strong></h2></td>';
                    $html .= '</tr>';
                    break;
                        
     }


     $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
$billType = CommonHelper::getBillType($tbilldata->final_bill);
//dd($formattedTItemNo , $billType);

if($headercheck != 'MB')
{
     $html .= '<tr>';
     $html .= '<td colspan="14" style="text-align: center;"><h5><strong>Bill No : ' . $formattedTItemNo . ' ' . $billType . '</strong></h5></td>';
     $html .= '</tr>';
}

$html .= '<tr>';
$html .= '<td colspan="6"><strong>Name of Work:</strong></td>';
$html .= '<td colspan="9" style="text-align: justify;">' . $workdata->Work_Nm . '</td>';
$html .= '</tr>';

$html .= '<tr>';
$html .= '<td colspan="6"><strong>Agency:</strong></td>';
$html .= '<td colspan="9">' . $workdata->Agency_Nm . '</td>';
$html .= '</tr>';

$agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';



$html .= '<tr>';
$html .= '<td colspan="6" style="width: 25%;"><strong>Authority:</strong></td>';
$html .= '<td colspan="3" style="width: 25%;">'.$workdata->Agree_No.'</td>';
 if(!empty($agreementDate))
 {
$html .= '<td colspan="2" style="width: 25%;"><strong>Date:</strong></td>';
$html .= '<td colspan="3" style="width: 25%;">' . $agreementDate . '</td>';
 }
$html .= '</tr>';



$workdate=$workdata->Wo_Dt ?? null;
$workorderdt = date('d-m-Y', strtotime($workdate));

$html .= '<tr>';
$html .= '<td colspan="6" style="width: 25%;"><strong>Work Order No:</strong></td>';
$html .= '<td colspan="3" style="width: 25%;">' . $workdata->WO_No . '</td>';
$html .= '<td colspan="2" style="width: 25%;"><strong>Work Order Date:</strong></td>';
$html .= '<td colspan="3" style="width: 25%;">' . $workorderdt . '</td>';
$html .= '</tr>';


$normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

$combinedDates = $normalmeas->merge($steelmeas);
$maxDate = $combinedDates->max();
$maxdate = date('d-m-Y', strtotime($maxDate));


if ($tbilldata->final_bill === 1) {
 $date = $workdata->actual_complete_date ?? null;
 $workcompletiondate = date('d-m-Y', strtotime($date));


 $html .= '<tr>';
 $html .= '<td colspan="6" style="width: 25%;"><strong>Actual Date of Completion:</strong></td>';
 $html .= '<td colspan="3" style="width: 25%;">' . $workcompletiondate . '</td>';
 $html .= '<td colspan="2" style="width: 25%;"><strong>Date of Measurement:</strong></td>';
$html .= '<td colspan="3" style="width: 25%;">' . $maxdate . '</td>';

 $html .= '</tr>';


} else {
 $date = $workdata->Stip_Comp_Dt ?? null;
 $workcompletiondate = date('d-m-Y', strtotime($date));

 $html .= '<tr>';
 $html .= '<td colspan="6" style="width: 25%;"><strong>Stipulated Date of Completion:</strong></td>';
 $html .= '<td colspan="3" style="width: 25%;">' . $workcompletiondate . '</td>';
 $html .= '<td colspan="2" style="width: 25%;"><strong>Date of Measurement:</strong></td>';
$html .= '<td colspan="3" style="width: 25%;">' . $maxdate . '</td>';

 $html .= '</tr>';


}


$html .= '</tbody>';

$html .= '</table>';
$html .= '</div>';

return $html;
}






////MB report PDF functions/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


//public $latestRecordEntryNos = [];

 // mb report
public function mbreport(Request $request , $tbillid)
{
 $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
//dd($tbillid);
 $html='';


 $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
 //dd($workid);
 $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
 //dd($workdata);
 $jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
 // Construct the full file path
 
 $imagePath = public_path('Uploads/signature/' . $sign->sign);
 //dd($imagePath);
 $imageData = base64_encode(file_get_contents($imagePath));
 $imageSrc = 'data:image/jpeg;base64,' . $imageData;
 
 $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
 $imageData2 = base64_encode(file_get_contents($imagePath2));
 $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;
  
 
 //dd($sign2->designation);
 $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
 $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

 $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
 $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

 //dd($workid);
     $EE_id=DB::table('workmasters')->where('Work_Id' , $workid)->value('EE_id');
     //dd($EE_id);
 
     //dd($dyeid);
     $sign3=DB::table('eemasters')->where('eeid' , $EE_id)->first();
     $designation=DB::table('designations')->where('Designation' , $sign3->Designation)->value('Designation');
    // dd($sign3->Designation);
     $division=DB::table('divisions')->where('div_id' , $sign3->divid)->value('div');


     $DBacYr = DB::table('acyrms')
    ->whereDate('Yr_St', '<=', $embsection2->Bill_Dt)
    ->whereDate('Yr_End', '>=', $embsection2->Bill_Dt)
    ->value('Ac_Yr');

    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($recordentrynos);
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();


    
 $html .= '<div class="container" style="margin-bottom: 50px; text-align: center;">
 <div class="row justify-content-center">
     <div class="col-md-8">
         <div class="card" style="border: 3px solid #000; border-width: 3px 1px; height: 950px;">

         <div class="text-center" style="margin-top:40px;">
         <h4 style="font-weight: bold;">'.$division.'</h4>
         </div>
         <div class="text-center">
         <h5 style="font-weight: bold;">'.$dyesubdivision.'</h5>
         </div>
                 
         <div class="card-body text-center" style="height: 400px; margin-top: 80px;">
                 <h2 style="margin-top: 20px;">FORM NO-52</h2>
                 <h3 style="margin-top: 20px;">MEASUREMENT BOOK</h3>
                 <h3 style="margin-top: 20px;">MB NO : '.$workid.'</h3>
                 <h4 style="margin-top: 20px;">'.$sign2->name.' , '.$jedesignation.'</h4>
                <h5 style="margin-top: 20px;">YEAR : '.$DBacYr.'</h5>
                 <p style="margin-top: 20px;"><h2>Name of Work : '.$workdata->Work_Nm.'</h2></p>
                 <!-- Add more lines or customize as needed -->
             </div>
         </div>
     </div>
 </div>
</div>';

 //$recordentrynos=DB::table('recordms')->where('t_bill_id' , $tbillid)->get();


// Fetch all record entries
$recordentrynos = DB::table('recordms')
    ->where('t_bill_id', $tbillid)
    ->orderBy('Record_Entry_Id', 'asc')
    ->get();

// If there are records, exclude the last one
if ($recordentrynos->isNotEmpty()) {
    $lastRecordId = $recordentrynos->pop()->Record_Entry_Id; // Remove and get the last record ID
    $recordentrynos = $recordentrynos->values(); // Re-index the collection
}

// Fetch the last record entry
$lastRecordEntry = DB::table('recordms')
    ->where('t_bill_id', $tbillid)
    ->where('Record_Entry_Id', $lastRecordId ?? null) // Fetch if lastRecordId is set
    ->first();
    
    //dd($lastRecordEntry);
    
    //dd($recordentrynos);
 $headercheck='MB';
 $header=$this->commonheaderview($tbillid , $headercheck);

 $html .=$header; 







// Read the image file and convert it to base64
// $imagePath = public_path('images/sign.jpg');
// $imageData = base64_encode(file_get_contents($imagePath));
// $imageSrc = 'data:image/jpeg;base64,' . $imageData;

// $imagePath2 = public_path('images/sign2.jpg');
// $imageData2 = base64_encode(file_get_contents($imagePath2));
// $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


$bitemid=null;

$recdate=null;




        //main table

        $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 30px; margin-right: 10px;">';
        $html .= '<thead>';    
        $html .= '<tr>';
        $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
        $html .= '<thead>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 5%;">Sr NO</th>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 30%;">Particulars</th>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%;">Number</th>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%;">Length</th>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%;">Breadth</th>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%;">Height</th>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%;">Quantity</th>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 15%;">Unit</th>';
        $html .= '</thead>';
        $html .= '</table>';
        $html .= '</tr>';
        // Add more table headers as needed
        $html .= '</thead>';
        $html .= '<tbody>';
    
    
    
        
        // foreach ($this->latestRecordEntryNos as $itemNo => $entry) {
        //     $itemNo = $entry['Item_No'];
        //     $recordEntryNo = $entry['Record_Entry_No'];
        //     //dd($recordEntryNo);
        //     // Do something with $itemNo and $recordEntryNo
        // }

       

  //dd($recordentrynos);

 foreach($recordentrynos as $recordentrydata)
 {
    $recdate=$recordentrydata->Rec_date;

    // 1 table

    //dd($recordentrydata);

    $rdate=$recordentrydata->Rec_date ?? null;
    $recordentrycdate = date('d-m-Y', strtotime($rdate));
    //$itemmeas ='';
    $itemmeas=$this->itemmeasdata($tbillid , $recdate);
    //dd($itemmeas);
    $html .= '<tr>';
    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<thead>';
    $html .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 50%;">Record Entry No :' . $recordentrydata->Record_Entry_No . '</th>';
    $html .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 50%;">Date :' . $recordentrycdate . '</th>';
    $html .= '</thead>';
    $html .= '</table>';
    $html .= '</tr>';
  
// 1 table end


//dd($result);
$html .=$itemmeas;
//dd($itemmeas);
 // Splitting a single cell into two equal-sized cells for signature
 $html .= '<tr style="line-height: 0;">';
 $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
 $html .= '<tbody>';
 $html .= '<td colspan="3" style="border: 1px solid black; padding: 8px; width: 50%; text-align: center; line-height: 0;">';
 if($embsection2->mb_status >= '3')
{
 $html .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
  $html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
 $html .= '<div style="line-height: 1; margin: 0;">';
 $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
 $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
 $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';

 $html .= '</div>';
}
  $html .= '</td>'; // First cell for signature details
  $html .= '<td colspan="6" style="border: 1px solid black; padding: 8px; width: 50%; text-align: center; line-height: 0;">';
  if($embsection2->mb_status >= '4')
  {
  
  $html .= '<div style="line-height: 1; margin: 0;"><strong>Checked by me at the site of work</strong></div>';
  $html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
  $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
  $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
  $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
  $html .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
  $html .= '</div>';
  }
  $html .= '</td>'; // First cell for signature details
     $html .= '</tbody>';
   $html .= '</table>'; 
$html .= '</tr>';
}

$html .= '<tr>'; // Add a new row for the bill header and date
$html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<thead>';
$html .= '<th colspan="2" style="border: 1px solid black; padding: 8px;  width: 100%;"></th>';
$html .= '</thead>';
$html .= '</table>';
$html .= '</tr>';



$formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
$billType = CommonHelper::getBillType($embsection2->final_bill);


if ($lastRecordEntry) {
    // Add your table view here
    $recno=DB::table('recordms')->where('t_bill_id' , $tbillid)->where('Rec_date' , $lastRecordEntry->Rec_date)->value('Record_Entry_No');


    // Convert Rec_date to dd mm yyyy format
$dateFormatted = date('d-m-Y', strtotime($lastRecordEntry->Rec_date));

    $html .= '<tr>'; // Start a new row for record entry details
    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<thead>';
    $html .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 50%;">Bill: ' . $formattedTItemNo . ' ' . $billType . '</th>';
    $html .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 50%;">Date of measurement: ' . $dateFormatted . '</th>';
    $html .= '</thead>';
    $html .= '<tbody>';


    $billitems = DB::table('bil_item')->where('t_bill_id', $tbillid)->orderBy('t_item_no', 'asc')->get();

    // Now you can use $data as needed
    // For example, you can pass it to another function or manipulate it
    // You can also access individual elements of the array like $data['key']
    //dd($data); // Assuming dd is a function f

    foreach($billitems as $itemdata)
    {

        $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

        $bitemId=$itemdata->b_item_id;
        //dd($bitemId);
        $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
        $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $lastRecordEntry->Rec_date)->get();

        $html .= '<tr>';
        $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th>';
        $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 90%; text-align: justify;"> ' . $itemdata->item_desc . '</th>';
        // Add more table headers as needed
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '</table>';
        $html .= '</tr>';



if($itemdata->exec_qty === 0)
{
    
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 80%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Excecuted</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%; font-weight: bold;"></td>';
    $html .='</tbody>';
    $html .= '</tr>';

}




//dd($data);

// Assuming $data is an associative array with keys as item numbers
// if (isset($data[$itemdata->b_item_id])) {
//     $recordentryno = $data[$itemdata->b_item_id]['Record_Entry_No'];
//     $Totalqty= $data[$itemdata->b_item_id]['Total_Uptodate_Quantity'];
//     $b_item_id=$data[$itemdata->b_item_id]['b_item_id'];

//     $embsCount = DB::table('embs')->where('b_item_id', $b_item_id)->count();
//     $stlmeasCount = DB::table('stlmeas')->where('b_item_id', $b_item_id)->count();

//     if ($embsCount == 0 && $stlmeasCount == 0)  {
//         // Add HTML for the case where measurements are not found
//         $html .= '<tr>';
//         $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 80%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed</td>';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%; font-weight: bold;"></td>';
//         $html .='</tbody>';
//         $html .= '</tr>';
//     } else {
//         // Add HTML for the case where measurements are executed
//         $html .= '<tr>';
//         $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 80%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity as per this MB Record Entry No:'.$recordentryno.'</td>';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $Totalqty.' </td>';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%; font-weight: bold;">' . $unit . '</td>';
//         $html .='</tbody>';
//         $html .= '</tr>';
//     }
// }

        //meas data check


                    // 2 table
            // Create a table inside the main table cell
        
        // 2 table end

        // 3 rd table


            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
        //dd($itemid);
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])) 
            {
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $lastRecordEntry->Rec_date)->get();
                
                $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                // dd($stldata , $bill_rc_data);

              

              



                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


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

        
                $sums = array_fill_keys($ldiamColumns, 0);

                foreach ($stldata as $row) {
                     foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                     }
                }//dd($stldata);


                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemId) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemId);
                })
                ->get();


            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();


        foreach ($bill_member as $index => $member) {
                //dd($member);
                    $rcmbrid=$member->rc_mbr_id;
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $lastRecordEntry->Rec_date)->get();
                //dd($memberdata);

            if ( !$memberdata->isEmpty()) {


                $html .= '<tr>';
                $html .= '<table style="border-collapse: collapse; width: 100%;"><thead>';
                $html .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
                $html .= '</thead></table>';
                $html .= '</tr>';
            

                $html .= '<tr>
                <table style="border-collapse: collapse; width: 100%; border: 1px solid black;">
               <thead>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 13%; min-width: 13%;">Bar Particulars</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">6mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">8mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">10mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">12mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">16mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">20mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 9%;">25mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 9%;">28mm</th>    
               </thead><tbody>';
            
                foreach ($stldata as $bar) {

                    if ($bar->rc_mbr_id == $member->rc_mbr_id) {

                    //dd($bar);// Assuming the bar data is within a property like "bar_data"
                    $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                  
                            
                     $html .=   '<tr><td style="border: 1px solid black; padding: 5px; width: 5%;  min-width: 5%;">'. $bar->bar_sr_no .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 13%; min-width: 13%;">'. $bar->bar_particulars.'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 10%; min-width: 10%;">'. $bar->no_of_bars .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 10%; min-width: 10%;">'. $bar->bar_length .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam6 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam8 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam10 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam12 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam16 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam20 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 9%; min-width: 9%;">'. $bar->ldiam25 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 9%; min-width: 9%;">'. $bar->ldiam28 .'</td></tr>';
                          
                     
                              
                        }

                    }
           $html .='</tbody></table> </tr>';
                  
                   


                }
        

            }

            $embssteeldata = DB::table('embs')->where('b_item_id', $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
    //dd($embssteeldata);

    $barlengthl6=0;
            $barlengthl8=0;
            $barlengthl10=0;
            $barlengthl12=0;
            $barlengthl16=0;
            $barlengthl20=0;
            $barlengthl25=0;
            $barlengthl28=0;
            $barlengthl32=0;
            $barlengthl36=0;
            $barlengthl40=0;
            $barlengthl45=0;

       foreach($embssteeldata as $embdata)
       {
        $particular=$embdata->parti;
        $firstThreeChars = substr($particular, 0, 3);

        // Set $sec_type based on the first 3 characters
        if ($firstThreeChars === "HCR") {
            $sec_type = "HCRM/CRS Bar";
        } else {
            $sec_type = "TMT Bar";
        }
        
        //dd($particular);
        if ($sec_type == "HCRM/CRS Bar") {
            $pattern = '/HCRM\/CRS Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
        } else {
            $pattern = '/TMT Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
        }
        if (preg_match($pattern, $particular, $matches)) {
            // $matches[1] contains the diameter value
            // $matches[3] contains the total length value
            $diameter = $matches[1];
            $totalLength = $matches[3];
   // dd($diameter , $totalLength);

    if ($diameter == '6') {
        $barlengthl6 += $totalLength;
    } 
    if ($diameter == '8') {
        $barlengthl8 += $totalLength;
    } 
    if ($diameter == '10') {
        $barlengthl10 += $totalLength;
    } 
    if ($diameter == '12') {
        $barlengthl12 += $totalLength;
    } 
    if ($diameter == '16') {
        $barlengthl16 += $totalLength;
    } 
    if ($diameter == '20') {
        $barlengthl20 += $totalLength;
    } 
    if ($diameter == '25') {
        $barlengthl25 += $totalLength;
    } 
    if ($diameter == '28') {
        $barlengthl28 += $totalLength;
    } 
    if ($diameter == '32') {
        $barlengthl32 += $totalLength;
    } 
    if ($diameter == '36') {
        $barlengthl36 += $totalLength;
    } 
    if ($diameter == '40') {
        $barlengthl40 += $totalLength;
    } 
    if ($diameter == '45') {
        $barlengthl45 += $totalLength;
    }
            // Output the extracted values
           
        }
       }

           
       $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; font-size: 13px;">
       <thead>
           <th style="padding: 5px; width: 5%; background-color: #f2f2f2; min-width: 5%;"></th>
           <th style="padding: 5px; background-color: #f2f2f2; width: 13%; min-width: 13%;"></th>
           <th style="padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;"></th>
           <th style="padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Total</th>
           <th style="border: 1px solid black; padding: 5px; width: 7%; background-color: #f2f2f2;">'. number_format($barlengthl6, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl8, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl10, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl12, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl16, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl20, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 7%;">'. number_format($barlengthl25, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 7%;">'. number_format($barlengthl28, 3) .'</th>
       </thead>
   </table>';
     
           

    }    
   


            $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
            $totalQty = 0; 
                foreach($normaldata as $nordata)
                {

                    // dd($unit);

                            $formula= $nordata->formula;
            
                                $html .= '<tr>';
                                $html .= '<table style="border-collapse: collapse; width: 100%;"><tbody>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 5%;">' . $nordata->sr_no . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 30%; word-wrap: break-word; max-width: 200px;">' . $nordata->parti . '</td>';
                            if($formula)
                            {
                                 
                                $html .= '<td colspan="4" style="border: 1px solid black; padding: 5px; width: 40%;">' . $nordata->formula . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 15%;">' . $unit . '</td>';

                            

                            }
                            else
                            {
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->number . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->length . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->breadth . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->height . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 15%;">' . $unit . '</td>';

                            }
                                $html .='</tbody></table>';
                                $html .= '</tr>';

                           

                  }
               
                  $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

                  $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

               //dd($Qtydec);
                  $totalQty=round(DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('notforpayment' , 0)->where('measurment_dt' , $lastRecordEntry->Rec_date)->sum('qty') ,  $Qtydec);
                  $totalQty=number_format($totalQty ,  3, '.', '');


                  $qtyaspersamerec = round(DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
                  ->sum('qty') , $Qtydec);
                  $qtyaspersamerec=number_format($qtyaspersamerec ,  3, '.', '');

                //dd($qtyaspersamerec);

                  $maxdate = DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
                  ->max('measurment_dt');

                  $recordentryno=DB::table('recordms')
                  ->where('t_bill_id', $tbillid)
                  ->where('Rec_date', $maxdate)
                  ->value('Record_Entry_No');
     //dd($maxdate);
                  $recqty = 0; // Initialize $recqty
 //dd($qtyaspersamerec);    
    //$
 //$recqty = number_format($qtyaspersamerec + $totalQty, 3);     
 
 $Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
 $recqty = number_format($Recqty ,  3, '.', '');

 $itemno = $itemdata->t_item_no;

 if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
     $itemno .= $itemdata->sub_no;
 }

 $itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);


 $itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');




 $recqty_float = floatval(str_replace(',', '', $recqty));
 $totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;

 $Totalquantityasper = number_format($totalquantityasper, 3, '.', '');




   
 $TotalQuantity=0;


 if($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0 && $totalQty == 0)
 {
    
    
       $html .= '<tr>';
       $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
       $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
       $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
       $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;"></td>';
       $html .='</tbody>';
       $html .= '</tr>';
   
   
  
   
   
 }
 elseif($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0 )
 {
 
    $TotalQuantity=$totalQty;
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
    $html .='</tbody>';
    $html .= '</tr>';


 }



if($qtyaspersamerec != 0)
{

    $TotalQuantity=number_format($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '');

//dd($qtyaspersamerec , $totalQty);
if($totalQty>0) 
{
$html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';
              
}

if($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty == 0)
{
   // dd($TotalQuantity);
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;"></td>';
    $html .='</tbody>';
    $html .= '</tr>';
}
else{


if($TotalQuantity == number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '') && $TotalQuantity > 0)
{
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity(As per this MB Record Entry No:'.$recordentryno.')</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
    $html .='</tbody>';
    $html .= '</tr>';
    

}

else
{
                  $html .= '<tr>';
                  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per this MB Record Entry No:'.$recordentryno.',  Item No:'.$itemno.'</td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '').' </td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
                  $html .='</tbody>';
                  $html .= '</tr>';



                  $html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';
//dd($TotalQuantity);

}

}

}





// $previousBillIds = DB::table('bills')
//                      ->where('work_id', '=', $workid)
//                     ->where('t_bill_Id', '<', $tbillid)
//                     ->pluck('t_bill_Id');

                   
//                   $prevbillsqty=0;
//                     foreach($previousBillIds as $prevtbillid)
//                     {
                       
//                        $bitemids= DB::table('bil_item')
//                        ->where('t_bill_id', $prevtbillid)
//                        ->where('t_item_id', $itemdata->t_item_id)
//                        ->get('b_item_id');

//                        foreach($bitemids as $bitemid)
//                        {
//                         //dd($bitemid);
//                         $previtemsqty = DB::table('embs')
//                         ->where('b_item_id' ,  $bitemid->b_item_id)
//                         ->where('notforpayment' , 0)
//                         ->sum('qty');

//                         $prevbillsqty += $previtemsqty;
//                        }
                       
//                     }
//                     //dd($prevbillsqty);

 if($itemdata->prv_bill_qty != 0 && $qtyaspersamerec == 0)
 {

    $TotalQuantity=number_format($totalQty+$itemdata_prv_bill_qty, 3, '.', '');

    if($totalQty>0) 
    {
//     $previousbillrqty = number_format($prevbillsqty , 3);
$html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';
    }


    if($itemdata_prv_bill_qty==0)
    {

        $html .= '<tr>';
        $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;"></td>';
        $html .='</tbody>';
        $html .= '</tr>';
    }
    else{

    if($TotalQuantity == $itemdata_prv_bill_qty && $TotalQuantity > 0)
{
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity(As per previous bill)</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
    $html .='</tbody>';
    $html .= '</tr>';
    

}

else
{

  //dd($itemdata);

  $html .= '<tr>';
  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per previous bill: </td>';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $itemdata_prv_bill_qty.' </td>';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
  $html .='</tbody>';
  $html .= '</tr>';

               
 
  //dd($TotalQuantity);
//dd($totalQty+$itemdata_prv_bill_qty);
  $html .= '<tr>';
                  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity: </td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
                  $html .='</tbody>';
                  $html .= '</tr>';



}



}
                }

// 3 table end
// 3 table end

$nordata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->count();
$steeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $lastRecordEntry->Rec_date)->count();

if($nordata > 0 || $steeldata > 0)
{
$this->lastupdatedRecordData[$itemdata->b_item_id] = [
    'Record_Entry_No' => $recno,
    't_item_no' => $itemdata->t_item_no, // Include t_item_no for comparison
    'Total_Uptodate_Quantity' => $TotalQuantity,
    'b_item_id' => $itemdata->b_item_id, // Include b_item_id
];

}
        
    }

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</tr>';
}





 $html .= '</tbody>';
 $html .= '</table>';


 $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black; line-height: 1.5;">';
 $html .= '<tr>';
 $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;"></td>';
 $html .= '</tr>';
 $html .='</table>';



 // Priyanka Edits..............................................................................................................

 $billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
 $eecheckdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('ee_check' , 1)->get();

    $imagePath3 = public_path('Uploads/signature/' . $sign3->sign);
 
    $imageData3 = base64_encode(file_get_contents($imagePath3));
    $imageSrc3 = 'data:image/jpeg;base64,' . $imageData3;

 if ($eecheckdata->isNotEmpty())
 {
 

 $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
 $html .= '<thead>';
 $html .= '<tr>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 100%;" colspan="5"><h4>Executive Engineer Checking:</h4></th>';
 $html .= '</tr>';
 $html .= '</thead>';
 
 $html .= '<thead>';
 $html .= '<tr>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Item No</th>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 40%;">Item Description</th>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 25%;">Measurement No</th>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 25%;">Quantity</th>';
  $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 25%;">Unit</th>';

 $html .= '</tr>';
 $html .= '</thead>';
 
 $html .= '<tbody>';
 
 
 $checked_mead_amt=0;
 $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
 
 $b_item_amt= DB::table('bills')
 ->where('t_bill_id', $tbillid)
 ->value('bill_amt');
 //dd($billitemdata);
 
 foreach($billitemdata as $itemdata)
 {
     //dd($itemdata);
     $bitemId=$itemdata->b_item_id;
     //dd($bitemId);
     //  $measnormaldata=DB::table('embs')->where('ee_check',1)->get();
     $meassr = DB::table('embs')
     ->select('sr_no', 'ee_chk_qty')
     ->where('b_item_id', $bitemId)
     ->where('ee_check', 1)
     ->get();
     
     //dd($meassr);
       if (!$meassr->isEmpty() ) {
          // if ($measnormaldata ) {
          //dd($measnormaldata->sr_no);
  
          $html .= '<tr>';
 
           $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
           $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;"> ' . $itemdata->exs_nm . '</td>';
  
  
  
  
          // $html .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 30%;">' .
          // $meassr .'</td>';
  
          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">';
  
           $numericValues = $meassr->pluck('sr_no')->toArray();
         if (!empty($numericValues)) {
             $html .= '<br>' . implode(', ', $numericValues);
         
  
          }
  
          // Close the table cell
          $html .= '</td>';
  
 
          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;">';
 
          // Extract ee_chk_qty values and concatenate them
          $ee_chk_qty_values = $meassr->pluck('ee_chk_qty')->toArray();
          if (!empty($ee_chk_qty_values)) {
              $html .= '<br>' . implode(', ', $ee_chk_qty_values);
          }
  
          $html .= '</td>';
           $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;"> ' . $itemdata->item_unit . '</td>';
 
  $html .= '</tr>';
  
 
 
 // Now you can use $html as needed, for example, output it in your view or send it as a response.
 
         //
 
             // dd($meassr[]);
 
 
         preg_match_all('/\d+/', $meassr, $numeric_values);
 
         // Convert the extracted numeric values to a comma-separated string
         $comma_separated_values = implode(',', $numeric_values[0]);
         // dd($numeric_values);
 
      }
 
 
     //  $measid=$itemdata->meas_id;
     //     //dd($measid);
 
      $qty = DB::table('embs')
          ->where('t_bill_id', $tbillid)
          ->where('ee_check', 1)
          ->value('qty');
      //dd($qty);
 
      $bill_rt = DB::table('bil_item')
          ->where('t_bill_id', $tbillid)
          ->value('bill_rt');
      //dd($bill_rt);
 
      $meas_amt=$bill_rt * $qty;
     //  dd($meas_amt);
      $checked_mead_amt=$checked_mead_amt+$meas_amt;
     //  //dd($checked_mead_amt);
      $result[]=$checked_mead_amt;
     //dd($result);
      // dd($checked_mead_amt);
      //dd($bitemid,$measid,$qty,$bill_rt,$meas_amt);
 
 }

 // dd($checked_mead_amt);
 
 $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
 //dd($Checked_Percentage);
 //Format the result to have only three digits after the decimal point
 $Checked_Percentage = number_format($Checked_Percentage1, 2);
 
 $checked_meas_amt = number_format($checked_mead_amt, 2);
 // dd($Checked_Percentage);
 
 //Image........
 
     //
     // Construct the full file path
     //dd($sign3);
    // dd($si);
      
 
  $convert= new CommonHelper();


    
    $html .= '<tfoot>';
    $html .= '<tr>';
    $html .= '<td colspan="5" style="text-align: center;">';
    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%;"> Checked measurements ' . $embsection2->EEChk_percentage . '% . (Value Rs . ' . $convert->formatIndianRupees($embsection2->EEChk_Amt) . ')</th>';
    $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%;">';
    $html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';

    $html .= '<div style="line-height: 1; margin: 0;">';
    $html .= '<p style="line-height: 4; margin: 0;"></p>';
    $html .= '<p style="line-height: 4; margin: 0;"></p>';
    $html .= '</div>';

    $html .= '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tfoot>';
    
    $html .= '</table>';

 }
$agencyid=DB::table('workmasters')->where('Work_Id' , $workid)->value('Agency_Id');
 //dd($agencyid);
$agencydata=DB::table('agencies')->where('id' , $agencyid)->first();
 //dd($agencydata);
$agencyceck=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
$originalDate = $agencyceck->Agency_Check_Date;
$newDate = date("d-m-Y", strtotime($originalDate));
//dd($originalDate);
if($agencyceck->Agency_Check == '1')
{

$imagePath4 = public_path('Uploads/signature/' . $agencydata->agencysign);
 
    $imageData4 = base64_encode(file_get_contents($imagePath4));
    $imageSrc4 = 'data:image/jpeg;base64,' . $imageData4;

  
    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<thead>';
    $html .= '<tr>';

    $html .= '</tr>';

    $html .= '<tbody>';
    $html .= '<td colspan="3" style=" padding: 8px; width: 50%; text-align: center; line-height: 0;">';
     $html .= '</td>'; // First cell for signature details
     $html .= '<td colspan="6" style="padding: 8px; width: 50%; text-align: center; line-height: 0;">';
     $html .= '<div style="line-height: 1; margin: 0;"><strong>I have Checked all the measurements and I accept the measurements</strong></div>';

     $html .= '<div style="line-height: 1; margin: 0;"><strong>Date :' . $newDate . '</strong></div>';
     $html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc4 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
     $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $agencydata->agency_nm . ' , '. $agencydata->Agency_Pl .'</strong></div>';
     $html .= '</div>';
     $html .= '</td>'; // First cell for signature details
        $html .= '</tbody>';
      $html .= '</table>'; 
 }
 


 $recdata = $this->latestRecordData;

 $lastrecentrydata = $this->lastupdatedRecordData;
 //dd($recdata);
 $data=$this->abstractreportdata($tbillid , $recdata , $lastrecentrydata);


 $html .=$data;

 
  $sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
//   dd($sammarydata);
  $C_netAmt= $sammarydata->c_netamt;
  $chqAmt= $sammarydata->chq_amt;
$commonHelper = new CommonHelper();
$amountInWords = $commonHelper->convertAmountToWords($C_netAmt);

$C_netAmt=$commonHelper->formatIndianRupees($C_netAmt);

// dd($amountInWords);
  
  if($sammarydata->mb_status > 10)
  {
    // dd('ok');
    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<tbody>';
       $html .= '<tr>';
$html .= '<td colspan="2" style="text-align: right;">';
$html .= '<div style="line-height: 1; margin: 0;"><strong>Passed for Rs.'.$C_netAmt.' (' . $amountInWords . ')</strong></div>';
$html .= '</td>';
$html .= '</tr>';
     $html .= '<tr>';
     $html .= '<th style=" padding: 5px;  width: 50%; text-align: center;">';
     $html .= '</th>';

     $html .= '<th style=" padding: 5px;  width: 50%; text-align: center;">';

     $html .= '<div style="text-align: center; width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
     $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';
      $html .= '</div>';
     $html .= '</th>'; // First cell for signature details
         $html .= '</tr>';
        $html .= '</tbody></table>';



    $commonHelperDeduction = new CommonHelper();
    // Call the function using the instance
    $htmlDeduction = $commonHelperDeduction->DeductionSummaryDetails($tbillid);    
    $html .= $htmlDeduction;

    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-top:20px;">';
    $html .= '<tbody>';
    $html .= '<td style="padding: 8px; width: 50%; text-align: center; line-height: 0;"></td>';

    $html .= '<td style="padding: 8px; width: 50%; text-align: center; line-height: 0;">';
    $html .= '<div style="line-height: 3; margin: 0;"></div>';
    $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
    $html .= '<div style="margin: 0; padding-top: 50px;"><strong>C.A & F.O</strong></div>'; // Adjusted padding-top
    $html .= '<div style="margin: 0;"><strong>' . $division . '</strong></div>';
    $html .= '</div>';
    $html .= '</td>'; // First cell for signature details
         $html .= '</tbody>'; 

    $html .= '</table>'; 

  }


 
 
 
 

 //main table close
//dd($html);
//  $pdf = new Dompdf();

//  // Read the image file and convert it to base64
//  //$imagePath = public_path('images/sign.jpg');
// // $imageData = base64_encode(file_get_contents($imagePath));
//  //
//  //$imageSrc = 'data:image/jpeg;base64,' . $imageData;

 
// // Image path using the asset helper function
// $pdf->loadHtml($html);
// //$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
// $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

// // (Optional) Set options for the PDF rendering
// $options = new Options();
// $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
// $pdf->setOptions($options);

// $pdf->render();

// // Output the generated PDF (inline or download)
// return $pdf->stream('generated-pdf.pdf');
 return view('reports/Mb' ,compact('embsection2' , 'html'));
}



// MB report convert pdf function
public function mbreportpdf(Request $request , $tbillid)
{
 $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
 //dd($embsection2);

$html='';

$workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($workid);
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
//dd($workdata);
$jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;
   //dd($jeid);
   $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
   $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
// Construct the full file path
$imagePath = public_path('Uploads/signature/' . $sign->sign);
$imageSrc = $imagePath;

$imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
$imageSrc2 = $imagePath2;
 


$jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
$jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

$dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
$dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

//dd($workid);
    $EE_id=DB::table('workmasters')->where('Work_Id' , $workid)->value('EE_id');
    //dd($EE_id);

    //dd($dyeid);
    $sign3=DB::table('eemasters')->where('eeid' , $EE_id)->first();
    $designation=DB::table('designations')->where('Designation' , $sign3->Designation)->value('Designation');
    $division=DB::table('divisions')->where('div_id' , $sign3->divid)->value('div');


    $DBacYr = DB::table('acyrms')
   ->whereDate('Yr_St', '<=', $embsection2->Bill_Dt)
   ->whereDate('Yr_End', '>=', $embsection2->Bill_Dt)
   ->value('Ac_Yr');


 
   
   $html .= '<div class="container" style="margin-bottom: 50px; text-align: center;">
   <div class="row justify-content-center">
       <div class="col-md-8">
           <div class="card" style="border: 3px solid #000; border-width: 3px 1px; height: 950px;">
  
           <div class="text-center" style="margin-top:40px;">
           <h2 style="font-weight: bold;">'.$division.'</h2>
           </div>
           <div class="text-center">
           <h3 style="font-weight: bold;">'.$dyesubdivision.'</h3>
           </div>
           
           <div class="card-body text-center" style="height: 400px; margin-top: 80px;">
                   <h2 style="margin-top: 20px;">FORM NO-52</h2>
                   <h3 style="margin-top: 20px;">MEASUREMENT BOOK</h3>
                   <h3 style="margin-top: 20px;">MB NO : '.$workid.'</h3>
                 <h4 style="margin-top: 50px;">'.$sign2->name.' , '.$jedesignation.'</h4>
                 <h5 style="margin-top: 20px;">YEAR : '.$DBacYr.'</h5>
                   <h2>Name of Work : '.$workdata->Work_Nm.'</h2>
                   <!-- Add more lines or customize as needed -->
               </div>
           </div>
       </div>
   </div>
  </div>';  
//   <h4>Pages : From {START_PAGES}  To {TOTAL_PAGES}</h4>
$headercheck='MB';


$tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
//dd($recordentrynos);

$division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
//dd($tbillid);

     $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
     $billType = CommonHelper::getBillType($embsection2->final_bill);
//dd($formattedTItemNo , $billType);


$agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';




$html .= '<div class="" style="margin-top:20px; border-collapse: collapse;">

<table style="width: 100%; border-collapse: collapse;">

<tr>
<td  colspan="1" style="padding: 4px; text-align: right;"><h3><strong>' . $division . '</strong></h3></td>
<td  style=" padding: 4px; text-align: center; margin: 0 10px;"><h3><strong>MB NO: ' . $workid . '</strong></h3></td>
<td  style="padding: 4px; text-align: right;"><h3><strong>' . $workdata->Sub_Div . '</strong></h3></td>
</tr>

<tr>
<td colspan="14" style="text-align: center;"><h2><strong>MEASUREMENT BOOK</strong></h2></td>
</tr>

<tr>
<td  style=""></td>
<td  style="padding: 8px; text-align: center;"><h5><strong></strong></h5></td>
</tr>

<tr>
<td style=""><strong>Name of Work:</strong></td>
<td colspan="2">' . $workdata->Work_Nm . '</td>
</tr>

<tr>
<td  style=""><strong>Agency:</strong></td>
<td  style="">' . $workdata->Agency_Nm . '</td>
</tr>';

$html .= '<tr>';
$html .= '<td colspan="2" style="width: 50%;"><strong>Authority:</strong>'.$workdata->Agree_No.'</td>';
if(!empty($agreementDate))
{
$html .= '<td colspan="" style="width: 50%; text-align: right;"><strong>Date:</strong>' . $agreementDate . '</td>';
}
else{
   $html .= '<td colspan="" style="width: 40%;"></td>';

}
$html .= '</tr>';

$workdate=$workdata->Wo_Dt ?? null;
$workorderdt = date('d-m-Y', strtotime($workdate));

$html .= '<tr>';
$html .= '<td colspan="2" style="width: 60%;"><strong>WorkOrder No:</strong>' . $workdata->WO_No . '</td>';
$html .= '<td colspan="" style="width: 40%; text-align: right;"><strong>WorkOrder Date:</strong>' . $workorderdt . '</td>';
$html .= '</tr>';


$normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

$combinedDates = $normalmeas->merge($steelmeas);
$maxDate = $combinedDates->max();
$maxdate = date('d-m-Y', strtotime($maxDate));


if ($tbilldata->final_bill === 1) {
$date = $workdata->actual_complete_date ?? null;
$workcompletiondate = date('d-m-Y', strtotime($date));

$html .= '<tr>';
$html .= '<td colspan="2" style="width: 60%;"><strong>Actual Date of Completion:</strong>' . $workcompletiondate . '</td>';
$html .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
$html .= '</tr>';



} else {
$date = $workdata->Stip_Comp_Dt ?? null;
$workcompletiondate = date('d-m-Y', strtotime($date));

$html .= '<tr>';
$html .= '<td colspan="2" style="width: 60%;"><strong>Stipulated Date of Completion:</strong>' . $workcompletiondate . '</td>';
$html .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
$html .= '</tr>';


}
$html .= '</table></div>';


//dd($header);




// // Read the image file and convert it to base64
// $imagePath = public_path('images/sign.jpg');
// $imageData = base64_encode(file_get_contents($imagePath));
// $imageSrc = 'data:image/jpeg;base64,' . $imageData;

// $imagePath2 = public_path('images/sign2.jpg');
// $imageData2 = base64_encode(file_get_contents($imagePath2));
// $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


$bitemid=null;

$recdate=null;



     //main table
     $html .= '<table style="border-collapse: collapse; border: 1px solid black;">';
     $html .= '<thead>';    
     $html .= '<tr>';

     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 5%;">Sr NO</th>';
     $html .= '<th style=" padding: 15px; background-color: #f2f2f2; width: 30%; ">Particulars</th>';
     $html .= '<th style="padding: 15px; background-color: #f2f2f2; width: 30%; "></th>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Number</th>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Length</th>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Breadth</th>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Height</th>';
     $html .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; width: 10%;">Quantity</th>';
     $html .= '<th style="border: 1px solid black; padding: 9px; background-color: #f2f2f2; width: 15%;">Unit</th>';
     $html .= '</tr>';

     // Add more table headers as needed
     $html .= '</thead>';
     $html .= '<tbody>';


     $recordentrynos = DB::table('recordms')
     ->where('t_bill_id', $tbillid)
     ->orderBy('Record_Entry_Id', 'asc')
     ->get();
     
     // If there are records, exclude the last one
     if ($recordentrynos->isNotEmpty()) {
     $lastRecordId = $recordentrynos->pop()->Record_Entry_Id; // Remove and get the last record ID
     $recordentrynos = $recordentrynos->values(); // Re-index the collection
     }
     
     // Fetch the last record entry
     $lastRecordEntry = DB::table('recordms')
     ->where('t_bill_id', $tbillid)
     ->where('Record_Entry_Id', $lastRecordId ?? null) // Fetch if lastRecordId is set
     ->first();
     
     
    //dd($recordentrynos);
foreach($recordentrynos as $recordentrydata)
{
 $recdate=$recordentrydata->Rec_date;

 // 1 table

 //dd($recordentrydata);

 $rdate=$recordentrydata->Rec_date ?? null;
 $recordentrycdate = date('d-m-Y', strtotime($rdate));
 //$itemmeas ='';
 $itemmeas=$this->itemmeasdatapdf($tbillid , $recdate);
 
 $html .= '<tr>';
 $html .= '<th  colspan="5" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:left;">Record Entry No :' . $recordentrydata->Record_Entry_No . '</th>';
 $html .= '<th  colspan="4" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Date :' . $recordentrycdate . '</th>';
 $html .= '</tr>';

// 1 table end


//dd($itemmeas);
$html .=$itemmeas;

// Splitting a single cell into two equal-sized cells for signature
$html .= '<tr style="line-height: 0;">';
$html .= '<td colspan="5" style="border: 1px solid black; padding: 5px;  text-align: center; line-height: 0;">';
if($embsection2->mb_status >= '3')
{

$html .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
$html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$html .= '<div style="line-height: 1; margin: 0;">';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
$html .= '</div>';
}
$html .= '</td>'; // First cell for signature details
$html .= '<td colspan="4" style="border: 1px solid black; padding: 5px;  text-align: center; line-height: 0;">';
if($embsection2->mb_status >= '4')
{

$html .= '<div style="line-height: 1; margin: 0;"><strong>Checked by me at the site of work</strong></div>';
$html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
}
$html .= '</div>';
$html .= '</td>'; // First cell for signature details
$html .= '</tr>';


}

 
  
  
  $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
  $billType = CommonHelper::getBillType($embsection2->final_bill);
  


  if ($lastRecordEntry) {
    // Add your table view here
    $recno=DB::table('recordms')->where('t_bill_id' , $tbillid)->where('Rec_date' , $lastRecordEntry->Rec_date)->value('Record_Entry_No');
//dd($recno);
    // Convert Rec_date to dd mm yyyy format
$dateFormatted = date('d-m-Y', strtotime($lastRecordEntry->Rec_date));

//empty tr add for space
$html .= '<tr style="border: 1px solid black;">'; // Start a new row for record entry details
$html .= '<th colspan="4" style="padding: 8px; background-color: #f2f2f2; "></th>';
$html .= '<th colspan="5" style="padding: 8px; background-color: #f2f2f2; "></th>';
$html .= '</tr>'; // Close the <tr>


$html .= '<tr>'; // Start a new row for record entry details
$html .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; ">Bill: ' . $formattedTItemNo . ' ' . $billType . '</th>';
$html .= '<th colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; ">Date of measurement: ' . $dateFormatted . '</th>';
$html .= '</tr>'; // Close the <tr>



$billitems = DB::table('bil_item')->where('t_bill_id', $tbillid)->orderBy('t_item_no', 'asc')->get();



foreach($billitems as $itemdata)
{

    $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

        $bitemId=$itemdata->b_item_id;
    //dd($bitemId);
    $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
    $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $lastRecordEntry->Rec_date)->get();

    $html .= '<tr>';
    $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; ">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th>';
    $html .= '<th colspan="7" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;  text-align: justify;"> ' . $itemdata->item_desc . '</th>';
    // Add more table headers as needed
    $html .= '</tr>';


    $data = $this->latestRecordData;



                // 2 table
        // Create a table inside the main table cell
    
    // 2 table end

    // 3 rd table


        $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
    //dd($itemid);
        if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])) 
        {
            $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $lastRecordEntry->Rec_date)->get();
            
            $bill_rc_data = DB::table('bill_rcc_mbr')->get();

            //dd($stldata , $bill_rc_data);

          

          



                $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
        'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


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

    
            $sums = array_fill_keys($ldiamColumns, 0);

            foreach ($stldata as $row) {
                 foreach ($ldiamColumns as $ldiamColumn) {
                    $sums[$ldiamColumn] += $row->$ldiamColumn;
                 }
            }//dd($stldata);


            $bill_member = DB::table('bill_rcc_mbr')
            ->whereExists(function ($query) use ($bitemId) {
            $query->select(DB::raw(1))
            ->from('stlmeas')
            ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
            ->where('bill_rcc_mbr.b_item_id', $bitemId);
            })
            ->get();


        $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

        $membersWithData = [];
        foreach ($bill_member as $index => $member) {
            $rcmbrid = $member->rc_mbr_id;
            $memberdata = DB::table('stlmeas')->where('rc_mbr_id', $rcmbrid)->where('date_meas', $lastRecordEntry->Rec_date)->get();
            if (!$memberdata->isEmpty()) {
                $membersWithData[] = $rcmbrid;
            }
        }
        
        // Get the last rc_mbr_id that has memberdata
        $lastMemberRcmbrid = end($membersWithData);

        foreach ($bill_member as $index => $member) {
            //dd($member);
                $rcmbrid=$member->rc_mbr_id;
                    $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $lastRecordEntry->Rec_date)->get();
            //dd($memberdata);

        if ( !$memberdata->isEmpty()) {

            $html .= '<tr>';
            $html .= '<th colspan="2" style="border: 1px solid black;  background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
            $html .= '<th colspan="3" style="border: 1px solid black;  background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black;  background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
            $html .= '</tr>';
        
        
            $html .= '<tr><td colspan="9">
            <table style="border-collapse: collapse; width: 100%; border: 1px solid black; font-size:13px;">
            <thead><tr>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">Sr No</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">Bar Particulars</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">No of Bars</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">Length of Bars</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">6mm</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">8mm</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">10mm</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">12mm</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">16mm</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">20mm</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">25mm</th>
           <th style="border: 1px solid black;  background-color: #f2f2f2;">28mm</th>    
           </tr></thead><tbody>';
          
        
             foreach ($stldata as $bar) {
 //dd($stldata);
                 if ($bar->rc_mbr_id == $member->rc_mbr_id) {

                //dd($bar);// Assuming the bar data is within a property like "bar_data"
                $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
              
                        
                $html .= '<tr><td style="border: 1px solid black; padding: 5px; ">'. $bar->bar_sr_no .'</td>
               <td style="border: 1px solid black; padding: 5px; ">'. $bar->bar_particulars.'</td>
               <td style="border: 1px solid black; padding: 5px; ">'. $bar->no_of_bars .'</td>
                <td style="border: 1px solid black; padding: 5px; ">'. $bar->bar_length .'</td>
                 <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam6 .'</td>
                <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam8 .'</td>
                 <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam10 .'</td>
                <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam12 .'</td>
                 <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam16 .'</td>
                 <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam20 .'</td>
                 <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam25 .'</td>
                 <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam28 .'</td></tr>';
                 
                          
                     }
                    
                }
               
                $embssteeldata = DB::table('embs')->where('b_item_id', $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
                //dd($embssteeldata);
                
                $barlengthl6=0;
                        $barlengthl8=0;
                        $barlengthl10=0;
                        $barlengthl12=0;
                        $barlengthl16=0;
                        $barlengthl20=0;
                        $barlengthl25=0;
                        $barlengthl28=0;
                        $barlengthl32=0;
                        $barlengthl36=0;
                        $barlengthl40=0;
                        $barlengthl45=0;
                
                   foreach($embssteeldata as $embdata)
                   {
                    $particular=$embdata->parti;
                    $firstThreeChars = substr($particular, 0, 3);
                
                    // Set $sec_type based on the first 3 characters
                    if ($firstThreeChars === "HCR") {
                        $sec_type = "HCRM/CRS Bar";
                    } else {
                        $sec_type = "TMT Bar";
                    }
                    
                    //dd($particular);
                    if ($sec_type == "HCRM/CRS Bar") {
                        $pattern = '/HCRM\/CRS Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
                    } else {
                        $pattern = '/TMT Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
                    }
                    if (preg_match($pattern, $particular, $matches)) {
                        // $matches[1] contains the diameter value
                        // $matches[3] contains the total length value
                        $diameter = $matches[1];
                        $totalLength = $matches[3];
                // dd($diameter , $totalLength);
                
                if ($diameter == '6') {
                    $barlengthl6 += $totalLength;
                } 
                if ($diameter == '8') {
                    $barlengthl8 += $totalLength;
                } 
                if ($diameter == '10') {
                    $barlengthl10 += $totalLength;
                } 
                if ($diameter == '12') {
                    $barlengthl12 += $totalLength;
                } 
                if ($diameter == '16') {
                    $barlengthl16 += $totalLength;
                } 
                if ($diameter == '20') {
                    $barlengthl20 += $totalLength;
                } 
                if ($diameter == '25') {
                    $barlengthl25 += $totalLength;
                } 
                if ($diameter == '28') {
                    $barlengthl28 += $totalLength;
                } 
                if ($diameter == '32') {
                    $barlengthl32 += $totalLength;
                } 
                if ($diameter == '36') {
                    $barlengthl36 += $totalLength;
                } 
                if ($diameter == '40') {
                    $barlengthl40 += $totalLength;
                } 
                if ($diameter == '45') {
                    $barlengthl45 += $totalLength;
                }
                        // Output the extracted values
                       
                     }
                   }
                
                       if ($rcmbrid == $lastMemberRcmbrid) {
                   $html .= '<tr>
                   <th colspan="4" style="border: 1px solid black; background-color: #f2f2f2; ">Total</th>
                   <th style="border: 1px solid black;  background-color: #f2f2f2;">'. number_format($barlengthl6, 3) .'</th>
                   <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl8, 3) .'</th>
                   <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl10, 3) .'</th>
                   <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl12, 3) .'</th>
                   <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl16, 3) .'</th>
                   <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl20, 3) .'</th>
                   <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl25, 3) .'</th>
                   <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl28, 3) .'</th>
                </tr>';
                
                       }
                $html .='</tbody></table></td></tr>';
    



            }


            


        }

     



       

}    



        $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
        $totalQty = 0; 
            foreach($normaldata as $nordata)
            {

                // dd($unit);

                        $formula= $nordata->formula;
        
                        $html .= '<tr>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->sr_no . '</td>';
                        $html .= '<td colspan="2" style="border: 1px solid black;  padding:5px; word-wrap: width: 100%; break-word; max-width: 200px;">' . $nordata->parti . '</td>';
                    if($formula)
                    {
                         
                        $html .= '<td colspan="4" style="border: 1px solid black; padding:5px;">' . $nordata->formula . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->qty . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $unit . '</td>';

                    

                    }
                    else
                    {
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->number . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->length . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->breadth . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->height . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->qty . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $unit . '</td>';

                    }
                        $html .= '</tr>';

                       

               }

              $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

              $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

           //dd($Qtydec);
              $totalQty=round(DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('notforpayment' , 0)->where('measurment_dt' , $lastRecordEntry->Rec_date)->sum('qty') ,  $Qtydec);
              $totalQty=number_format($totalQty ,  3, '.', '');


              $qtyaspersamerec = round(DB::table('embs')
              ->where('t_bill_id', $tbillid)
              ->where('b_item_id', $itemdata->b_item_id)
              ->where('notforpayment' , 0)
              ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
              ->sum('qty') , $Qtydec);
              $qtyaspersamerec=number_format($qtyaspersamerec ,  3, '.', '');

            //dd($qtyaspersamerec);

              $maxdate = DB::table('embs')
              ->where('t_bill_id', $tbillid)
              ->where('b_item_id', $itemdata->b_item_id)
              ->where('notforpayment' , 0)
              ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
              ->max('measurment_dt');

              $recordentryno=DB::table('recordms')
              ->where('t_bill_id', $tbillid)
              ->where('Rec_date', $maxdate)
              ->value('Record_Entry_No');
 //dd($maxdate);
              $recqty = 0; // Initialize $recqty
//dd($qtyaspersamerec);    
//$
//$recqty = number_format($qtyaspersamerec + $totalQty, 3);     

$Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
$recqty = number_format($Recqty ,  3, '.', '');

$itemno = $itemdata->t_item_no;

if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
 $itemno .= $itemdata->sub_no;
}

$itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);


$itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');




$recqty_float = floatval(str_replace(',', '', $recqty));
$totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;

$Totalquantityasper = number_format($totalquantityasper, 3, '.', '');





// $TotalQuantity=0;


if($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0 && $totalQty == 0)
{


   $html .= '<tr>';
   $html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
   $html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">Not Executed </td>';
   $html .= '<td style="border: 1px solid black; padding: 2px; "></td>';
   $html .= '</tr>';





}
elseif($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0 )
{

$TotalQuantity=$totalQty;
$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px; font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  ">' . $unit . '</td>';
$html .= '</tr>';


}



if($qtyaspersamerec != 0)
{

$TotalQuantity=number_format($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '');

//dd($qtyaspersamerec , $totalQty);
if($totalQty>0) 
{
$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  ">' . $unit . '</td>';
$html .= '</tr>';
          
}

if($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty == 0)
{
//dd($TotalQuantity);
$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">Not Executed </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  "></td>';
$html .= '</tr>';
}
else{


if($TotalQuantity == number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '') && $TotalQuantity > 0)
{
$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity(As per this MB Record Entry No:'.$recordentryno.')</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $TotalQuantity.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px; ">' . $unit . '</td>';
$html .= '</tr>';


}

else
{
              $html .= '<tr>';
              $html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Quantity as per this MB Record Entry No:'.$recordentryno.',  Item No:'.$itemno.'</td>';
              $html .= '<td style="border: 1px solid black; padding: 5px; font-weight: bold;">'. number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '').' </td>';
              $html .= '<td style="border: 1px solid black; padding: 2px;  ">' . $unit . '</td>';
              $html .= '</tr>';



              $html .= '<tr>';
$html .= '<td  colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $TotalQuantity.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
$html .= '</tr>';
//dd($TotalQuantity);

}

 }

 }





// $previousBillIds = DB::table('bills')
//                      ->where('work_id', '=', $workid)
//                     ->where('t_bill_Id', '<', $tbillid)
//                     ->pluck('t_bill_Id');

               
//                   $prevbillsqty=0;
//                     foreach($previousBillIds as $prevtbillid)
//                     {
                   
//                        $bitemids= DB::table('bil_item')
//                        ->where('t_bill_id', $prevtbillid)
//                        ->where('t_item_id', $itemdata->t_item_id)
//                        ->get('b_item_id');

//                        foreach($bitemids as $bitemid)
//                        {
//                         //dd($bitemid);
//                         $previtemsqty = DB::table('embs')
//                         ->where('b_item_id' ,  $bitemid->b_item_id)
//                         ->where('notforpayment' , 0)
//                         ->sum('qty');

//                         $prevbillsqty += $previtemsqty;
//                        }
                   
//                     }
//                     //dd($prevbillsqty);

if($itemdata->prv_bill_qty != 0 && $qtyaspersamerec == 0)
{

$TotalQuantity=number_format($totalQty+$itemdata_prv_bill_qty, 3, '.', '');

if($totalQty>0) 
{
//     $previousbillrqty = number_format($prevbillsqty , 3);
$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  ">' . $unit . '</td>';
$html .= '</tr>';
}


if($itemdata_prv_bill_qty==0)
{

    $html .= '<tr>';
    $html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">Not Executed </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;"></td>';
    $html .= '</tr>';
}
else{

if($TotalQuantity == $itemdata_prv_bill_qty && $TotalQuantity > 0)
{
$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;  text-align: right ; font-weight: bold; ">Total Uptodate Quantity(As per previous bill)</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $TotalQuantity.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px; ">' . $unit . '</td>';
$html .= '</tr>';


}

else
{

//dd($itemdata);

$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Quantity as per previous bill: </td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $itemdata_prv_bill_qty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
$html .= '</tr>';

           

//dd($TotalQuantity);
//dd($totalQty+$itemdata_prv_bill_qty);
$html .= '<tr>';
              $html .= '<td  colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity: </td>';
              $html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $TotalQuantity.' </td>';
              $html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
              $html .= '</tr>';



}



}
            }

// 3 table end
// 3 table end

$nordata=DB::table('embs')->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $lastRecordEntry->Rec_date)->count();
$steeldata=DB::table('stlmeas')->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $lastRecordEntry->Rec_date)->count();

if($nordata > 0 || $steeldata > 0)
{
$this->lastupdatedRecordData[$itemdata->b_item_id] = [
'Record_Entry_No' => $recno,
't_item_no' => $itemdata->t_item_no, // Include t_item_no for comparison
'Total_Uptodate_Quantity' => $TotalQuantity,
'b_item_id' => $itemdata->b_item_id, // Include b_item_id
];

}



    
    
   }

   }

  


  
   $html .= '</tbody></table>';

     


//  // Priyanka Edits..............................................................................................................

 $billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
 $eecheckdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('ee_check' , 1)->get();
    $imagePath3 = public_path('Uploads/signature/' . $sign3->sign);
 
    $imageSrc3 = $imagePath3;

 if ($eecheckdata->isNotEmpty())
 {
 

 $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
 $html .= '<thead>';
 $html .= '<tr>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 100%;" colspan="5"><h4>Executive Engineer Checking:</h4></th>';
 $html .= '</tr>';
 $html .= '</thead>';
 
 $html .= '<thead>';
 $html .= '<tr>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; ">Item No</th>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; ">Item Description</th>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; ">Measurement No</th>';
  $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; ">Quantity</th>';
$html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; ">Unit</th>';
 $html .= '</tr>';
 $html .= '</thead>';
 
 $html .= '<tbody>';
 
 
 $checked_mead_amt=0;
 $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
 
 $b_item_amt= DB::table('bills')
 ->where('t_bill_id', $tbillid)
 ->value('bill_amt');
 //dd($billitemdata);
 
 foreach($billitemdata as $itemdata)
 {
     //dd($itemdata);
     $bitemId=$itemdata->b_item_id;
     //dd($bitemId);
     //  $measnormaldata=DB::table('embs')->where('ee_check',1)->get();
    //dd($measnormaldata);
    $meassr = DB::table('embs')
    ->select('sr_no', 'ee_chk_qty')
    ->where('b_item_id', $bitemId)
    ->where('ee_check', 1)
    ->get();
    
    //dd($meassr);
      if (!$meassr->isEmpty() ) {
         // if ($measnormaldata ) {
         //dd($measnormaldata->sr_no);
 
         $html .= '<tr>';

          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;"> ' . $itemdata->exs_nm . '</td>';
 
 
 
 
         // $html .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 30%;">' .
         // $meassr .'</td>';
 
         $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">';
 
          $numericValues = $meassr->pluck('sr_no')->toArray();
        if (!empty($numericValues)) {
            $html .= '<br>' . implode(', ', $numericValues);
        
 
         }
 
         // Close the table cell
         $html .= '</td>';
 

         $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;">';

         // Extract ee_chk_qty values and concatenate them
         $ee_chk_qty_values = $meassr->pluck('ee_chk_qty')->toArray();
         if (!empty($ee_chk_qty_values)) {
             $html .= '<br>' . implode(', ', $ee_chk_qty_values);
         }
 
         $html .= '</td>';
          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;"> ' . $itemdata->item_unit . '</td>';

 $html .= '</tr>';
 
 // Now you can use $html as needed, for example, output it in your view or send it as a response.
 
         //
 
             // dd($meassr[]);
 
 
         preg_match_all('/\d+/', $meassr, $numeric_values);
 
         // Convert the extracted numeric values to a comma-separated string
         $comma_separated_values = implode(',', $numeric_values[0]);
         // dd($numeric_values);
 
      }
 
 
     //  $measid=$itemdata->meas_id;
     //     //dd($measid);
 
      $qty = DB::table('embs')
          ->where('t_bill_id', $tbillid)
          ->where('ee_check', 1)
          ->value('qty');
      //dd($qty);
 
      $bill_rt = DB::table('bil_item')
          ->where('t_bill_id', $tbillid)
          ->value('bill_rt');
      //dd($bill_rt);
 
      $meas_amt=$bill_rt * $qty;
     //  dd($meas_amt);
      $checked_mead_amt=$checked_mead_amt+$meas_amt;
     //  //dd($checked_mead_amt);
      $result[]=$checked_mead_amt;
     //dd($result);
      // dd($checked_mead_amt);
      //dd($bitemid,$measid,$qty,$bill_rt,$meas_amt);
 
 }

 // dd($checked_mead_amt);
 
 $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
 //dd($Checked_Percentage);
 //Format the result to have only three digits after the decimal point
 $Checked_Percentage = number_format($Checked_Percentage1, 2);
 
 $checked_meas_amt = number_format($checked_mead_amt, 2);
 // dd($Checked_Percentage);
 
 //Image........
 
     //dd($workid);
     // Construct the full file path
     //dd($sign3);
    // dd($si);
      
       $convert = new CommonHelper();

    $html .= '</table>';

    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%; text-align: left;"> Checked measurements  ' . $embsection2->EEChk_percentage . '% . (Value Rs . ' . $convert->formatIndianRupees($embsection2->EEChk_Amt) . ')</th>';
    $html .= '<th style="border: 1px solid black; padding: 5px; width: 50%;">';
    $html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';

    $html .= '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '</table>';

}

    $agencyid=DB::table('workmasters')->where('Work_Id' , $workid)->value('Agency_Id');
 //dd($agencyid);
$agencydata=DB::table('agencies')->where('id' , $agencyid)->first();
 //dd($agencydata);
$agencyceck=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
$originalDate = $agencyceck->Agency_Check_Date;
$newDate = date("d-m-Y", strtotime($originalDate));
//dd($newDate);
if($agencyceck->Agency_Check == '1')
{

$imagePath4 = public_path('Uploads/signature/' . $agencydata->agencysign);
 
    $imageSrc4 = $imagePath4;
  

    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<tbody>';
       $html .= '<tr>';
$html .= '<td colspan="2" style="text-align: right;">';
$html .= '<strong>I have Checked all the measurements and I accept the measurements</strong>';
$html .= '</td>';
$html .= '</tr>';
     $html .= '<tr>';
     $html .= '<th style=" padding: 5px;  width: 50%; text-align: center;">';
     $html .= '</th>';

     $html .= '<th>';

     $html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc4 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
     $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $agencydata->agency_nm . ' , '. $agencydata->Agency_Pl .'</strong></div>';
     $html .= '</div>';
     $html .= '</th>'; // First cell for signature details
         $html .= '</tr>';
        $html .= '</tbody></table>';
    
    
    
    
    
    
    
    
    
}

$recdata = $this->latestRecordData;

$lastrecentrydata = $this->lastupdatedRecordData;
//dd($lastrecentrydata);
$data=$this->abstractpdfdata($tbillid , $recdata , $lastrecentrydata);


 $html .= $data;



 $sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
//   dd($sammarydata);
  $C_netAmt= $sammarydata->c_netamt;
  $chqAmt= $sammarydata->chq_amt;
$commonHelper = new CommonHelper();
$amountInWords = $commonHelper->convertAmountToWords($C_netAmt);
$C_netAmt=$commonHelper->formatIndianRupees($C_netAmt);

// dd($amountInWords);
  
  if($sammarydata->mb_status > 10)
  {
    // dd('ok');

   $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<tbody>';
       $html .= '<tr>';
$html .= '<td colspan="2" style="text-align: right;">';
$html .= '<div style="line-height: 1; margin: 0;"><strong>Passed for Rs.'.$C_netAmt.' (' . $amountInWords . ')</strong></div>';
$html .= '</td>';
$html .= '</tr>';
     $html .= '<tr>';
     $html .= '<th style=" padding: 5px;  width: 50%; text-align: center;">';
     $html .= '</th>';

     $html .= '<th>';

     $html .= '<div style="line-height: 1; width: 50%; margin: 0;"><strong></div>';

    $html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';
     $html .= '</div>';
     $html .= '</th>'; // First cell for signature details
         $html .= '</tr>';
        $html .= '</tbody></table>';; 

    $commonHelperDeduction = new CommonHelper();
    // Call the function using the instance
    $htmlDeduction = $commonHelperDeduction->DeductionSummaryDetails($tbillid);    
    $html .= $htmlDeduction;

    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-top: 20px;">';
    $html .= '<tbody>';
    $html .= '<tr>';

  $html .= '<td style="padding: 8px; width: 50%; text-align: center;"></td>';

    $html .= '<td style="padding: 60px 8px 8px 8px; width: 50%; text-align: center; line-height: 0;">';
    $html .= '<div style="height: 100px;"><strong>C.A & F.O</strong></div>'; // Adjusted padding-top
    $html .= '<div style="height: 100px;"><strong>' . $division . '</strong></div>';
    $html .= '</td>'; // First cell for signature details
    $html .= '</tr>';


         $html .= '</tbody>'; 

    $html .= '</table>'; 

   }






 



  $mpdf = new Mpdf();
  $mpdf->autoScriptToLang = true;
  $mpdf->autoLangToFont = true;

  $logo = public_path('photos/zplogo5.jpeg');
 

// Set watermark image
$mpdf->SetWatermarkImage($logo);

// Show watermark image
$mpdf->showWatermarkImage = true;


// Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
$mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed

  $previousbilldata=DB::table('bills')->where('work_id' , $workid) 
  ->where('t_bill_Id', '<', $tbillid) 
  ->orderByDesc('t_bill_Id') // Order by billid in descending order
  ->first(); 
  // Filter bills with IDs less than current bill ID
// Determine the total number of pages
// Write HTML content to PDF
$mpdf->WriteHTML($html);


// Determine the total number of pages
$totalPages = $mpdf->PageNo();

$upiId = 'pm.priyanka@postbank';
$amount = 1; // Replace with the actual payment amount

$qrCodeContent = 'Your QR code content here'; // Replace with your actual content
        
// $tbillid = 12345;
// $workid = 56263546723;





$startingPage = 2;

if ($previousbilldata !== null) {
    $startPageNumber = $previousbilldata->pg_upto + 1;
} else {
    // If no previous bill data exists, set startingPage to 1
    $startPageNumber = 1;
}

//dd($startPageNumber);
// Define the starting number for the displayed page numbers
// Calculate the total number of pages to be displayed in the footer
$totalFooterPages = $totalPages + $startPageNumber-2;

DB::table('bills')->where('t_bill_Id' , $tbillid)->update([
    'pg_from'=>$startPageNumber,
    'pg_upto'=>$totalFooterPages
]);

for ($i = 1; $i <= 1; $i++) { // Only loop once for the first page
    $mpdf->page = $i;
    
    // Conditional style for first page
    if ($i === 1) {
        // Content centered on the first page
        $mpdf->WriteHTML('<div style="position: absolute; top: 39%; left: 42%; transform: translateX(-50%); font:weight;"><h3>Page No from ' . $startPageNumber . ' to ' . $totalFooterPages . '</h3></div>');
    } 
    
}

$Normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$Steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

$combinedDates = $Normalmeas->merge($Steelmeas);
$Maxdate = $combinedDates->max();
$Maxdate = date('d-m-Y', strtotime($Maxdate));


$billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

$paymentInfo = "$workid" . PHP_EOL . "$startPageNumber-$totalFooterPages" . PHP_EOL . "$Maxdate";


$qrCode = QrCode::size(60)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(1)
->generate($paymentInfo);


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);


// Define the background image in CSS
//$backgroundImagePath = 'path/to/your/image.jpg';
$mpdf->SetHTMLHeader('<div class="background-image" style="background-image: url(' . $imageSrc3 . ');"></div>');


// Add page numbers to each page starting from the specified page number
for ($i = 2; $i <= $totalPages; $i++) {
    // Calculate the displayed page number
    $pageNumber = $startPageNumber;
    
    // Set the current page for mPDF
    $mpdf->page = $i;
    
    if ($i === 2) {
        // Content centered on the first page
        $mpdf->WriteHTML('<div style="position: absolute; top: 10%; left: 89%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div>');
    } 
    // Write the page number to the PDF
    $mpdf->WriteHTML('<div style="position: absolute; top: 20px; right: 20px;">Page No ' . $pageNumber . '</div>');
    $startPageNumber++;

}


 $mpdf->Output('MB-' . $tbillid . '.pdf', 'D');
//return $pdf->stream('MB-' . $tbillid . '-pdf.pdf');
}




public function mbreportpdfcopy(Request $request , $tbillid)
{
 $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
 //dd($embsection2);

$html='';

$workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($workid);
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
//dd($workdata);
$jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;
   //dd($jeid);
   $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
   $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
// Construct the full file path
$imagePath = public_path('Uploads/signature/' . $sign->sign);
$imageData = base64_encode(file_get_contents($imagePath));
$imageSrc = 'data:image/jpeg;base64,' . $imageData;

$imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
$imageData2 = base64_encode(file_get_contents($imagePath2));
$imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;
 


$jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
$jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

$dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
$dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

//dd($workid);
    $EE_id=DB::table('workmasters')->where('Work_Id' , $workid)->value('EE_id');
    //dd($EE_id);

    //dd($dyeid);
    $sign3=DB::table('eemasters')->where('eeid' , $EE_id)->first();
    $designation=DB::table('designations')->where('Designation' , $sign3->Designation)->value('Designation');
    $division=DB::table('divisions')->where('div_id' , $sign3->divid)->value('div');


    $DBacYr = DB::table('acyrms')
   ->whereDate('Yr_St', '<=', $embsection2->Bill_Dt)
   ->whereDate('Yr_End', '>=', $embsection2->Bill_Dt)
   ->value('Ac_Yr');


 
   
   $html .= '<div class="container" style="margin-bottom: 50px; text-align: center;">
   <div class="row justify-content-center">
       <div class="col-md-8">
           <div class="card" style="border: 3px solid #000; border-width: 3px 1px; height: 950px;">
  
           <div class="text-center" style="margin-top:40px;">
           <h2 style="font-weight: bold;">'.$division.'</h2>
           </div>
           <div class="text-center">
           <h3 style="font-weight: bold;">'.$dyesubdivision.'</h3>
           </div>
           
           <div class="card-body text-center" style="height: 400px; margin-top: 80px;">
                   <h2 style="margin-top: 20px;">FORM NO-52</h2>
                   <h3 style="margin-top: 20px;">MEASUREMENT BOOK</h3>
                   <h3 style="margin-top: 20px;">MB NO : '.$workid.'</h3>
                 <h4 style="margin-top: 20px;">'.$sign2->name.' , '.$jedesignation.'</h4>
                 <h5 style="margin-top: 20px;">YEAR : '.$DBacYr.'</h5>
                   <h2>Name of Work : '.$workdata->Work_Nm.'</h2>
                   <!-- Add more lines or customize as needed -->
               </div>
           </div>
       </div>
   </div>
  </div>';  
//   <h4>Pages : From {START_PAGES}  To {TOTAL_PAGES}</h4>
$headercheck='MB';
$header=$this->commonheader($tbillid , $headercheck);

$html .=$header;
//dd($header);




// // Read the image file and convert it to base64
// $imagePath = public_path('images/sign.jpg');
// $imageData = base64_encode(file_get_contents($imagePath));
// $imageSrc = 'data:image/jpeg;base64,' . $imageData;

// $imagePath2 = public_path('images/sign2.jpg');
// $imageData2 = base64_encode(file_get_contents($imagePath2));
// $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


$bitemid=null;

$recdate=null;



     //main table
     $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
     $html .= '<thead>';    
     $html .= '<tr>';
     $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
     $html .= '<thead>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 5%;">Sr NO</th>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 30%;">Particulars</th>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Number</th>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Length</th>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Breadth</th>';
     $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Height</th>';
     $html .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; width: 10%;">Quantity</th>';
     $html .= '<th style="border: 1px solid black; padding: 9px; background-color: #f2f2f2; width: 15%;">Unit</th>';

     $html .= '</thead>';
     $html .= '</table>';
     $html .= '</tr>';
     // Add more table headers as needed
     $html .= '</thead>';
     $html .= '<tbody>';
 
 
 
     
 
 
    // Fetch all record entries
$recordentrynos = DB::table('recordms')
->where('t_bill_id', $tbillid)
->orderBy('Record_Entry_Id', 'asc')
->get();

// If there are records, exclude the last one
if ($recordentrynos->isNotEmpty()) {
$lastRecordId = $recordentrynos->pop()->Record_Entry_Id; // Remove and get the last record ID
$recordentrynos = $recordentrynos->values(); // Re-index the collection
}

// Fetch the last record entry
$lastRecordEntry = DB::table('recordms')
->where('t_bill_id', $tbillid)
->where('Record_Entry_Id', $lastRecordId ?? null) // Fetch if lastRecordId is set
->first();



 

foreach($recordentrynos as $recordentrydata)
{
 $recdate=$recordentrydata->Rec_date;

 // 1 table

 //dd($recordentrydata);

 $rdate=$recordentrydata->Rec_date ?? null;
 $recordentrycdate = date('d-m-Y', strtotime($rdate));
 //$itemmeas ='';
 $itemmeas=$this->itemmeasdata($tbillid , $recdate);
 $html .= '<tr>';
 $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
 $html .= '<thead>';
 $html .= '<th  style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 50%;">Record Entry No :' . $recordentrydata->Record_Entry_No . '</th>';
 $html .= '<th  style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 50%;">Date :' . $recordentrycdate . '</th>';
 $html .= '</thead>';
 $html .= '</table>';
 $html .= '</tr>';

// 1 table end


//dd($result);
$html .=$itemmeas;

// Splitting a single cell into two equal-sized cells for signature
$html .= '<tr style="line-height: 0;">';
$html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<tbody>';
$html .= '<td colspan="3" style="border: 1px solid black; padding: 5px; max-width: 40%; text-align: center; line-height: 0;">';
if($embsection2->mb_status >= '3')
{

$html .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
$html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$html .= '<div style="line-height: 1; margin: 0;">';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
$html .= '</div>';
}
$html .= '</td>'; // First cell for signature details
$html .= '<td colspan="6" style="border: 1px solid black; padding: 5px; max-width: 60%; text-align: center; line-height: 0;">';
if($embsection2->mb_status >= '4')
{

$html .= '<div style="line-height: 1; margin: 0;"><strong>Checked by me at the site of work</strong></div>';
$html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
}
$html .= '</div>';
$html .= '</td>'; // First cell for signature details
  $html .= '</tbody>';
$html .= '</table>'; 
$html .= '</tr>';
}



$html .= '<tr>'; // Start a new row for record entry details
$html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<thead>';
$html .= '<tr>'; // <tr> should be inside <thead>
$html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 100%;"></th>';
$html .= '</tr>'; // Close the <tr>
$html .= '</thead>';
$html .= '</table>'; // Close the <table>
$html .= '</tr>'; // Close the outer <tr>


$formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
$billType = CommonHelper::getBillType($embsection2->final_bill);


if ($lastRecordEntry) {
    // Add your table view here
    $recno=DB::table('recordms')->where('t_bill_id' , $tbillid)->where('Rec_date' , $lastRecordEntry->Rec_date)->value('Record_Entry_No');

    // Convert Rec_date to dd mm yyyy format
$dateFormatted = date('d-m-Y', strtotime($lastRecordEntry->Rec_date));

$html .= '<tr>'; // Start a new row for record entry details
$html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<thead>';
$html .= '<tr>'; // <tr> should be inside <thead>
$html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 50%;">Bill: ' . $formattedTItemNo . ' ' . $billType . '</th>';
$html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 50%;">Date of measurement: ' . $dateFormatted . '</th>';
$html .= '</tr>'; // Close the <tr>
$html .= '</thead>';
$html .= '</table>'; // Close the <table>
$html .= '</tr>'; // Close the outer <tr>


    $billitems = DB::table('bil_item')->where('t_bill_id', $tbillid)->orderBy('t_item_no', 'asc')->get();

    // Now you can use $data as needed
    // For example, you can pass it to another function or manipulate it
    // You can also access individual elements of the array like $data['key']
    //dd($data); // Assuming dd is a function f

    foreach($billitems as $itemdata)
    {

        $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

            $bitemId=$itemdata->b_item_id;
        //dd($bitemId);
        $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
        $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $lastRecordEntry->Rec_date)->get();

        $html .= '<tr>';
        $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
        $html .= '<tr>';
        $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th>';
        $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 90%; text-align: justify;"> ' . $itemdata->item_desc . '</th>';
        // Add more table headers as needed
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</tr>';


        $data = $this->latestRecordData;

//dd($data);

// Assuming $data is an associative array with keys as item numbers
// if (isset($data[$itemdata->b_item_id])) {
//     $recordentryno = $data[$itemdata->b_item_id]['Record_Entry_No'];
//     $Totalqty= $data[$itemdata->b_item_id]['Total_Uptodate_Quantity'];
//     $b_item_id=$data[$itemdata->b_item_id]['b_item_id'];

//     $embsCount = DB::table('embs')->where('b_item_id', $b_item_id)->count();
//     $stlmeasCount = DB::table('stlmeas')->where('b_item_id', $b_item_id)->count();

//     if ($embsCount == 0 && $stlmeasCount == 0)  {
//         // Add HTML for the case where measurements are not found
//         $html .= '<tr>';
//         $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 80%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed</td>';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%; font-weight: bold;"></td>';
//         $html .='</tbody>';
//         $html .= '</tr>';
//     } else {
//         // Add HTML for the case where measurements are executed
//         $html .= '<tr>';
//         $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 80%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity as per this MB Record Entry No:'.$recordentryno.'</td>';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $Totalqty.' </td>';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%; font-weight: bold;">' . $unit . '</td>';
//         $html .='</tbody>';
//         $html .= '</tr>';
//     }
// }

        //meas data check


                    // 2 table
            // Create a table inside the main table cell
        
        // 2 table end

        // 3 rd table


            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
        //dd($itemid);
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])) 
            {
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $lastRecordEntry->Rec_date)->get();
                
                $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                // dd($stldata , $bill_rc_data);

              

              



                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


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

        
                $sums = array_fill_keys($ldiamColumns, 0);

                foreach ($stldata as $row) {
                     foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                     }
                }//dd($stldata);


                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemId) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemId);
                })
                ->get();


            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();


        foreach ($bill_member as $index => $member) {
                //dd($member);
                    $rcmbrid=$member->rc_mbr_id;
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $lastRecordEntry->Rec_date)->get();
                //dd($memberdata);

            if ( !$memberdata->isEmpty()) {


                $html .= '<tr>';
                $html .= '<table style="border-collapse: collapse; width: 100%;"><thead>';
                $html .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
                $html .= '</thead></table>';
                $html .= '</tr>';
            

                $html .= '<tr>
                <table style="border-collapse: collapse; width: 100%; border: 1px solid black;">
               <thead>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 13%; min-width: 13%;">Bar Particulars</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">6mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">8mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">10mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">12mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">16mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">20mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 9%;">25mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 9%;">28mm</th>    
               </thead><tbody>';
            
                foreach ($stldata as $bar) {

                    if ($bar->rc_mbr_id == $member->rc_mbr_id) {

                    //dd($bar);// Assuming the bar data is within a property like "bar_data"
                    $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                  
                            
                     $html .=   '<tr><td style="border: 1px solid black; padding: 5px; width: 5%;  min-width: 5%;">'. $bar->bar_sr_no .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 13%; min-width: 13%;">'. $bar->bar_particulars.'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 10%; min-width: 10%;">'. $bar->no_of_bars .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 10%; min-width: 10%;">'. $bar->bar_length .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam6 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam8 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam10 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam12 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam16 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam20 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 9%; min-width: 9%;">'. $bar->ldiam25 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 9%; min-width: 9%;">'. $bar->ldiam28 .'</td></tr>';
                          
                     
                              
                        }

                        $html .='</tbody></table> </tr>';


                    }
                  
                   


                }
        

            }

            $embssteeldata = DB::table('embs')->where('b_item_id', $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
    //dd($embssteeldata);

    $barlengthl6=0;
            $barlengthl8=0;
            $barlengthl10=0;
            $barlengthl12=0;
            $barlengthl16=0;
            $barlengthl20=0;
            $barlengthl25=0;
            $barlengthl28=0;
            $barlengthl32=0;
            $barlengthl36=0;
            $barlengthl40=0;
            $barlengthl45=0;

       foreach($embssteeldata as $embdata)
       {
        $particular=$embdata->parti;
        $firstThreeChars = substr($particular, 0, 3);

        // Set $sec_type based on the first 3 characters
        if ($firstThreeChars === "HCR") {
            $sec_type = "HCRM/CRS Bar";
        } else {
            $sec_type = "TMT Bar";
        }
        
        //dd($particular);
        if ($sec_type == "HCRM/CRS Bar") {
            $pattern = '/HCRM\/CRS Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
        } else {
            $pattern = '/TMT Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
        }
        if (preg_match($pattern, $particular, $matches)) {
            // $matches[1] contains the diameter value
            // $matches[3] contains the total length value
            $diameter = $matches[1];
            $totalLength = $matches[3];
   // dd($diameter , $totalLength);

    if ($diameter == '6') {
        $barlengthl6 += $totalLength;
    } 
    if ($diameter == '8') {
        $barlengthl8 += $totalLength;
    } 
    if ($diameter == '10') {
        $barlengthl10 += $totalLength;
    } 
    if ($diameter == '12') {
        $barlengthl12 += $totalLength;
    } 
    if ($diameter == '16') {
        $barlengthl16 += $totalLength;
    } 
    if ($diameter == '20') {
        $barlengthl20 += $totalLength;
    } 
    if ($diameter == '25') {
        $barlengthl25 += $totalLength;
    } 
    if ($diameter == '28') {
        $barlengthl28 += $totalLength;
    } 
    if ($diameter == '32') {
        $barlengthl32 += $totalLength;
    } 
    if ($diameter == '36') {
        $barlengthl36 += $totalLength;
    } 
    if ($diameter == '40') {
        $barlengthl40 += $totalLength;
    } 
    if ($diameter == '45') {
        $barlengthl45 += $totalLength;
    }
            // Output the extracted values
           
        }
       }

           
       $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; font-size: 13px;">
       <thead><tr>
           <th style="padding: 5px; width: 5%; background-color: #f2f2f2; min-width: 5%;"></th>
           <th style="padding: 5px; background-color: #f2f2f2; width: 13%; min-width: 13%;"></th>
           <th style="padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;"></th>
           <th style="padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Total</th>
           <th style="border: 1px solid black; padding: 5px; width: 7%; background-color: #f2f2f2;">'. number_format($barlengthl6, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl8, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl10, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl12, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl16, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl20, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 7%;">'. number_format($barlengthl25, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 7%;">'. number_format($barlengthl28, 3) .'</th>
           <tr></thead>
   </table>';
     
           

    }    
   


            $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
            $totalQty = 0; 
                foreach($normaldata as $nordata)
                {

                    // dd($unit);

                            $formula= $nordata->formula;
            
                                $html .= '<tr>';
                                $html .= '<table style="border-collapse: collapse; width: 100%;"><tbody>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 5%;">' . $nordata->sr_no . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 30%; word-wrap: break-word; max-width: 200px;">' . $nordata->parti . '</td>';
                            if($formula)
                            {
                                 
                                $html .= '<td colspan="4" style="border: 1px solid black; padding: 5px; width: 40%;">' . $nordata->formula . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 15%;">' . $unit . '</td>';

                            

                            }
                            else
                            {
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->number . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->length . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->breadth . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->height . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 15%;">' . $unit . '</td>';

                            }
                                $html .='</tbody></table>';
                                $html .= '</tr>';

                           

                  }
               
                  $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

                  $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

               //dd($Qtydec);
                  $totalQty=round(DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('notforpayment' , 0)->where('measurment_dt' , $lastRecordEntry->Rec_date)->sum('qty') ,  $Qtydec);
                  $totalQty=number_format($totalQty ,  3, '.', '');


                  $qtyaspersamerec = round(DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
                  ->sum('qty') , $Qtydec);
                  $qtyaspersamerec=number_format($qtyaspersamerec ,  3, '.', '');

                //dd($qtyaspersamerec);

                  $maxdate = DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
                  ->max('measurment_dt');

                  $recordentryno=DB::table('recordms')
                  ->where('t_bill_id', $tbillid)
                  ->where('Rec_date', $maxdate)
                  ->value('Record_Entry_No');
     //dd($maxdate);
                  $recqty = 0; // Initialize $recqty
 //dd($qtyaspersamerec);    
    //$
 //$recqty = number_format($qtyaspersamerec + $totalQty, 3);     
 
 $Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
 $recqty = number_format($Recqty ,  3, '.', '');

 $itemno = $itemdata->t_item_no;

 if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
     $itemno .= $itemdata->sub_no;
 }

 $itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);


 $itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');




 $recqty_float = floatval(str_replace(',', '', $recqty));
 $totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;

 $Totalquantityasper = number_format($totalquantityasper, 3, '.', '');




   
 $TotalQuantity=0;


 if($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0 && $totalQty == 0)
 {
    
    
       $html .= '<tr>';
       $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
       $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
       $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
       $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;"></td>';
       $html .='</tbody>';
       $html .= '</tr>';
   
   
  
   
   
 }
 elseif($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0 )
 {
 
    $TotalQuantity=$totalQty;
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
    $html .='</tbody>';
    $html .= '</tr>';


 }



if($qtyaspersamerec != 0)
{

    $TotalQuantity=number_format($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '');

//dd($qtyaspersamerec , $totalQty);
if($totalQty>0) 
{
$html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';
              
}

if($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty == 0)
{
    //dd($TotalQuantity);
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;"></td>';
    $html .='</tbody>';
    $html .= '</tr>';
}
else{


if($TotalQuantity == number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '') && $TotalQuantity > 0)
{
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity(As per this MB Record Entry No:'.$recordentryno.')</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
    $html .='</tbody>';
    $html .= '</tr>';
    

}

else
{
                  $html .= '<tr>';
                  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per this MB Record Entry No:'.$recordentryno.',  Item No:'.$itemno.'</td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '').' </td>';
                  $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
                  $html .='</tbody>';
                  $html .= '</tr>';



                  $html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';
//dd($TotalQuantity);

}

}

}





// $previousBillIds = DB::table('bills')
//                      ->where('work_id', '=', $workid)
//                     ->where('t_bill_Id', '<', $tbillid)
//                     ->pluck('t_bill_Id');

                   
//                   $prevbillsqty=0;
//                     foreach($previousBillIds as $prevtbillid)
//                     {
                       
//                        $bitemids= DB::table('bil_item')
//                        ->where('t_bill_id', $prevtbillid)
//                        ->where('t_item_id', $itemdata->t_item_id)
//                        ->get('b_item_id');

//                        foreach($bitemids as $bitemid)
//                        {
//                         //dd($bitemid);
//                         $previtemsqty = DB::table('embs')
//                         ->where('b_item_id' ,  $bitemid->b_item_id)
//                         ->where('notforpayment' , 0)
//                         ->sum('qty');

//                         $prevbillsqty += $previtemsqty;
//                        }
                       
//                     }
//                     //dd($prevbillsqty);

 if($itemdata->prv_bill_qty != 0 && $qtyaspersamerec == 0)
 {

    $TotalQuantity=number_format($totalQty+$itemdata_prv_bill_qty, 3, '.', '');

    if($totalQty>0) 
    {
//     $previousbillrqty = number_format($prevbillsqty , 3);
$html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';
    }


    if($itemdata_prv_bill_qty==0)
    {

        $html .= '<tr>';
        $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
        $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;"></td>';
        $html .='</tbody>';
        $html .= '</tr>';
    }
    else{

    if($TotalQuantity == $itemdata_prv_bill_qty && $TotalQuantity > 0)
{
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity(As per previous bill)</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
    $html .='</tbody>';
    $html .= '</tr>';
    

}

else
{

  //dd($itemdata);

  $html .= '<tr>';
  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per previous bill: </td>';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $itemdata_prv_bill_qty.' </td>';
  $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
  $html .='</tbody>';
  $html .= '</tr>';

               
 
  //dd($TotalQuantity);
//dd($totalQty+$itemdata_prv_bill_qty);
  $html .= '<tr>';
                  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity: </td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
                  $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
                  $html .='</tbody>';
                  $html .= '</tr>';



}



}
                }

// 3 table end
// 3 table end

$nordata=DB::table('embs')->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $lastRecordEntry->Rec_date)->count();
$steeldata=DB::table('stlmeas')->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $lastRecordEntry->Rec_date)->count();

if($nordata > 0 || $steeldata > 0)
{
$this->lastupdatedRecordData[$itemdata->b_item_id] = [
    'Record_Entry_No' => $recno,
    't_item_no' => $itemdata->t_item_no, // Include t_item_no for comparison
    'Total_Uptodate_Quantity' => $TotalQuantity,
    'b_item_id' => $itemdata->b_item_id, // Include b_item_id
];

}



        
        
    }

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</tr>';
}


$html .= '</tbody>';
$html .= '</table>';





 // Priyanka Edits..............................................................................................................

 $billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
 $eecheckdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('ee_check' , 1)->get();
    $imagePath3 = public_path('Uploads/signature/' . $sign3->sign);
 
    $imageData3 = base64_encode(file_get_contents($imagePath3));
    $imageSrc3 = 'data:image/jpeg;base64,' . $imageData3;

 if ($eecheckdata->isNotEmpty())
 {
 

 $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
 $html .= '<thead>';
 $html .= '<tr>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 100%;" colspan="3"><h4>Executive Engineer Checking:</h4></th>';
 $html .= '</tr>';
 $html .= '</thead>';
 
 $html .= '<thead>';
 $html .= '<tr>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Item No</th>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 50%;">Item Description</th>';
 $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 40%;">Measurements</th>';
 $html .= '</tr>';
 $html .= '</thead>';
 
 $html .= '<tbody>';
 
 
 $checked_mead_amt=0;
 $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
 
 $b_item_amt= DB::table('bills')
 ->where('t_bill_id', $tbillid)
 ->value('bill_amt');
 //dd($billitemdata);
 
 foreach($billitemdata as $itemdata)
 {
     //dd($itemdata);
     $bitemId=$itemdata->b_item_id;
     //dd($bitemId);
     //  $measnormaldata=DB::table('embs')->where('ee_check',1)->get();
    //dd($measnormaldata);
     $meassr=DB::table('embs')->select('sr_no')->where('b_item_id' , $bitemId)->where('ee_check',1)->get();
    //dd($meassr);
      if (!$meassr->isEmpty() ) {
         // if ($measnormaldata ) {
         //dd($measnormaldata->sr_no);
 
         $html .= '<tr>';

          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;"> ' . $itemdata->exs_nm . '</td>';
 
 
 
 
         // $html .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 30%;">' .
         // $meassr .'</td>';
 
         $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">';
 
         $numericValues = Str::of($meassr)
             ->matchAll('/\d+/')
             ->map(function ($match) {
                 return (int)$match;
             })
             ->toArray();
 
         // Check if there are numeric values
         if (!empty($numericValues)) {
             $html .= '<br> ' . implode(', ', $numericValues);
 
 
         }
 
         // Close the table cell
         $html .= '</td>';
 
 
 $html .= '</tr>';
 
 // Now you can use $html as needed, for example, output it in your view or send it as a response.
 
         //
 
             // dd($meassr[]);
 
 
         preg_match_all('/\d+/', $meassr, $numeric_values);
 
         // Convert the extracted numeric values to a comma-separated string
         $comma_separated_values = implode(',', $numeric_values[0]);
         // dd($numeric_values);
 
      }
 
 
     //  $measid=$itemdata->meas_id;
     //     //dd($measid);
 
      $qty = DB::table('embs')
          ->where('t_bill_id', $tbillid)
          ->where('ee_check', 1)
          ->value('qty');
      //dd($qty);
 
      $bill_rt = DB::table('bil_item')
          ->where('t_bill_id', $tbillid)
          ->value('bill_rt');
      //dd($bill_rt);
 
      $meas_amt=$bill_rt * $qty;
     //  dd($meas_amt);
      $checked_mead_amt=$checked_mead_amt+$meas_amt;
     //  //dd($checked_mead_amt);
      $result[]=$checked_mead_amt;
     //dd($result);
      // dd($checked_mead_amt);
      //dd($bitemid,$measid,$qty,$bill_rt,$meas_amt);
 
 }

 // dd($checked_mead_amt);
 
 $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
 //dd($Checked_Percentage);
 //Format the result to have only three digits after the decimal point
 $Checked_Percentage = number_format($Checked_Percentage1, 2);
 
 $checked_meas_amt = number_format($checked_mead_amt, 2);
 // dd($Checked_Percentage);
 
 //Image........
 
     //dd($workid);
     // Construct the full file path
     //dd($sign3);
    // dd($si);
      
 
 

    
    $html .= '<tfoot>';
    $html .= '<tr>';
    $html .= '<td colspan="3" style="text-align: center;">';
    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%; text-align: center;"> Checked measurements  ' . $embsection2->EEChk_percentage . '% . (Value Rs .' . $embsection2->EEChk_Amt . ')</th>';
    $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%;">';
    $html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';

    $html .= '<div style="line-height: 1; margin: 0;">';
    $html .= '<p style="line-height: 4; margin: 0;"></p>';
    $html .= '<p style="line-height: 4; margin: 0;"></p>';
    $html .= '</div>';

    $html .= '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tfoot>';
    
    $html .= '</table>';
}

    $agencyid=DB::table('workmasters')->where('Work_Id' , $workid)->value('Agency_Id');
 //dd($agencyid);
$agencydata=DB::table('agencies')->where('id' , $agencyid)->first();
 //dd($agencydata);
$agencyceck=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
$originalDate = $agencyceck->Agency_Check_Date;
$newDate = date("d-m-Y", strtotime($originalDate));
//dd($newDate);
if($agencyceck->Agency_Check == '1')
{

$imagePath4 = public_path('Uploads/signature/' . $agencydata->agencysign);
 
    $imageData4 = base64_encode(file_get_contents($imagePath4));
    $imageSrc4 = 'data:image/jpeg;base64,' . $imageData4;
  

    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
    $html .= '<tbody>';
       $html .= '<tr>';
$html .= '<td colspan="2" style="text-align: right;">';
$html .= '<strong>I have Checked all the measurements and I accept the measurements</strong>';
$html .= '</td>';
$html .= '</tr>';
     $html .= '<tr>';
     $html .= '<th style=" padding: 5px;  width: 50%; text-align: center;">';
     $html .= '</th>';

     $html .= '<th>';

     $html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc4 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
     $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $agencydata->agency_nm . ' , '. $agencydata->Agency_Pl .'</strong></div>';
     $html .= '</div>';
     $html .= '</th>'; // First cell for signature details
         $html .= '</tr>';
        $html .= '</tbody></table>';
    
    
    
    
    
    
    
    
    
}

$recdata = $this->latestRecordData;

$lastrecentrydata = $this->lastupdatedRecordData;
//dd($lastrecentrydata);
$data=$this->abstractpdfdata($tbillid , $recdata , $lastrecentrydata);
//dd($data);

$html .= $data;



 $sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
//   dd($sammarydata);
  $C_netAmt= $sammarydata->c_netamt;
  $chqAmt= $sammarydata->chq_amt;
$commonHelper = new CommonHelper();
$amountInWords = $commonHelper->convertAmountToWords($C_netAmt);
// dd($amountInWords);
  
  if($sammarydata->mb_status > 10)
  {
    // dd('ok');

   $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
    $html .= '<tbody>';
       $html .= '<tr>';
$html .= '<td colspan="2" style="text-align: right;">';
$html .= '<div style="line-height: 1; margin: 0;"><strong>Passed for Rs.'.$C_netAmt.' (' . $amountInWords . ')</strong></div>';
$html .= '</td>';
$html .= '</tr>';
     $html .= '<tr>';
     $html .= '<th style=" padding: 5px;  width: 50%; text-align: center;">';
     $html .= '</th>';

     $html .= '<th>';

     $html .= '<div style="line-height: 1; width: 50%; margin: 0;"><strong></div>';

    $html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';
     $html .= '</div>';
     $html .= '</th>'; // First cell for signature details
         $html .= '</tr>';
        $html .= '</tbody></table>';; 

    $commonHelperDeduction = new CommonHelper();
    // Call the function using the instance
    $htmlDeduction = $commonHelperDeduction->DeductionSummaryDetails($tbillid);    
    $html .= $htmlDeduction;

    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-top: 20px;">';
    $html .= '<tbody>';
    $html .= '<td style="padding: 8px; width: 50%; text-align: center; line-height: 0;"></td>';

    $html .= '<td style="padding: 8px; width: 50%; text-align: center; line-height: 0;">';
    $html .= '<div style="line-height: 3; margin: 0;"></div>';
    $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
    $html .= '<div style="margin: 0; padding-top: 50px;"><strong>C.A & F.O</strong></div>'; // Adjusted padding-top
    $html .= '<div style="margin: 0;"><strong>' . $division . '</strong></div>';
    $html .= '</div>';
    $html .= '</td>'; // First cell for signature details
         $html .= '</tbody>'; 

    $html .= '</table>'; 

  }



  $mpdf = new Mpdf();
  $mpdf->autoScriptToLang = true;
  $mpdf->autoLangToFont = true;
  //print_r($chunks)
 
  //$mpdf->SetFont('MarathiFont');
  ///dd($chunks);
 // Write HTML chunks iteratively
 //foreach ($chunks as $chunk) {
    
     $mpdf->WriteHTML('<h1>hello world</h1>');
 //}
 // Output PDF as download
 $mpdf->Output('Subdivisionchecklist-' . $tbillid . '.pdf', 'D');
 

// //main table close
// //dd($html);
// $pdf = new Dompdf();

// // Read the image file and convert it to base64
// //$imagePath = public_path('images/sign.jpg');
// // $imageData = base64_encode(file_get_contents($imagePath));
// //
// //$imageSrc = 'data:image/jpeg;base64,' . $imageData;


// // Image path using the asset helper function
// $pdf->loadHtml($html);
// //$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
// $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

// // (Optional) Set options for the PDF rendering
// $options = new Options();
// $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
// $pdf->setOptions($options);

// // $startpages=0;
// // $totalPages=0;
// // $Totalpages=0;
// // if($embsection2->t_bill_No == 1)
// // {
// //     $startpages = 1;
// //  $html = str_replace('{START_PAGES}', $startpages, $html);
// //  //dd($startpages);


// // }
// // else{
// //     $startpages = $embsection2->pg_from;
// //     $html = str_replace('{START_PAGES}', $startpages, $html);
   
// //     //dd($totalPages);
// // //$Totalpages=$startpages+$totalPages;
    
// // }
// //dd($startpages);
// $pdf->loadHtml($html);


//  $pdf->render();
// // if($embsection2->t_bill_No == 1)
// // {
// //     $totalPages = $pdf->getCanvas()->get_page_count();
// //     $Totalpages = $totalPages;
// // }
// // else
// // {
// //     $totalPages = $pdf->getCanvas()->get_page_count();
// //     $Totalpages = $totalPages+$startpages;
// // }

// // $html .= str_replace('{TOTAL_PAGES}', $Totalpages, $html);

// // DB::table('bills')->where('t_bill_Id' , $tbillid)->update(['pg_upto' => $Totalpages]);
// $totalPages = $pdf->getCanvas()->get_page_count();
// $font = $pdf->getFontMetrics()->getFont("Arial Unicode MS");
// $pdf->getCanvas()->page_text(510, 10, "Page: {PAGE_NUM}  of  $totalPages", $font, 12, array(0, 0, 0));
// //     }
// // $totalPages = $pdf->getCanvas()->get_page_count();



// // // Add page numbers manually to each page
// // for ($pageNumber = 2; $pageNumber <= $totalPages; $pageNumber++) {
// //     // Go to the specific page
// //     //$pdf->getCanvas()->set_page($pageNumber);

// //     // Set position and text for page number
// //     $x = 520; // X-coordinate
// //     $y = 10; // Y-coordinate
// //     $text = "Page: $pageNumber of $totalPages"; // Text to display
// //     $font = $pdf->getFontMetrics()->getFont("helvetica", "regular"); // Font

// //     // Add the page number text to the current page
// //     $pdf->getCanvas()->text($x, $y, $text, $font, 10, array(0, 0, 0));

// //     // If it's not the last page, add the placeholder for the next page number
// //     if ($pageNumber !== $totalPages) {
// //         $pdf->getCanvas()->page_text(520, 10, "Page: {PAGE_NUM}", $font, 10, array(0, 0, 0));
// //     }
// // }// Set the encoding (UTF-8 in this example)

// Output the generated PDF (inline or download)
return $pdf->stream('MB-' . $tbillid . '-pdf.pdf');
}



public function itemmeasdatapdf($tbillid , $recdate)
{
//dd($tbillid , $recdate);
            $html ='';
            
            $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
$workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');



    //dd($billitemdata);
   foreach($billitemdata as $itemdata)
   {

    
    $recno=DB::table('recordms')->where('t_bill_id' , $tbillid)->where('Rec_date' , $recdate)->value('Record_Entry_No');

    $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

            $bitemId=$itemdata->b_item_id;
        //dd($bitemId);
        $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $recdate)->get();
        $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $recdate)->get();
        //dd($meassteeldata);
        //meas data check
        if (!$measnormaldata->isEmpty() || !$meassteeldata->isEmpty()) {



                    // 2 table
            // Create a table inside the main table cell
            $html .= '<tr>';

            $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;" width="10%" >Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th>';
            $html .= '<th colspan="7" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;" width="90%"> ' . $itemdata->item_desc . '</th>';
            // Add more table headers as needed
            $html .= '</tr>';
        
        // 2 table end

        // 3 rd table


            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
        //dd($itemid);
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])) 
            {
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->get();
                
                $bill_rc_data = DB::table('bill_rcc_mbr')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->get();

                 //dd($stldata , $bill_rc_data);

              

              



                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


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

        
                $sums = array_fill_keys($ldiamColumns, 0);

                foreach ($stldata as $row) {
                     foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                     }
                }//dd($stldata);


                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemId) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemId);
                })
                ->get();
//dd($bill_member);

            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();


            $membersWithData = [];
foreach ($bill_member as $index => $member) {
    $rcmbrid = $member->rc_mbr_id;
    $memberdata = DB::table('stlmeas')->where('rc_mbr_id', $rcmbrid)->where('date_meas', $recdate)->get();
    if (!$memberdata->isEmpty()) {
        $membersWithData[] = $rcmbrid;
    }
}

// Get the last rc_mbr_id that has memberdata
$lastMemberRcmbrid = end($membersWithData);
//dd($bill_member);
// bill members
        foreach ($bill_member as $index => $member) {
                //dd($member);
                    $rcmbrid=$member->rc_mbr_id;
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('b_item_id', $bitemId)->where('date_meas' , $recdate)->get();
                //dd($memberdata);

            if ( !$memberdata->isEmpty()) {


                $html .= '<tr>';
                $html .= '<th colspan="2" style="border: 1px solid black;  background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
                $html .= '<th colspan="3" style="border: 1px solid black;  background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black;  background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
                $html .= '</tr>';
            
            
                $html .= '<tr><td colspan="9">
                <table style="border-collapse: collapse; width: 100%; border: 1px solid black;">
                <thead><tr>
               <th style="border: 1px solid black; background-color: #f2f2f2;">Sr No</th>
               <th style="border: 1px solid black; background-color: #f2f2f2;">Bar Particulars</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">No of Bars</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">Length of Bars</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">6mm</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">8mm</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">10mm</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">12mm</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">16mm</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">20mm</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">25mm</th>
               <th style="border: 1px solid black; background-color: #f2f2f2; ">28mm</th>    
               </tr></thead><tbody>';

      
               
//$steeldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->->where('rc_mbr_id' , $rcmbrid)get();

          //dd($stldata); 
// foreach ($stldata as $bar) {
   // dd($bar->rc_mbr_id , $member->rc_mbr_id);
   //dd($memberdata);
   //inner foreach 
   foreach ($stldata as $bar) {
   if ($bar->rc_mbr_id == $member->rc_mbr_id) {
   // dd($bar->rc_mbr_id , $member->rc_mbr_id);

        // Assuming the bar data is within a property like "bar_data"
        $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));

        $html .= '<tr><td style="border: 1px solid black; padding: 5px; ">'. $bar->bar_sr_no .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->bar_particulars.'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->no_of_bars .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->bar_length .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam6 .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam8 .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam10 .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam12 .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam16 .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam20 .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam25 .'</td>
                     <td style="border: 1px solid black; padding: 5px; ">'. $bar->ldiam28 .'</td></tr>';
    }


   }



    // Total value of measurement
    $embssteeldata = DB::table('embs')->where('b_item_id', $bitemId)->where('measurment_dt' , $recdate)->get();
    //dd($embssteeldata);

            $barlengthl6=0;
            $barlengthl8=0;
            $barlengthl10=0;
            $barlengthl12=0;
            $barlengthl16=0;
            $barlengthl20=0;
            $barlengthl25=0;
            $barlengthl28=0;
            $barlengthl32=0;
            $barlengthl36=0;
            $barlengthl40=0;
            $barlengthl45=0;

       foreach($embssteeldata as $embdata)
       {
        //dd($embdata);
        $particular=$embdata->parti;
        $firstThreeChars = substr($particular, 0, 3);

        // Set $sec_type based on the first 3 characters
        if ($firstThreeChars === "HCR") {
            $sec_type = "HCRM/CRS Bar";
        } else {
            $sec_type = "TMT Bar";
        }
        
        //dd($particular);
        if ($sec_type == "HCRM/CRS Bar") {
            $pattern = '/HCRM\/CRS Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
        } else {
            $pattern = '/TMT Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
        }
        //dd($pattern, $particular, $matches);
        if (preg_match($pattern, $particular, $matches)) {
            // $matches[1] contains the diameter value
            // $matches[3] contains the total length value
            $diameter = $matches[1];
            $totalLength = $matches[3];
    //dd($diameter , $totalLength);

    if ($diameter == '6') {
        $barlengthl6 += $totalLength;
    } 
    if ($diameter == '8') {
        $barlengthl8 += $totalLength;
    } 
    if ($diameter == '10') {
        $barlengthl10 += $totalLength;
    } 
    if ($diameter == '12') {
        $barlengthl12 += $totalLength;
    } 
    if ($diameter == '16') {
        $barlengthl16 += $totalLength;
    } 
    if ($diameter == '20') {
        $barlengthl20 += $totalLength;
    } 
    if ($diameter == '25') {
        $barlengthl25 += $totalLength;
    } 
    if ($diameter == '28') {
        $barlengthl28 += $totalLength;
    } 
    if ($diameter == '32') {
        $barlengthl32 += $totalLength;
    } 
    if ($diameter == '36') {
        $barlengthl36 += $totalLength;
    } 
    if ($diameter == '40') {
        $barlengthl40 += $totalLength;
    } 
    if ($diameter == '45') {
        $barlengthl45 += $totalLength;
    }
            // Output the extracted values
           
        }
       }

          

       if ($rcmbrid == $lastMemberRcmbrid) {
       $html .= '<tr>
       <th colspan="4" style="border: 1px solid black; background-color: #f2f2f2; ">Total</th>
       <th style="border: 1px solid black;  background-color: #f2f2f2;">'. number_format($barlengthl6, 3) .'</th>
       <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl8, 3) .'</th>
       <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl10, 3) .'</th>
       <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl12, 3) .'</th>
       <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl16, 3) .'</th>
       <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl20, 3) .'</th>
       <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl25, 3) .'</th>
       <th style="border: 1px solid black;  background-color: #f2f2f2; ">'. number_format($barlengthl28, 3) .'</th>
    </tr>';

       }
   $html .='</tbody></table></td></tr>';




                }

            }



           



               
             
         
 
      
    }    
   
        //dd($html);

            $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();
            //dd()
            $totalQty = 0; 
                foreach($normaldata as $nordata)
                {

                    // dd($unit);

                            $formula= $nordata->formula;
            
                                $html .= '<tr>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->sr_no . '</td>';
                                $html .= '<td colspan="2" style="border: 1px solid black;  padding:5px; word-wrap: width: 100%; break-word; max-width: 200px;">' . $nordata->parti . '</td>';
                            if($formula)
                            {
                                 
                                $html .= '<td colspan="4" style="border: 1px solid black; padding:5px;">' . $nordata->formula . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $unit . '</td>';

                            

                            }
                            else
                            {
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->number . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->length . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->breadth . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->height . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $unit . '</td>';

                            }
                                $html .= '</tr>';

                           

                  }


            
               
                  $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

                  $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

               //dd($Qtydec);
                  $totalQty=round(DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('notforpayment' , 0)->where('measurment_dt' , $recdate)->sum('qty') ,  $Qtydec);
                  $totalQty=number_format($totalQty ,  3, '.', '');


                  $qtyaspersamerec = round(DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $recdate)
                  ->sum('qty') , $Qtydec);
                  $qtyaspersamerec=number_format($qtyaspersamerec ,  3, '.', '');

                //dd($qtyaspersamerec);

                  $maxdate = DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $recdate)
                  ->max('measurment_dt');

                  $recordentryno=DB::table('recordms')
                  ->where('t_bill_id', $tbillid)
                  ->where('Rec_date', $maxdate)
                  ->value('Record_Entry_No');
     //dd($maxdate);
                  $recqty = 0; // Initialize $recqty
 //dd($qtyaspersamerec);    
    //$
 //$recqty = number_format($qtyaspersamerec + $totalQty, 3);     
 
 $Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
 $recqty = number_format($Recqty ,  3, '.', '');

 $itemno = $itemdata->t_item_no;

 if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
     $itemno .= $itemdata->sub_no;
 }

 $itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);


 $itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');




 $recqty_float = floatval(str_replace(',', '', $recqty));
 $totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;

 $Totalquantityasper = number_format($totalquantityasper, 3, '.', '');

 $TotalQuantity=0;


 if($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0)
 {
 
    $TotalQuantity=$totalQty;
    $html .= '<tr>';
    $html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;  text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $totalQty.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
    $html .= '</tr>';


 }


if($qtyaspersamerec != 0)
{

    $TotalQuantity=number_format($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '');




//dd($qtyaspersamerec , $totalQty);
$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
$html .= '</tr>';
              

                  $html .= '<tr>';
                  $html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;  text-align: right ; font-weight: bold; ">Quantity as per this MB Record Entry No:'.$recordentryno.',  Item No:'.$itemno.'</td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '').' </td>';
                  $html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
                  $html .= '</tr>';



                  $html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $TotalQuantity.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
$html .= '</tr>';
//dd($TotalQuantity);

}





// // $previousBillIds = DB::table('bills')
// //                      ->where('work_id', '=', $workid)
// //                     ->where('t_bill_Id', '<', $tbillid)
// //                     ->pluck('t_bill_Id');

                   
// //                   $prevbillsqty=0;
// //                     foreach($previousBillIds as $prevtbillid)
// //                     {
                       
// //                        $bitemids= DB::table('bil_item')
// //                        ->where('t_bill_id', $prevtbillid)
// //                        ->where('t_item_id', $itemdata->t_item_id)
// //                        ->get('b_item_id');

// //                        foreach($bitemids as $bitemid)
// //                        {
// //                         //dd($bitemid);
// //                         $previtemsqty = DB::table('embs')
// //                         ->where('b_item_id' ,  $bitemid->b_item_id)
// //                         ->where('notforpayment' , 0)
// //                         ->sum('qty');

// //                         $prevbillsqty += $previtemsqty;
// //                        }
                       
// //                     }
// //                     //dd($prevbillsqty);

 if($itemdata->prv_bill_qty != 0 && $qtyaspersamerec == 0)
 {

    $TotalQuantity=number_format($totalQty+$itemdata_prv_bill_qty, 3, '.', '');

//     $previousbillrqty = number_format($prevbillsqty , 3);
$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px; text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px; font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
$html .= '</tr>';
  //dd($itemdata);

  $html .= '<tr>';
  $html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;  text-align: right ; font-weight: bold; ">Quantity as per previous bill: </td>';
  $html .= '<td style="border: 1px solid black; padding: 5px; font-weight: bold;">'. $itemdata_prv_bill_qty.' </td>';
  $html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
  $html .='</tbody>';
  $html .= '</tr>';

               
 
  //dd($TotalQuantity);
//dd($totalQty+$itemdata_prv_bill_qty);
  $html .= '<tr>';
                  $html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity: </td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $TotalQuantity.' </td>';
                  $html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
                  $html .= '</tr>';

                }

// 3 table end
// 3 table end
//dd($TotalQuantity);
$this->latestRecordData[$itemdata->b_item_id] = [
    'Record_Entry_No' => $recno,
    't_item_no' => $itemdata->t_item_no, // Include t_item_no for comparison
    'Total_Uptodate_Quantity' => $TotalQuantity,
    'b_item_id' => $itemdata->b_item_id, // Include b_item_id
];
$_SESSION['latestRecordData'] = $this->latestRecordData;



       }
    

    

    }


   $returnHTML = $html;
   //dd($returnHTML);
return $returnHTML;
}


public function itemmeasdata($tbillid , $recdate)
{

            $html ='';
            
            $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
$workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');



    
   foreach($billitemdata as $itemdata)
   {

    
    $recno=DB::table('recordms')->where('t_bill_id' , $tbillid)->where('Rec_date' , $recdate)->value('Record_Entry_No');

    $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

            $bitemId=$itemdata->b_item_id;
        //dd($bitemId);
        $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $recdate)->get();
        $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $recdate)->get();
        //meas data check
        if (!$measnormaldata->isEmpty() || !$meassteeldata->isEmpty()) {



                    // 2 table
            // Create a table inside the main table cell
            $html .= '<tr>';
            $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th>';
            $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 90%; text-align: justify;"> ' . $itemdata->item_desc . '</th>';
            // Add more table headers as needed
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '</table>';
            $html .= '</tr>';
        
        // 2 table end

        // 3 rd table


            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
        //dd($itemid);
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"])) 
            {
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->get();
                
                $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                // dd($stldata , $bill_rc_data);

              

              



                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


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

        
                $sums = array_fill_keys($ldiamColumns, 0);

                foreach ($stldata as $row) {
                     foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                     }
                }//dd($stldata);


                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemId) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemId);
                })
                ->get();


            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();


        foreach ($bill_member as $index => $member) {
                //dd($member);
                    $rcmbrid=$member->rc_mbr_id;
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $recdate)->get();
                //dd($memberdata);

            if ( !$memberdata->isEmpty()) {


                $html .= '<tr>';
                $html .= '<table style="border-collapse: collapse; width: 100%;"><thead>';
                $html .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
                $html .= '</thead></table>';
                $html .= '</tr>';
            

                $html .= '<tr>
                <table style="border-collapse: collapse; width: 100%; border: 1px solid black;">
               <thead>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 13%; min-width: 13%;">Bar Particulars</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">6mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">8mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">10mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">12mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">16mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">20mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 9%;">25mm</th>
               <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 9%;">28mm</th>    
               </thead><tbody>';
            
                foreach ($stldata as $bar) {

                    if ($bar->rc_mbr_id == $member->rc_mbr_id) {

                    //dd($bar);// Assuming the bar data is within a property like "bar_data"
                    $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
                  
                            
                     $html .=   '<tr><td style="border: 1px solid black; padding: 5px; width: 5%;  min-width: 5%;">'. $bar->bar_sr_no .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 13%; min-width: 13%;">'. $bar->bar_particulars.'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 10%; min-width: 10%;">'. $bar->no_of_bars .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 10%; min-width: 10%;">'. $bar->bar_length .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam6 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam8 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam10 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam12 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam16 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam20 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 9%; min-width: 9%;">'. $bar->ldiam25 .'</td>
                     <td style="border: 1px solid black; padding: 5px; width: 9%; min-width: 9%;">'. $bar->ldiam28 .'</td></tr>';
                          
                     
                              
                        }

                    }
           $html .='</tbody></table> </tr>';
                  
                   


                }
        

            }

            $embssteeldata = DB::table('embs')->where('b_item_id', $bitemId)->where('measurment_dt' , $recdate)->get();
    //dd($embssteeldata);

    $barlengthl6=0;
            $barlengthl8=0;
            $barlengthl10=0;
            $barlengthl12=0;
            $barlengthl16=0;
            $barlengthl20=0;
            $barlengthl25=0;
            $barlengthl28=0;
            $barlengthl32=0;
            $barlengthl36=0;
            $barlengthl40=0;
            $barlengthl45=0;

       foreach($embssteeldata as $embdata)
       {
        $particular=$embdata->parti;
        $firstThreeChars = substr($particular, 0, 3);

        // Set $sec_type based on the first 3 characters
        if ($firstThreeChars === "HCR") {
            $sec_type = "HCRM/CRS Bar";
        } else {
            $sec_type = "TMT Bar";
        }
        
        //dd($particular);
        if ($sec_type == "HCRM/CRS Bar") {
            $pattern = '/HCRM\/CRS Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
        } else {
            $pattern = '/TMT Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
        }
        if (preg_match($pattern, $particular, $matches)) {
            // $matches[1] contains the diameter value
            // $matches[3] contains the total length value
            $diameter = $matches[1];
            $totalLength = $matches[3];
   // dd($diameter , $totalLength);

    if ($diameter == '6') {
        $barlengthl6 += $totalLength;
    } 
    if ($diameter == '8') {
        $barlengthl8 += $totalLength;
    } 
    if ($diameter == '10') {
        $barlengthl10 += $totalLength;
    } 
    if ($diameter == '12') {
        $barlengthl12 += $totalLength;
    } 
    if ($diameter == '16') {
        $barlengthl16 += $totalLength;
    } 
    if ($diameter == '20') {
        $barlengthl20 += $totalLength;
    } 
    if ($diameter == '25') {
        $barlengthl25 += $totalLength;
    } 
    if ($diameter == '28') {
        $barlengthl28 += $totalLength;
    } 
    if ($diameter == '32') {
        $barlengthl32 += $totalLength;
    } 
    if ($diameter == '36') {
        $barlengthl36 += $totalLength;
    } 
    if ($diameter == '40') {
        $barlengthl40 += $totalLength;
    } 
    if ($diameter == '45') {
        $barlengthl45 += $totalLength;
    }
            // Output the extracted values
           
        }
       }

           
       $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; font-size: 13px;">
       <thead>
           <th style="padding: 5px; width: 5%; background-color: #f2f2f2; min-width: 5%;"></th>
           <th style="padding: 5px; background-color: #f2f2f2; width: 13%; min-width: 13%;"></th>
           <th style="padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;"></th>
           <th style="padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Total</th>
           <th style="border: 1px solid black; padding: 5px; width: 7%; background-color: #f2f2f2;">'. number_format($barlengthl6, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl8, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl10, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl12, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl16, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl20, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 7%;">'. number_format($barlengthl25, 3) .'</th>
           <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 7%;">'. number_format($barlengthl28, 3) .'</th>
       </thead>
   </table>';
     
           

    }    
   


            $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();
            $totalQty = 0; 
                foreach($normaldata as $nordata)
                {

                    // dd($unit);

                            $formula= $nordata->formula;
            
                                $html .= '<tr>';
                                $html .= '<table style="border-collapse: collapse; width: 100%;"><tbody>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 5%;">' . $nordata->sr_no . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 30%; word-wrap: break-word; max-width: 200px;">' . $nordata->parti . '</td>';
                            if($formula)
                            {
                                 
                                $html .= '<td colspan="4" style="border: 1px solid black; padding: 5px; width: 40%;">' . $nordata->formula . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 2px; width: 15%;">' . $unit . '</td>';

                            

                            }
                            else
                            {
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->number . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->length . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->breadth . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->height . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 2px; width: 15%;">' . $unit . '</td>';

                            }
                                $html .='</tbody></table>';
                                $html .= '</tr>';

                           

                  }
               
                  $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

                  $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

               //dd($Qtydec);
                  $totalQty=round(DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('notforpayment' , 0)->where('measurment_dt' , $recdate)->sum('qty') ,  $Qtydec);
                  $totalQty=number_format($totalQty ,  3, '.', '');


                  $qtyaspersamerec = round(DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $recdate)
                  ->sum('qty') , $Qtydec);
                  $qtyaspersamerec=number_format($qtyaspersamerec ,  3, '.', '');

                //dd($qtyaspersamerec);

                  $maxdate = DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $recdate)
                  ->max('measurment_dt');

                  $recordentryno=DB::table('recordms')
                  ->where('t_bill_id', $tbillid)
                  ->where('Rec_date', $maxdate)
                  ->value('Record_Entry_No');
     //dd($maxdate);
                  $recqty = 0; // Initialize $recqty
 //dd($qtyaspersamerec);    
    //$
 //$recqty = number_format($qtyaspersamerec + $totalQty, 3);     
 
 $Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
 $recqty = number_format($Recqty ,  3, '.', '');

 $itemno = $itemdata->t_item_no;

 if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
     $itemno .= $itemdata->sub_no;
 }

 $itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);


 $itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');




 $recqty_float = floatval(str_replace(',', '', $recqty));
 $totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;

 $Totalquantityasper = number_format($totalquantityasper, 3, '.', '');

 $TotalQuantity=0;


 if($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0)
 {
 
    $TotalQuantity=$totalQty;
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
    $html .='</tbody>';
    $html .= '</tr>';


 }


if($qtyaspersamerec != 0)
{

    $TotalQuantity=number_format($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '');

//dd($qtyaspersamerec , $totalQty);
$html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';
              

                  $html .= '<tr>';
                  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per this MB Record Entry No:'.$recordentryno.',  Item No:'.$itemno.'</td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '').' </td>';
                  $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
                  $html .='</tbody>';
                  $html .= '</tr>';



                  $html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';
//dd($TotalQuantity);

}





// $previousBillIds = DB::table('bills')
//                      ->where('work_id', '=', $workid)
//                     ->where('t_bill_Id', '<', $tbillid)
//                     ->pluck('t_bill_Id');

                   
//                   $prevbillsqty=0;
//                     foreach($previousBillIds as $prevtbillid)
//                     {
                       
//                        $bitemids= DB::table('bil_item')
//                        ->where('t_bill_id', $prevtbillid)
//                        ->where('t_item_id', $itemdata->t_item_id)
//                        ->get('b_item_id');

//                        foreach($bitemids as $bitemid)
//                        {
//                         //dd($bitemid);
//                         $previtemsqty = DB::table('embs')
//                         ->where('b_item_id' ,  $bitemid->b_item_id)
//                         ->where('notforpayment' , 0)
//                         ->sum('qty');

//                         $prevbillsqty += $previtemsqty;
//                        }
                       
//                     }
//                     //dd($prevbillsqty);

 if($itemdata->prv_bill_qty != 0 && $qtyaspersamerec == 0)
 {

    $TotalQuantity=number_format($totalQty+$itemdata_prv_bill_qty, 3, '.', '');

//     $previousbillrqty = number_format($prevbillsqty , 3);
$html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';
  //dd($itemdata);

  $html .= '<tr>';
  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per previous bill: </td>';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $itemdata_prv_bill_qty.' </td>';
  $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
  $html .='</tbody>';
  $html .= '</tr>';

               
 
  //dd($TotalQuantity);
//dd($totalQty+$itemdata_prv_bill_qty);
  $html .= '<tr>';
                  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity: </td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
                  $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
                  $html .='</tbody>';
                  $html .= '</tr>';

                }

// 3 table end
// 3 table end
//dd($TotalQuantity);
$this->latestRecordData[$itemdata->b_item_id] = [
    'Record_Entry_No' => $recno,
    't_item_no' => $itemdata->t_item_no, // Include t_item_no for comparison
    'Total_Uptodate_Quantity' => $TotalQuantity,
    'b_item_id' => $itemdata->b_item_id, // Include b_item_id
];
$_SESSION['latestRecordData'] = $this->latestRecordData;



        }
    

    

   }


   $returnHTML = $html;
   //dd(  $returnHTML);
return $returnHTML;
}




////Abstract report PDF functions/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////





public function abstractreport(Request $request , $tbillid)
{
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    $abstractreport='';
$recordentrynos=DB::table('recordms')->where('t_bill_id' , $tbillid)->get();


$headercheck='Abstract';
$header=$this->commonheaderview($tbillid , $headercheck);

$abstractreport .=$header;

$data=$this->abstractreportdata($tbillid);

$abstractreport .=$data;


    return view('reports/AbstractReport' ,compact('embsection2' , 'abstractreport'));
   
}



public function abstractreportpdf($tbillid)
{


    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    $abstractreport='';
$recordentrynos=DB::table('recordms')->where('t_bill_id' , $tbillid)->get();


$headercheck='Abstract';
$header=$this->commonheader($tbillid , $headercheck);
//dd($header);

$abstractreport .=$header;


$tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

$billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();


$workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($workid);
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
//dd($workdata);
$jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;
//dd($dyeid);
$sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
$sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
// Construct the full file path
$imagePath = public_path('Uploads/signature/' . $sign->sign);
$imageData = base64_encode(file_get_contents($imagePath));
$imageSrc = 'data:image/jpeg;base64,' . $imageData;

$imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
$imageData2 = base64_encode(file_get_contents($imagePath2));
$imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



$jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
//dd($jedesignation);
$jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

$dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
$dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');




        // // Read the image file and convert it to base64
        // $imagePath = public_path('images/sign.jpg');
        // $imageData = base64_encode(file_get_contents($imagePath));
        // $imageSrc = 'data:image/jpeg;base64,' . $imageData;
        
        // $imagePath2 = public_path('images/sign2.jpg');
        // $imageData2 = base64_encode(file_get_contents($imagePath2));
        // $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;
        
        
// Initialize the report HTML
$abstractreport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
$abstractreport .= '<thead>';
$abstractreport .= '<tr style=" width: 100%;">';
$abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 5%; word-wrap: break-word;">Tender Item No</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Upto Date Quantity</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 24%; word-wrap: break-word;">Description of Item</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Bill/Tender Rate</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Total Upto Date Amount</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Now to be Paid Amount</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 17%; word-wrap: break-word;">Remark</th>';
$abstractreport .= '</tr>';
$abstractreport .= '</thead>';
$abstractreport .= '<tbody>';

// Loop through your data to generate table rows
foreach ($billitems as $itemdata) {
    $bitemId = $itemdata->b_item_id;
    $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->value('item_id');

    if (
        !in_array(substr($itemid, -6), [
            "001992", "003229", "002047", "002048", "004349", "001991",
            "004345", "002566", "004350", "003940", "003941", "004346",
            "004348", "004347"
        ]) && !(substr($itemid, 0, 4) === "TEST")     
    ) {
        // Generate table rows with data
        $abstractreport .= '<tr>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 5%; text-align: center; word-wrap: break-word;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $itemdata->exec_qty . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 5%; text-align: right; word-wrap: break-word;">' . $itemdata->item_unit . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 24%; word-wrap: break-word;">' . $itemdata->exs_nm . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $itemdata->bill_rt . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $itemdata->b_item_amt . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $itemdata->cur_amt . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 17%; text-align: center; word-wrap: break-word;"></td>';
        $abstractreport .= '</tr>';
    }
}


$workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

$abpc = $workdata->A_B_Pc;
//dd($abpc);
$abobelowatper=$workdata->Above_Below;
//dd($abobelowatper);
if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
    // Row will be generated only when abpc is not equal to 0 or 'At Per'
    $abstractreport .= '<tr>';
    $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total Part A Amount</strong></td>';
    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->part_a_amt . '</strong></td>';
    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_part_a_amt . '</strong></td>';
    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; word-wrap: break-word;"></td>';
    $abstractreport .= '</tr>';
}


// dd($workdata);

if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Tender Above bellow Result : '.$workdata->A_B_Pc.' '.$workdata->Above_Below.'</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->a_b_effect . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_abeffect . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 
 
}

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->gst_base . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_gstbase . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>GST Amount '.$tbilldata->gst_rt.'%</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->gst_amt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_gstamt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

 $hasMatchingId =false;
foreach ($billitems as $roylabitem) {
    $bitemid = $roylabitem->b_item_id;
    $itemid = DB::table('bil_item')->where('b_item_id', $bitemid)->value('item_id');

    if (in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])  || (substr($itemid, 0, 4) === "TEST")   ) {
        $hasMatchingId = true;
        // If any ID matches, set the flag to true
        break; // No need to continue checking if we've found a match
    }
}

if($hasMatchingId)
{

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->part_a_gstamt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_part_a_gstamt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 
 
}
//  $abstractreport .= '</tbody>';
// $abstractreport .= '</table>';



if($hasMatchingId)
{

//dd($hasMatchingId);
$abstractreport .= '<tr style=" width: 100%;">';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 5%; word-wrap: break-word;">Tender Item No</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Upto Date Quantity</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 24%; word-wrap: break-word;">Description of Item</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Bill/Tender Rate</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Total Upto Date Amount</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Now to be Paid Amount</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 15%; word-wrap: break-word;">Remark</th>';
$abstractreport .= '</tr>';

}



 foreach($billitems as $roylabitem)
 {
    //dd($itemdata);
    $bitemid=$roylabitem->b_item_id;
            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->get()->value('item_id');
            //dd($itemid);
                 if (
                    in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"]) || (substr($itemid, 0, 4) === "TEST")
                      
                )
                {
                   
                    $abstractreport .= '<tr>'; 
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 3px; width: 5%; text-align:center; word-wrap: break-word;">' . $roylabitem->t_item_no . ' '.$roylabitem->sub_no.'</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->exec_qty . '</td>';
                    $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 5%; text-align: right; word-wrap: break-word;">' . $itemdata->item_unit . '</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 24%; word-wrap: break-word;">' . $roylabitem->exs_nm . '</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->bill_rt . '</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->b_item_amt . '</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->cur_amt . '</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 3px; width: 15%; word-wrap: break-word;"></td>';
                    $abstractreport .= '</tr>';

                
                }
 }         
 

 if ($hasMatchingId) {
   

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->part_b_amt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_part_b_amt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

}

else
{

}
 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Grand Total(effective Part A + Part B)</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->bill_amt_gt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_billamtgt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->bill_amt_ro . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_billamtro . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->net_amt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_netamt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Previously Paid Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->p_net_amt . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

//dd($tbilldata);
 $nowpayamounttotal = number_format($tbilldata->net_amt - $tbilldata->p_net_amt, 2);
 $nowpayamountcurrent = number_format($tbilldata->c_netamt, 2);
 
 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Now to be paid Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $nowpayamounttotal . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>'.$nowpayamountcurrent.'</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 



 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>**Total Deduction</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->p_tot_ded . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->tot_ded . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Recovery</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->p_tot_recovery . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->tot_recovery . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';


 $chequeamttotal=number_format($tbilldata->net_amt-$tbilldata->p_tot_ded , 2);
 $chequeamtcurrent=number_format($tbilldata->c_netamt-$tbilldata->tot_ded , 2);


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Cheque Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $chequeamtcurrent . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

 $abstractreport .= '<tr style="line-height: 0;">';
 $abstractreport .= '<td colspan="4" style="border: 1px solid black; padding: 6px; text-align: center; line-height: 0;">';



 if($embsection2->mb_status >= '3')
 {
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and abstracted by me</strong></div>';

 $abstractreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
 $abstractreport .= '<div style="line-height: 1; margin: 0;">';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
  $abstractreport .= '</div>';
 }
 $abstractreport .= '</td>'; // First cell for signature details
 $abstractreport .= '<td colspan="4" style="border: 1px solid black; padding: 6px;  text-align: center; line-height: 0;">';
 if($embsection2->mb_status >= '4')
 {

 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Checked by me</strong></div>';

 $abstractreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
 $abstractreport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
  $abstractreport .= '</div>';
 }
 $abstractreport .= '</td>'; // First cell for signature details
 
 $abstractreport .= '</tr>';
 
      $abstractreport .= '</tbody></table>'; 


      $abstractreport .= '<div style=" margin-top: 20px; margin-left: 22px; margin-right: 17px;"><h4>**Deduction Details :</h4></div>';

      $abstractreport .= '<table style="border-collapse: collapse; width: 30%; border: 1px solid black; margin-top:20px; margin-left: 22px; margin-right: 17px;">';
      $abstractreport .= '<thead>';
      $abstractreport .= '<tr>';
      $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Deductions</th>';
      $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Percentage</th>';
      $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Amount</th>';
      $abstractreport .= '</tr>';
          // Sub-columns under Excess and Saving headings
      $abstractreport .= '</thead>';
      $abstractreport .= '<tbody>';
 
      $deductiondata=DB::table('billdeds')->where('T_Bill_Id' , $tbillid)->get();
      //dd($deductiondata);
      foreach($deductiondata as $deduction)
      {
      $abstractreport .= '<tr>'; 
      $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>'.$deduction->Ded_Head.'</strong></td>';
      $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>'.$deduction->Ded_pc.'</strong></td>';
      $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>'.$deduction->Ded_Amt.'</strong></td>';
      $abstractreport .= '</tr>';
      }
     // dd($tbilldata);
      $abstractreport .= '<tr>'; 
      $abstractreport .= '<td colspan=2 style="border: 1px solid black; padding: 8px;  text-align:center; word-wrap: break-word;"><strong>Total</strong></td>';
      $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->tot_ded . '</strong></td>';
      $abstractreport .= '</tr>';

      $abstractreport .= '</tbody>';
      $abstractreport .= '</table>';
 
 
//main table close
//dd($html);
$pdf = new Dompdf();

// Read the image file and convert it to base64
//$imagePath = public_path('images/sign.jpg');
// $imageData = base64_encode(file_get_contents($imagePath));
//
//$imageSrc = 'data:image/jpeg;base64,' . $imageData;


// Image path using the asset helper function
$pdf->loadHtml($abstractreport);
//$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
$pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

// (Optional) Set options for the PDF rendering
$options = new Options();
$options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
$pdf->setOptions($options);

$pdf->render();

// Output the generated PDF (inline or download)
return $pdf->stream('Abstract-'.$tbillid.'-pdf.pdf');
}



public function abstractpdfdata($tbillid , $recdata , $lastrecdata)
{


     //dd($recdata);
     $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
     //$sign = auth()->user()->sign; // Read the image file and convert it to base64
     $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($workid);
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
//dd($workdata);
$jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;
//dd($dyeid);
$sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
$sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
// Construct the full file path
$imagePath = public_path('Uploads/signature/' . $sign->sign);
$imageSrc = $imagePath;

$imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
$imageSrc2 = $imagePath2;



$jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
//dd($sign2->designation);
$jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

$dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
$dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

 $convert=new CommonHelper();

  $abstractreport='';

  $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
  $abstractreport .= '<h2 style="text-align: center;">Abstract</h2>';
  
  $abstractreport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
  $abstractreport .= '<thead>';
  $abstractreport .= '<tr>';
  $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 5%; word-wrap: break-word;">Tender Item No</th>';
  $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Upto Date Quantity</th>';
  $abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
  $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 40%; word-wrap: break-word;">Description of Item</th>';
  $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Bill/Tender Rate</th>';
  $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Total Upto Date Amount</th>';
  $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Now to be Paid Amount</th>';
  $abstractreport .= '</tr>';
  $abstractreport .= '</thead>';
  $abstractreport .= '<tbody>';
  
  $billitems = DB::table('bil_item')
  ->where('t_bill_id', $tbillid)
  ->orderBy('t_item_no', 'asc')
  ->get();

// Loop through your data to generate table rows
foreach ($billitems as $itemdata) {
    $bitemId = $itemdata->b_item_id;
    $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->value('item_id');

    if (
        !in_array(substr($itemid, -6), [
            "001992", "003229", "002047", "002048", "004349", "001991",
            "004345", "002566", "004350", "003940", "003941", "004346",
            "004348", "004347"
        ]) && !(substr($itemid, 0, 4) === "TEST")     
    ) {
        // Generate table rows with data
        $abstractreport .= '<tr>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 5%; text-align: center; word-wrap: break-word;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $itemdata->exec_qty . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 5%; text-align: right; word-wrap: break-word;">' . $itemdata->item_unit . '</td>';
        $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 40%; word-wrap: break-word;">' . $itemdata->exs_nm . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $convert->formatIndianRupees($itemdata->bill_rt) . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $convert->formatIndianRupees($itemdata->b_item_amt) . '</td>';
        $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $convert->formatIndianRupees($itemdata->cur_amt) . '</td>';
        // if (isset($lastrecdata[$bitemId])) {
        //     $abstractreport .= '<td style="border: 1px solid black; padding: 3px; width: 15%; word-wrap: break-word; text-align: center;">Bill</td>';
        // } elseif (isset($recdata[$bitemId])) {
        //     $abstractreport .= '<td style="border: 1px solid black; padding: 3px; width: 15%; word-wrap: break-word; text-align: center;">' . $recdata[$bitemId]['Record_Entry_No'] . '</td>';
        // }elseif($itemdata->cur_qty == 0 && $itemdata->prv_bill_qty != 0) {
        //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">Previous bill Qty</td>';
        // } 
        // else {
        //     $abstractreport .= '<td style="border: 1px solid black; padding: 3px; width: 15%; word-wrap: break-word; text-align: center;">Not Executed</td>';
        // }        
        $abstractreport .= '</tr>';
    }
}


$workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

$abpc = $workdata->A_B_Pc;
//dd($abpc);
$abobelowatper=$workdata->Above_Below;
//dd($abobelowatper);
if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
    // Row will be generated only when abpc is not equal to 0 or 'At Per'
    $abstractreport .= '<tr>';
    $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total Part A Amount</strong></td>';
    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->part_a_amt) . '</strong></td>';
    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_part_a_amt) . '</strong></td>';
    // $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; word-wrap: break-word;"></td>';
    $abstractreport .= '</tr>';
}


// dd($workdata);

if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Tender Above bellow Result : '.$workdata->A_B_Pc.' '.$workdata->Above_Below.'</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->a_b_effect) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_abeffect) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 
 
}

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->gst_base) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_gstbase) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>GST Amount '.$tbilldata->gst_rt.'%</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->gst_amt) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_gstamt) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

 $hasMatchingId =false;
foreach ($billitems as $roylabitem) {
    $bitemid = $roylabitem->b_item_id;
    $itemid = DB::table('bil_item')->where('b_item_id', $bitemid)->value('item_id');

    if (in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])  || (substr($itemid, 0, 4) === "TEST")   ) {
        $hasMatchingId = true;
        // If any ID matches, set the flag to true
        break; // No need to continue checking if we've found a match
    }
}

if($hasMatchingId)
{

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->part_a_gstamt) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_part_a_gstamt) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 
 
}
//  $abstractreport .= '</tbody>';
// $abstractreport .= '</table>';



if($hasMatchingId)
{

$abstractreport .= '<tr>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 5%; word-wrap: break-word;">Tender Item No</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Upto Date Quantity</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 40%; word-wrap: break-word;">Description of Item</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Bill/Tender Rate</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Total Upto Date Amount</th>';
$abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Now to be Paid Amount</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 15%; word-wrap: break-word;">Record entry NO</th>';
$abstractreport .= '</tr>';


}



 foreach($billitems as $roylabitem)
 {
    //dd($itemdata);
    $bitemid=$roylabitem->b_item_id;
            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->get()->value('item_id');
            //dd($itemid);
                 if (
                    in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"]) || (substr($itemid, 0, 4) === "TEST")
                      
                )
                {
                   
                    $abstractreport .= '<tr>'; 
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 5%; text-align:center; word-wrap: break-word;">' . $roylabitem->t_item_no . ' '.$roylabitem->sub_no.'</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->exec_qty . '</td>';
                    $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 5%;  text-align: right; word-wrap: break-word;">' . $itemdata->item_unit . '</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 24%; word-wrap: break-word;">' . $roylabitem->exs_nm . '</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $convert->formatIndianRupees($roylabitem->bill_rt) . '</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $convert->formatIndianRupees($roylabitem->b_item_amt) . '</td>';
                    $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $convert->formatIndianRupees($roylabitem->cur_amt) . '</td>';
                    // if (isset($lastrecdata[$bitemid])) {
                    //     $abstractreport .= '<td style="border: 1px solid black; padding: 3px; width: 15%; word-wrap: break-word; text-align: center;">Bill</td>';
                    // } elseif(isset($recdata[$bitemid])) {
                    //     $abstractreport .= '<td style="border: 1px solid black; padding: 3px; width: 15%; word-wrap: break-word; text-align: center;">' . $recdata[$bitemid]['Record_Entry_No'] . '</td>';
                    // } elseif($roylabitem->cur_qty == 0  && $roylabitem->prv_bill_qty != 0) {
                    //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">Previous bill Qty</td>';
                    // } 
                    // else {
                    //     $abstractreport .= '<td style="border: 1px solid black; padding: 3px; width: 15%; word-wrap: break-word; text-align: center;">Not Executed</td>';
                    // }                    
                    $abstractreport .= '</tr>';

                
                }
 }         
 

 if ($hasMatchingId) {
   

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->part_b_amt) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_part_b_amt) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

}

// else
// {

// }
//dd($tbilldata->bill_amt_gt , $tbilldata->c_billamtgt);
 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Grand Total(effective Part A + Part B)</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->bill_amt_gt) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_billamtgt) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->bill_amt_ro) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_billamtro) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->net_amt) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Previously Paid Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_net_amt) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

//dd($tbilldata);
 $nowpayamounttotal = $tbilldata->net_amt - $tbilldata->p_net_amt;
 $nowpayamountcurrent = $tbilldata->c_netamt;
 
 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Now to be paid Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($nowpayamounttotal) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>'.$convert->formatIndianRupees($nowpayamountcurrent).'</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 



 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>**Total Deduction</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_tot_ded) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->tot_ded) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Recovery</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_tot_recovery) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->tot_recovery) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';


 $chequeamttotal=$tbilldata->net_amt-$tbilldata->p_tot_ded;
 $chequeamtcurrent=$tbilldata->c_netamt-$tbilldata->tot_ded;


 $abstractreport .= '<tr>'; 
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Cheque Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($chequeamtcurrent) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>'; 

 $abstractreport .= '<tr style="line-height: 0;">';
 $abstractreport .= '<td colspan="4" style="border: 1px solid black; padding: 6px; text-align: center; line-height: 0;">';



 if($embsection2->mb_status >= '3')
 {
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and abstracted by me</strong></div>';

 $abstractreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
 $abstractreport .= '<div style="line-height: 1; margin: 0;">';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
  $abstractreport .= '</div>';
 }
 $abstractreport .= '</td>'; // First cell for signature details
 $abstractreport .= '<td colspan="3" style="border: 1px solid black; padding: 6px;  text-align: center; line-height: 0;">';
 if($embsection2->mb_status >= '4')
 {

 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Checked by me</strong></div>';

 $abstractreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
 $abstractreport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
  $abstractreport .= '</div>';
 }
 $abstractreport .= '</td>'; // First cell for signature details
 
 $abstractreport .= '</tr>';
 
 $abstractreport .= '</tbody></table>'; 


return $abstractreport;

}

public function abstractreportdata($tbillid , $recdata , $lastrecdata)
{


    //dd($recdata);
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
       //$sign = auth()->user()->sign; // Read the image file and convert it to base64
       $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($workid);
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
//dd($workdata);
$jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;
//dd($dyeid);
$sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
$sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
// Construct the full file path
$imagePath = public_path('Uploads/signature/' . $sign->sign);
$imageData = base64_encode(file_get_contents($imagePath));
$imageSrc = 'data:image/jpeg;base64,' . $imageData;

$imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
$imageData2 = base64_encode(file_get_contents($imagePath2));
$imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;

$convert=new CommonHelper();


$jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
//dd($sign2->designation);
$jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

$dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
$dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');


    $abstractreport='';

    $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    $abstractreport .= '<h2 style="text-align: center;">Abstract</h2>';
    $abstractreport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;  margin-right: 10px;">';
    // $abstractreport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px; ">';
    $abstractreport .= '<thead>';
    $abstractreport .= '<tr>';
    $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Tender Item No</th>';
    $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Upto Date Quantity</th>';
    $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
    $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 40%; word-wrap: break-word;">Description of Item</th>';
    $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Bill/Tender Rate</th>';
    $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width:8%; word-wrap: break-word;">Total Upto Date Amount</th>';
    $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Now to be Paid Amount</th>';
    // $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 21%; word-wrap: break-word;">Record Entry No</th>';
    $abstractreport .= '</tr>';
    $abstractreport .= '</thead>';
    $abstractreport .= '<tbody>';
    
        $billitems = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->orderBy('t_item_no', 'asc')
    ->get();

    
    foreach ($billitems as $itemdata) {
        $bitemId = $itemdata->b_item_id;
        $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->get()->value('item_id');
        if (
            !in_array(substr($itemid, -6), [
                "001992", "003229", "002047", "002048", "004349", "001991",
                "004345", "002566", "004350", "003940", "003941", "004346",
                "004348", "004347"
            ])  && !(substr($itemid, 0, 4) === "TEST")   
        ) {
            $abstractreport .= '<tr>';
            $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: center; word-wrap: break-word;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
            $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 8%; text-align: right; word-wrap: break-word;">' . $itemdata->exec_qty . '</td>';
            $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $itemdata->item_unit . '</td>';
            $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 40%; word-wrap: break-word;">' . $itemdata->exs_nm . '</td>';
            $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 8%; text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($itemdata->bill_rt) . '</td>';
            $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 8%; text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($itemdata->b_item_amt) . '</td>';
            $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 8%; text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($itemdata->cur_amt) . '</td>';
            // if (isset($lastrecdata[$bitemId])) {
            //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">Bill</td>';
            // } elseif (isset($recdata[$bitemId])) {
            //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">' . $recdata[$bitemId]['Record_Entry_No'] . '</td>';
            // } elseif($itemdata->cur_qty == 0  && $itemdata->prv_bill_qty != 0) {
            //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">Previous bill Qty</td>';
            // }
            // else {
            //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">Not Executed</td>';
            // }
                        $abstractreport .= '</tr>';
        }
    }


    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

$abpc = $workdata->A_B_Pc;
$abobelowatper=$workdata->Above_Below;
if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {


    $abstractreport .= '<tr>'; 
    $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 63%; text-align:right; word-wrap: break-word;"><strong>Total Part A Amount</strong></td>';
    $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->part_a_amt) . '</strong></td>';
    $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_part_a_amt) . '</strong></td>';
    // $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 21%; word-wrap: break-word;"></td>';
    $abstractreport .= '</tr>';
    
}

if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {

     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Tender Above bellow Result : '.$workdata->A_B_Pc.' '.$workdata->Above_Below.'</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->a_b_effect) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_abeffect) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

}

     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->gst_base) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_gstbase) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>GST Amount '.$tbilldata->gst_rt.'%</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->gst_amt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_gstamt) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

     $hasMatchingId =false;
     foreach ($billitems as $roylabitem) {
         $bitemid = $roylabitem->b_item_id;
         $itemid = DB::table('bil_item')->where('b_item_id', $bitemid)->value('item_id');
     
         if ( in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"]) || (substr($itemid, 0, 4) === "TEST")   
       )  {
             $hasMatchingId = true;
             // If any ID matches, set the flag to true
             break; // No need to continue checking if we've found a match
         }
     }
     
     if($hasMatchingId)
     {


     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->part_a_gstamt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_part_a_gstamt) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';
     }
     //$abstractreport .= '</tbody></table>'; 





     if($hasMatchingId)
     {
     $abstractreport .= '<tr>';
     $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Tender Item No</th>';
     $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Upto Date Quantity</th>';
     $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
     $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 40%; word-wrap: break-word;">Description of Item</th>';
     $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Bill/Tender Rate</th>';
     $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width:8%; word-wrap: break-word;">Total Upto Date Amount</th>';
     $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Now to be Paid Amount</th>';
    //  $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2;  text-align:center; width: 21%; word-wrap: break-word;">Record Entry No</th>';
     $abstractreport .= '</tr>';


    }





     foreach($billitems as $roylabitem)
     {
        //dd($itemdata);
        $bitemid=$roylabitem->b_item_id;
                $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->get()->value('item_id');
                //dd($itemid);
                     if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566",
                    "004350", "003940", "003941", "004346", "004348", "004347"]) || (substr($itemid, 0, 4) === "TEST")   
                    )
                    {
                        $abstractreport .= '<tr>'; 
                        $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 5%; text-align:center; word-wrap: break-word;">' . $roylabitem->t_item_no . ' '.$roylabitem->sub_no.'</td>';
                        $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->exec_qty . '</td>';
                        $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $itemdata->item_unit . '</td>';
                        $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 40%; word-wrap: break-word;">' . $roylabitem->exs_nm . '</td>';
                        $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;">' . $convert->formatIndianRupees($roylabitem->bill_rt) . '</td>';
                        $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;">' . $convert->formatIndianRupees($roylabitem->b_item_amt) . '</td>';
                        $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;">' . $convert->formatIndianRupees($roylabitem->cur_amt) . '</td>';
                        // if (isset($lastrecdata[$bitemid])) {
                        //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">Bill</td>';
                        // } elseif (isset($recdata[$bitemid])) {
                        //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">' . $recdata[$bitemid]['Record_Entry_No'] . '</td>';
                        // } 
                        // elseif($roylabitem->cur_qty == 0 && $roylabitem->prv_bill_qty != 0) {
                        //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">Previous bill Qty</td>';
                        // }else {
                        //     $abstractreport .= '<td style="border: 1px solid black; padding: 8px; width: 20%; word-wrap: break-word; text-align: center;">Not Executed</td>';
                        // }
                                                $abstractreport .= '</tr>';

                    }
     }         
     
     

     if($hasMatchingId){
     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->part_b_amt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_part_b_amt) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     }
     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Grand Total(effective Part A + Part B)</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->bill_amt_gt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_billamtgt) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->bill_amt_ro) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_billamtro) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->net_amt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Previously Paid Amount</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_net_amt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     $nowpayamounttotal = $tbilldata->net_amt - $tbilldata->p_net_amt;
     $nowpayamountcurrent = $tbilldata->c_netamt;
     
     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Now to be paid Amount</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($nowpayamounttotal) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>'. $convert->formatIndianRupees($nowpayamountcurrent) .'</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

     ///dd($tbilldata->p_tot_ded);


     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>**Total Deduction</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_tot_ded) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->tot_ded) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Recovery</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_tot_recovery) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->tot_recovery) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

     $chequeamttotal=$tbilldata->net_amt-$tbilldata->p_tot_ded;
     $chequeamtcurrent=$tbilldata->c_netamt-$tbilldata->tot_ded;


     $abstractreport .= '<tr>'; 
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Cheque Amount</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($chequeamtcurrent) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

    $abstractreport .= '<tr style="line-height: 0;">';
    $abstractreport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; max-width: 40%; text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '3')
    {
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and abstracted by me</strong></div>';

    $abstractreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $abstractreport .= '<div style="line-height: 1; margin: 0;">';
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
     $abstractreport .= '</div>';
    }
    $abstractreport .= '</td>'; // First cell for signature details
    $abstractreport .= '<td colspan="4" style="border: 1px solid black; padding: 6px;  text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '4')
    {
   
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Checked by me</strong></div>';

    $abstractreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $abstractreport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
     $abstractreport .= '</div>';
    }
       $abstractreport .= '</td>'; // First cell for signature details

    $abstractreport .= '</tr>';

     $abstractreport .= '</tbody></table>'; 

    //  $abstractreport .= '<div style=" margin-top: 20px; margin-left: 22px; margin-right: 17px;"><h4>**Deduction Details :</h4></div>';

    //  $abstractreport .= '<table style="border-collapse: collapse; width: 30%; border: 1px solid black; margin-top:20px; margin-left: 22px; margin-right: 17px;">';
    //  $abstractreport .= '<thead>';
    //  $abstractreport .= '<tr style="background-color: #ffffcc;">';
    //  $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Deductions</th>';
    //  $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Percentage</th>';
    //  $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Amount</th>';
    //  $abstractreport .= '</tr>';
    //      // Sub-columns under Excess and Saving headings
    //  $abstractreport .= '</thead>';
    //  $abstractreport .= '<tbody>';

    //  $deductiondata=DB::table('billdeds')->where('T_Bill_Id' , $tbillid)->get();
    //  //dd($deductiondata);
    //  foreach($deductiondata as $deduction)
    //  {
    //  $abstractreport .= '<tr>'; 
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>'.$deduction->Ded_Head.'</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>'.$deduction->Ded_pc.'</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>'.$deduction->Ded_Amt.'</strong></td>';
    //  $abstractreport .= '</tr>';
    //  }

    //  $abstractreport .= '<tr>'; 
    //  $abstractreport .= '<td colspan=2 style="border: 1px solid black; padding: 8px;  text-align:center; word-wrap: break-word;"><strong>Total</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->tot_ded . '</strong></td>';
    //  $abstractreport .= '</tr>';

    //  $abstractreport .= '</tbody>';
    //  $abstractreport .= '</table>';





    return $abstractreport;
}










////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////





  public function excesssavingreport(Request $request , $tbillid)
  {

    $excessreport = '';
    $headercheck='Excess';
$excessreport=$this->commonheaderview($tbillid , $headercheck);

    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_Id' ,$tbillid)->value('work_id');

        $billitems=DB::table('bil_item')->where('t_bill_id' ,$tbillid)->orderby('t_item_no' , 'asc')->get();



    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    //dd($workid);
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');



//     // Read the image file and convert it to base64
// $imagePath = public_path('images/sign.jpg');
// $imageData = base64_encode(file_get_contents($imagePath));
// $imageSrc = 'data:image/jpeg;base64,' . $imageData;

// $imagePath2 = public_path('images/sign2.jpg');
// $imageData2 = base64_encode(file_get_contents($imagePath2));
// $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;

$convert = new Commonhelper();
    $excessreport .= '<div class="table-responsive">';


    // Creating the table structure
    $excessreport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
    $excessreport .= '<thead>';
    $excessreport .= '<tr>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Sr No</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 15%; word-wrap: break-word;">Item of Work</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 10%; word-wrap: break-word;">Tendered Quantity</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 10%; word-wrap: break-word;">Tendered Rate</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 10%; word-wrap: break-word;">Tendered Item Amount</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 10%; word-wrap: break-word;">Executed Quantity</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 10%; word-wrap: break-word;">Allowed Rate</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 15%; word-wrap: break-word;">Uptodate Amount</th>'; // New column header
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 10%; word-wrap: break-word;" colspan="2">Excess</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 10%; word-wrap: break-word;" colspan="2">Saving</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 15%; word-wrap: break-word;">Remark</th>';
    $excessreport .= '</tr>';
        // Sub-columns under Excess and Saving headings
    $excessreport .= '<tr>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;" colspan="9"></th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;" colspan="1">Quantity</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;" colspan="1">Amount</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;" colspan="1">Quantity</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;" colspan="1">Amount</th>';
    $excessreport .= '</tr>';
    $excessreport .= '</thead>';
    $excessreport .= '<tbody>';

    // Add more rows with data here using a loop or dynamically generated content
    $totalTItemAmt=0;
    $savingAmount=0;
    $totalSavingAmount=0;
    $savingQuantity=0;
    $totalsavingQuantity=0;
    $totalExcessAmount=0;
    $totalexcessQuantity=0;
    //dd($billitems);
    foreach($billitems as $bilitem)
    {

        //dd($bilitem);
        $excessreport .= '<tr>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $bilitem->t_item_no . ' ' . $bilitem->sub_no . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px;">' . $bilitem->exs_nm . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:left;">' . $bilitem->item_unit . '</td>';

        $tnddata=DB::table('tnditems')->join('bil_item', 'tnditems.t_item_id', '=', 'bil_item.t_item_id')->where('tnditems.work_Id', $workid)
        ->where('tnditems.t_item_id', $bilitem->t_item_id)->first();

        // $tnddata=DB::table('tnditems')
        // ->where('tnditems.t_item_id', $bilitem->t_item_id)->first();

       //dd($tnddata->tnd_qty);
       $billdata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
      // dd($billdata);


        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $tnddata->tnd_qty . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($tnddata->tnd_rt) . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($tnddata->t_item_amt) . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $bilitem->exec_qty . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($bilitem->bill_rt) . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($bilitem->b_item_amt) . '</td>';


        $totalTItemAmt += $tnddata->t_item_amt;
        //dd($totalTItemAmt);
        $ResultQuantity = $tnddata->tnd_qty - $bilitem->exec_qty;
        $resultAmount = $tnddata->t_item_amt - $bilitem->b_item_amt;
//dd($ResultQuantity , $resultAmount);
        if ($ResultQuantity > 0)
        {
            $savingQuantity = $ResultQuantity;
            $totalsavingQuantity += $savingQuantity;


        }
        elseif ($ResultQuantity < 0)
        {
            $excessQuantity = -$ResultQuantity;
            $totalexcessQuantity += $excessQuantity;
        }


        if ($resultAmount > 0)
        {
            $savingAmount=$resultAmount;
            $totalSavingAmount += $resultAmount;

        }
        elseif ($resultAmount < 0)
        {
            $excessAmount=-$resultAmount;
            $totalExcessAmount += $excessAmount;
        }


//dd($excessQuantity , $excessAmount);
if ($resultAmount > 0) {
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . number_format($savingQuantity , 3) . '</td>';
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($savingAmount) . '</td>';
} elseif($resultAmount < 0) {
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . number_format($excessQuantity , 3) . '</td>';
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($excessAmount) . '</td>';
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
}
else
{
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
    $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
}
        // if ($ResultQuantity > 0  &&  $resultAmount > 0) {
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $savingQuantity . '</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $savingAmount . '</td>';
        // } elseif($ResultQuantity > 0  &&  $resultAmount < 0) {
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $excessAmount . '</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $savingQuantity . '</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
        // }
        // elseif($ResultQuantity < 0  &&  $resultAmount > 0)
        // {
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $excessQuantity . '</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $savingAmount . '</td>';
        // }
        // elseif($ResultQuantity < 0  &&  $resultAmount < 0)
        // {
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $excessQuantity . '</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $excessAmount . '</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
        //     $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">0</td>';
        // }

        $excessreport .= '<td style="border: 1px solid black; padding: 8px;">' . $bilitem->exsave_Remks . '</td>';
        $excessreport .= '</tr>';

       // dd($bilitem);
    }

    $netEffect = $totalSavingAmount - $totalExcessAmount;

    $excessreport .= '<tr>';
    $excessreport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:right; width: 10%; word-wrap: break-word; font-weight: bold;">TOTAL</td>';
    $excessreport .= '<td colspan="2" style="border: 1px solid black; padding: 8px; text-align:right; width: 8%; font-weight: bold;">' . $convert->formatIndianRupees($totalTItemAmt) . '</td>';
    $excessreport .= '<td colspan="3" style="border: 1px solid black; padding: 8px; text-align:right; width: 8%;"></td>';
    $excessreport .= '<td colspan="2" style="border: 1px solid black; padding: 8px; text-align:right; width: 8%; font-weight: bold;">' . $convert->formatIndianRupees($totalExcessAmount) . '</td>';
    $excessreport .= '<td colspan="2" style="border: 1px solid black; padding: 8px; text-align:right; width: 8%; font-weight: bold;">' . $convert->formatIndianRupees($totalSavingAmount) . '</td>';
    $excessreport .= '<td colspan="3" style="border: 1px solid black; padding: 8px; text-align:right; width: 6%; font-weight: bold;">' . $convert->formatIndianRupees($netEffect) . '</td>';
    $excessreport .= '</tr>';


    $excessreport .= '<tr style="line-height: 0;">';
    $excessreport .= '<td colspan="7" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '3')
    {

    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
    $excessreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $excessreport .= '<div style="line-height: 1; margin: 0;">';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
    $excessreport .= '</div>';
    }
    $excessreport .= '</td>'; // First cell for signature details

    $excessreport .= '<td colspan="7" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
   // $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>At the site of checked by me work </strong></div>';
   if($embsection2->mb_status >= '4')
   {

    $excessreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $excessreport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
    $excessreport .= '</div>';
    $excessreport .= '</td>'; // Second cell for signature details
   }
    $excessreport .= '</tr>';



        $excessreport .= '</tbody>';
    $excessreport .= '</table>';

    $excessreport .= '</div>';


    return view('reports/ExcessSavingStatement' ,compact('excessreport' , 'embsection2'));
   }


public function excessreportpdf(Request $request , $tbillid)
{
    $excessreport = '';
    $headercheck='Excess';
//$excessreport=$this->commonheader($tbillid , $headercheck);

    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_Id' ,$tbillid)->value('work_id');

        $billitems=DB::table('bil_item')->where('t_bill_id' ,$tbillid)->orderby('t_item_no' , 'asc')->get();



    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    //dd($workid);
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');




    $tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
//dd($recordentrynos);

$division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
//dd($tbillid);

     $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
     $billType = CommonHelper::getBillType($embsection2->final_bill);
//dd($formattedTItemNo , $billType);


$agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';


$formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
     $billType = CommonHelper::getBillType($tbilldata->final_bill);
//dd($formattedTItemNo , $billType);

// $tbillid = 12345;
// $workid = 56263546723;

$billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

$paymentInfo = "$tbillid";




$qrCode = QrCode::size(90)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(10)
->generate($paymentInfo);


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);



$excessreport .= '<div style="position: absolute; top: 12%; left: 83%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">

<table style="width: 100%; border-collapse: collapse; margin-left: 22px; margin-right: 17px;">

<tr>
<td  colspan="2" style="padding: 4px; text-align: left;"><h3><strong>' . $division . '</strong></h3></td>
<td  colspan="1" style=" padding: 4px; text-align: center; margin: 0 10px;"><h3><strong>MB NO: ' . $workid . '</strong></h3></td>
<td  style="padding: 4px; text-align: right;"><h3><strong>' . $workdata->Sub_Div . '</strong></h3></td>
</tr>

<tr>
<td colspan="14" style="text-align: center;"><h2><strong>EXCESS SAVING STATEMENT</strong></h2></td>
</tr>


<tr>
<td  colspan="2" style=""></td>
<td  style="padding: 8px; text-align: center;"><h5><strong>Bill No : ' . $formattedTItemNo . ' ' . $billType . '</strong></h5></td>
</tr>



<tr>
<td  style=""></td>
<td  style="padding: 8px; text-align: center;"><h5><strong></strong></h5></td>
</tr>

<tr>
<td style=""><strong>Name of Work:</strong></td>
<td colspan="2">' . $workdata->Work_Nm . '</td>
</tr>

<tr>
<td  style=""><strong>Agency:</strong></td>
<td  style="">' . $workdata->Agency_Nm . '</td>
</tr>';

$excessreport .= '<tr>';
$excessreport .= '<td colspan="3" style="width: 50%;"><strong>Authority:</strong>'.$workdata->Agree_No.'</td>';
if(!empty($agreementDate))
{
$excessreport .= '<td colspan="" style="width: 50%; text-align: right;"><strong>Date:</strong>' . $agreementDate . '</td>';
}
else{
   $excessreport .= '<td colspan="" style="width: 40%;"></td>';

}
$excessreport .= '</tr>';

$workdate=$workdata->Wo_Dt ?? null;
$workorderdt = date('d-m-Y', strtotime($workdate));

$excessreport .= '<tr>';
$excessreport .= '<td colspan="3" style="width: 60%;"><strong>WorkOrder No:</strong>' . $workdata->WO_No . '</td>';
$excessreport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>WorkOrder Date:</strong>' . $workorderdt . '</td>';
$excessreport .= '</tr>';


$normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

$combinedDates = $normalmeas->merge($steelmeas);
$maxDate = $combinedDates->max();
$maxdate = date('d-m-Y', strtotime($maxDate));


if ($tbilldata->final_bill === 1) {
$date = $workdata->actual_complete_date ?? null;
$workcompletiondate = date('d-m-Y', strtotime($date));

$excessreport .= '<tr>';
$excessreport .= '<td colspan="3" style="width: 60%;"><strong>Actual Date of Completion:</strong>' . $workcompletiondate . '</td>';
$excessreport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
$excessreport .= '</tr>';



} else {
$date = $workdata->Stip_Comp_Dt ?? null;
$workcompletiondate = date('d-m-Y', strtotime($date));

$excessreport .= '<tr>';
$excessreport .= '<td colspan="3" style="width: 60%;"><strong>Stipulated Date of Completion:</strong>' . $workcompletiondate . '</td>';
$excessreport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
$excessreport .= '</tr>';


}
$excessreport .= '</table></div>';

$convert=new Commonhelper();

    // Creating the table structure
    $excessreport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
    $excessreport .= '<thead>';
    $excessreport .= '<tr>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 4%;  word-wrap: break-word;">Sr No</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 25%;  word-wrap: break-word;">Item of Work</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 4%;  word-wrap: break-word;">Unit</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 4%;  word-wrap: break-word;">Tendered Quantity</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 5%;  word-wrap: break-word;">Tendered Rate</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 5%;  word-wrap: break-word;">Tendered Item Amount</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 5%;  word-wrap: break-word;">Executed Quantity</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 5%;  word-wrap: break-word;">Allowed Rate</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 5%;  word-wrap: break-word;">Uptodate Amount</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 2%;  word-wrap: break-word;" colspan="2">Excess</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 2%;  word-wrap: break-word;" colspan="2">Saving</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 2px; background-color: #f2f2f2; text-align:center; width: 5%;  word-wrap: break-word;">Remark</th>';
    $excessreport .= '</tr>';
    // Sub-columns under Excess and Saving headings
    $excessreport .= '<tr>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center;" colspan="9"></th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 1%;  word-wrap: break-word;" colspan="1">Quantity</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 1%;  word-wrap: break-word;" colspan="1">Amount</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 1%;  word-wrap: break-word;" colspan="1">Quantity</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 1%;  word-wrap: break-word;" colspan="1">Amount</th>';
    $excessreport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; width: 3%;  word-wrap: break-word;"></th>';
    $excessreport .= '</tr>';
    $excessreport .= '</thead>';
    $excessreport .= '<tbody>';
    // Add more rows with data here using a loop or dynamically generated content
    $totalTItemAmt=0;
    $savingAmount=0;
    $totalSavingAmount=0;
    $savingQuantity=0;
    $totalsavingQuantity=0;
    $totalexcessQuantity=0;
    $totalExcessAmount=0;
    //dd($billitems);
    foreach($billitems as $bilitem)
    {

        //dd($bilitem);

        $tnddata=DB::table('tnditems')->join('bil_item', 'tnditems.t_item_id', '=', 'bil_item.t_item_id')->where('tnditems.work_Id', $workid)
        ->where('tnditems.t_item_id', $bilitem->t_item_id)->first();

       //dd($tnddata);
       $billdata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
      // dd($billdata);




        $totalTItemAmt += $tnddata->t_item_amt;
        //dd($totalTItemAmt);
        $ResultQuantity = $tnddata->tnd_qty - $bilitem->exec_qty;
        $resultAmount = $tnddata->t_item_amt - $bilitem->b_item_amt;

        $excessreport1= '';
        $excessreport2= '';
        $excessreport3= '';
        $excessreport4= '';

        if ($ResultQuantity > 0)
        {
            $savingQuantity = $ResultQuantity;
            $totalsavingQuantity += $savingQuantity;


        }
        elseif ($ResultQuantity < 0)
        {
            $excessQuantity = -$ResultQuantity;
            $totalexcessQuantity += $excessQuantity;
        }


        if ($resultAmount > 0)
        {
            $savingAmount=$resultAmount;
            $totalSavingAmount += $resultAmount;

        }
        elseif ($resultAmount < 0)
        {
            $excessAmount=-$resultAmount;
            $totalExcessAmount += $excessAmount;
        }

        if ($resultAmount > 0) {
            $excessreport1 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%; word-wrap: break-word;">0</td>';
            $excessreport2 .= '<td style="border: 1px solid black; padding: 1px; text-align:right;  width: 1%;  word-wrap: break-word;">0</td>';
            $excessreport3 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">' . number_format($savingQuantity , 3) . '</td>';
            $excessreport4 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">' . $convert->formatIndianRupees($savingAmount) . '</td>';
        } elseif($resultAmount < 0) {

            $excessreport1 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">' . number_format($excessQuantity , 3) . '</td>';
            $excessreport2 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">' . $convert->formatIndianRupees($excessAmount) . '</td>';
            $excessreport3 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';
            $excessreport4 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';
        }
        else
        {
            $excessreport3 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';
            $excessreport4 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';
            $excessreport3 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';
            $excessreport4 .= '<td style="border: 1px solid black; padding: 1px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';

        }

        // if ($ResultQuantity > 0  &&  $resultAmount > 0) {

        //     $excessreport1 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%; word-wrap: break-word;">0</td>';
        //     $excessreport2 .= '<td style="border: 1px solid black; padding: 2px; text-align:right;  width: 1%;  word-wrap: break-word;">0</td>';
        //     $excessreport3 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">' . $savingQuantity . '</td>';
        //     $excessreport4 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">' . $savingAmount . '</td>';

        // } elseif($ResultQuantity > 0  &&  $resultAmount < 0) {
        //     $excessreport1 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';
        //     $excessreport2 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">' . $excessAmount . '</td>';
        //     $excessreport3 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">' . $savingQuantity . '</td>';
        //     $excessreport4 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';

        // }
        // elseif($ResultQuantity < 0  &&  $resultAmount > 0)
        // {
        //     $excessreport1 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">' . $savingQuantity . '</td>';
        //     $excessreport2 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';
        //     $excessreport3 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';
        //     $excessreport4 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">' . $savingAmount . '</td>';

        // }
        // elseif($ResultQuantity < 0  &&  $resultAmount < 0)
        // {
        //     $excessreport1 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">' . $savingQuantity . '</td>';
        //     $excessreport2 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">' . $savingAmount . '</td>';
        //     $excessreport3 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';
        //     $excessreport4 .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 1%;  word-wrap: break-word;">0</td>';

        // }

        $excessreport .= '<tr>';
        $excessreport .= '<td style="border: 1px solid black; padding: 2px;  width: 3%; text-align:right;  word-wrap: break-word;" >' . $bilitem->t_item_no . ' ' . $bilitem->sub_no . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 2px;  width: 7%;  word-wrap: break-word;">' . $bilitem->exs_nm . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 2px; text-align:left;  width: 3%;  word-wrap: break-word;" >' . $bilitem->item_unit . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 2px; text-align:right;  width: 4%;  word-wrap: break-word;">' . $tnddata->tnd_qty . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 4%;  word-wrap: break-word;">' . $convert->formatIndianRupees($tnddata->tnd_rt) . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 4%;  word-wrap: break-word;">' . $convert->formatIndianRupees($tnddata->t_item_amt) . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 4%;  word-wrap: break-word;">' . $bilitem->exec_qty . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 4%;  word-wrap: break-word;">' . $convert->formatIndianRupees($bilitem->bill_rt) . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 2px; text-align:right; width: 4%;  word-wrap: break-word;">' . $convert->formatIndianRupees($bilitem->b_item_amt) . '</td>';

        $excessreport .=$excessreport1;
        $excessreport .=$excessreport2;
        $excessreport .=$excessreport3;
        $excessreport .=$excessreport4;
        $excessreport .= '<td style="border: 1px solid black; padding: 2px;  word-wrap: break-word; width: 5%;">' . $bilitem->exsave_Remks . '</td>';
        $excessreport .= '</tr>';

       // dd($bilitem);
    }

    $netEffect = $totalSavingAmount - $totalExcessAmount;

    $excessreport .= '<tr>';
    $excessreport .= '<td colspan="4" style="border: 1px solid black; padding: 4px; background-color: #f2f2f2; text-align:right; width: 10%; word-wrap: break-word; font-weight: bold;">TOTAL</td>';
    $excessreport .= '<td colspan="2" style="border: 1px solid black; padding: 4px; text-align:right; width: 4%; font-weight: bold;">' . $convert->formatIndianRupees($totalTItemAmt) . '</td>';
    $excessreport .= '<td colspan="3" style="border: 1px solid black; padding: 4px; text-align:right; width: 4%;"></td>';
    $excessreport .= '<td colspan="2" style="border: 1px solid black; padding: 4px; text-align:right; width: 4%; font-weight: bold;">' . $convert->formatIndianRupees($totalExcessAmount) . '</td>';
    $excessreport .= '<td colspan="2" style="border: 1px solid black; padding: 4px; text-align:right; width: 4%; font-weight: bold;">' . $convert->formatIndianRupees($totalSavingAmount) . '</td>';
    $excessreport .= '<td  style="border: 1px solid black; padding: 4px; text-align:right; width: 5%; font-weight: bold;">' . $convert->formatIndianRupees($netEffect) . '</td>';
    $excessreport .= '</tr>';

    $excessreport .= '<tr style="line-height: 0;">';
    $excessreport .= '<td colspan="7" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '3')
    {

    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
    $excessreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $excessreport .= '<div style="line-height: 1; margin: 0;">';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
    $excessreport .= '</div>';
    }
    $excessreport .= '</td>'; // First cell for signature details

    $excessreport .= '<td colspan="7" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
   // $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>At the site of checked by me work </strong></div>';
   if($embsection2->mb_status >= '4')
   {

    $excessreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $excessreport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
    $excessreport .= '</div>';
    $excessreport .= '</td>'; // Second cell for signature details
   }
    $excessreport .= '</tr>';


    $excessreport .= '</tbody>';
    $excessreport .= '</table>';






    $mpdf = new \Mpdf\Mpdf(['orientation' => 'L']); // Set orientation to landscape
    $mpdf->autoScriptToLang = true;
    $mpdf->autoLangToFont = true;
    
    $logo = public_path('photos/zplogo5.jpeg');
    
    // Set watermark image
    $mpdf->SetWatermarkImage($logo);
    
    // Show watermark image
    $mpdf->showWatermarkImage = true;
    
    // Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
    $mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed
    
    
    // Write HTML content to PDF
    $mpdf->WriteHTML($excessreport);
    

    //$mpdf->WriteHTML($html);


// Determine the total number of pages




//dd($startPageNumber);
// Define the starting number for the displayed page numbers
// Calculate the total number of pages to be displayed in the footer



$totalPages = $mpdf->PageNo();


// Add page numbers to each page starting from the specified page number
for ($i = 2; $i <= $totalPages; $i++) {
    // Calculate the displayed page number
    
    // Set the current page for mPDF
    $mpdf->page = $i;
    
    if ($i === 1) {
        // Content centered on the first page
        $mpdf->WriteHTML('<div style="position: absolute; top: 50%; left: 50%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div>');
    } 
    // Write the page number to the PDF
    //$mpdf->WriteHTML('<div style="position: absolute; top: 20px; right: 20px;">Page No ' . $pageNumber . '</div>');
    //$startPageNumber++;

}

    // Determine the total number of pages
    $totalPages = $mpdf->PageNo();
    
    // Output PDF as download
    $mpdf->Output('Excess-' . $tbillid . '.pdf', 'D');





}





/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   public function recoveryreportpdf(Request $request , $tbillid)
   {

    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    //dd($workid);
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    $headercheck='Recovery';
$header=$this->commonheader($tbillid , $headercheck);


$tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
//dd($recordentrynos);

$division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
//dd($tbillid);

     $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
     $billType = CommonHelper::getBillType($embsection2->final_bill);
//dd($formattedTItemNo , $billType);


$agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';


$formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
     $billType = CommonHelper::getBillType($tbilldata->final_bill);
//dd($formattedTItemNo , $billType);

// $tbillid = 12345;
// $workid = 56263546723;

$billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

$paymentInfo = "$tbillid";




$qrCode = QrCode::size(90)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(10)
->generate($paymentInfo);


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);

$RecoveryReport='';


$RecoveryReport .= '<div style="position: absolute; top: 12%; left: 83%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">

<table style="width: 100%; border-collapse: collapse; margin-left: 22px; margin-right: 17px;">

<tr>
<td  colspan="2" style="padding: 4px; text-align: left;"><h3><strong>' . $division . '</strong></h3></td>
<td  colspan="1" style=" padding: 4px; text-align: center; margin: 0 10px;"><h3><strong>MB NO: ' . $workid . '</strong></h3></td>
<td  style="padding: 4px; text-align: right;"><h3><strong>' . $workdata->Sub_Div . '</strong></h3></td>
</tr>

<tr>
<td colspan="14" style="text-align: center;"><h2><strong>RECOVERY STATEMENT</strong></h2></td>
</tr>


<tr>
<td  colspan="2" style=""></td>
<td  style="padding: 8px; text-align: center;"><h5><strong>Bill No : ' . $formattedTItemNo . ' ' . $billType . '</strong></h5></td>
</tr>



<tr>
<td  style=""></td>
<td  style="padding: 8px; text-align: center;"><h5><strong></strong></h5></td>
</tr>

<tr>
<td style=""><strong>Name of Work:</strong></td>
<td colspan="2">' . $workdata->Work_Nm . '</td>
</tr>

<tr>
<td  style=""><strong>Agency:</strong></td>
<td  style="">' . $workdata->Agency_Nm . '</td>
</tr>';

$RecoveryReport .= '<tr>';
$RecoveryReport .= '<td colspan="3" style="width: 50%;"><strong>Authority:</strong>'.$workdata->Agree_No.'</td>';
if(!empty($agreementDate))
{
$RecoveryReport .= '<td colspan="" style="width: 50%; text-align: right;"><strong>Date:</strong>' . $agreementDate . '</td>';
}
else{
   $RecoveryReport .= '<td colspan="" style="width: 40%;"></td>';

}
$RecoveryReport .= '</tr>';

$workdate=$workdata->Wo_Dt ?? null;
$workorderdt = date('d-m-Y', strtotime($workdate));

$RecoveryReport .= '<tr>';
$RecoveryReport .= '<td colspan="3" style="width: 60%;"><strong>WorkOrder No:</strong>' . $workdata->WO_No . '</td>';
$RecoveryReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>WorkOrder Date:</strong>' . $workorderdt . '</td>';
$RecoveryReport .= '</tr>';


$normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

$combinedDates = $normalmeas->merge($steelmeas);
$maxDate = $combinedDates->max();
$maxdate = date('d-m-Y', strtotime($maxDate));


if ($tbilldata->final_bill === 1) {
$date = $workdata->actual_complete_date ?? null;
$workcompletiondate = date('d-m-Y', strtotime($date));

$RecoveryReport .= '<tr>';
$RecoveryReport .= '<td colspan="3" style="width: 60%;"><strong>Actual Date of Completion:</strong>' . $workcompletiondate . '</td>';
$RecoveryReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
$RecoveryReport .= '</tr>';



} else {
$date = $workdata->Stip_Comp_Dt ?? null;
$workcompletiondate = date('d-m-Y', strtotime($date));

$RecoveryReport .= '<tr>';
$RecoveryReport .= '<td colspan="3" style="width: 60%;"><strong>Stipulated Date of Completion:</strong>' . $workcompletiondate . '</td>';
$RecoveryReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
$RecoveryReport .= '</tr>';


}
$RecoveryReport .= '</table></div>';




$convert=new Commonhelper();









    
    // dd($header);
//$RecoveryReport .=$header;
$RecoveryReport .= '<table style="border-collapse: collapse; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
$RecoveryReport .= '<thead>';
$RecoveryReport .= '<tr>';
$RecoveryReport .= '<th rowspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Sr.</th>';
$RecoveryReport .= '<th rowspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Material</th>';
$RecoveryReport .= '<th colspan="3" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">As per Tender (Schedule-A)</th>';
$RecoveryReport .= '<th colspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Up-to-date Issue</th>';
$RecoveryReport .= '<th colspan="2" style="border: 1px solid black; padding: 8px; text-align: center; word-wrap: break-word;">Already Recovered</th>';
$RecoveryReport .= '<th colspan="2"  style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Proposed to be Recovered Now</th>';
$RecoveryReport .= '<th colspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Balance to be Recovered</th>';
$RecoveryReport .= '<th rowspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Remark</th>';
$RecoveryReport .= '</tr>';
$RecoveryReport .= '<tr>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Rate</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
$RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
$RecoveryReport .= '</tr>';
$RecoveryReport .= '</thead>';
$RecoveryReport .= '<tbody>';

$RecoverytbillrelatedData = DB::table('recoveries')
    ->where('t_bill_id', $tbillid)
    ->get();

    // $TotalRecovery=DB::table('bills')
    // ->where('t_bill_id', $tbillid)
    // ->value('tot_recovery') ?? 0;
    // // dd($TotalRecovery);

    $TotalRecovery=0;


foreach ($RecoverytbillrelatedData as $data)
{
    $RecoveryReport .= '<tr>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  height: 60px; text-align: right; word-wrap: break-word;">' . $data->Sr_no . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;">' . $data->Material . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $data->Mat_Qty . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->Mat_Rt) . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->Mat_Amt) . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $data->UptoDt_m_Qty . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->UptoDt_m_Amt) . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $data->pre_m_Qty . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->pre_M_Amt) . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $data->Cur_M_Qty . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->Cur_M_Amt) . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $data->Bal_M_Qty . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->Bal_M_Amt) . '</td>';
    $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $RecoveryReport .= '</tr>';
    
     $TotalRecovery += $data->Cur_M_Amt; // Sum up the current month amount
}

//$TotalRecovery = number_format($TotalRecovery, 2); // Format total recovery amount to two decimal places


$RecoveryReport .= '<tr>';
$RecoveryReport .= '<td colspan="10" style="border: 1px solid black; padding: 8px;  text-align: right; font-weight:bold;"> Total Recovery';
$RecoveryReport .= '</td>';
$RecoveryReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px;  text-align: center; font-weight:bold;">' . $convert->formatIndianRupees($TotalRecovery) . '</td>';
$RecoveryReport .= '<td colspan="3" style="border: 1px solid black; padding: 8px;  text-align: center; font-weight:bold;"></td>';

$RecoveryReport .= '</tr>';


$RecoveryReport .= '<tr style="line-height: 0;">';
$RecoveryReport .= '<td colspan="6" style="border: 1px solid black; padding: 8px;  text-align: center; line-height: 0;">';
//$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
if($embsection2->mb_status >= '3')
{

$RecoveryReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RecoveryReport .= '<div style="line-height: 1; margin: 0;">';
$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
$RecoveryReport .= '</div>';
$RecoveryReport .= '</td>'; // First cell for signature details
}
$RecoveryReport .= '<td colspan="8" style="border: 1px solid black; padding: 8px;  text-align: center; line-height: 0;">';
//$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>At the site of checked by me work</strong></div>';
if($embsection2->mb_status >= '4')
{
$RecoveryReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RecoveryReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
$RecoveryReport .= '</div>';
}
$RecoveryReport .= '</td>'; // First cell for signature details

$RecoveryReport .= '</tr>';

$RecoveryReport .= '</tbody>';

$RecoveryReport .= '</table>';

$mpdf = new \Mpdf\Mpdf(['orientation' => 'L']); // Set orientation to landscape
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;

$logo = public_path('photos/zplogo5.jpeg');

// Set watermark image
$mpdf->SetWatermarkImage($logo);

// Show watermark image
$mpdf->showWatermarkImage = true;

// Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
$mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed


// Write HTML content to PDF
$mpdf->WriteHTML($RecoveryReport);


//$mpdf->WriteHTML($html);


// Determine the total number of pages




//dd($startPageNumber);
// Define the starting number for the displayed page numbers
// Calculate the total number of pages to be displayed in the footer



$totalPages = $mpdf->PageNo();


// Add page numbers to each page starting from the specified page number
for ($i = 2; $i <= $totalPages; $i++) {
// Calculate the displayed page number

// Set the current page for mPDF
$mpdf->page = $i;

if ($i === 1) {
    // Content centered on the first page
    $mpdf->WriteHTML('<div style="position: absolute; top: 50%; left: 50%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div>');
} 
// Write the page number to the PDF
//$mpdf->WriteHTML('<div style="position: absolute; top: 20px; right: 20px;">Page No ' . $pageNumber . '</div>');
//$startPageNumber++;

}

// Determine the total number of pages
$totalPages = $mpdf->PageNo();


// Output the generated PDF (inline or download)
//return $mpdf->stream('Recovery-'.$tbillid.'-pdf.pdf');
$mpdf->Output('Recovery-' . $tbillid . '.pdf', 'D');
   }


public function recoveryreport(Request $request , $tbillid)
{
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    //dd($workid);
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    $headercheck='Recovery';
    $header=$this->commonheaderview($tbillid , $headercheck);
    // dd($header);
       $RecoveryReport='';
    $RecoveryReport .=$header;
    
    
     $convert=new Commonhelper();

        $RecoveryReport .= '<div class="table-responsive">';

    $RecoveryReport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
    $RecoveryReport .= '<thead>';
    $RecoveryReport .= '<tr>';
    $RecoveryReport .= '<th rowspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Sr.</th>';
    $RecoveryReport .= '<th rowspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Material</th>';
    $RecoveryReport .= '<th colspan="3" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">As per Tender (Schedule-A)</th>';
    $RecoveryReport .= '<th colspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Up-to-date Issue</th>';
    $RecoveryReport .= '<th colspan="2" style="border: 1px solid black; padding: 8px; text-align: center; word-wrap: break-word;">Already Recovered</th>';
    $RecoveryReport .= '<th colspan="2"  style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Proposed to be Recovered Now</th>';
    $RecoveryReport .= '<th colspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Balance to be Recovered</th>';
    $RecoveryReport .= '<th rowspan="2" style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Remark</th>';
    $RecoveryReport .= '</tr>';
    $RecoveryReport .= '<tr>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Rate</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Quantity</th>';
    $RecoveryReport .= '<th style="border: 1px solid black; padding: 8px;  text-align: center; word-wrap: break-word;">Amount</th>';
    $RecoveryReport .= '</tr>';
    $RecoveryReport .= '</thead>';
    $RecoveryReport .= '<tbody>';

    $RecoverytbillrelatedData = DB::table('recoveries')
        ->where('t_bill_id', $tbillid)
        ->get();

        // $TotalRecovery=DB::table('bills')
        // ->where('t_bill_id', $tbillid)
        // ->value('tot_recovery') ?? 0;
        // // dd($TotalRecovery);
    $TotalRecovery=0;



    foreach ($RecoverytbillrelatedData as $data) {
        $RecoveryReport .= '<tr>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 2%; height: 60px; text-align: right; word-wrap: break-word;">' . $data->Sr_no . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 30%; text-align: left; word-wrap: break-word;">' . $data->Material . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $data->Mat_Qty . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->Mat_Rt) . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->Mat_Amt) . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $data->UptoDt_m_Qty . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->UptoDt_m_Amt) . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $data->pre_m_Qty . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->pre_M_Amt) . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $data->Cur_M_Qty . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->Cur_M_Amt) . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 3%; text-align: right; word-wrap: break-word;">' . $data->Bal_M_Qty . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 5%; text-align: right; word-wrap: break-word;">' . $convert->formatIndianRupees($data->Bal_M_Amt) . '</td>';
        $RecoveryReport .= '<td style="border: 1px solid black; padding: 8px; width: 27%; text-align: left; word-wrap: break-word;"></td>';
        $RecoveryReport .= '</tr>';
    $TotalRecovery += $data->Cur_M_Amt; // Sum up the current month amount


    }

//$TotalRecovery = number_format($TotalRecovery, 2); // Format total recovery amount to two decimal places

    $RecoveryReport .= '<tr>';
    $RecoveryReport .= '<td colspan="10" style="border: 1px solid black; padding: 8px; max-width: 40%; text-align: right; font-weight:bold;"> Total Recovery';
    $RecoveryReport .= '</td>';
    $RecoveryReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; max-width: 40%; text-align: center; font-weight:bold;">' . $convert->formatIndianRupees($TotalRecovery) . '</td>';
    $RecoveryReport .= '</tr>';




    $RecoveryReport .= '<tr style="line-height: 0;">';
    $RecoveryReport .= '<td colspan="6" style="border: 1px solid black; padding: 8px; max-width: 40%; text-align: center; line-height: 0;">';
  //  $RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
  if($embsection2->mb_status >= '3')
  {

  $RecoveryReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
  $RecoveryReport .= '<div style="line-height: 1; margin: 0;">';
  $RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
  $RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
  $RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
  $RecoveryReport .= '</div>';
  $RecoveryReport .= '</td>'; // First cell for signature details
  }
  $RecoveryReport .= '<td colspan="8" style="border: 1px solid black; padding: 8px; max-width: 60%; text-align: center; line-height: 0;">';
  //$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>At the site of checked by me work</strong></div>';
  if($embsection2->mb_status >= '4')
  {
  $RecoveryReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
  $RecoveryReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
  $RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
  $RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
  $RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
  $RecoveryReport .= '</div>';
  }
  $RecoveryReport .= '</td>'; // First cell for signature details

    $RecoveryReport .= '</tr>';

    $RecoveryReport .= '</tbody>';

    $RecoveryReport .= '</table>';

    $RecoveryReport .= '</div>';



    return view('reports/RecoveryStatement' ,compact( 'embsection2' , 'tbillid','RecoveryReport'));
   }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




// public function billreport(Request $request , $tbillid)
// {
//     $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
//     $BillItemRt=DB::table('bil_item')->where('t_bill_Id' , $tbillid)->select('tnd_rt','bill_rt');

//     $Billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('work_id','bill_amt_gt','bill_amt_ro','net_amt','gst_amt','gst_rt','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
//                                                                         'part_b_amt','gst_base','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',


//                                                                         'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')->first();
//     // dd($Billinfo);
//     $work_id=$Billinfo->work_id;


//     //dd($work_id);
//     $workdata=DB::table('workmasters')->where('Work_Id' , $work_id)->first();
//     $Maxdt=DB::table('embs')->where('t_bill_Id' , $tbillid)->max('measurment_dt');
//     $dates = Carbon::createFromFormat('Y-m-d',  $Maxdt)->format('d/m/Y');
//     //dd($dates);

//     $Agency_Pl=DB::table('agencies')->where('id' , $workdata->Agency_Id)->select('Agency_Pl','agencysign')->first();
//     $JE_nm=DB::table('jemasters')->where('jeid' , $workdata->jeid)->select('name','designation','sign')->first();
//     $DYE_nm=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation','sign')->first();
//     $EE_nm=DB::table('eemasters')->where('eeid' , $workdata->EE_id)->select('name','subname','designation','sign')->first();

//     $imagePath1 = public_path('Uploads/signature/' . $DYE_nm->sign);
//     $imageDatad = base64_encode(file_get_contents($imagePath1));
//     $imageSrcDYE = 'data:image/jpeg;base64,' . $imageDatad;

//     $imagePath2 = public_path('Uploads/signature/' . $EE_nm->sign);
//     $imageDatae = base64_encode(file_get_contents($imagePath2));
//     $imageSrcEE = 'data:image/jpeg;base64,' . $imageDatae;

//     //  dd($Agency_Pl->agencysign);
//      $imagePath3 = public_path('Uploads/signature/' . $Agency_Pl->agencysign);
//      $imageDataa = base64_encode(file_get_contents($imagePath3));
//      $imageSrcAgency = 'data:image/jpeg;base64,' . $imageDataa;
//      //dd($imageSrc2);
//     // $DYE_Design=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();
//     //dd($DYE_nm);  $DYE_nm->designation
//     $headercheck='Bill';
//     $cvno=$Billinfo->cv_no;
//     // dd($cvno);
//     $isFinalBill=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('final_bill','t_bill_No')->first();
//     // dd($isFinalBill);
//     $FirstBill=$isFinalBill->t_bill_No;
//     $FinalBill=$isFinalBill->final_bill;
//     //dd($FirstBill,$FinalBill);
//     // $header=$this->commonheader();
//     $rbbillno=CommonHelper::formatNumbers($FirstBill);
//     $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);
//     // dd($prev_rbbillno);
//     $BillReport= '';

//     if($FirstBill==1 && $FinalBill==1){
//         // dd("iff ok");
//     $BillReport .= '<h5 style="text-align: center; margin-bottom:50px; font-weight:bold; font-size:25px; padding: 8px; word-wrap: break-word;">FORM - 55 : First And Final Bill</h5>';

//     $BillReport .= '<table style=" margin-left: 20px; margin-right:120px;">';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th style="width: 50%; text-align: center;  word-wrap: break-word;">Notes</th>';
//     $BillReport .= '<th  style="padding-left: 200px; width: 50%;word-wrap: break-word;">'.$workdata->Sub_Div.'</th>';
//     $BillReport .= '</tr>';
//     $BillReport .= '</thead>';
//     $BillReport .= '<tbody>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="padding-left: 200px; width: 50%;text-align: justify;>';
//     $BillReport .= '<p style="padding: 8px; width: 50%;>(For Contractors and suppliers :- To be used when a single payment is made for a job or contract, i.e. only on its completion. A single form may be used generally for making first & final payments several works or supplies if they pertain to the same time. A single form may also be used for making first & final payment to several piece-workers or suppliers if they relate to the same work and billed at the same time. In this case column 2 should be subdivided into two parts, the first part for "Name of Contractor / Piece-worker / Supplier: ABC Constructions, Sangli" and the second for "Items of work" etc.) and the space in Remarks column used for obtaining acceptance of the bill & acknowledgments of amount paid to different piece-workers or suppliers.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 50px; width: 50%;>';

//     $BillReport .= '<p style="width: 50%;"> Cash Book Voucher No';
//     $cvno=$Billinfo->cv_no;
//     if ($cvno) {
//         $BillReport .=  "' . $Billinfo->cv_no .'";
//     }
//     if ($Billinfo->cv_dt) {
//         $BillReport .=  "' . $Billinfo->cv_dt .'";
//     }

//     $BillReport .= '</p><p>For</p><p>Name of Contractor  / Piece-worker / Supplier : '.$workdata->Agency_Nm.''.$Agency_Pl->Agency_Pl.'  </p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="padding-left: 150px; text-align: justify;" >';
//     $BillReport .= '<p style="padding-left: 50px; text-align: justify;">1. In the case of payments to suppliers, red ink entry should be made across the page above the entries relating thereto in one of the following forms applicable to the case,</p>';
//     $BillReport .= '<ul style="padding-left: 100px; text-align: justify;" >';
//     $BillReport .= '<li>(i) Stock, No.: B1/HO/1234</li>';
//     $BillReport .= '<li>(ii) Purchase for Stock,</li>';
//     $BillReport .= '<li>(iii) Purchase for Direct issue to work,</li>';
//     $BillReport .= '<li>(iv) Purchase for work issued to contractor on</li>';
//     $BillReport .= '</ul>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 50px;">';
//     $BillReport .= '<p> * Agreement / Rate List / Requisition  </p><p>No.: '.$workdata->Agree_No.'</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="padding-left: 50px; text-align: justify;" >';
//     $BillReport .= '<p style="padding-left: 150px; text-align: justify;" >2. In the case of works, the accounts of which are kept by subheads, the amount relating to all items of work following under the same "sub-head" should be totaled in red ink.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 50px;">';
//     $BillReport .= '<p>Name of work :'. $workdata->Work_Nm . '</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="padding-left: 110px; text-align: justify;" >';
//     $BillReport .= '<p style="padding-left: 90px; text-align: justify;" >3. Payment should be attested by some known person when the payee\'s acknowledgment is given by a mark, seal or thumb impression.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="padding-left: 100px; text-align: justify;" >';
//     $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >4. The person actually making the payment should initial (and date) the column provided for the purpose against each payment.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 50px;">';
//     $BillReport .= '<p><b>Account Classification :-</b> </p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="padding-left: 100px; text-align: justify;" >';
//     $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >Audit / Account Enfacement</p><br><br><br>';
//     $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >Checked</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 50px;">';
//     $BillReport .= '<p>PLAN WORKS</p>';
//     $BillReport .= '<p>NON-PLAN WORKS</p>';
//     $BillReport .= '<ul>';
//     $BillReport .= '<li>Minor Head | ORIGINAL WORKS Communication</li>';
//     $BillReport .= '<li>Head | Repair & Maint (a) Buildings (a)</li>';
//     $BillReport .= '<li>Sub Head or ------------------------------------------------Detailed Head</li>';

//     $BillReport .= '</ul>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="padding-left: 100px; text-align: justify;" >';
//     $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >2. Transactions of roadside materials entered in the statements of receipts, issues, and balances of Road metal.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 50px;">';
//     $BillReport .= '<p>Provisions during the current year         Rs...........</p>';
//     $BillReport .= '<p>Expenditure incurred </p>';
//     $BillReport .= '<p>during the current year        Rs..........</p>';
//     $BillReport .= '</td>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="padding-left: 150px; text-align: justify;" >';
//     $BillReport .= '<p style="display: inline; padding-left: 90px;">Clerk</p><p style="display: inline; padding-left: 320px;">Accountant</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 50px;">';
//     $BillReport .= '<p>Balance available Rs.......</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="padding-left: 100px; text-align: justify;"  >';
//     $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >* Strike out words which are not applicable </p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 50px;" >';
//     $BillReport .= '<p>a) Score out what is not applicable</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';
//     $BillReport .= '</tbody>';
//     $BillReport .= '</table><br><br>';


//     $royaltylab = [ "001991","001992","002048","004349","002047","003229","004346","004347","004348","004350"];

//     $NormalData = DB::table('bil_item')
//     ->select('exec_qty', 'item_unit', 'item_desc', 'bill_rt', 'b_item_amt', 't_bill_id', 'item_id', 't_item_no', 'sub_no')
//     ->where('t_bill_id', $tbillid)
//     ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//     ->get();
//     // dd("Okkkkk");
//     //  dd($NormalData);


//     $DBWorkId=DB::table('bills')
//     ->where('t_bill_Id',$tbillid)
//     ->value('work_id');
//     // dd($DBWorkId);
//     $DBaboveBellow=DB::table('workmasters')
//     ->select('Above_Below','A_B_Pc')
//     ->where('Work_Id',$DBWorkId)
//     ->first();
//     // dd($DBaboveBellow);



//     $FINALBILL =DB::table('bil_item')
//     ->select('exec_qty','item_unit','item_desc','bill_rt','b_item_amt','t_bill_id','item_id','t_item_no','sub_no')
//     ->where('t_bill_id',$tbillid)
//     ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//     ->get();
//     // dd($FINALBILL);


//         $DBbillTablegetData=DB::table('bills')
//         ->select('c_part_a_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
//         'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')
//         ->where('t_bill_Id',$tbillid)
//         ->first();
//         // dd($DBbillTablegetData);
//     // $amountInWords = convertAmountToWords($DBbillTablegetData->c_netamt);
//     $commonHelper = new CommonHelper();
// $amountInWords = $commonHelper->convertAmountToWords($DBbillTablegetData->c_netamt);
//     // $amountInWords=$this->convertAmountToWords($DBbillTablegetData->c_netamt);
//     //  dd($amountInWords);

//     $BillReport .= '<table style=" margin-left: 150px; margin-right:120px;" >';
//     $BillReport .= '<tr>';
//     $BillReport .= '<th style="padding-left:20px; padding: 18px;  ">Name of Work : </th>';
//     $BillReport .= '<th style="padding-left: 20px;padding: 8px;  ">'. $workdata->Work_Nm . '</th>';
//     $BillReport .= '<th  style="padding-left: 40px; text-align: right; ">'.$workdata->Sub_Div.'</th>';
//     $BillReport .= '</tr>';
//     $BillReport .= '<thead>';
//     $BillReport .= '<tbody>';

//     $BillReport .= '<tr>';

//     $BillReport .= '</thead>';




//     foreach ($NormalData as $data)
//     {
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="border: 1px solid black; width: 10px; padding: 8px; width: 1%; height: 60px; text-align: right; word-wrap: break-word;">' . '<div>' . $data->exec_qty . '</div><div>' . $data->item_unit . '</div></td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 30%; text-align: left; word-wrap: break-word;">' . $data->item_desc . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 2%; text-align: right; word-wrap: break-word;">' . $data->bill_rt . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: left; word-wrap: break-word;">' . $data->item_unit . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: right; word-wrap: break-word;">' . $data->b_item_amt . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">  </td>';
//         $BillReport .= '</tr>';
//     }
//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total BItem Amount</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_part_a_amt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Deduct for  '.$DBaboveBellow->Above_Below.' '.$DBaboveBellow->A_B_Pc.'  as per Tender (Except Roy/Lab/Ins Item) </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_abeffect.' </td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> GST Base	</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_gstbase.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> GST Amount 18%</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_gstamt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_part_a_gstamt.' </td>';
//     $BillReport .= '</tr>';



//     foreach ($FINALBILL as $data)
//     {
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="border: 1px solid black; width: 10px; padding: 8px; width: 1%; height: 60px; text-align: right; word-wrap: break-word;">' . '<div>' . $data->exec_qty . '</div><div>' . $data->item_unit . '</div></td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 30%; text-align: left; word-wrap: break-word;">' . $data->item_desc . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 2%; text-align: right; word-wrap: break-word;">' . $data->bill_rt . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: left; word-wrap: break-word;">' . $data->item_unit . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: right; word-wrap: break-word;">' . $data->b_item_amt . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">  </td>';
//         $BillReport .= '</tr>';
//     }
//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total Part B Amount</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_part_b_amt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Grand Total</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_billamtgt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Add for rounding off</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_billamtro.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Final Total	 </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_netamt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Previously Paid Amount From Per Bill </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->p_net_amt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">Now to be paid Amount </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_netamt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=6 style="border: 1px solid black;  padding: 8px; width: 10%; text-align: center; word-wrap: break-word;"> Total value of work done or supplies made  ' .  $DBbillTablegetData->c_netamt.'</td>';
//     $BillReport .= '</tr>';

//     // $amountInWords=$this->convertAmountToWords($DBbillTablegetData->c_netamt);
//     $commonHelper = new CommonHelper();
//     $amountInWords = $commonHelper->convertAmountToWords($DBbillTablegetData->c_netamt);

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=6 style="border: 1px solid black;  padding: 8px; width: 10%; text-align: center; word-wrap: break-word;"> In  Word ' .  $amountInWords.' Nil Only </td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<table style="margin-left: 190px; margin-right:160px;" >';
//     $BillReport .= '<tr>';

//     $BillReport .= '<td colspan=6 style="padding: 8px; text-align:left;word-wrap: break-word;">Measurements recorded by '.$JE_nm->name.' on '.$dates.' in M. Book No '.$tbillid.' checked by '.$DYE_nm->name.'100.00 %. </td>';
//     $BillReport .= '<td  style="padding: 8px;text-align: left;  word-wrap: break-word;">Received Rs. ' .  $DBbillTablegetData->c_netamt.' ' .  $amountInWords.' Nil Only. in final settlement of work.</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan=2 style="  padding: 8px;padding-top:20px;  height: 60px; text-align:left; word-wrap: break-word;">Dated :  /      /     </td>';
//     $BillReport .= '<td colspan=2 style=" padding: 8px; padding-top:20px;   text-align: center; word-wrap: break-word;">Countersigned</td>';
//     $BillReport .= '<td colspan=1  style=" padding: 8px; padding-top:70px;   text-align: right; word-wrap: break-word; padding-left: 190px;"> Witness</td>';
//     $BillReport .= '<td colspan=3  style=" padding: 8px; padding-top:70px;   text-align: center; word-wrap: break-word; padding-right: 160px;"> Stamp</td><br>';

//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan=10 style=" padding: 8px; text-align: center; padding-top:50px; word-wrap: break-word; padding-left: 500px;"> Payees dated signature</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';


//     // $BillReport .= '<td colspan="2" style="width: 200px; height: 60px; text-align:center"><img src="' . $imageSrc  . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br> ';
//     $BillReport .= '<td colspan=1 style=" padding: 8px; height: 60px; padding-top: 40px; text-align: center; word-wrap: break-word;"><img src="' . $imageSrcDYE  . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>'.$DYE_nm->designation.' <br> '.$workdata->Sub_Div.'</td>';
//     $BillReport .= '<td colspan=3 style=" padding: 25px;  text-align: center; padding-top: 40px; word-wrap: break-word;"><img src="' . $imageSrcEE  . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>'.$EE_nm->designation.'  <br>'.$workdata->Div.' </td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan=6 style=" padding: 8px; height: 60px;  text-align: left; word-wrap: break-word;">Pay by cash / cheque Rs.( ) Rupees</td>';
//     $BillReport .= '<td colspan=2 style=" padding: 8px;  text-align: left;  word-wrap: break-word;">Paid by me by cash / vide cheque No.</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan=6 style=" padding: 8px; height: 30px;  text-align: left; word-wrap: break-word;">Dated</td>';
//     $BillReport .= '<td colspan=2 style=" padding: 8px;  text-align: left;  word-wrap: break-word;">Dated</td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan=6 style=" padding: 8px; height: 60px; padding-top: 30px;  text-align: left; word-wrap: break-word;">Officer authorizing payment </td>';
//     $BillReport .= '<td colspan=2 style=" padding: 8px;  text-align: left; padding-top: 30px;  word-wrap: break-word;">Dated Initials of person making the payment</td>';
//     $BillReport .= '</tr>';
//     $BillReport .= '</table>';


//     $BillReport .= '</table>';

//     }
//     else
//     {
//       //dd("Okkkkkk");
//         $BillReport .= '<h5 style="text-align: center; font-weight:bold; font-size:25px; padding: 8px; word-wrap: break-word;">Z. P. FORM - 58 - C </h5>';
//         $BillReport .= '<h1 style="text-align: center; font-size:20px; word-wrap: break-word;">(See Rule 174)</h1>';
//         $BillReport .= '<h1 style="text-align: center; margin-bottom:50px; font-size:20px; word-wrap: break-word;">'.$workdata->Div.'</h1>';

//         $BillReport .= '<table>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<th style="width: 50%; text-align: center;  word-wrap: break-word;">Notes</th>';
//         $BillReport .= '<th  style="padding-left: 200px; width: 50%; word-wrap: break-word;">'.$workdata->Sub_Div.'</th>';
//         $BillReport .= '</tr>';
//         $BillReport .= '<tbody>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td>';
//         $BillReport .= '<p style="padding: 8px; width: 100%;">(For Contractors and suppliers. This form provides only for payment for work or suppliesctually measured.)<br> 1. The full name of the work as given in the estimate should be entered against the line "Name of work" except in the case of bills for "Stock" materials.<br></br>
//                         2. The purpose of supply applicable to thecase should be filled in and rest scored out.</br>3. If the outlay on the work is recorded by sub-heads, the total for each sub-head should be shown on Column 5 and against this total, there should be an entry in Column 6 also. In no other case should any entries be made in Column 6.</br></br></p>';
//         $BillReport .= '</td>';

//         $BillReport .= '<td>';
//         $BillReport .= '<p style="padding-left: 50px;">==============================================================================</p>';
//         $BillReport .= '<p style="width: 100%; text-align: justify; padding-left: 50px;"><b> RUNNING ACCOUNT BILL - C </b> </p>';
//         $BillReport .= '<p style="width: 100%; text-align: justify; padding-left: 50px;"> Cash Book Voucher No:';
//         $cvno=$Billinfo->cv_no;
//         if ($cvno) {
//             $BillReport .=  '' . $Billinfo->cv_no .'';
//         }
//         $BillReport .= '</p><p style="width: 100%; text-align: justify; padding-left: 50px;">====================================================================</p >';
//         $BillReport .= '<p style="width: 100%; text-align: justify; padding-left: 50px;">Name of Contractor  / Piece-worker / Supplier : '.$workdata->Agency_Nm.','.$Agency_Pl->Agency_Pl.'  </p>';
//         $BillReport .= '</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td>';
//         $BillReport .= '<p style="width: 50%; padding-left: 90px;  text-align: justify;">Memorandum of Payments</p>';
//         $BillReport .= '<p style="text-align: justify;">4. The figures against (k) should be test to see that it agrees with the total if Items 4&55. If the net amount is to be paid is lessthan Rs 10 and it cannot be included in a cheque, the payment should be made in cash, thepay order being altered suitably any alterati-on attested by dated initials.</br></br>6. The payes acknowledgement should be forthe gross amount paid as per Item 5, i.e.a+b+c</br></br> 7. Payment should be attested by some known person when the payes acknowledgement is given by a mark seal or thumb impression.</br></br> 8. The column "Figures for Works Abstract" is not required in the case of bills of supplies.</br>
//         =============================================================================</p>';
//         $BillReport .= '<td style="padding-left: 50px;">';
//         $BillReport .= '<p>Name of work :</p>';
//         $BillReport .= '&nbsp; &nbsp; &nbsp; &nbsp;'. $workdata->Work_Nm . '</p>';
//         $BillReport .= '<p style="text-align: justify;">Purpose of Supply :</p>';
//         $BillReport .= '<p style="text-align: justify;"> Serial No of this bill :'.$rbbillno.' R.A. Bill</p>';

//         if($prev_rbbillno===0)
//         {
//             // dd($prev_rbbillno);
//             $BillReport .= '<p style="text-align: justify;"> No and date of last :  - R.A. Bill paid vide bill for this work :  C.V. No : <br></p>';
//         }
//         else{
//             $BillReport .= '<p style="text-align: justify;"> No and date of last :'.$prev_rbbillno.'  R.A. Bill paid vide bill for this work :  C.V. No : <br></p>';
//         }
//         $cvno=$Billinfo->cv_no;
//         if ($cvno) {
//             $BillReport .=  '' . $Billinfo->cv_no .'';
//         }
//         $cvdate=$Billinfo->cv_dt;
//         $BillReport .= '<p style=" text-align: justify;">for</p>';

//         if ($cvdate) {
//             //dd($cvdate);
//             $date1=date_create($cvdate);
//             $formattedDate = $date1->format('d/m/Y');
//             $date=date_create($formattedDate);
//             $dt2=date_format($date,"M/Y");
//             // dd($dt2);
//             $BillReport .= $dt2;
//         }

//         $BillReport .= '<p style=" text-align: justify;" > Reference to agreement : ' . $workdata->Agree_No . '.';
//         $OcommenceDate = date('d/m/Y', strtotime($workdata->Wo_Dt));
//         $dueDate = date('d/m/Y', strtotime($workdata->Stip_Comp_Dt));
//         $BillReport .= '<p style=" text-align: justify;" > Date of order to commence the work : '.$OcommenceDate.'.';
//         $BillReport .= '</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="text-align: top;">Account Classification -  <b>'.$workdata->F_H_Code.'</b>';
//         $BillReport .= '<p style= text-align: justify;">PLAN WORKS/NON-PLAN WORKS <br>Minor Head | ORIGINAL WORKS Communication/Head | Repair & Maint (a) Buildings (a)Sub Head or Detailed Head<br>
//         ==========================================================================</p>';

//         $BillReport .= '</td>';
//         $BillReport .= '<td style="padding-left: 50px;">';
//         $BillReport .= '<p style="text-align: justify;" > Due date of completion of work :'.$dueDate.'<br>Extensions granted, if any, - - - <br>
//                         from time to time with - - -<br>reference to authority - - <br>  Actual date of completion :';
//         if ($workdata->actual_complete_date) {

//             $Act_dt_compli = date('d/m/Y', strtotime($workdata->actual_complete_date));
//             $BillReport .=  "' . $Act_dt_compli .'";
//         }
//         $BillReport .= '</p></td>';
//         $BillReport .= '</tr>';


//         $BillReport .= '<tr>';
//         $BillReport .= '<td style= text-align: justify;">';

//         // $BillReport .= '</0l>';
//         $BillReport .= '</td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style=" text-align: justify;">';
//         $BillReport .= '<p style=" text-align: justify;">Provisions during the current year         Rs...........</p>';
//         $BillReport .= '<p style=" text-align: justify;">Expenditure incurred </p>';
//         $BillReport .= '<p style=" text-align: justify;">during the current year        Rs..........</p>';
//         $BillReport .= '<p style=" text-align: justify;">Balance available Rs.......</p>';
//         $BillReport .= '<p style=" text-align: justify;">a) Score out what is not applicable</p>';
//         $BillReport .= '</td>';

//         $BillReport .= '<td style="padding-left: 50px;">';
//         $BillReport .= '<p>1) Security Deposit to be recovered as per agreement<br> 2) Security Deposit previously recovered <br> 3) Security Deposit to be recovered from this bill<br>4) Balance to be recovered</p>';
//         $BillReport .= '</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '</tbody>';
//         $BillReport .= '</table><br>';
//         //-----------------------------------------------------------------------------------------------------------------------------------------------------------
//         // Next Page---------------------------------------------------------------------------

//         $BillReport .= '<div style="page-break-before: always;"></div>';

//         $BillReport .= '<table class="table table-bordered table-collapse" style="border: 1px solid black; border-collapse: collapse; margin: 0;">';
//         $BillReport .= '<thead>';
//         // $BillReport .= '<br><br><br><table>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<th  colspan="4" style="width: 60%; text-align: left; word-wrap: break-word;">' . $workdata->Div . '</th>';
//         $BillReport .= '<th  colspan="3" style="width: 40%; text-align: right;  word-wrap: break-word;">' . $workdata->Sub_Div . '</th>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<th  colspan="2" style=" width: 60%; text-align: justify; ">Name of Work:  </th>';
//         $BillReport .= '<th  colspan="5" style=" width: 40%; text-align: left; word-wrap: break-word;">'.$workdata->Work_Nm . '</th>';
//         $BillReport .= '</tr>';
//         // $BillReport .= '</table>';


//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 3%; word-wrap: break-word;">Unit</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:justify; width: 8%; word-wrap: break-word;">Quantity executed up-to-date as per Measurement Book</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 55%; word-wrap: break-word;">Item of Work</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Bill<br>----------------<br>tender Rate Rs.</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:justify; width: 8%; word-wrap: break-word;">Payments of Actual up-to-date Rs.</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:justify; width:8%; word-wrap: break-word;">On the basis of measurements Since the previous Bill Rs.</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Remark</td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '</thead>';
//         $BillReport .= '<tbody>';


//         //For Royalty Surcharg Items..........
//         $royaltylab = [ "001991","001992","002048","004349","002047","003229","004346","004347","004348","004350"];

//         $NormalData = DB::table('bil_item')
//         ->where('t_bill_id', $tbillid)
//         ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//         ->get();
//         // dd($NormalData);

//         if($NormalData){
//             $header1=$this->commonforeachview($NormalData,$tbillid,$work_id);
//             //dd($header1);
//         $BillReport .=$header1;

//         $abpc = $workdata->A_B_Pc;
//         $abobelowatper=$workdata->Above_Below;


//         if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
//             $BillReport .= '<tr>';
//             $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Total Part A Amount</strong></td>';
//             $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->part_a_amt . '</strong></td>';
//             $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_part_a_amt . '</strong></td>';
//             $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//             $BillReport .= '</tr>';
//         }

//         if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
//             $BillReport .= '<tr>';
//             $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Tender Above Below Result: ' . $workdata->A_B_Pc . ' ' . $workdata->Above_Below . '</strong></td>';
//             $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_abeffect . '</strong></td>';
//             $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->a_b_effect . '</strong></td>';
//             $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//             $BillReport .= '</tr>';
//         }

//         $BillReport .= '<tr>';
//         $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
//         $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->gst_base . '</strong></td>';
//         $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_gstbase . '</strong></td>';
//         $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>GST Amount ' . $Billinfo->gst_rt . '%</strong></td>';
//         $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->gst_amt . '</strong></td>';
//         $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_gstamt . '</strong></td>';
//         $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//         $BillReport .= '</tr>';
//         }
//         $RoyaltyData = DB::table('bil_item')
//         ->where('t_bill_id', $tbillid)
//         ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//         ->get();

//         //dd($RoyaltyData);
//         if (!$RoyaltyData->isEmpty()) {
//             // dd("Okkk");
//             $header1=$this->commonforeachview($RoyaltyData,$tbillid,$work_id);
//             // dd($header1);
//             $BillReport .=$header1;
//             // $BillReport .= '<table>';
//             // $BillReport .= '<tbody>';

//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->part_b_amt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_part_b_amt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
//             $BillReport .= '</tr>';

//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Grand Total</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->bill_amt_gt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_billamtgt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
//             $BillReport .= '</tr>';

//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->bill_amt_ro . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_billamtro . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
//             $BillReport .= '</tr>';

//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->net_amt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_netamt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
//             $BillReport .= '</tr>';

//             // $c_netamt=$this->convertAmountToWords($Billinfo->c_netamt);
//             $commonHelper = new CommonHelper();
//             $c_netamt = $commonHelper->convertAmountToWords($Billinfo->c_netamt);

//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="border: 1px solid black;  padding: 8px; width:66%; text-align: right; word-wrap: break-word;"> In  Word <strong>'.$c_netamt.' Nil Only </strong></td>';
//             $BillReport .= '<th colspan="1" style="border: 1px solid black;  padding: 8px; width: 8%; text-align: right; word-wrap: break-word;"></th>';
//             $BillReport .= '<th colspan="1" style="border: 1px solid black;  padding: 8px; width: 8%; text-align: right; word-wrap: break-word;"></th>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black;  padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
//             $BillReport .= '</tr>';

//             // $BillReport .= '</tbody>';
//             // $BillReport .= '</table>';
//         }
//         // $BillReport .= '</tbody>';
//         // $BillReport .= '</table>';


//         // $BillReport .= '<table style="border-collapse: collapse; border: none; margin-left: 30px; margin-right: 30px; text-align:center;">';
//         // $BillReport .= '<tbody>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="7" style="padding: 8px; background-color: #f2f2f2; text-align:left; width: 55%; word-wrap: break-word;"> ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br>
//                         The measurements made by '.$JE_nm->name.' , '.$JE_nm->designation.' on '.$dates.' and are recorded at
//                         Measurement Book No '.$work_id.' No advance payment has been made previously
//                         without detailed measurements.</td>';
//         $BillReport .= '</tr>';
//         // $BillReport .= '</tbody>';
//         // $BillReport .= '</table>';


//         // $BillReport .= '<table style="border-collapse: collapse; border: none; margin-left: 80%; margin-right: 30px; text-align:center;">';
//         // $BillReport .= '<tr >';



//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="5" style="padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';

//         $BillReport .= '<td colspan="2" style="width: 200px; height: 60px; text-align:center"> <img src="' . $imageSrcDYE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;">';

//         $BillReport .= '<br>'.$DYE_nm->designation.'';

//         $BillReport .= '<br>'.$workdata->Sub_Div.'';

//         $BillReport .= '<br> * Dated Signature of Officer preparing bill';
//         $BillReport .= '</tr>';
//         // $BillReport .= '</tbody>';
//         // $BillReport .= '</table>';

//         // $BillReport .= '<table>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="7" style="text-align: center;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="5" style="border-collapse: collapse; text-align:left;">  Dated : </td>';
//         $BillReport .= '<td colspan="2" style="border-collapse: collapse; text-align:center;">  Countersigned  </td>';
//         // $BillReport .= '<td colspan="1" style="border-collapse: collapse; text-align:left;"> </td>';
//         $BillReport .= '</tr><br><br><br>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="5" style="border-collapse: collapse; text-align:bottom;"> Dated Signature of the Contractor </td>';
//         $BillReport .= '<td colspan="2" style="height: 60px; text-align:center;"> <img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>'.$EE_nm->designation.'<br>  '.$workdata->Div.'</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="7" style="font-size=1px;"> The second signature is only necessary when the officer who prepares the bill is not the officer who makes the payment. </td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '</tbody>';

//         $BillReport .= '</table>';

// //Last Page-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

//     $BillReport .= '<div style="page-break-before: always;"></div>';
//     $BillReport .= '</div><h6 style="text-align: center; font-weight:bold;  word-wrap: break-word;">III - Memorandum of Payments </h6>';
//     $BillReport .= '<h2 style="text-align: center; font-weight:bold; word-wrap: break-word;">=========================================================</h2>';

//     $BillReport .= '<p style="text-align: left">1. Total Value of work done, as per Account-I, Column 5, Entry (A)</p>';
//     $BillReport .= '<p style="text-align: left;">2. Deduct Amount withheld :</p>';

//     $BillReport .= '<table>';
//     $BillReport .= '<tbody>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;">----------------<br>Figures for<br> Work abstract<br>-----------------</td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">(a) From previous bill as per last Running Account Bill</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: right;">----------------<br>Rs. &nbsp &nbsp &nbsp Ps.<br>----------------</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">(b) From this bill</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: right;">----------------</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;"> 3. Balance, i.e. "Up-to-date" payments (Items 1 - 2)</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;">(K)</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; word-wrap: break-word;"> Total amount of payments already made as per entry<br>
//     of last Running Account Bill No.<br>
//     forwarded with accounts for</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;">(K)</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">5. Payments now to be made as detailed below :-(K)</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;">--------</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;"> (a) By recovery of amounts creditable to this work -(a)<br>
//     Value of stock supplied as detailed<br>
//     in the ledger in (a)</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';
//     $BillReport .= '</tbody>';
//     $BillReport .= '</table>';

//     $BillReport .= '<table>';
//     $BillReport .= '<tbody>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="margin-right: 30px; text-align: left;">-----------------------------------------------------------------------------------------------------------------------------------------------</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left"></td>';
//     $BillReport .= '<td style="width: 50%; margin-left: 10%; text-align: left;">Total 2(b) + 5(a) (G)</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 70%; margin-right: 10px; text-align: left;">------------------------------------------------------------------------------------------------------------------------------------------</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '</tbody>';
//     $BillReport .= '</table>';


//     $BillReport .= '<table>';
//     $BillReport .= '<tbody>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  (b) By recovery of amounts creditable to other<br> &nbsp &nbsp &nbsp works or heads of account (b)</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  1) Security Deposit</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  2) Income Tax -  ------%   </td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  3) Surcharge - --------%</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  4) Education cess - ----------%</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  5) M. Vat - 2 / 4   ---------%</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  6) Royalty</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">   7) Insurance - 1 %</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">   8) Deposit</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">   9)---------</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">   10) -------------------</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
//     $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;>  (c) By Cheque</td>';
//     $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; "></td>';
//     $BillReport .= '</tr>';
//     $BillReport .= '</tbody>';
//     $BillReport .= '</table>';

//     // $BillReport .= '<tr>';
//     $BillReport .= '<p style="width: 100%; margin-right: 30px; text-align: left;">   --------------------------------------------------------------------------------------------------------------------------------------------------------</p>';
//     // $BillReport .= '</tr>';

//     $BillReport .= '<p style="text-align: justify; margin-left: 10%;">Total 5(b) + 5(c) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (H)
//      </p>';

//      $BillReport .= '<p style="text-align: justify; margin-left: 10%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
//      </p>';

//      $BillReport .= '<p style="text-align: justify; margin-left: 10%; ">Pay Rs. *( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ) By Cheque
//      </p><br><br>';


//      $BillReport .= '<table>';
//      $BillReport .= '<tbody>';

//      $BillReport .= '<tr>';
//      $BillReport .= '<td style="text-align: justify; margin-left: 10%;">Received Rs. *(  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ) As per above</td>';
//      $BillReport .= '</tr><br>';

//      $BillReport .= '<tr>';
//      $BillReport .= '<td style="text-align: left;">Memorandum on account of this work<br>&nbsp; &nbsp;  &nbsp; &nbsp; Dated /  /  </td>';
//      $BillReport .= '<td style="text-align: left;">Stamp</td>';
//      $BillReport .= '</tr>';

//      $BillReport .= '<tr>';
//      $BillReport .= '<p style="width:100%; text-align:left; padding-left:10px;">Witness</p>';

//     // //  dd($Agency_Pl->agencysign);
//     //  $imagePath = public_path('Uploads/signature/' . $Agency_Pl->agencysign);
//     //  $imageData = base64_encode(file_get_contents($imagePath));
//     //  $imageSrc2 = 'data:image/jpeg;base64,' . $imageData;

//      $BillReport .= '<p style="width:100%; text-align:right;"><img src="' . $imageSrcAgency . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>(Full signature of the contractor)</p>';
//      $BillReport .= '</tr>';

//      $BillReport .= '</tbody>';
//      $BillReport .= '</table>';

//      $BillReport .= '<table>';
//      $BillReport .= '<tbody>';

//      $BillReport .= '<tr>';
//      $BillReport .= '<td style="text-align: justify; margin-left: 10%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
//      $BillReport .= '</tr>';

//      $BillReport .= '<tr>';
//      $BillReport .= '<td colspan=4 style="text-align: left;">Paid by me, vide cheque No.</td>';
//      $BillReport .= '<td colspan=3 style="text-align: right;">Dated / / </td>';
//      $BillReport .= '</tr>';

//      $BillReport .= '<tr>';
//      $BillReport .= '<td style="text-align: center; text-align: right; padding-left:10px;">(Dated initials of the person actually making the payments.)</td>';
//      $BillReport .= '</tr>';

//      $BillReport .= '<tr>';
//      $BillReport .= '<td style="text-align: justify; padding-right:10%; ">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
//      $BillReport .= '</tr>';

//     $BillReport .= '</tbody>';
//     $BillReport .= '</table>';


//     $BillReport .= '<p style="text-align: center; font-weight:bold;  word-wrap: break-word;">IV - Remarks </p>';
//     $BillReport .= '<p style="text-align: justify; ">(This space is reserved for any remarks the disbursing officer or the Executive Engineer may
//     wish to record in respect of the execution of the work, check of measurements or the state of
//     contractor s accounts.) </p>';

//     }
//     return view('reports/Bill' ,compact( 'embsection2' ,'tbillid', 'BillReport'));
// }

// public function Billreportpdf(Request $request , $tbillid)
// {
//     // $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

//     $Billinfo=DB::table('bills')
//     ->where('t_bill_Id' , $tbillid)
//     ->select('work_id','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
//             'part_b_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
//             'c_part_b_amt','bill_amt_gt','c_billamtgt','bill_amt_ro','c_billamtro','net_amt','gst_base','c_netamt','p_net_amt','c_gstbase','gst_rt','gst_amt')->first();
//     // dd($Billinfo);
//     // $Billinfo->gst_base=$Billinfo->gst_base=0;
//     $work_id=$Billinfo->work_id;
//     //dd($work_id);
//     $workdata=DB::table('workmasters')->where('Work_Id' , $work_id)->first();
//     $Maxdt=DB::table('embs')->where('t_bill_Id' , $tbillid)->max('measurment_dt');
//     $dates = Carbon::createFromFormat('Y-m-d',  $Maxdt)->format('d/m/Y');
//     //dd($dates);

//     $Agency_Pl=DB::table('agencies')->where('id' , $workdata->Agency_Id)->select('Agency_Pl','agencysign')->first();
//     $JE_nm=DB::table('jemasters')->where('jeid' , $workdata->jeid)->select('name','designation','sign')->first();
//     $DYE_nm=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation','sign')->first();
//     $EE_nm=DB::table('eemasters')->where('eeid' , $workdata->EE_id)->select('name','subname','designation','sign')->first();
//     // $DYE_Design=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();


//     $imagePathj = public_path('Uploads/signature/' . $JE_nm->sign);
//     $imageData1 = base64_encode(file_get_contents($imagePathj));
//     $imageSrcJE = 'data:image/jpeg;base64,' . $imageData1;

//     $imagePathd = public_path('Uploads/signature/' . $DYE_nm->sign);
//     $imageData2 = base64_encode(file_get_contents($imagePathd));
//     $imageSrcDYE = 'data:image/jpeg;base64,' . $imageData2;

//     $imagePathEE = public_path('Uploads/signature/' . $EE_nm->sign);
//     $imageData3 = base64_encode(file_get_contents($imagePathEE));
//     $imageSrcEE = 'data:image/jpeg;base64,' . $imageData3;

//     //  dd($Agency_Pl->agencysign);
//     $imagePatha = public_path('Uploads/signature/' . $Agency_Pl->agencysign);
//     $imageData4 = base64_encode(file_get_contents($imagePatha));
//     $imageSrcAgecy = 'data:image/jpeg;base64,' . $imageData4;


//     //dd($DYE_nm);
//     $headercheck='Bill';
//     $cvno=$Billinfo->cv_no;
//     // dd($cvno);
//     $isFinalBill=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('final_bill','t_bill_No')->first();
//     // dd($isFinalBill);
//     $FirstBill=$isFinalBill->t_bill_No;
//     $FinalBill=$isFinalBill->final_bill;
//     //dd($FirstBill,$FinalBill);
//     // $header=$this->commonheader();
//     $rbbillno=CommonHelper::formatNumbers($FirstBill);
//     $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);
//     // dd($prev_rbbillno);
//     $royaltylab = [ "001991","001992","002048","004349","002047","003229","004346","004347","004348","004350"];

//     $NormalData = DB::table('bil_item')
//     ->select('exec_qty', 'item_unit', 'item_desc', 'bill_rt', 'b_item_amt', 't_bill_id', 'item_id', 't_item_no', 'sub_no')
//     ->where('t_bill_id', $tbillid)
//     ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//     ->get();
//     // dd("Okkkkk");
//     //  dd($NormalData);

//     $DBWorkId=DB::table('bills')
//     ->where('t_bill_Id',$tbillid)
//     ->value('work_id');
//     // dd($DBWorkId);
//     $DBaboveBellow=DB::table('workmasters')
//     ->select('Above_Below','A_B_Pc')
//     ->where('Work_Id',$DBWorkId)
//     ->first();
//     // dd($DBaboveBellow);

//     $FINALBILL =DB::table('bil_item')
//     ->select('exec_qty','item_unit','item_desc','bill_rt','b_item_amt','t_bill_id','item_id','t_item_no','sub_no')
//     ->where('t_bill_id',$tbillid)
//     ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//     ->get();
//     // dd($FINALBILL);


//     $DBbillTablegetData=DB::table('bills')
//     ->select('c_part_a_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
//     'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')
//     ->where('t_bill_Id',$tbillid)
//     ->first();
//     // dd($DBbillTablegetData);
//     // dd($header);
//     $BillReport= '';
//     // $BillReport .=$header;
//     if($FirstBill==1 && $FinalBill==1){
//     $BillReport .= '<h5 style="text-align: center; font-weight: bold; font-size: 15px; word-wrap: break-word;">FORM - 55 : First And Final Bill</h5>';

//     $BillReport .= '<table style="margin-left: 20px; font-size: 13px; width: 100%;">';
//     $BillReport .= '<thead></thead>';
//     $BillReport .= '<tbody>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th style="word-wrap: break-word;">Notes</th>';
//     $BillReport .= '<th  style="padding-left: 200px; width: 50%;word-wrap: break-word;">'.$workdata->Sub_Div.'</th>';
//     $BillReport .= '</tr>';
//     $BillReport .= '</thead>';
//     $BillReport .= '<tbody>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width: 50%; text-align: justify;">';
//     $BillReport .= '<p style="width: 100%;">(For Contractors and suppliers: To be used when a single payment is made for a job or contract, i.e. only on its completion. A single form may be used generally for making first & final payments to several works or supplies if they pertain to the same time. A single form may also be used for making first & final payment to several piece-workers or suppliers if they relate to the same work and billed at the same time. In this case, column 2 should be subdivided into two parts, the first part for "Name of Contractor / Piece-worker / Supplier: ABC Constructions, Sangli" and the second for "Items of work" etc.) and the space in Remarks column used for obtaining acceptance of the bill & acknowledgments of the amount paid to different piece-workers or suppliers.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="width: 50%; padding-left: 20px;">';
//     $BillReport .= '<p style="width: 50%;"> Cash Book Voucher No';
//     if ($cvno) {
//         $BillReport .=  "' . $Billinfo->cv_no .'";
//     }
//     if ($Billinfo->cv_dt) {
//         $BillReport .=  "' . $Billinfo->cv_dt .'";
//     }
//     $BillReport .= '</p><p>For</p><p>Name of Contractor  / Piece-worker / Supplier : '.$workdata->Agency_Nm.''.$Agency_Pl->Agency_Pl.'  </p>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="text-align: justify;">';
//     $BillReport .= '<p style="text-align: justify;">1. In the case of payments to suppliers, red ink entry should be made across the page above the entries relating thereto in one of the following forms applicable to the case,(i) Stock, No.: B1/HO/1234(ii) Purchase for Stock,(iii) Purchase for Direct issue to work,(iv) Purchase for work issued to contractor on</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 20px;">';
//     $BillReport .= '<p style="display: inline; padding-left: 10px;">* Agreement / Rate List / Requisition  </p><p>No.: '.$workdata->Agree_No.'</p><br><p style="display: inline; padding-left: 50px;">No. : '. $workdata->Agree_No.' '. $workdata->Agree_Dt.'</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="text-align: justify;">';
//     $BillReport .= '<p style="text-align: justify;">2. In the case of works, the accounts of which are kept by subheads, the amount relating to all items of work following under the same "sub-head" should be totaled in red ink.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 15px; text-align: justify;">';
//     $BillReport .= '<p>Name of work : '. $workdata->Work_Nm . '</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="text-align: justify;">';
//     $BillReport .= '<p style="text-align: justify;">3. Payment should be attested by some known person when the payee\'s acknowledgment is given by a mark, seal, or thumb impression.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="text-align: justify;">';
//     $BillReport .= '<p style="text-align: justify;">4. The person actually making the payment should initial (and date) the column provided for the purpose against each payment.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 50px;">';
//     $BillReport .= '<p><b>Account Classification :-</b></p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="text-align: justify;">';
//     $BillReport .= '<p style="text-align: justify;">Audit / Account Enfacement</p><br><br><br>';
//     $BillReport .= '<p style="text-align: justify;">Checked</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 10px;">';
//     $BillReport .= '<p>PLAN WORKS</p>';
//     $BillReport .= '<p>NON-PLAN WORKS</p>';
//     $BillReport .= '<ul>';
//     $BillReport .= '<li>Minor Head | ORIGINAL WORKS Communication</li>';
//     $BillReport .= '<li>Head | Repair & Maint (a) Buildings (a)</li>';
//     $BillReport .= '<li>Sub Head or ------------------------------------------------Detailed Head</li>';
//     $BillReport .= '</ul>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="text-align: justify;">';
//     $BillReport .= '<p style="text-align: justify;">2. Transactions of roadside materials entered in the statements of receipts, issues, and balances of Road metal.</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 15px;">';
//     $BillReport .= '<p>Provisions during the current year Rs...........</p>';
//     $BillReport .= '<p>Expenditure incurred</p>';
//     $BillReport .= '<p>during the current year Rs..........</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="">';
//     $BillReport .= '<p style="display: inline; padding-left: 90px;">Clerk</p><p style="display: inline; padding-left: 190px;">Accountant</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="">';
//     $BillReport .= '<p>Balance available Rs.......</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="text-align: justify;">';
//     $BillReport .= '<p style="text-align: justify;">* Strike out words that are not applicable</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '<td style="padding-left: 10px;">';
//     $BillReport .= '<p>a) Score out what is not applicable</p>';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '</tbody>';
//     $BillReport .= '</table><br><br>';

//     $royaltylab = [ "001991","001992","002048","004349","002047","003229","004346","004347","004348","004350"];

//     $NormalData = DB::table('bil_item')
//     ->select('exec_qty', 'item_unit', 'item_desc', 'bill_rt', 'b_item_amt', 't_bill_id', 'item_id', 't_item_no', 'sub_no')
//     ->where('t_bill_id', $tbillid)
//     ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//     ->get();
//     // dd($NormalData);

//     $DBWorkId=DB::table('bills')
//     ->where('t_bill_Id',$tbillid)
//     ->value('work_id');
//     // dd($DBWorkId);
//     $DBaboveBellow=DB::table('workmasters')
//     ->select('Above_Below','A_B_Pc')
//     ->where('Work_Id',$DBWorkId)
//     ->first();
//     // dd($DBaboveBellow);

//     $FINALBILL =DB::table('bil_item')
//     ->select('exec_qty','item_unit','item_desc','bill_rt','b_item_amt','t_bill_id','item_id','t_item_no','sub_no')
//     ->where('t_bill_id',$tbillid)
//     ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//     ->get();
//     // dd($FINALBILL);

//     $DBbillTablegetData=DB::table('bills')
//     ->select('c_part_a_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
//     'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')
//     ->where('t_bill_Id',$tbillid)
//     ->first();
//   // dd($DBbillTablegetData);



//     // $amountInWords=$this->convertAmountToWords($DBbillTablegetData->c_netamt);
//     $commonHelper = new CommonHelper();
//     $amountInWords = $commonHelper->convertAmountToWords($DBbillTablegetData->c_netamt);
//     // $amountInWords = convertAmountToWords($DBbillTablegetData->c_netamt);

//      //dd($amountInWords);
//     $BillReport .= '<table style="margin-left:30px; margin-right:200px; border-collapse: collapse;">';
//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=1 >Name of Work : </th>';
//     $BillReport .= '<th colspan=3 >'. $workdata->Work_Nm . '</th>';
//     $BillReport .= '<th colspan=1 style="padding-left: 40px; text-align: right; ">'.$workdata->Sub_Div.'</th>';
//     $BillReport .= '</tr>';
//     $BillReport .= '<thead>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%;  text-align: center; word-wrap: break-word;">Quantity</th>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 30%;  text-align: center; word-wrap: break-word;">Item of Work or supplies (grouped under sub-head or sub-works of estimates)</th>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px;  width: 2%; text-align: center; word-wrap: break-word;">Rate  Rs.</th>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%;  text-align: center; word-wrap: break-word;">Unit</th>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%; text-align: center; word-wrap: break-word;">Amount Rs.</th>';
//     $BillReport .= '<th   style="border: 1px solid black; padding: 8px; width: 10%;  text-align: center; word-wrap: break-word;">Remarks </th>';
//     $BillReport .= '</tr>';
//     $BillReport .= '<tr>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%; text-align: center; word-wrap: break-word;">1</th>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 30%;  text-align: center; word-wrap: break-word;">2</th>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px;  width: 2%; text-align: center; word-wrap: break-word;">3</th>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%;  text-align: center; word-wrap: break-word;">4</th>';
//     $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%; text-align: center; word-wrap: break-word;">5</th>';
//     $BillReport .= '<th   style="border: 1px solid black; padding: 8px;  width: 10%; text-align: center; word-wrap: break-word;">6</th>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tbody>';

//     foreach ($NormalData as $data)
//     {
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="border: 1px solid black;padding: 8px; width: 1%;  text-align: right; word-wrap: break-word;">' . '<div>' . $data->exec_qty . '</div><div>' . $data->item_unit . '</div></td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 30%; text-align: left; word-wrap: break-word;">' . $data->item_desc . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 2%; text-align: right; word-wrap: break-word;">' . $data->bill_rt . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: left; word-wrap: break-word;">' . $data->item_unit . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: right; word-wrap: break-word;">' . $data->b_item_amt . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">  </td>';
//         $BillReport .= '</tr>';
//     }
//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total BItem Amount</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_part_a_amt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Deduct for  '.$DBaboveBellow->Above_Below.' '.$DBaboveBellow->A_B_Pc.'  as per Tender (Except Roy/Lab/Ins Item) </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_abeffect.' </td>';
//     $BillReport .= '</tr>';


//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> GST Base	</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_gstbase.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> GST Amount 18%</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_gstamt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"> ' . $DBbillTablegetData->c_part_a_gstamt.' </td>';
//     $BillReport .= '</tr>';


//     foreach ($FINALBILL as $data)
//     {
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%;  text-align: right; word-wrap: break-word;">' . '<div>' . $data->exec_qty . '</div><div>' . $data->item_unit . '</div></td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 30%; text-align: left; word-wrap: break-word;">' . $data->item_desc . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 2%; text-align: right; word-wrap: break-word;">' . $data->bill_rt . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: left; word-wrap: break-word;">' . $data->item_unit . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: right; word-wrap: break-word;">' . $data->b_item_amt . '</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">  </td>';
//         $BillReport .= '</tr>';
//     }
//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total Part B Amount</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_part_b_amt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Grand Total</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_billamtgt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Add for rounding off</td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_billamtro.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Final Total	 </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_netamt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Previously Paid Amount From Per Bill </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->p_net_amt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">Now to be paid Amount </td>';
//     $BillReport .= '<td colspan=2 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">' .  $DBbillTablegetData->c_netamt.' </td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=6 style="border: 1px solid black;  padding: 8px; width: 10%; text-align: center; word-wrap: break-word;"> Total value of work done or supplies made  ' .  $DBbillTablegetData->c_netamt.'</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '<tr>';
//     $BillReport .= '<th colspan=6 style="border: 1px solid black;  padding: 8px; width: 10%; text-align: center; word-wrap: break-word;"> In  Word ' .  $amountInWords.' Nil Only </td>';
//     $BillReport .= '</tr>';
//     $BillReport .= '</thead>';

//     $BillReport .= '</tbody>';
//     $BillReport .= '</table>';



//     $BillReport .= '<table style="margin-left:50px;  width: 100%; ">';
//     $BillReport .= '<thead></thead>';
//     $BillReport .= '<tbody>';

//     // First Row
//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan="2" style="width:50%; text-align:justify; padding-right:40px;">';
//     $BillReport .= 'Measurements recorded by '.$JE_nm->name.' on '.$dates.' in M. Book No '.$tbillid.' checked by '.$DYE_nm->name.'100.00%.';
//     $BillReport .= '</td>';
//     $BillReport .= '<td   style="width:50%; text-align:justify;">';

//     $BillReport .= 'Received Rs. ' . $DBbillTablegetData->c_netamt . ' ' . $amountInWords . ' Nil Only. in final settlement of work.';
//     $BillReport .= '</td>';
//     $BillReport .= '</tr>';

//     // Second Row
//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width:1%; text-align:left; padding-top:20px;">Dated: </td>';
//     $BillReport .= '<td style="width:2%; text-align:left; padding-top:20px; padding-right:50px;">Countersigned</td>';
//     $BillReport .= '<td style="width:5%; text-align:left; padding-top:70px; padding-right:150px;">Witness</td>';
//     $BillReport .= '<td style="width:5%; text-align:left; padding-top:70px; padding-right:170px;">Stamp</td>';
//     $BillReport .= '</tr>';

//     // Third Row
//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan="4" style="text-align:right; padding:8px; height:60px; padding-right:150px;">Payee\'s dated signature</td>';
//     $BillReport .= '</tr>';

//     // Fourth Row
//     $BillReport .= '<tr>';
//     $BillReport .= '<td style="width:50%; text-align:left;"><img src="' . $imageSrcDYE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>'.$DYE_nm->designation.' <br> '.$workdata->Sub_Div.'</td>';
//     $BillReport .= '<td style="width:50%; text-align:left;"><img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;">'.$EE_nm->designation.'  <br>'.$workdata->Div.'</td>';
//     $BillReport .= '</tr>';

//     // Fifth Row
//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan="2" style="width:50%; text-align:left; padding-top:20px;">Pay by cash / cheque Rs.( ) Rupees</td>';
//     $BillReport .= '<td colspan="2" style="width:50%; text-align:left; padding-top:30px; padding-left:10px;">Paid by me by cash / vide cheque No.</td>';
//     $BillReport .= '</tr>';

//     // Additional Row
//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan="2" style="width:50%; text-align:left;  padding-bottom:20px;">Date.</td>';
//     $BillReport .= '<td colspan="2" style="width:50%; text-align:left;  padding-bottom:20px;">Date.</td>';
//     $BillReport .= '</tr>';

//     // Sixth Row
//     $BillReport .= '<tr>';
//     $BillReport .= '<td colspan="2" style="width:50%; text-align:left;">Officer authorizing payment</td>';
//     $BillReport .= '<td colspan="2" style="width:50%; text-align:left;">Dated Initials of person making the payment</td>';
//     $BillReport .= '</tr>';

//     $BillReport .= '</tbody>';
//     $BillReport .= '</table>';

//     }
//     else
//     {

//         $BillReport .= '<h1 style="text-align: center; font-weight: bold; font-size: 120%; word-wrap: break-word;">Z. P. FORM - 58 - C</h6>';
//         $BillReport .= '<h1 style="text-align: center; font-size: 100%; word-wrap: break-word;">(See Rule 174)</h1>';
//         $BillReport .= '<h1 style="text-align: center; margin-bottom: 4%; font-size: 80%; word-wrap: break-word;">' . $workdata->Div . '</h1>';

//         $BillReport .= '<table>';

//         $BillReport .= '<tr style="width: 100%;">';
//         $BillReport .= '<td style="width: 100%; font-size: 70%; text-align: center; padding-left:5%; word-wrap: break-word;"><strong>Notes</strong></th>';
//         $BillReport .= '<td style="width: 100%; font-size: 70%; text-align: center; padding-left:5%; word-wrap: break-word;"><strong>' . $workdata->Sub_Div . '</strong></th>';
//         $BillReport .= '</tr>';


//         $BillReport .= '<tbody>';
//         $BillReport .= '<tr style="width: 100%;">';
//         $BillReport .= '<td>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">(For Contractors and suppliers. This form provides only for payment for work or supplies actually measured.)<br> 1. The full name of the work as given in the estimate should be entered against the line "Name of work" except in the case of bills for "Stock" materials.<br></br>
//                             2. The purpose of supply applicable to the case should be filled in and rest scored out.</br></br>3. If the outlay on the work is recorded by sub-heads, the total for each sub-head should be shown on Column 5 and against this total, there should be an entry in Column 6 also. In no other case should any entries be made in Column 6.</br></br></p>';
//         $BillReport .= '</td>';

//         $BillReport .= '<td>';
//          $BillReport .= '<p style="max-width: 100%; text-align: center; padding-left:5%;">============================</p>';
//         $BillReport .= '<P style="padding-left: 3%; font-size: 70%;  width: 100%; text-align: center;"><b>RUNNING ACCOUNT BILL-C</b></p>';
//         $BillReport .= '<p style="padding-left: 5%; font-size: 70%; width: 100%; text-align: justify;">Cash Book Voucher No:';
//         $cvno=$Billinfo->cv_no;
//         if ($cvno) {
//             $BillReport .=  '' . $Billinfo->cv_no .'';
//         }
//          $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">============================</p>';
//         $BillReport .= '</p><p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%;">Name of Contractor / Piece-worker / Supplier: ' . $workdata->Agency_Nm . ',' . $Agency_Pl->Agency_Pl . '  </p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">Name of work :</p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">'. $workdata->Work_Nm . '</p>';
//         $BillReport .= '</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td>';
//         $BillReport .= '<p style="max-width: 100%; text-align: center; font-size: 70%; padding-left:5%; word-wrap: break-word;"><strong>Memorandum of Payments</strog><br ></p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">
//                 4. The figures against (k) should be test to see that it agrees with the total if Items 4&55.
//                 If the net amount is to be paid is lessthan Rs 10 and it cannot be included in a cheque,
//                 the payment should be made in cash, thepay order being altered suitably any alterati-on
//                 attested by dated initials.</br></br>6. The payes acknowledgement should be forthe gross
//                 amount paid as per Item 5, i.e.a+b+c</br></br> 7. Payment should be attested by some known
//                 person when the payes acknowledgement is given by a mark seal or thumb impression.</br></br>
//                     8. The column "Figures for Works Abstract" is not required in the case of bills of supplies.</br></p></td>';

//         $BillReport .= '<td>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">Purpose of Supply :</p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%;padding-left:5%; word-wrap: break-word;"> Serial No of this bill :'.$rbbillno.' R.A. Bill</p>';

//         if($prev_rbbillno===0)
//         {
//             $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;" > No and date of last :  -- R.A. Bill paid vide bill for this work :  C.V. No : <br></p>';
//         }
//         else{
//             $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;" > No and date of last :'.$prev_rbbillno.'  R.A. Bill paid vide bill for this work :  C.V. No : <br> </p>';
//         }
//         $cvno=$Billinfo->cv_no;
//         if ($cvno) {
//             $BillReport .=  '' . $Billinfo->cv_no .'';
//         }
//         $cvdate=$Billinfo->cv_dt;
//         $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">for</p>';

//         if ($cvdate) {
//             //dd($cvdate);
//             $date1=date_create($cvdate);
//             $formattedDate = $date1->format('d/m/Y');
//             $date=date_create($formattedDate);
//             $dt2=date_format($date,"M/Y");
//             // dd($dt2);
//             $BillReport .= $dt2;
//         }

//         $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;"> Reference to agreement : ' . $workdata->Agree_No . '</p>';
//         $OcommenceDate = date('d/m/Y', strtotime($workdata->Wo_Dt));
//         $dueDate = date('d/m/Y', strtotime($workdata->Stip_Comp_Dt));
//         $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;"> Date of order to commence the work : '.$OcommenceDate.'</p>';
//         $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;"> Due date of completion of work :'.$dueDate.'</p>';
//         $BillReport .= '</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 100%; text-align: justify; font-size: 70%; padding-left:9%; padding-top:1%; word-wrap: break-word;"><b>Account Classification - </b><br> '.$workdata->F_H_Code.'';
//         $BillReport .= '</td>';

//         $BillReport .= '<td>';
//         $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;" >Extensions granted, if any, - - - <br>
//                         from time to time with - - -<br>reference to authority - - <br><br></p>';

//         $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;"> Actual date of completion :';
//         if ($workdata->actual_complete_date) {

//             $Act_dt_compli = date('d/m/Y', strtotime($workdata->actual_complete_date));
//             $BillReport .=  "' . $Act_dt_compli .'";
//         }
//         $BillReport .= '</p></td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">PLAN WORKS</p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">NON-PLAN WORKS<br>Minor Head | ORIGINAL WORKS Communication<br>Head | Repair & Maint (a) Buildings (a)<br>Sub Head or -----------------------Detailed Head<br>============================================</p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">Provisions during the current year         Rs...........</p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">Expenditure incurred </p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">during the current year        Rs..........</p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">Balance available Rs.......</p>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">a) Strike out what is not applicable</p>';
//         $BillReport .= '</td>';

//         $BillReport .= '<td>';
//         $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 70%; padding-left:5%; word-wrap: break-word;">1) Security Deposit to be recovered as per agreement<br> <br> 2) Security Deposit previously recovered <br><br> 3) Security Deposit to be recovered from this bill<br><br>4) Balance to be recovered</p>';
//         $BillReport .= '</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '</tbody>';
//         $BillReport .= '</table>';

//         //PDF SECOND PAGE...............................................................................................................................................

//         $BillReport .= '<div style="page-break-before: always;"></div>';
//         $BillReport .= '<table>';
//         $BillReport .= '<tbody>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="6" style="font-size: 70%; text-align: left;">' . $workdata->Div . '</td>';
//         $BillReport .= '<td colspan="6" style="font-size: 70%; text-align: right;">' . $workdata->Sub_Div . '</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="6" style="text-align: left; font-size: 70%;">Name of Work:  </td>';
//         $BillReport .= '<td colspan="6" style="text-align: center; font-size: 70%;">'.$workdata->Work_Nm .'</td>';
//         $BillReport .= '</tr><br>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="12" style="text-align: center; font-size: 90%;"> I - Account of  Work Executed  </td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '</tbody>';
//         $BillReport .= '</table>';

//         // Foreach Header...........................................................................................................
//          $BillReport .= '<table class="table table-bordered table-collapse" style="border: 1px solid black; border-collapse: collapse; margin: 0;">';
//         // $BillReport .= '<thead>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; font-size: 70%; text-align:left;     width: 3%; word-wrap: break-word;">Unit</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; font-size: 70%; text-align:justify;  width: 8%; word-wrap: break-word;">Quantity executed up-to-date as per measurement Book</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; font-size: 70%; text-align:center;   width: 55%; word-wrap: break-word;">Item of Work</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; font-size: 70%; text-align:center;   width: 8%; word-wrap: break-word;">Bill<br>----------------<br>tender Rate Rs.</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; font-size: 70%; text-align:justify;  width: 8%; word-wrap: break-word;">Payments of Actual up-to-date Rs.</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; font-size: 70%; text-align:justify;  width: 8%; word-wrap: break-word;">On the basis of measurements Since the previous Bill Rs.</td>';
//         $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; font-size: 70%; text-align:center;   width: 5%; word-wrap: break-word;">Remark</td>';
//         $BillReport .= '</tr>';
//         // $BillReport .= '</thead>';
//         $BillReport .= '<tbody>';



//         //For Royalty Surcharg Items.............................................................................................
//         $royaltylab = [ "001991","001992","002048","004349","002047","003229","004346","004347","004348","004350"];

//         $NormalData = DB::table('bil_item')
//         ->where('t_bill_id', $tbillid)
//         ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//         ->get();
//         // dd($NormalData);

//         if($NormalData){
//             $header1=$this->commonforeach($NormalData,$tbillid,$work_id);
//             // dd($header1);
//             $BillReport .=$header1;
//             $abpc = $workdata->A_B_Pc;
//             $abobelowatper=$workdata->Above_Below;

//         if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Total Part A Amount</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->part_a_amt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_part_a_amt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//             $BillReport .= '</tr>';
//         }

//         if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Tender Above Bellow Result: ' . $workdata->A_B_Pc . ' ' . $workdata->Above_Below . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_abeffect . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->a_b_effect . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//             $BillReport .= '</tr>';
//         }

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="4" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
//         $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->gst_base . '</strong></td>';
//         $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_gstbase . '</strong></td>';
//         $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="4" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>GST Amount ' . $Billinfo->gst_rt . '%</strong></td>';
//         $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->gst_amt . '</strong></td>';
//         $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_gstamt . '</strong></td>';
//         $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//         $BillReport .= '</tr>';

//         }
//         $RoyaltyData = DB::table('bil_item')
//         ->where('t_bill_id', $tbillid)
//         ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
//         ->get();

//         //dd($RoyaltyData);
//          if (!$RoyaltyData->isEmpty()) {
//             // dd("Okkk");
//             $header1=$this->commonforeach($RoyaltyData,$tbillid,$work_id);
//             // dd($header1);
//             $BillReport .=$header1;

//             $BillReport .= '<tr>';
//             $BillReport .= '<td  colspan="4" style="border: 1px solid black; font-size: 70%; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
//             $BillReport .= '<td  colspan="1" style="border: 1px solid black;font-size: 70%; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->part_b_amt . '</strong></td>';
//             $BillReport .= '<td  colspan="1" style="border: 1px solid black;font-size: 70%; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_part_b_amt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//             $BillReport .= '</tr>';

//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="border: 1px solid black;font-size: 70%; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Grand Total</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black;font-size: 70%; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->bill_amt_gt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black;font-size: 70%; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_billamtgt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//             $BillReport .= '</tr>';


//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="border: 1px solid black;font-size: 70%; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
//             $BillReport .= '<td colspan="1"  style="border: 1px solid black;font-size: 70%; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->bill_amt_ro . '</strong></td>';
//             $BillReport .= '<td colspan="1"  style="border: 1px solid black;font-size: 70%; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_billamtro . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//             $BillReport .= '</tr>';


//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="4" style="border: 1px solid black;font-size: 70%; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
//             $BillReport .= '<td colspan="1"  style="border: 1px solid black;font-size: 70%; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->net_amt . '</strong></td>';
//             $BillReport .= '<td colspan="1"  style="border: 1px solid black;font-size: 70%; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $Billinfo->c_netamt . '</strong></td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//             $BillReport .= '</tr>';

//             $Net_Pre_subtraction=$Billinfo->net_amt-$Billinfo->p_net_amt;

//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan="5" style="border: 1px solid black; font-size: 70%; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">Net value of work or supplies since previous bill (F) :</td>';
//             $BillReport .= '<td colspan="1" style="border: 1px solid black; font-size: 70%; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"><strong>' .  $Net_Pre_subtraction.'</strong> </td>';
//             $BillReport .= '<td colspan="1" style="font-size: 70%; border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//             $BillReport .= '</tr>';

//             // $c_netamt=$this->convertAmountToWords($Billinfo->c_netamt);
//             $commonHelper = new CommonHelper();
//             $c_netamt = $commonHelper->convertAmountToWords($Billinfo->c_netamt);
//             $BillReport .= '<tr>';
//             $BillReport .= '<td colspan=7 style="border: 1px solid black;  font-size: 70%;  width: 10%; text-align: center; word-wrap: break-word;"> In  Word<strong> '.$c_netamt.' Nil Only </strong></td>';
//             $BillReport .= '</tr>';

//         }


//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="7" style="font-size: 70%; text-align:justify; word-wrap: break-word; border: 1px solid white;"> -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br>
//         The measurements made by '.$JE_nm->name.' , '.$JE_nm->designation.' on '.$dates.' and are recorded at
//          Measurement Book No. '.$tbillid.' No advance payment has been made previously
//         without detailed measurements.</td>';
//         $BillReport .= '</tr>';

//         // $imagePath = public_path('Uploads/signature/' . $DYE_nm->sign);
//         // $imageData = base64_encode(file_get_contents($imagePath));
//         // $imageSrcDYE = 'data:image/jpeg;base64,' . $imageData;

//         // $BillReport .= '<br><table style="font-size: 70%; border: none; margin-left: 70%; margin-right: 30px; text-align:center;">';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="4" style="font-size: 70%;  width: 10%; text-align: center; word-wrap: break-word; border: 1px solid white;"></td>';

//         $BillReport .= '<td colspan="3" style="font-size: 70%; text-align:center; border: 1px solid white;"><img src="' . $imageSrcDYE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>
//         '.$DYE_nm->designation.'<br>
//         '.$workdata->Sub_Div.'<br>
//         <br> * Dated Signature of Officer preparing bill </td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan="7" style="border: 1px solid white;">----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '</tbody>';
//         $BillReport .= '</table>';

//         // $imagePath = public_path('Uploads/signature/' . $EE_nm->sign);
//         // $imageData = base64_encode(file_get_contents($imagePath));
//         // $imageSrcEE = 'data:image/jpeg;base64,' . $imageData;

//         $BillReport .= '<table>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=6 style="font-size: 70%;   text-align:left;">  Dated : </td>';
//         $BillReport .= '<td colspan=6 style="font-size: 70%;   text-align:right; margin-left: 60%;">  Countersigned </td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 100%; font-size: 70%; text-align: left;  word-wrap: break-word;"> Dated Signature of the Contractor</td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 100%; font-size: 70%; margin-left: 80%; text-align:right;"><img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 100%; font-size: 70%; margin-left: 80%; text-align:right;">'.$EE_nm->designation.'</td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="font-size: 70%;  margin-left: 80%;  text-align:right;">'.$workdata->Div.'</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td>-------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="font-size:60%;"> The second signature is only necessary when the officer who prepares the bill is not the officer who makes the payment. </td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '</table>';

//         // PDF LAST PAGE--------------------------------------------------------------------------------------------------------------
//         $BillReport .= '<div style="page-break-before: always;"></div>';
//         $BillReport .= '<h6 style="text-align: center; font-weight:bold; word-wrap: break-word;">III - Memorandum of Payments </h6>';
//         // $BillReport .= '<P style="text-align: center; font-weight:bold; font-size: 70%;  word-wrap: break-word;">===============================================================================================</P>';

//         $BillReport .= '<p style="text-align: left; font-size: 70%;">1. Total Value of work done, as per Account-I, Column 5, Entry (A)</p>';
//         $BillReport .= '<p style="text-align: left; font-size: 70%;">2. Deduct Amount withheld :</p>';


//         $BillReport .= '<table>';
//         $BillReport .= '<tbody>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%; ">----------------<br>Figures for<br> Work abstract<br>-----------------</td>';
//         $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: center; font-size: 70%; ">(a) From previous bill as per last Running Account Bill</td>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: center; font-size: 70%;">----------------<br>Rs.&nbsp; &nbsp; &nbsp; &nbsp; Ps.<br>----------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
//         $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%;">(b) From this bill</td>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: center; font-size: 70%;">----------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
//         $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%;"> 3. Balance, i.e. "Up-to-date" payments (Items 1 - 2)</td>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;">(K)</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
//         $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; word-wrap: break-word;"> Total amount of payments already made as per entry<br>
//                         of last Running Account Bill No.<br>
//                         forwarded with accounts for</td>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;">(K)</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
//         $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: center; font-size: 70%;">5. Payments now to be made as detailed below :-</td>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: center; font-size: 70%;">-------------------<br> Rs. &nbsp; &nbsp; &nbsp; &nbsp; Ps.<br>------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
//         $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%;"> (a) By recovery of amounts creditable to this work -(a)<br>
//                             Value of stock supplied as detailed<br>
//                             in the ledger in (a)</td>';
//         $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '</tbody>';
//         $BillReport .= '</table>';


//         $BillReport .= '<table>';
//         $BillReport .= '<tbody>';
//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="margin-right: 30px; text-align: left; font-size: 70%; padding-botton:2%;">-----------------------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 100%; text-align: center; font-size: 70%; padding-botton:2%;">Total 2(b) + 5(a) (G)</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td style="width: 70%; margin-right: 10px; text-align: left; font-size: 70%; padding-botton:2%;">-----------------------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';
//         $BillReport .= '</tbody>';
//         $BillReport .= '</table>';


//         $BillReport .= '<table>';
//         $BillReport .= '<tbody>';
//         $BillReport .= '<tr colspan=10>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;">(b) By recovery of amounts creditable to other<br> &nbsp &nbsp &nbsp works or heads of account (b)</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;">  1) Security Deposit</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;">  2) Income Tax -  ------%   </td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;">  3) Surcharge - --------%</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;">4) Education cess - %</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;"> 5) M. Vat - 2 / 4 % </td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;"> 6) Royalty</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;"> 7) Insurance - 1 %</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;">8) Deposit</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;"> 9) </td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;">10)</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;"> C) By check </td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="width: 100%; margin-right: 30px; text-align: left; font-size: 70%;">   --------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">Total 5(b) + 5(c)  (H)</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">Pay Rs. *(  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;) By Cheque</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 60%;"> * Here specify the net amount payable [Item 5(c)] &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;(Dated initials of the disbursing officer)</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">Received Rs. *(  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ) As per above</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=10 style="text-align: left; font-size: 70%;">Memorandum on account of this work<br>&nbsp; &nbsp;  &nbsp; &nbsp; Dated /  /  </td>';
//         $BillReport .= '<td colspan=10 style="text-align: right; font-size: 70%;">Stamp</td>';
//         $BillReport .= '</tr>';

//         $imagePath = public_path('Uploads/signature/' . $Agency_Pl->agencysign);
//         $imageData = base64_encode(file_get_contents($imagePath));
//         $imageSrcagency = 'data:image/jpeg;base64,' . $imageData;

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=5 style="width:50%; text-align:left; font-size: 70%; padding-left:10px;"></td>';
//         $BillReport .= '<td colspan=5 style="width:50%; text-align:right; font-size: 20%;"><img src="' . $imageSrcagency . '" alt="Base64 Encoded Image" style="width: 100px; height: 30px;"></td>';
//         $BillReport .= '</tr>';



//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=5 style="width:50%; text-align:left; font-size: 70%; padding-left:10px;">Witness</td>';
//         $BillReport .= '<td colspan=5 style="width:50%; text-align:right; font-size: 70%;">(Full signature of the contractor)</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '</tbody>';
//         $BillReport .= '</table>';

//         $BillReport .= '<table>';
//         $BillReport .= '<tbody>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=12 style="text-align: justify; margin-left: 10%;  font-size: 70%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=6 style="font-size: 70%; text-align: left;">Paid by me, vide cheque No.</td>';
//         $BillReport .= '<td colspan=6 style="font-size: 70%; text-align: center;">Dated / / </td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=4 style="width:50%; font-size: 70%; text-align: left;">Cashier</td>';
//         $BillReport .= '<td colspan=8 style="width:50%; font-size: 70%; text-align: right;">(Dated initials of the person actually making the payments.)</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '<tr>';
//         $BillReport .= '<td colspan=12 style="text-align: justify; padding-right:10%; font-size: 70%; padding-botton:2%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
//         $BillReport .= '</tr>';

//         $BillReport .= '</tbody>';
//         $BillReport .= '</table>';

//         $BillReport .= '<table>';
//         $BillReport .= '<tbody>';
//         $BillReport .= '<h6 style="text-align: center; font-weight:bold;  padding: 8px; word-wrap: break-word;">IV - Remarks </h6>';
//         $BillReport .= '<p style="text-align: justify;  font-size: 70%; padding: 8px;">(This space is reserved for any remarks the disbursing officer or the Executive Engineer may
//                         wish to record in respect of the execution of the work, check of measurements or the state of
//                         contractor  accounts.) </p>';
//         $BillReport .= '</tbody>';
//         $BillReport .= '</table>';

//     }


//     $pdf = new Dompdf();
//     // Image path using the asset helper function
//     $pdf->loadHtml($BillReport);
//     //$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
//     $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

//     // (Optional) Set options for the PDF rendering
//     $options = new Options();
//     $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
//     $pdf->setOptions($options);

//     $pdf->render();
//     return $pdf->stream('Bill-Report'.$tbillid.'-pdf.pdf');

//     // return $pdf->stream('Bill-Report-pdf.pdf');
// }

public function billreport(Request $request , $tbillid)
{
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    $BillItemRt=DB::table('bil_item')->where('t_bill_Id' , $tbillid)->select('tnd_rt','bill_rt');

    $Billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('work_id','bill_amt_gt','bill_amt_ro','net_amt','gst_amt','gst_rt','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
                                                                        'part_b_amt','gst_base','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',


                                                                        'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')->first();
    // dd($Billinfo);
    $work_id=$Billinfo->work_id;


    //dd($work_id);
    $workdata=DB::table('workmasters')->where('Work_Id' , $work_id)->first();
    $Maxdt=DB::table('embs')->where('t_bill_Id' , $tbillid)->max('measurment_dt');
    $dates = Carbon::createFromFormat('Y-m-d',  $Maxdt)->format('d/m/Y');
    //dd($dates);

    $Agency_Pl=DB::table('agencies')->where('id' , $workdata->Agency_Id)->select('Agency_Pl','agencysign')->first();
    $JE_nm=DB::table('jemasters')->where('jeid' , $workdata->jeid)->select('name','designation','sign')->first();
    $DYE_nm=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation','sign')->first();
    $EE_nm=DB::table('eemasters')->where('eeid' , $workdata->EE_id)->select('name','subname','designation','sign')->first();

    $imagePath1 = public_path('Uploads/signature/' . $DYE_nm->sign);
    $imageDatad = base64_encode(file_get_contents($imagePath1));
    $imageSrcDYE = 'data:image/jpeg;base64,' . $imageDatad;

    $imagePath2 = public_path('Uploads/signature/' . $EE_nm->sign);
    $imageDatae = base64_encode(file_get_contents($imagePath2));
    $imageSrcEE = 'data:image/jpeg;base64,' . $imageDatae;

    //  dd($Agency_Pl->agencysign);
     $imagePath3 = public_path('Uploads/signature/' . $Agency_Pl->agencysign);
     $imageDataa = base64_encode(file_get_contents($imagePath3));
     $imageSrcAgency = 'data:image/jpeg;base64,' . $imageDataa;
     //dd($imageSrc2);
    // $DYE_Design=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();
    //dd($DYE_nm);  $DYE_nm->designation
    $headercheck='Bill';
    $cvno=$Billinfo->cv_no;
    // dd($cvno);
    $isFinalBill=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('final_bill','t_bill_No')->first();
    // dd($isFinalBill);
    $FirstBill=$isFinalBill->t_bill_No;
    $FinalBill=$isFinalBill->final_bill;
    //dd($FirstBill,$FinalBill);
    // $header=$this->commonheader();


    // $header=$this->commonheader();
    $rbbillno=CommonHelper::formatTItemNo($FirstBill);
    $rbbiill=CommonHelper::getBillType($FinalBill);

    $rbbillno=CommonHelper::formatNumbers($FirstBill);
    $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);


    // dd($prev_rbbillno);
    $BillReport= '';

    if($FirstBill==1 && $FinalBill==1){
        // dd("iff ok");
    $BillReport .= '<h5 style="text-align: center; margin-bottom:50px; font-weight:bold; font-size:25px; padding: 8px; word-wrap: break-word;">FORM - 55 : First And Final Bill</h5>';
    $BillReport .= '<div class="table-responsive">';
    $BillReport .= '<table>';

    $BillReport .= '<tr>';
    $BillReport .= '<th style="width: 50%; text-align: center;  word-wrap: break-word;">Notes</th>';
    $BillReport .= '<th  style="padding-left: 200px; width: 50%;word-wrap: break-word;">'.$workdata->Sub_Div.'</th>';
    $BillReport .= '</tr>';
    $BillReport .= '</thead>';
    $BillReport .= '<tbody>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 200px; width: 50%;text-align: justify;>';
    $BillReport .= '<p style="padding: 8px; width: 50%;>(For Contractors and suppliers :- To be used when a single payment is made for a job or contract, i.e. only on its completion. A single form may be used generally for making first & final payments several works or supplies if they pertain to the same time. A single form may also be used for making first & final payment to several piece-workers or suppliers if they relate to the same work and billed at the same time. In this case column 2 should be subdivided into two parts, the first part for "Name of Contractor / Piece-worker / Supplier: ABC Constructions, Sangli" and the second for "Items of work" etc.) and the space in Remarks column used for obtaining acceptance of the bill & acknowledgments of amount paid to different piece-workers or suppliers.</p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px; width: 50%;>';

    $BillReport .= '<p style="width: 50%;"> Cash Book Voucher No';
    $cvno=$Billinfo->cv_no;
    if ($cvno) {
        $BillReport .=  "' . $Billinfo->cv_no .'";
    }
    if ($Billinfo->cv_dt) {
        $BillReport .=  "' . $Billinfo->cv_dt .'";
    }

    $BillReport .= '</p><p>For</p><p>Name of Contractor  / Piece-worker / Supplier : '.$workdata->Agency_Nm.''.$Agency_Pl->Agency_Pl.'  </p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 150px; text-align: justify;" >';
    $BillReport .= '<p style="padding-left: 50px; text-align: justify;">1. In the case of payments to suppliers, red ink entry should be made across the page above the entries relating thereto in one of the following forms applicable to the case,</p>';
    $BillReport .= '<ul style="padding-left: 100px; text-align: justify;" >';
    $BillReport .= '<li>(i) Stock, No.: B1/HO/1234</li>';
    $BillReport .= '<li>(ii) Purchase for Stock,</li>';
    $BillReport .= '<li>(iii) Purchase for Direct issue to work,</li>';
    $BillReport .= '<li>(iv) Purchase for work issued to contractor on</li>';
    $BillReport .= '</ul>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px;">';
         $agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';

    $BillReport .= '<p> * Agreement / Rate List / Requisition  </p><p>No.: '.$workdata->Agree_No.'          ' . ($agreementDate ? 'Date: ' . $agreementDate : '') . '</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 50px; text-align: justify;" >';
    $BillReport .= '<p style="padding-left: 150px; text-align: justify;" >2. In the case of works, the accounts of which are kept by subheads, the amount relating to all items of work following under the same "sub-head" should be totaled in red ink.</p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px;">';
    $BillReport .= '<p>Name of work :'. $workdata->Work_Nm . '</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 110px; text-align: justify;" >';
    $BillReport .= '<p style="padding-left: 90px; text-align: justify;" >3. Payment should be attested by some known person when the payee\'s acknowledgment is given by a mark, seal or thumb impression.</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 100px; text-align: justify;" >';
    $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >4. The person actually making the payment should initial (and date) the column provided for the purpose against each payment.</p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px;">';
    $BillReport .= '<p><b>Account Classification :-</b> </p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';


    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 100px; text-align: justify;" >';
    $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >Audit / Account Enfacement</p><br><br><br>';
    $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >Checked</p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px;">';
    $BillReport .= '<p>PLAN WORKS</p>';
    $BillReport .= '<p>NON-PLAN WORKS</p>';
    $BillReport .= '<ul>';
    $BillReport .= '<li>Minor Head | ORIGINAL WORKS Communication</li>';
    $BillReport .= '<li>Head | Repair & Maint (a) Buildings (a)</li>';
    $BillReport .= '<li>Sub Head or ------------------------------------------------Detailed Head</li>';

    $BillReport .= '</ul>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 100px; text-align: justify;" >';
    $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >2. Transactions of roadside materials entered in the statements of receipts, issues, and balances of Road metal.</p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px;">';
    $BillReport .= '<p>Provisions during the current year         Rs...........</p>';
    $BillReport .= '<p>Expenditure incurred </p>';
    $BillReport .= '<p>during the current year        Rs..........</p>';
    $BillReport .= '</td>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 150px; text-align: justify;" >';
    $BillReport .= '<p style="display: inline; padding-left: 90px;">Clerk</p><p style="display: inline; padding-left: 320px;">Accountant</p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px;">';
    $BillReport .= '<p>Balance available Rs.......</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 100px; text-align: justify;"  >';
    $BillReport .= '<p style="padding-left: 100px; text-align: justify;" >* Strike out words which are not applicable </p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px;" >';
    $BillReport .= '<p>a) Score out what is not applicable</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';
    $BillReport .= '</tbody>';
    $BillReport .= '</table>';
    $BillReport .= '</div><br><br>';


    $royaltylab = [ "001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566",
                "004350", "003940", "003941", "004346", "004348", "004347"];

    $NormalData = DB::table('bil_item')
    ->select('exec_qty', 'item_unit', 'item_desc', 'bill_rt', 'b_item_amt', 't_bill_id', 'item_id', 't_item_no', 'sub_no')
    ->where('t_bill_id', $tbillid)
    ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();
    // dd("Okkkkk");
    //  dd($NormalData);


    $DBWorkId=DB::table('bills')
    ->where('t_bill_Id',$tbillid)
    ->value('work_id');
    // dd($DBWorkId);
    $DBaboveBellow=DB::table('workmasters')
    ->select('Above_Below','A_B_Pc')
    ->where('Work_Id',$DBWorkId)
    ->first();
    // dd($DBaboveBellow);



    $FINALBILL =DB::table('bil_item')
    ->select('exec_qty','item_unit','item_desc','bill_rt','b_item_amt','t_bill_id','item_id','t_item_no','sub_no')
    ->where('t_bill_id',$tbillid)
    ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();
    // dd($FINALBILL);


        $DBbillTablegetData=DB::table('bills')
        ->select('c_part_a_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
        'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')
        ->where('t_bill_Id',$tbillid)
        ->first();
        // dd($DBbillTablegetData);

    $commonHelper = new CommonHelper();
$amountInWords = $commonHelper->convertAmountToWords($DBbillTablegetData->c_netamt);

    //  dd($amountInWords);
$BillReport .= '<div class="table-responsive">';
    $BillReport .= '<table style="" >';
$BillReport .= '<tr>';
    $BillReport .= '<th colspan=1 >Name of Work : </th>';
    $BillReport .= '<th colspan=5 >'. $workdata->Work_Nm . '</th>';
    $BillReport .= '</tr>';
    $BillReport .= '<tr>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%;  text-align: center; word-wrap: break-word;">Quantity</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 30%;  text-align: center; word-wrap: break-word;">Item of Work or supplies (grouped under sub-head or sub-works of estimates)</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px;  width: 2%; text-align: center; word-wrap: break-word;">Rate  (Rs.)</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%;  text-align: center; word-wrap: break-word;">Unit</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%; text-align: center; word-wrap: break-word;">Amount (Rs.)</th>';
    $BillReport .= '<th   style="border: 1px solid black; padding: 8px; width: 10%;  text-align: center; word-wrap: break-word;">Remarks </th>';
    $BillReport .= '</tr>';


    $BillReport .= '<tr>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%; text-align: center; word-wrap: break-word;">1</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 30%;  text-align: center; word-wrap: break-word;">2</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px;  width: 2%; text-align: center; word-wrap: break-word;">3</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%;  text-align: center; word-wrap: break-word;">4</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%; text-align: center; word-wrap: break-word;">5</th>';
    $BillReport .= '<th   style="border: 1px solid black; padding: 8px;  width: 10%; text-align: center; word-wrap: break-word;">6</th>';
    $BillReport .= '</tr>';


    $BillReport .= '<tbody>';

    foreach ($NormalData as $data)
    {
        $BillReport .= '<tr>';
        $BillReport .= '<td style="border: 1px solid black; width: 10px; padding: 8px; width: 1%; height: 60px; text-align: right; word-wrap: break-word;">' . '<div>' . $data->exec_qty . '</div><div>' . $data->item_unit . '</div></td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 30%; text-align: left; word-wrap: break-word;">' . $data->item_desc . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 2%; text-align: right; word-wrap: break-word;">' . $commonHelper->formatIndianRupees($data->bill_rt) . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: left; word-wrap: break-word;">' . $data->item_unit . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: right; word-wrap: break-word;">' . $commonHelper->formatIndianRupees($data->b_item_amt) . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">  </td>';
        $BillReport .= '</tr>';
    }
$BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total Part A Amount</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_a_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Deduct for  '.$DBaboveBellow->Above_Below.' '.$DBaboveBellow->A_B_Pc.'  as per Tender</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_abeffect).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';


    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> GST Base	</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_gstbase).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> GST Amount 18%</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_gstamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_a_gstamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';

    $BillReport .= '</tr>';



    foreach ($FINALBILL as $data)
    {
        $BillReport .= '<tr>';
        $BillReport .= '<td style="border: 1px solid black; width: 10px; padding: 8px; width: 1%; height: 60px; text-align: right; word-wrap: break-word;">' . '<div>' . $data->exec_qty . '</div><div>' . $data->item_unit . '</div></td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 30%; text-align: left; word-wrap: break-word;">' . $data->item_desc . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 2%; text-align: right; word-wrap: break-word;">' . $commonHelper->formatIndianRupees($data->bill_rt) . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: left; word-wrap: break-word;">' . $data->item_unit . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 1%; text-align: right; word-wrap: break-word;">' . $commonHelper->formatIndianRupees($data->b_item_amt) . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;">  </td>';
        $BillReport .= '</tr>';
    }
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total Part B Amount</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_b_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Grand Total</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_billamtgt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Add for rounding off</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_billamtro).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Final Total	 </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_netamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Previously Paid Amount From Per Bill </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->p_net_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">Now to be paid Amount </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_netamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';


    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=6 style="border: 1px solid black;  padding: 8px; width: 10%; text-align: center; word-wrap: break-word;"> Total value of work done or supplies made  ' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_netamt).'</td>';
    $BillReport .= '</tr>';

    // $amountInWords=$this->convertAmountToWords($DBbillTablegetData->c_netamt);
    $commonHelper = new CommonHelper();
    $amountInWords = $commonHelper->convertAmountToWords($DBbillTablegetData->c_netamt);

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=6 style="border: 1px solid black;  padding: 8px; width: 10%; text-align: center; word-wrap: break-word;"> In  Word (' .$amountInWords.')  </td>';
    $BillReport .= '</tr>';


    $BillReport .= '<tr style="border-collapse:">';

    $BillReport .= '<table style="border-collapse: collapse;  border: 1px solid black;">';
    $BillReport .= '<tr>';

    $BillReport .= '<td  style="border: 1px solid black; padding: 8px; text-align:left;  word-wrap: break-word;">Measurements recorded by '.$JE_nm->name.' on '.$dates.' in M. Book No '.$tbillid.' checked by '.$DYE_nm->name.'100.00 %. ';
    
    $BillReport .= '<div style="display: flex; ">'; // Flexbox for layout control
$BillReport .= '<div style="width: 50%; padding: 50px; ">'; // Half width for date
$BillReport .= 'Date: ______/______/______'; // Your date content here
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; text-align: right; padding: 50px; ">'; // Half width for signature
$BillReport .= 'Countersigned'; // Your signature content here
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '<div style="display: flex;   height: 165px;">'; // Flexbox for layout control
$BillReport .= '<div style="width: 50%;  height: 20px; text-align:center">'; // Half width for date
$BillReport .= '<img src="' . $imageSrcDYE  . '" alt="Base64 Encoded Image" style="width: 100px; "><br>'.$DYE_nm->designation.'<br> '.$workdata->Sub_Div.'';
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; text-align: right; height: 20px; text-align:center">'; // Half width for signature
$BillReport .= '<img src="' . $imageSrcEE  . '" alt="Base64 Encoded Image" style="width: 100px;"><br>'.$EE_nm->designation.'<br>'.$workdata->Div.'';
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '</td>';

     $convert=new Commonhelper();
   
$BillReport .= '<td style="border: 1px solid black; padding: 8px; text-align: left; word-wrap: break-word;">Received Rs. ' . $convert->formatIndianRupees($DBbillTablegetData->c_netamt) . ' (' . $amountInWords . '). in final settlement of work.';
$BillReport .= '<div style="display: flex; margin-bottom: 100px;">'; // Flexbox for layout control
$BillReport .= '<div style="width: 50%; padding-top: 80px; display: block;">'; // Half width for date
$BillReport .= 'Witness'; // Your date content here
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; padding-top: 80px; display: block;">'; // Half width for date
$BillReport .= '<div>Stamp</div>'; // Your signature content here with space
$BillReport .= '<div style="margin-top: 30px;">Payees dated signature</div>'; // Additional line without <br> with space using margin-top
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '</td>';
$BillReport .= '</tr>';



$BillReport .= '<tr>';

$BillReport .= '<td  style="border: 1px solid black; padding: 8px; text-align:left;  word-wrap: break-word;">';

$BillReport .= '<div style="display: flex; ">'; // Flexbox for layout control
$BillReport .= '<div style="width: 50%;">'; // Half width for date
$BillReport .= 'Pay by cash / cheque Rs.( __________________ ) Rupees<br>Dated:'; // Your date content here
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; text-align: right;">'; // Half width for signature
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '<div style="display: flex; padding-top: 40px;">'; // Flexbox for layout control
$BillReport .= '<div style="width: 50%;  height: 20px; text-align:left">'; // Half width for date
$BillReport .= 'Officer authorizing payment';
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; text-align: right; height: 270px; text-align:center">'; // Half width for signature
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '</td>';
$BillReport .= '<td  style="border: 1px solid black; padding: 8px; text-align:left;  word-wrap: break-word;">';

$BillReport .= '<div style="display: flex; ">'; // Flexbox for layout control
$BillReport .= '<div style="width: 50%;">'; // Half width for date
$BillReport .= 'Paid by me by cash / vide cheque No.<br>Dated:'; // Your date content here
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; text-align: right;">'; // Half width for signature
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '<div style="display: flex; padding-top: 20px;">'; // Flexbox for layout control
$BillReport .= '<div style="width: 50%;  height: 160px; text-align:left">'; // Half width for date
$BillReport .= 'Dated Initials of person making the payment';
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; text-align: right; height: 20px; text-align:center">'; // Half width for signature
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '</td>';

$BillReport .= '</tr>';



    $BillReport .= '</table>';

    $BillReport .= '</tr>';


    $BillReport .= '</table>';

    $BillReport .= '</div>';

    }
    else
    {
       //dd("Okkkkkk");
        $BillReport .= '<h5 style="text-align: center; font-weight:bold; font-size:25px; padding: 8px; word-wrap: break-word;">Z. P. FORM - 58 - C </h5>';
        $BillReport .= '<h1 style="text-align: center; font-size:20px; word-wrap: break-word;">(See Rule 174)</h1>';
        $BillReport .= '<h1 style="text-align: center; margin-bottom:50px; font-size:20px; word-wrap: break-word;">'.$workdata->Div.'</h1>';
        $BillReport .= '<div class="table-responsive">';
        $BillReport .= '<table>';

        $BillReport .= '<tr>';
        $BillReport .= '<th style="width: 50%; text-align: center;  word-wrap: break-word;">Notes</th>';
        $BillReport .= '<th  style="padding-left: 200px; width: 50%; word-wrap: break-word;">'.$workdata->Sub_Div.'</th>';
        $BillReport .= '</tr>';
        $BillReport .= '<tbody>';

        $BillReport .= '<tr>';
        $BillReport .= '<td>';
        $BillReport .= '<p style="padding: 8px; width: 100%;">(For Contractors and suppliers. This form provides only for payment for work or suppliesctually measured.)<br> 1. The full name of the work as given in the estimate should be entered against the line "Name of work" except in the case of bills for "Stock" materials.<br></br>
                        2. The purpose of supply applicable to thecase should be filled in and rest scored out.</br>3. If the outlay on the work is recorded by sub-heads, the total for each sub-head should be shown on Column 5 and against this total, there should be an entry in Column 6 also. In no other case should any entries be made in Column 6.</br></br></p>';
        $BillReport .= '</td>';

        $BillReport .= '<td>';
        $BillReport .= '<p style="padding-left: 50px;">==============================================================================</p>';
        $BillReport .= '<p style="width: 100%; text-align: justify; padding-left: 50px;"><b> RUNNING ACCOUNT BILL - C </b> </p>';
        $BillReport .= '<p style="width: 100%; text-align: justify; padding-left: 50px;"> Cash Book Voucher No:';
        $cvno=$Billinfo->cv_no;
        if ($cvno) {
            $BillReport .=  '' . $Billinfo->cv_no .'';
        }
        $BillReport .= '</p><p style="width: 100%; text-align: justify; padding-left: 50px;">====================================================================</p >';
        $BillReport .= '<p style="width: 100%; text-align: justify; padding-left: 50px;">Name of Contractor  / Piece-worker / Supplier : '.$workdata->Agency_Nm.','.$Agency_Pl->Agency_Pl.'  </p>';
        $BillReport .= '</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td>';
        $BillReport .= '<p style="width: 50%; padding-left: 90px;  text-align: justify;">Memorandum of Payments</p>';
        $BillReport .= '<p style="text-align: justify;">4. The figures against (k) should be test to see that it agrees with the total if Items 4&55. If the net amount is to be paid is lessthan Rs 10 and it cannot be included in a cheque, the payment should be made in cash, thepay order being altered suitably any alterati-on attested by dated initials.</br></br>6. The payes acknowledgement should be forthe gross amount paid as per Item 5, i.e.a+b+c</br></br> 7. Payment should be attested by some known person when the payes acknowledgement is given by a mark seal or thumb impression.</br></br> 8. The column "Figures for Works Abstract" is not required in the case of bills of supplies.</br>
        =============================================================================</p>';
        $BillReport .= '<td style="padding-left: 50px;">';
        $BillReport .= '<p>Name of work :</p>';
        $BillReport .= '&nbsp; &nbsp; &nbsp; &nbsp;'. $workdata->Work_Nm . '</p>';
        $BillReport .= '<p style="text-align: justify;">Purpose of Supply :</p>';
        $BillReport .= '<p style="text-align: justify;"> Serial No of this bill :'.$rbbillno.' '.$rbbiill.'</p>';

        if($prev_rbbillno===0)
        {
            // dd($prev_rbbillno);
            $BillReport .= '<p style="text-align: justify;"> No and date of last :  - R.A. Bill paid vide bill for this work :  C.V. No : <br></p>';
        }
        else{
            $BillReport .= '<p style="text-align: justify;"> No and date of last :'.$prev_rbbillno.'  R.A. Bill paid vide bill for this work :  C.V. No : <br></p>';
        }
        $cvno=$Billinfo->cv_no;
        if ($cvno) {
            $BillReport .=  '' . $Billinfo->cv_no .'';
        }
        $cvdate=$Billinfo->cv_dt;
        $BillReport .= '<p style=" text-align: justify;">for</p>';

        if ($cvdate) {
            //dd($cvdate);
            $date1=date_create($cvdate);
            $formattedDate = $date1->format('d/m/Y');
            $date=date_create($formattedDate);
            $dt2=date_format($date,"M/Y");
            // dd($dt2);
            $BillReport .= $dt2;
        }

        $BillReport .= '<p style=" text-align: justify;" > Reference to agreement : ' . $workdata->Agree_No . '.';
        $OcommenceDate = date('d/m/Y', strtotime($workdata->Wo_Dt));
        $dueDate = date('d/m/Y', strtotime($workdata->Stip_Comp_Dt));
        $BillReport .= '<p style=" text-align: justify;" > Date of order to commence the work : '.$OcommenceDate.'.';
        $BillReport .= '</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style="text-align: top;">Account Classification -  <b>'.DB::table('fundhdms')->where('F_H_id', $workdata->F_H_id)->value('F_H_CODE').'</b>';
        $BillReport .= '<p style= text-align: justify;">PLAN WORKS/NON-PLAN WORKS <br>Minor Head | ORIGINAL WORKS Communication/Head | Repair & Maint (a) Buildings (a)Sub Head or Detailed Head<br>
        ==========================================================================</p>';

        $BillReport .= '</td>';
        $BillReport .= '<td style="padding-left: 50px;">';
        $BillReport .= '<p style="text-align: justify;" > Due date of completion of work :'.$dueDate.'<br>Extensions granted, if any, - - - <br>
                        from time to time with - - -<br>reference to authority - - <br>  Actual date of completion :';
        if ($workdata->actual_complete_date) {

            $Act_dt_compli = date('d/m/Y', strtotime($workdata->actual_complete_date));
            $BillReport .=  "' . $Act_dt_compli .'";
        }
        $BillReport .= '</p></td>';
        $BillReport .= '</tr>';


        $BillReport .= '<tr>';
        $BillReport .= '<td style= text-align: justify;">';

        // $BillReport .= '</0l>';
        $BillReport .= '</td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        $BillReport .= '<td style=" text-align: justify;">';
        $BillReport .= '<p style=" text-align: justify;">Provisions during the current year         Rs...........</p>';
        $BillReport .= '<p style=" text-align: justify;">Expenditure incurred </p>';
        $BillReport .= '<p style=" text-align: justify;">during the current year        Rs..........</p>';
        $BillReport .= '<p style=" text-align: justify;">Balance available Rs.......</p>';
        $BillReport .= '<p style=" text-align: justify;">a) Score out what is not applicable</p>';
        $BillReport .= '</td>';

        $BillReport .= '<td style="padding-left: 50px;">';
        $BillReport .= '<p>1) Security Deposit to be recovered as per agreement<br> 2) Security Deposit previously recovered <br> 3) Security Deposit to be recovered from this bill<br>4) Balance to be recovered</p>';
        $BillReport .= '</td>';
        $BillReport .= '</tr>';

        $BillReport .= '</table>';
        $BillReport .= '</div><br>';
        //-----------------------------------------------------------------------------------------------------------------------------------------------------------
        // Next Page---------------------------------------------------------------------------

        $BillReport .= '<div style="page-break-before: always;"></div>';
        $BillReport .= '<div class="table-responsive">';


        $BillReport .= '<table class="table table-bordered table-collapse" style="border: 1px solid black; border-collapse: collapse; margin: 0;">';
        $BillReport .= '<thead>';
        // $BillReport .= '<br><br><br><table>';
        $BillReport .= '<tr>';
        $BillReport .= '<th  colspan="4" style="width: 60%; text-align: left; word-wrap: break-word;">' . $workdata->Div . '</th>';
        $BillReport .= '<th  colspan="3" style="width: 40%; text-align: right;  word-wrap: break-word;">' . $workdata->Sub_Div . '</th>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<th  colspan="2" style=" width: 60%; text-align: justify; ">Name of Work:  </th>';
        $BillReport .= '<th  colspan="5" style=" width: 40%; text-align: left; word-wrap: break-word;">'.$workdata->Work_Nm . '</th>';
        $BillReport .= '</tr>';
        // $BillReport .= '</table>';


        $BillReport .= '<tr>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 3%; word-wrap: break-word;">Unit</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:justify; width: 8%; word-wrap: break-word;">Quantity executed up-to-date as per Measurement Book</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 55%; word-wrap: break-word;">Item of Work</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Bill<br>----------------<br>tender Rate Rs.</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:justify; width: 8%; word-wrap: break-word;">Payments of Actual up-to-date Rs.</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:justify; width:8%; word-wrap: break-word;">On the basis of measurements Since the previous Bill Rs.</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Remark</td>';
        $BillReport .= '</tr>';
        $BillReport .= '</thead>';
        $BillReport .= '<tbody>';


        //For Royalty Surcharg Items..........
        $royaltylab = [ "001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566",
                "004350", "003940", "003941", "004346", "004348", "004347"];

        $NormalData = DB::table('bil_item')
        ->where('t_bill_id', $tbillid)
        ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
         ->orderBy('t_item_no', 'asc') // Ordering by 'id' in ascending order
        ->get();
        // dd($NormalData);

        if($NormalData){
            $header1=$this->commonforeachview($NormalData,$tbillid,$work_id);
            //dd($header1);
        $BillReport .=$header1;

        $abpc = $workdata->A_B_Pc;
        $abobelowatper=$workdata->Above_Below;
        
         $convert = new Commonhelper();


        if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
            $BillReport .= '<tr>';
            $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Total Part A Amount</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->part_a_amt) . '</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_part_a_amt) . '</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';
        }

        if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
            $BillReport .= '<tr>';
            $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Tender Above Below Result: ' . $workdata->A_B_Pc . ' ' . $workdata->Above_Below . '</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_abeffect) . '</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->a_b_effect) . '</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';
        }

        $BillReport .= '<tr>';
        $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->gst_base) . '</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_gstbase) . '</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>GST Amount ' . $Billinfo->gst_rt . '%</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->gst_amt) . '</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_gstamt) . '</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
        $BillReport .= '</tr>';
        }
        $RoyaltyData = DB::table('bil_item')
        ->where('t_bill_id', $tbillid)
        ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
         ->orderBy('t_item_no', 'asc') // Ordering by 'id' in ascending order
        ->get();

        //dd($RoyaltyData);
        if (!$RoyaltyData->isEmpty()) {
            // dd("Okkk");
            $header1=$this->commonforeachview($RoyaltyData,$tbillid,$work_id);
            // dd($header1);
            $BillReport .=$header1;
            // $BillReport .= '<table>';
            // $BillReport .= '<tbody>';

            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->part_b_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_part_b_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
            $BillReport .= '</tr>';

            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Grand Total</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->bill_amt_gt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_billamtgt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
            $BillReport .= '</tr>';

            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->bill_amt_ro) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_billamtro) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
            $BillReport .= '</tr>';

            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->net_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_netamt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
            $BillReport .= '</tr>';

            // $c_netamt=$this->convertAmountToWords($Billinfo->c_netamt);
            $commonHelper = new CommonHelper();
            $c_netamt = $commonHelper->convertAmountToWords($Billinfo->c_netamt);

            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black;  padding: 8px; width:66%; text-align: right; word-wrap: break-word;"> In  Word <strong>('.$c_netamt.' ) </strong></td>';
            $BillReport .= '<th colspan="1" style="border: 1px solid black;  padding: 8px; width: 8%; text-align: right; word-wrap: break-word;"></th>';
            $BillReport .= '<th colspan="1" style="border: 1px solid black;  padding: 8px; width: 8%; text-align: right; word-wrap: break-word;"></th>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black;  padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
            $BillReport .= '</tr>';

            // $BillReport .= '</tbody>';
            // $BillReport .= '</table>';
        }
        // $BillReport .= '</tbody>';
        // $BillReport .= '</table>';


        // $BillReport .= '<table style="border-collapse: collapse; border: none; margin-left: 30px; margin-right: 30px; text-align:center;">';
        // $BillReport .= '<tbody>';
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="7" style="padding: 8px; background-color: #f2f2f2; text-align:left; width: 55%; word-wrap: break-word;"> ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br>
                        The measurements made by '.$JE_nm->name.' , '.$JE_nm->designation.' on '.$dates.' and are recorded at
                        Measurement Book No '.$work_id.' No advance payment has been made previously
                        without detailed measurements.</td>';
        $BillReport .= '</tr>';
        // $BillReport .= '</tbody>';
        // $BillReport .= '</table>';


        // $BillReport .= '<table style="border-collapse: collapse; border: none; margin-left: 80%; margin-right: 30px; text-align:center;">';
        // $BillReport .= '<tr >';



        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="5" style="padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';

        $BillReport .= '<td colspan="2" style="width: 200px; height: 60px; text-align:center"> <img src="' . $imageSrcDYE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;">';

        $BillReport .= '<br>'.$DYE_nm->designation.'';

        $BillReport .= '<br>'.$workdata->Sub_Div.'';

        $BillReport .= '<br> * Dated Signature of Officer preparing bill';
        $BillReport .= '</tr>';
        // $BillReport .= '</tbody>';
        // $BillReport .= '</table>';

        // $BillReport .= '<table>';
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="7" style="text-align: center;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="5" style="border-collapse: collapse; text-align:left;">  Dated : </td>';
        $BillReport .= '<td colspan="2" style="border-collapse: collapse; text-align:center;">  Countersigned  </td>';
        // $BillReport .= '<td colspan="1" style="border-collapse: collapse; text-align:left;"> </td>';
        $BillReport .= '</tr><br><br><br>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="5" style="border-collapse: collapse; text-align:bottom;"> Dated Signature of the Contractor </td>';
        $BillReport .= '<td colspan="2" style="height: 60px; text-align:center;"> <img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>'.$EE_nm->designation.'<br>  '.$workdata->Div.'</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="7" style="font-size=1px;"> The second signature is only necessary when the officer who prepares the bill is not the officer who makes the payment. </td>';
        $BillReport .= '</tr>';
        $BillReport .= '</tbody>';

        $BillReport .= '</table>';
        $BillReport .= '</div>';


//Last Page-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    $BillReport .= '<div style="page-break-before: always;"></div>';
    $BillReport .= '</div><h6 style="text-align: center; font-weight:bold;  word-wrap: break-word;">III - Memorandum of Payments </h6>';
    $BillReport .= '<h2 style="text-align: center; font-weight:bold; word-wrap: break-word;">=========================================================</h2>';

    $BillReport .= '<p style="text-align: left">1. Total Value of work done, as per Account-I, Column 5, Entry (A)</p>';
    $BillReport .= '<p style="text-align: left;">2. Deduct Amount withheld :</p>';

    $BillReport .= '<div class="table-responsive">';

    $BillReport .= '<table>';
    $BillReport .= '<tbody>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;">----------------<br>Figures for<br> Work abstract<br>-----------------</td>';
    $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">(a) From previous bill as per last Running Account Bill</td>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: right;">----------------<br>Rs. &nbsp &nbsp &nbsp Ps.<br>----------------</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
    $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">(b) From this bill</td>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: right;">----------------</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
    $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;"> 3. Balance, i.e. "Up-to-date" payments (Items 1 - 2)</td>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;">(K)</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
    $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; word-wrap: break-word;"> Total amount of payments already made as per entry<br>
    of last Running Account Bill No.<br>
    forwarded with accounts for</td>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;">(K)</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
    $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">5. Payments now to be made as detailed below :-(K)</td>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;">--------</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
    $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;"> (a) By recovery of amounts creditable to this work -(a)<br>
    Value of stock supplied as detailed<br>
    in the ledger in (a)</td>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
    $BillReport .= '</tr>';
    $BillReport .= '</tbody>';
    $BillReport .= '</table>';

    $BillReport .= '<table>';
    $BillReport .= '<tbody>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="margin-right: 30px; text-align: left;">-----------------------------------------------------------------------------------------------------------------------------------------------</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left"></td>';
    $BillReport .= '<td style="width: 50%; margin-left: 10%; text-align: left;">Total 2(b) + 5(a) (G)</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 70%; margin-right: 10px; text-align: left;">------------------------------------------------------------------------------------------------------------------------------------------</td>';
    $BillReport .= '</tr>';

    $BillReport .= '</tbody>';
    $BillReport .= '</table>';


$BillReport .= '<table>';
$BillReport .= '<tbody>';

$DedMaster_Info=DB::table('dedmasters')->select('Ded_M_Id')->get();
//  dd($DedMaster_Info);

$billDed_Info=DB::table('billdeds')->where('t_bill_Id' , $tbillid)->get('Ded_M_Id');
// dd($billDed_Info);

$sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
// dd($sammarydata);
$C_netAmt= $sammarydata->c_netamt;
$chqAmt= $sammarydata->chq_amt;
$commonHelper = new CommonHelper();
$amountInWords = $commonHelper->convertAmountToWords($C_netAmt);
// dd($amountInWords);

$SecDepositepc = DB::table('dedmasters')->where('Ded_M_Id', 2)->value('Ded_pc') ?: '';
$CGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 3)->value('Ded_pc') ?: '';
$SGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 4)->value('Ded_pc') ?: '';
$Incomepc = DB::table('dedmasters')->where('Ded_M_Id', 5)->value('Ded_pc') ?: '';
$Insurancepc = DB::table('dedmasters')->where('Ded_M_Id', 7)->value('Ded_pc') ?: '';
$Labourpc = DB::table('dedmasters')->where('Ded_M_Id', 8)->value('Ded_pc') ?: '';
$AdditionalSDpc = DB::table('dedmasters')->where('Ded_M_Id', 9)->value('Ded_pc') ?: '';
$Royaltypc = DB::table('dedmasters')->where('Ded_M_Id', 10)->value('Ded_pc') ?: '';
$finepc = DB::table('dedmasters')->where('Ded_M_Id', 11)->value('Ded_pc') ?: '';
$Recoverypc = DB::table('dedmasters')->where('Ded_M_Id', 13)->value('Ded_pc') ?: '';

// Check if any value is 0 and assign an empty string
$SecDepositepc = $SecDepositepc != 0 ? $SecDepositepc . '%' : '';
$CGSTpc = $CGSTpc != 0 ? $CGSTpc . '%' : '';
$SGSTpc = $SGSTpc != 0 ? $SGSTpc . '%' : '';
$Incomepc = $Incomepc != 0 ? $Incomepc . '%' : '';
$Insurancepc = $Insurancepc != 0 ? $Insurancepc . '%' : '';
$Labourpc = $Labourpc != 0 ? $Labourpc . '%' : '';
$AdditionalSDpc = $AdditionalSDpc != 0 ? $AdditionalSDpc . '%' : '';
$Royaltypc = $Royaltypc != 0 ? $Royaltypc . '%' : '';
$finepc = $finepc != 0 ? $finepc . '%' : '';
$Recoverypc = $Recoverypc != 0 ? $Recoverypc . '%' : '';



$deductionAmount=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->get();
// dd($deductionAmount);
$additionalSDAmt=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->where('Ded_Head','Additional S.D')->value('Ded_Amt');
$additionalSDAmt = $additionalSDAmt ? $additionalSDAmt : '0.00';
// dd($additionalSDAmt);
$Security=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Security Deposite')
->value('Ded_Amt');
$Security = $Security ? $Security : '0.00';
// dd($Security);
$Income=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Income Tax')
->value('Ded_Amt');
$Income = $Income ? $Income : '0.00';
// dd($Income);
$CGST=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','CGST')
->value('Ded_Amt');
$CGST = $CGST ? $CGST : '0.00';
// dd($CGST);
$SGST=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','SGST')
->value('Ded_Amt');
$SGST = $SGST ? $SGST : '0.00';
// dd($SGST);
$Insurance=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Work Insurance')
->value('Ded_Amt');
$Insurance = $Insurance ? $Insurance : '0.00';
// dd($Insurance);
$Labour=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Labour cess')
->value('Ded_Amt');
$Labour = $Labour ? $Labour : '0.00';
// dd($Labour);
$Royalty=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Royalty Charges')
->value('Ded_Amt');
$Royalty = $Royalty ? $Royalty : '0.00';
// dd($Royalty);
$fine=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','fine')
->value('Ded_Amt');
$fine = $fine ? $fine : '0.00';
// dd($fine);
$Recovery=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Audit Recovery')
->value('Ded_Amt');
$Recovery = $Recovery ? $Recovery : '0.00';
// dd($Recovery);
$BillReport .= '<tr colspan=12 style="margin-top:5%;">';
$BillReport .= '<td colspan=12 style="margin-left:25%;">';
$BillReport .= '<div style="text-align: center; margin-top: 20px;">';
$BillReport .= '<table style="border: 1px solid black; border-collapse: collapse; margin: auto; height: 50%;">';
$BillReport .= '<thead>';
$BillReport .= '<tr>'; // Open a table row within the thead section
$BillReport .= '<td style="border: 1px solid black; : 8px;">Amount</td>';
$BillReport .= '<td style="border: 1px solid black;">Details</td>';
$BillReport .= '</tr>'; // Close the table row within the thead section
$BillReport .= '</thead>';
$BillReport .= '<tbody>';

$BillReport .='<tr >';
$BillReport .= '<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($additionalSDAmt).'</td>';
$BillReport .='<td style="border: 1px solid black;text-align:left;">Additional S.D: &nbsp;&nbsp;&nbsp; '.$AdditionalSDpc.'</td>';
$BillReport .='</tr>';
$BillReport .='<tr >';
$BillReport .='<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Security) .'</td>';
$BillReport .='<td style="border: 1px solid black;">Security Deposite: &nbsp;&nbsp;&nbsp; '.$SecDepositepc.'</td>';
$BillReport .='</tr>';
$BillReport .='<tr >';
$BillReport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Insurance) .'</td>';
$BillReport .='<td style="border: 1px solid black; text-align:left;">Insurance: &nbsp;&nbsp;&nbsp; '.$Insurancepc.'</td>';
$BillReport .='</tr>';
$BillReport .='<tr >';
$BillReport .='<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Labour) .'</td>';
$BillReport .='<td style="border: 1px solid black;text-align:left;">Labour Cess: &nbsp;&nbsp;&nbsp; '. $Labourpc.'</td>';
$BillReport .='</tr>';
$BillReport .='<tr >';
$BillReport .='<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Income) .'</td>';
$BillReport .='<td style="border: 1px solid black; text-align:left;">Income Tax: &nbsp;&nbsp;&nbsp; '. $Incomepc.'</td>';
$BillReport .='</tr>';
$BillReport .='<tr >';
$BillReport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($CGST) .'</td>';
$BillReport .='<td style="border: 1px solid black; text-align:left;">CGST: &nbsp;&nbsp;&nbsp; '.$CGSTpc.'</td>';
$BillReport .='</tr>';
$BillReport .='<tr >';
$BillReport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($SGST) .'</td>';
$BillReport .='<td style="border: 1px solid black;text-align:left;">SGST: &nbsp;&nbsp;&nbsp; '. $SGSTpc.'</td>';
$BillReport .='</tr>';
$BillReport .='<tr >';
$BillReport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Royalty) .'</td>';
$BillReport .='<td style="border: 1px solid black; text-align:left;">Royalty:  charges &nbsp;&nbsp;&nbsp;'. $Royaltypc.'</td>';
$BillReport .='</tr>';
$BillReport .='<tr >';
$BillReport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($fine) .'</td>';
$BillReport .='<td style="border: 1px solid black; text-align:left;">Fine:  &nbsp;&nbsp;&nbsp; '. $finepc.'</td>';
$BillReport .='</tr>';
$BillReport .='<tr >';
$BillReport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Recovery) .'</td>';
$BillReport .='<td style="border: 1px solid black;text-align:left;">Audit Recovery:  &nbsp;&nbsp;&nbsp; '. $Recoverypc.'</td>';
$BillReport .='</tr>';

$BillReport .='<tr>';
$BillReport .='<td style="border: 1px solid black; text-align:right;"> '. $commonHelper->formatIndianRupees($chqAmt) .' </td>';
$BillReport .='<td style="border: 1px solid black; text-align:left;">Cheque Amount &nbsp;&nbsp;&nbsp;  </td>';
$BillReport .='</tr>';
$BillReport .='<tr>';
$BillReport .='<td style="border: 1px solid black; text-align:right; "> '. $commonHelper->formatIndianRupees($C_netAmt) .' </td>';
$BillReport .='<td style="border: 1px solid black; text-align:left;">Total &nbsp;&nbsp;&nbsp;</td>';
$BillReport .='</tr>';

// Inner table end.......
$BillReport .= '</tbody>';
$BillReport .= '</table>';
$BillReport .= '</div>';
$BillReport .= '</td>';

$BillReport .= '<td colspan=12 style="text-align: right;">';
$BillReport .= '<table>';
$BillReport .= '<thead>';

$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  (b) By recovery of amounts creditable to other<br> &nbsp  works or heads of account(b)</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';

$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  1) Security Deposit</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';

$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  2) Income Tax -  ------%   </td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';

$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  3) Surcharge - --------%</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';

$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  4) Education cess - ----------%</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';


$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  5) M. Vat - 2 / 4   ---------%</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';

$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">  6) Royalty</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';


$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">   7) Insurance - 1 %</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';

$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">   8) Deposit</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';

$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">   9)---------</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';

$BillReport .= '<tr>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;">   10) -------------------</td>';
$BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
$BillReport .= '</tr>';

$BillReport .= '</thead>';
$BillReport .= '</table>';
$BillReport .= '</td>';
//Main tr..................
$BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left;"></td>';
    $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left;>  (c) By Cheque</td>';
    $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; "></td>';
    $BillReport .= '</tr>';
    $BillReport .= '</tbody>';
    $BillReport .= '</table>';

    // $BillReport .= '<tr>';
    $BillReport .= '<p style="width: 100%; margin-right: 30px; text-align: left;">   --------------------------------------------------------------------------------------------------------------------------------------------------------</p>';
    // $BillReport .= '</tr>';

    $BillReport .= '<p style="text-align: justify; margin-left: 10%;">Total 5(b) + 5(c) &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; (H)
     </p>';

     $BillReport .= '<p style="text-align: justify; margin-left: 10%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
     </p>';

     $BillReport .= '<p style="text-align: justify; margin-left: 10%; ">Pay Rs. *( &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ) By Cheque
     </p><br><br>';


     $BillReport .= '<table>';
     $BillReport .= '<tbody>';

     $BillReport .= '<tr>';
     $BillReport .= '<td style="text-align: justify; margin-left: 10%;">Received Rs. *(  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ) As per above</td>';
     $BillReport .= '</tr><br>';

     $BillReport .= '<tr>';
     $BillReport .= '<td style="text-align: left;">Memorandum on account of this work<br>&nbsp; &nbsp;  &nbsp; &nbsp; Dated /  /  </td>';
     $BillReport .= '<td style="text-align: left;">Stamp</td>';
     $BillReport .= '</tr>';

     $BillReport .= '<tr>';
     $BillReport .= '<p style="width:100%; text-align:left; padding-left:10px;">Witness</p>';
     $BillReport .= '<p style="width:100%; text-align:right;"><img src="' . $imageSrcAgency . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>(Full signature of the contractor)</p>';
     $BillReport .= '</tr>';

     $BillReport .= '</tbody>';
     $BillReport .= '</table>';

     $BillReport .= '<table>';
     $BillReport .= '<tbody>';

     $BillReport .= '<tr>';
     $BillReport .= '<td style="text-align: justify; margin-left: 10%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
     $BillReport .= '</tr>';

     $BillReport .= '<tr>';
     $BillReport .= '<td colspan=4 style="text-align: left;">Paid by me, vide cheque No.</td>';
     $BillReport .= '<td colspan=3 style="text-align: right;">Dated / / </td>';
     $BillReport .= '</tr>';

     $BillReport .= '<tr>';
     $BillReport .= '<td style="text-align: center; text-align: right; padding-left:10px;">(Dated initials of the person actually making the payments.)</td>';
     $BillReport .= '</tr>';

     $BillReport .= '<tr>';
     $BillReport .= '<td style="text-align: justify; padding-right:10%; ">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
     $BillReport .= '</tr>';

    $BillReport .= '</tbody>';
    $BillReport .= '</table>';
    $BillReport .= '</div>';



    $BillReport .= '<p style="text-align: center; font-weight:bold;  word-wrap: break-word;">IV - Remarks </p>';
    $BillReport .= '<p style="text-align: justify; ">(This space is reserved for any remarks the disbursing officer or the Executive Engineer may
    wish to record in respect of the execution of the work, check of measurements or the state of
    contractor s accounts.) </p>';

    }
    return view('reports/Bill' ,compact( 'embsection2' ,'tbillid', 'BillReport'));
}

public function Billreportpdf(Request $request , $tbillid)
{
    // $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    $Billinfo=DB::table('bills')
    ->where('t_bill_Id' , $tbillid)
    ->select('work_id','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
            'part_b_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
            'c_part_b_amt','bill_amt_gt','c_billamtgt','bill_amt_ro','c_billamtro','net_amt','gst_base','c_netamt','p_net_amt','c_gstbase','gst_rt','gst_amt')->first();
    // dd($Billinfo);
    // $Billinfo->gst_base=$Billinfo->gst_base=0;
    $work_id=$Billinfo->work_id;
    //dd($work_id);
    $workdata=DB::table('workmasters')->where('Work_Id' , $work_id)->first();
    $Maxdt=DB::table('embs')->where('t_bill_Id' , $tbillid)->max('measurment_dt');
    $dates = Carbon::createFromFormat('Y-m-d',  $Maxdt)->format('d/m/Y');
    //dd($dates);

    $Agency_Pl=DB::table('agencies')->where('id' , $workdata->Agency_Id)->select('Agency_Pl','agencysign')->first();
    $JE_nm=DB::table('jemasters')->where('jeid' , $workdata->jeid)->select('name','designation','sign')->first();
    $DYE_nm=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation','sign')->first();
    $EE_nm=DB::table('eemasters')->where('eeid' , $workdata->EE_id)->select('name','subname','designation','sign')->first();
    // $DYE_Design=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();


    $imagePathj = public_path('Uploads/signature/' . $JE_nm->sign);
    $imageData1 = base64_encode(file_get_contents($imagePathj));
    $imageSrcJE = 'data:image/jpeg;base64,' . $imageData1;

    $imagePathd = public_path('Uploads/signature/' . $DYE_nm->sign);
    $imageData2 = base64_encode(file_get_contents($imagePathd));
    $imageSrcDYE = 'data:image/jpeg;base64,' . $imageData2;

    $imagePathEE = public_path('Uploads/signature/' . $EE_nm->sign);
    $imageData3 = base64_encode(file_get_contents($imagePathEE));
    $imageSrcEE = 'data:image/jpeg;base64,' . $imageData3;

    //  dd($Agency_Pl->agencysign);
    $imagePatha = public_path('Uploads/signature/' . $Agency_Pl->agencysign);
    $imageData4 = base64_encode(file_get_contents($imagePatha));
    $imageSrcAgecy = 'data:image/jpeg;base64,' . $imageData4;


    //dd($DYE_nm);
    $headercheck='Bill';
    $cvno=$Billinfo->cv_no;
    // dd($cvno);
    $isFinalBill=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('final_bill','t_bill_No')->first();
    // dd($isFinalBill);
    $FirstBill=$isFinalBill->t_bill_No;
    $FinalBill=$isFinalBill->final_bill;
    //dd($FirstBill,$FinalBill);
    // $header=$this->commonheader();
    $rbbillno=CommonHelper::formatNumbers($FirstBill);
    $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);



    // $header=$this->commonheader();
    $rbbillno=CommonHelper::formatTItemNo($FirstBill);
    $rbbiill=CommonHelper::getBillType($FinalBill);

    $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);


    // dd($prev_rbbillno);
    $royaltylab = [ "001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566",
                "004350", "003940", "003941", "004346", "004348", "004347"];

    $NormalData = DB::table('bil_item')
    ->select('exec_qty', 'item_unit', 'item_desc', 'bill_rt', 'b_item_amt', 't_bill_id', 'item_id', 't_item_no', 'sub_no')
    ->where('t_bill_id', $tbillid)
    ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();
    // dd("Okkkkk");
    //  dd($NormalData);

    $DBWorkId=DB::table('bills')
    ->where('t_bill_Id',$tbillid)
    ->value('work_id');
    // dd($DBWorkId);
    $DBaboveBellow=DB::table('workmasters')
    ->select('Above_Below','A_B_Pc')
    ->where('Work_Id',$DBWorkId)
    ->first();
    // dd($DBaboveBellow);

    $FINALBILL =DB::table('bil_item')
    ->select('exec_qty','item_unit','item_desc','bill_rt','b_item_amt','t_bill_id','item_id','t_item_no','sub_no')
    ->where('t_bill_id',$tbillid)
    ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();
    // dd($FINALBILL);


    $DBbillTablegetData=DB::table('bills')
    ->select('c_part_a_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
    'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')
    ->where('t_bill_Id',$tbillid)
    ->first();
    // dd($DBbillTablegetData);
    // dd($header);
    $BillReport= '';

    $paymentInfo = "$tbillid";
    
    
    
    
$qrCode = QrCode::size(90)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(10)
->generate($paymentInfo);


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);


$BillReport .= '<div style="position: absolute; top: 2%; left: 87%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">';


    // $BillReport .=$header;
    if($FirstBill==1 && $FinalBill==1){
    $BillReport .= '<h5 style="text-align: center; font-weight: bold; font-size: 15px; word-wrap: break-word;">FORM - 55 : First And Final Bill</h5>';

    $BillReport .= '<table style="margin-left: 20px; font-size: 13px; width: 100%;">';
    $BillReport .= '<thead></thead>';
    $BillReport .= '<tbody>';

    $BillReport .= '<tr>';
    $BillReport .= '<th style="word-wrap: break-word;">Notes</th>';
    $BillReport .= '<th  style="padding-left: 20px; word-wrap: break-word; text-align: left;">'.$workdata->Sub_Div.'</th>';
    $BillReport .= '</tr>';
    $BillReport .= '</thead>';
    $BillReport .= '<tbody>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="width: 50%; text-align: justify;">';
    $BillReport .= '<p style="width: 100%;">(For Contractors and suppliers: To be used when a single payment is made for a job or contract, i.e. only on its completion. A single form may be used generally for making first & final payments to several works or supplies if they pertain to the same time. A single form may also be used for making first & final payment to several piece-workers or suppliers if they relate to the same work and billed at the same time. In this case, column 2 should be subdivided into two parts, the first part for "Name of Contractor / Piece-worker / Supplier: ABC Constructions, Sangli" and the second for "Items of work" etc.) and the space in Remarks column used for obtaining acceptance of the bill & acknowledgments of the amount paid to different piece-workers or suppliers.<br><br><br></p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="width: 50%; padding-left: 20px;">';
    $BillReport .= '<p style="width: 50%;"> Cash Book Voucher No<br><br>';
    if ($cvno) {
        $BillReport .=  "' . $Billinfo->cv_no .'";
    }
    if ($Billinfo->cv_dt) {
        $BillReport .=  "' . $Billinfo->cv_dt .'";
    }
    $BillReport .= '</p><p>For<br><br></p><p>Name of Contractor  / Piece-worker / Supplier : '.$workdata->Agency_Nm.''.$Agency_Pl->Agency_Pl.'  </p>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="text-align: justify;">';
    $BillReport .= '<p style="text-align: justify;">1. In the case of payments to suppliers, red ink entry should be made across the page above the entries relating thereto in one of the following forms applicable to the case,(i) Stock, No.: B1/HO/1234(ii) Purchase for Stock,(iii) Purchase for Direct issue to work,(iv) Purchase for work issued to contractor on<br><br><br></p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 20px;">';
    
     $agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';
     
    $BillReport .= '<p style="display: inline; padding-left: 10px;">* Agreement / Rate List / Requisition  </p><p>No.: '.$workdata->Agree_No.'    ' . ($agreementDate ? 'Date: ' . $agreementDate : '') . '</p><br><p style="display: inline; padding-left: 50px;">No. : '. $workdata->Agree_No.'    ' . ($agreementDate ? 'Date: ' . $agreementDate : '') . '</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="text-align: justify;">';
    $BillReport .= '<p style="text-align: justify;">2. In the case of works, the accounts of which are kept by subheads, the amount relating to all items of work following under the same "sub-head" should be totaled in red ink.<br><br><br></p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 15px; text-align: justify;">';
    $BillReport .= '<p>Name of work : '. $workdata->Work_Nm . '</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="text-align: justify;">';
    $BillReport .= '<p style="text-align: justify;">3. Payment should be attested by some known person when the payee\'s acknowledgment is given by a mark, seal, or thumb impression.<br><br><br></p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="text-align: justify;">';
    $BillReport .= '<p style="text-align: justify;">4. The person actually making the payment should initial (and date) the column provided for the purpose against each payment.<br><br><br></p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px;">';
    $BillReport .= '<p><b>Account Classification :-</b></p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="text-align: justify;">';
    $BillReport .= '<p style="text-align: justify;">Audit / Account Enfacement</p><br><br><br>';
    $BillReport .= '<p style="text-align: justify;">Checked<br><br><br></p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 10px;">';
    $BillReport .= '<p>PLAN WORKS<br><br></p>';
    $BillReport .= '<p>NON-PLAN WORKS<br><br></p>';
    $BillReport .= '<ul>';
    $BillReport .= '<li>Minor Head | ORIGINAL WORKS Communication</li>';
    $BillReport .= '<li>Head | Repair & Maint (a) Buildings (a)</li>';
    $BillReport .= '<li>Sub Head or ------------------------------------------------Detailed Head<br></li>';
    $BillReport .= '</ul>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="text-align: justify;">';
    $BillReport .= '<p style="text-align: justify;">2. Transactions of roadside materials entered in the statements of receipts, issues, and balances of Road metal.<br><br><br><br></p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 15px;">';
    $BillReport .= '<p><br>Provisions during the current year Rs...........</p><br>';
    $BillReport .= '<p>Expenditure incurred</p><br>';
    $BillReport .= '<p>during the current year Rs..........</p><br>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="">';
    $BillReport .= '<p style="display: inline; padding-left: 90px;">Clerk<br><br></p><p style="display: inline; padding-left: 190px;">Accountant<br><br><br></p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="">';
    $BillReport .= '<p>Balance available Rs.......</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="text-align: justify;">';
    $BillReport .= '<p style="text-align: justify;">* Strike out words that are not applicable</p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 10px;">';
    $BillReport .= '<p>a) Score out what is not applicable</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';

    $BillReport .= '</tbody>';
    $BillReport .= '</table><br>';

    $royaltylab = [ "001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566",
                "004350", "003940", "003941", "004346", "004348", "004347"];

    $NormalData = DB::table('bil_item')
    ->select('exec_qty', 'item_unit', 'item_desc', 'bill_rt', 'b_item_amt', 't_bill_id', 'item_id', 't_item_no', 'sub_no')
    ->where('t_bill_id', $tbillid)
    ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();
    // dd($NormalData);

    $DBWorkId=DB::table('bills')
    ->where('t_bill_Id',$tbillid)
    ->value('work_id');
    // dd($DBWorkId);
    $DBaboveBellow=DB::table('workmasters')
    ->select('Above_Below','A_B_Pc')
    ->where('Work_Id',$DBWorkId)
    ->first();
    // dd($DBaboveBellow);

    $FINALBILL =DB::table('bil_item')
    ->select('exec_qty','item_unit','item_desc','bill_rt','b_item_amt','t_bill_id','item_id','t_item_no','sub_no')
    ->where('t_bill_id',$tbillid)
    ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();
    // dd($FINALBILL);

    $DBbillTablegetData=DB::table('bills')
    ->select('c_part_a_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
    'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')
    ->where('t_bill_Id',$tbillid)
    ->first();
   // dd($DBbillTablegetData);
   $isFinalBill=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('final_bill','t_bill_No')->first();
   // dd($isFinalBill);
   $FirstBill=$isFinalBill->t_bill_No;
   $FinalBill=$isFinalBill->final_bill;
   //dd($FirstBill,$FinalBill);
   // $header=$this->commonheader();
   $rbbillno=CommonHelper::formatTItemNo($FirstBill);
   $rbbiill=CommonHelper::getBillType($FinalBill);


  // dd($FirstBill,$FinalBill);
   // $header=$this->commonheader();

    $commonHelper = new CommonHelper();
    $amountInWords = $commonHelper->convertAmountToWords($DBbillTablegetData->c_netamt);


     //dd($amountInWords);
    $BillReport .= '<table style="margin-left:30px; border-collapse: collapse;">';
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan="1" style="width: 100px;">Name of Work:</th>';
    $BillReport .= '<th colspan="5" style="width: 600px;">'. $workdata->Work_Nm . '</th>';
    $BillReport .= '</tr>';
    $BillReport .= '<tr>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 50px; text-align: center; word-wrap: break-word;">Quantity</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 300px; text-align: center; word-wrap: break-word;">Item of Work or supplies (grouped under sub-head or sub-works of estimates)</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 70px; text-align: center; word-wrap: break-word;">Rate (Rs.)</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 50px; text-align: center; word-wrap: break-word;">Unit</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 70px; text-align: center; word-wrap: break-word;">Amount (Rs.)</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 100px; text-align: center; word-wrap: break-word;">Remarks</th>';
    $BillReport .= '</tr>';
    
    $BillReport .= '<thead>';
    $BillReport .= '<tr>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 50px; text-align: center; word-wrap: break-word;">1</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 300px; text-align: center; word-wrap: break-word;">2</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 70px; text-align: center; word-wrap: break-word;">3</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 50px; text-align: center; word-wrap: break-word;">4</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 70px; text-align: center; word-wrap: break-word;">5</th>';
    $BillReport .= '<th style="border: 1px solid black; padding: 8px; width: 100px; text-align: center; word-wrap: break-word;">6</th>';
    $BillReport .= '</tr>';
    $BillReport .= '<tbody>';

    foreach ($NormalData as $data)
    {
        $BillReport .= '<tr>';
        $BillReport .= '<td style="border: 1px solid black;padding: 8px; width: 50px;  text-align: right; word-wrap: break-word;">' . '<div>' . $data->exec_qty . '</div><div>' . $data->item_unit . '</div></td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 300px; text-align: left; word-wrap: break-word;">' . $data->item_desc . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 70px; text-align: right; word-wrap: break-word;">' . $commonHelper->formatIndianRupees($data->bill_rt) . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 50px; text-align: left; word-wrap: break-word;">' . $data->item_unit . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 70px; text-align: right; word-wrap: break-word;">' . $commonHelper->formatIndianRupees($data->b_item_amt) . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; width: 100px; text-align: left; word-wrap: break-word;">  </td>';
        $BillReport .= '</tr>';
    }
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Total Part A Amount</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_a_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Deduct for  '.$DBaboveBellow->Above_Below.' '.$DBaboveBellow->A_B_Pc.'  as per Tender</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_abeffect).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';


    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> GST Base	</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_gstbase).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> GST Amount 18%</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_gstamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Total </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_a_gstamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';

    $BillReport .= '</tr>';



    foreach ($FINALBILL as $data)
    {
        $BillReport .= '<tr>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px;   text-align: right; word-wrap: break-word;">' . '<div>' . $data->exec_qty . '</div><div>' . $data->item_unit . '</div></td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;">' . $data->item_desc . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $commonHelper->formatIndianRupees($data->bill_rt) . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;">' . $data->item_unit . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $commonHelper->formatIndianRupees($data->b_item_amt) . '</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;">  </td>';
        $BillReport .= '</tr>';
    }
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Total Part B Amount</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_b_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Grand Total</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_billamtgt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Add for rounding off</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_billamtro).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Final Total	 </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_netamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Previously Paid Amount From Per Bill </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->p_net_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">Now to be paid Amount </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_netamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=6 style="border: 1px solid black;  padding: 8px;  text-align: center; word-wrap: break-word;"> Total value of work done or supplies made  ' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_netamt).'</td>';
    $BillReport .= '</tr>';

    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=6 style="border: 1px solid black;  padding: 8px;  text-align: center; word-wrap: break-word;"> In  Word (' .  $amountInWords.' ) </td>';
    $BillReport .= '</tr>';
    $BillReport .= '</thead>';

    $BillReport .= '</tbody>';
    $BillReport .= '</table>';



    $BillReport .= '<table style="margin-left:27px;  width: 100%; border-collapse: collapse;">';
    $BillReport .= '<thead></thead>';
    $BillReport .= '<tbody>';

 

$BillReport .= '<tr>';
$BillReport .= '<td  style="width:50%; border: 1px solid black; padding: 8px; word-wrap: break-word;">Measurements recorded by '.$JE_nm->name.' on '.$dates.' in M. Book No '.$tbillid.' checked by   '.$DYE_nm->name.'  100.00%.<br><br><br>';
$BillReport .= '<div style="">'; // Flexbox for layout control
$BillReport .= '<span style=" padding: 10px; text-align: left; width:50%;">'; // Half width for date
$BillReport .= 'Date: ______/______/______'; // Your date content here
$BillReport .= '</span>';
$BillReport .= '<span style=" text-align: right; padding-top: 60px; width:50%;">'; // Half width for signature
$BillReport .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Countersigned'; // Your signature content here
$BillReport .= '</span>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '<br><br><div style="margin-top:20px;">'; // Flexbox for layout control
// $BillReport .= '</span>';';

$BillReport .= '<div style="display:inline;">'; // Flexbox for layout control

$BillReport .= '<table>'; // Flexbox for layout control
$BillReport .= '<tbody>'; // Flexbox for layout control
$BillReport .= '<tr>'; // Flexbox for layout control
$BillReport .= '<td style="text-align: left;">'; // Flexbox for layout control
$BillReport .= '<img src="' . $imageSrcDYE  . '" alt="Base64 Encoded Image" style="width: 80px; text-align: right;">';
$BillReport .= '<br>'.$DYE_nm->designation.'<br>'.$workdata->Sub_Div.'';

$BillReport .= '</td>'; // Flexbox for layout control
$BillReport .= '<td style="text-align: right;">'; // Flexbox for layout control
$BillReport .= '<img src="' . $imageSrcEE  . '" alt="Base64 Encoded Image" style="width: 80px; text-align: right;">';

$BillReport .= '<br>'.$EE_nm->designation.'<br>'.$workdata->Div.'';

$BillReport .= '</td>'; // Flexbox for layout control
$BillReport .= '</tr>'; // Flexbox for layout control
$BillReport .= '</tbody>'; // Flexbox for layout control
$BillReport .= '</table>'; // Flexbox for layout control

     $convert=new Commonhelper();

$BillReport .= '</td>';
   
$BillReport .= '<td style="border: 1px solid black; padding: 8px; text-align: left; word-wrap: break-word;">Received Rs. ' . $convert->formatIndianRupees($DBbillTablegetData->c_netamt) . ' (' . $amountInWords . ') . in final settlement of work.';
$BillReport .= '<table style="margin-top:40px; margin-bottom:50px; width:100%;">'; // Flexbox for layout control
$BillReport .= '<tbody>'; // Flexbox for layout control
$BillReport .= '<tr>'; // Flexbox for layout control
$BillReport .= '<td style="text-align: left; margin-top:40px;">'; // Flexbox for layout control
$BillReport .= 'Witness'; // Your date content here
$BillReport .= '</td>'; // Flexbox for layout control
$BillReport .= '</tr>'; // Flexbox for layout control
$BillReport .= '<tr>'; // Flexbox for layout control
$BillReport .= '<td style="text-align: right;">'; // Flexbox for layout control

$BillReport .= '<div style="text-align:right; padding: 10px; padding-top:20px;">Stamp</div>'; // Your signature content here with space
$BillReport .= '</td>'; // Flexbox for layout control

$BillReport .= '</tr>'; // Flexbox for layout control
$BillReport .= '<tr>'; // Flexbox for layout control
$BillReport .= '<td style="text-align: right;">'; // Flexbox for layout control

$BillReport .= '<div ></div>'; // Additional line without <br> with space using margin-top
$BillReport .= '</td>'; // Flexbox for layout control
$BillReport .= '</tr>'; // Flexbox for layout control

$BillReport .= '</tbody>'; // Flexbox for layout control
$BillReport .= '</table>'; // Flexbox for layout control



$BillReport .= '<div>'; // Flexbox for layout control


$BillReport .= '<span style="text-align:right; padding-top:20px;">Payees dated signature'; // Flexbox for layout control

$BillReport .= '</span>'; // Flexbox for layout control
$BillReport .= '</div>'; // Flexbox for layout control


 $BillReport .= '</td>';
// $BillReport .= '</tr>';



$BillReport .= '<tr>';

$BillReport .= '<td  style="border: 1px solid black; padding: 8px; text-align:left;  word-wrap: break-word;">';

$BillReport .= '<div style="display: flex; ">'; // Flexbox for layout control
$BillReport .= '<div style="">'; // Half width for date
$BillReport .= 'Pay by cash / cheque Rs.( __________________ ) Rupees<br><br>Dated:<br><br><br>Officer authorizing payment'; // Your date content here
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '</td>';
$BillReport .= '<td  style="border: 1px solid black; padding: 8px; text-align:left;  word-wrap: break-word;">';

$BillReport .= '<div style="display: flex; ">'; // Flexbox for layout control
$BillReport .= '<div style="">'; // Half width for date
$BillReport .= 'Paid by me by cash / vide cheque No.<br><br>Dated:<br><br><br>Dated Initials of person making the payment'; // Your date content here
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '</td>';

$BillReport .= '</tr>';

$BillReport .= '</tbody>';


    $BillReport .= '</table>';



    
//      $BillReport .= '<table style="border-collapse: collapse;  border: 1px solid black;">';
//     $BillReport .= '<tr>';

//     $BillReport .= '<td  style="border: 1px solid black; padding: 8px; text-align:left;  word-wrap: break-word;">Measurements recorded by '.$JE_nm->name.' on '.$dates.' in M. Book No '.$tbillid.' checked by '.$DYE_nm->name.'100.00 %. ';
    
//     $BillReport .= '<div style="display: flex; ">'; // Flexbox for layout control
// $BillReport .= '<div style="width: 50%; padding: 50px; ">'; // Half width for date
// $BillReport .= 'Date: ______/______/______'; // Your date content here
// $BillReport .= '</div>';
// $BillReport .= '<div style="width: 50%; text-align: right; padding: 50px; ">'; // Half width for signature
// $BillReport .= 'Countersigned'; // Your signature content here
// $BillReport .= '</div>';
// $BillReport .= '</div>'; // End flexbox
// $BillReport .= '<div style="display: flex;   height: 100px;">'; // Flexbox for layout control
// $BillReport .= '<div style="width: 50%;  height: 20px; text-align:center">'; // Half width for date
// $BillReport .= '<img src="' . $imageSrcDYE  . '" alt="Base64 Encoded Image" style="width: 100px; "><br>'.$DYE_nm->designation.'<br> '.$workdata->Sub_Div.'';
// $BillReport .= '</div>';
// $BillReport .= '<div style="width: 50%; text-align: right; height: 20px; text-align:center">'; // Half width for signature
// $BillReport .= '<img src="' . $imageSrcEE  . '" alt="Base64 Encoded Image" style="width: 100px;"><br>'.$EE_nm->designation.'<br>'.$workdata->Div.'';
// $BillReport .= '</div>';
// $BillReport .= '</div>'; // End flexbox
// $BillReport .= '</td>';
   
// $BillReport .= '<td style="border: 1px solid black; padding: 8px; text-align: left; word-wrap: break-word;">Received Rs. ' . $DBbillTablegetData->c_netamt . ' ' . $amountInWords . ' Nil Only. in final settlement of work.';
// $BillReport .= '<div style="display: flex; margin-bottom: 100px;">'; // Flexbox for layout control
// $BillReport .= '<div style="width: 50%; padding-top: 80px; display: block;">'; // Half width for date
// $BillReport .= 'Witness'; // Your date content here
// $BillReport .= '</div>';
// $BillReport .= '<div style="width: 50%; padding-top: 80px; display: block;">'; // Half width for date
// $BillReport .= '<div>Stamp</div>'; // Your signature content here with space
// $BillReport .= '<div style="margin-top: 30px;">Payees dated signature</div>'; // Additional line without <br> with space using margin-top
// $BillReport .= '</div>';
// $BillReport .= '</div>'; // End flexbox
// $BillReport .= '</td>';
// $BillReport .= '</tr>';



// $BillReport .= '<tr>';

// $BillReport .= '<td  style="border: 1px solid black; padding: 8px; text-align:left;  word-wrap: break-word;">';

// $BillReport .= '<div style="display: flex; ">'; // Flexbox for layout control
// $BillReport .= '<div style="width: 50%;">'; // Half width for date
// $BillReport .= 'Pay by cash / cheque Rs.( __________________ ) Rupees<br>Dated:'; // Your date content here
// $BillReport .= '</div>';
// $BillReport .= '<div style="width: 50%; text-align: right;">'; // Half width for signature
// $BillReport .= '</div>';
// $BillReport .= '</div>'; // End flexbox
// $BillReport .= '<div style="display: flex; padding-top: 40px;">'; // Flexbox for layout control
// $BillReport .= '<div style="width: 50%;  height: 20px; text-align:left">'; // Half width for date
// $BillReport .= 'Officer authorizing payment';
// $BillReport .= '</div>';
// $BillReport .= '<div style="width: 50%; text-align: right; height: 20px; text-align:center">'; // Half width for signature
// $BillReport .= '</div>';
// $BillReport .= '</div>'; // End flexbox
// $BillReport .= '</td>';
// $BillReport .= '<td  style="border: 1px solid black; padding: 8px; text-align:left;  word-wrap: break-word;">';

// $BillReport .= '<div style="display: flex; ">'; // Flexbox for layout control
// $BillReport .= '<div style="width: 50%;">'; // Half width for date
// $BillReport .= 'Paid by me by cash / vide cheque No.<br>Dated:'; // Your date content here
// $BillReport .= '</div>';
// $BillReport .= '<div style="width: 50%; text-align: right;">'; // Half width for signature
// $BillReport .= '</div>';
// $BillReport .= '</div>'; // End flexbox
// $BillReport .= '<div style="display: flex; padding-top: 40px;">'; // Flexbox for layout control
// $BillReport .= '<div style="width: 50%;  height: 20px; text-align:left">'; // Half width for date
// $BillReport .= 'Dated Initials of person making the payment';
// $BillReport .= '</div>';
// $BillReport .= '<div style="width: 50%; text-align: right; height: 20px; text-align:center">'; // Half width for signature
// $BillReport .= '</div>';
// $BillReport .= '</div>'; // End flexbox
// $BillReport .= '</td>';

// $BillReport .= '</tr>';



//     $BillReport .= '</table>';


    }
    else
    {

        $BillReport .= '<h1 style="text-align: center; font-weight: bold; font-size: 120%; word-wrap: break-word;">Z. P. FORM - 58 - C</h6>';
        $BillReport .= '<h1 style="text-align: center; font-size: 100%; word-wrap: break-word;">(See Rule 174)</h1>';
        $BillReport .= '<h1 style="text-align: center; margin-bottom: 4%; font-size: 80%; word-wrap: break-word;">' . $workdata->Div . '</h1>';

        $BillReport .= '<table>';

        $BillReport .= '<tr style="width: 100%;">';
        $BillReport .= '<td style="width: 100%;  text-align: center; padding-left:5%; word-wrap: break-word;"><strong>Notes</strong></th>';
        $BillReport .= '<td style="width: 100%;  text-align: center; padding-left:5%; word-wrap: break-word;"><strong>' . $workdata->Sub_Div . '</strong></th>';
        $BillReport .= '</tr>';


        $BillReport .= '<tr style="width: 100%;">';
        $BillReport .= '<td style="padding-top: 10px;">';
        $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">(For Contractors and suppliers. This form provides only for payment for work or supplies actually measured.)<br> 1. The full name of the work as given in the estimate should be entered against the line "Name of work" except in the case of bills for "Stock" materials.<br><br>
                            2. The purpose of supply applicable to the case should be filled in and rest scored out.
                            <br><br>3. If the outlay on the work is recorded by sub-heads, the total for each sub-head should be shown on Column 5 and against this total, there should be an entry in Column 6 also. In no other case should any entries be made in Column 6.<br><br></p>';
        $BillReport .= '</td>';


        
        $BillReport .= '<td >';
          $BillReport .= '<p style="max-width: 100%; text-align: center; padding-left:25%;">============================<br><b></b></p><br>';
        $BillReport .= '<p style="margin-left:9%; padding-top:1%;   width: 100%; text-align: right; style="padding-top: 10px;""><b>RUNNING ACCOUNT BILL-C</b></p><br>';
        $BillReport .= '<p style="padding-left: 5%;  width: 100%; text-align: justify; style="padding-top: 10px;"">Cash Book Voucher No:</p>';
        $cvno=$Billinfo->cv_no;
        if ($cvno) {
            $BillReport .=  '' . $Billinfo->cv_no .'';
        }
         $BillReport .= '<p style="max-width: 100%; text-align: justify;  padding-left:5%; word-wrap: break-word;">============================</p><br>';
        $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 85%; padding-left:5%;">Name of Contractor / Piece-worker / Supplier: ' . $workdata->Agency_Nm . ',' . $Agency_Pl->Agency_Pl . '  </p><br>';
        $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">Name of work :</p>';
        $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">'. $workdata->Work_Nm . '</p><br><br>';
        $BillReport .= '</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td >';
        $BillReport .= '<br><p style="max-width: 100%; text-align: center;  padding-left:15%; word-wrap: break-word;"><strong>Memorandum of Payments</strog></p>';
        $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">
                4. The figures against (k) should be test to see that it agrees with the total if Items 4&5<br><br>
                5.If the net amount is to be paid is lessthan Rs 10 and it cannot be included in a cheque,
                the payment should be made in cash, thepay order being altered suitably any alterati-on
                attested by dated initials.<br><br>
                6. The payes acknowledgement should be forthe grossamount paid as per Item 5, i.e.a+b+c.<br><br> 
                7. Payment should be attested by some knownperson when the payes acknowledgement is given by a mark seal or thumb impression.<br><br>
                8. The column "Figures for Works Abstract" is not required in the case of bills of supplies.</p></td>';

        $BillReport .= '<td>';
        $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">Purpose of Supply :</p>';
        $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;"> Serial No of this bill :'.$rbbillno.''.$rbbiill.'</p>';

        if($prev_rbbillno===0)
        {
            $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;" > No and date of last :  -- R.A. Bill paid vide bill for this work :  C.V. No : <br></p>';
        }
        else{
            $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;" > No and date of last :'.$prev_rbbillno.'  R.A. Bill paid vide bill for this work :  C.V. No : <br> </p>';
        }
        $cvno=$Billinfo->cv_no;
        if ($cvno) {
            $BillReport .=  '' . $Billinfo->cv_no .'';
        }
        $cvdate=$Billinfo->cv_dt;
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">for</p>';

        if ($cvdate) {
            //dd($cvdate);
            $date1=date_create($cvdate);
            $formattedDate = $date1->format('d/m/Y');
            $date=date_create($formattedDate);
            $dt2=date_format($date,"M/Y");
            // dd($dt2);
            $BillReport .= $dt2;
        }

        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;"> Reference to agreement : ' . $workdata->Agree_No . '</p>';
        $OcommenceDate = date('d/m/Y', strtotime($workdata->Wo_Dt));
        $dueDate = date('d/m/Y', strtotime($workdata->Stip_Comp_Dt));
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;"> Date of order to commence the work : '.$OcommenceDate.'</p>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;"> Due date of completion of work :'.$dueDate.'</p>';
        $BillReport .= '</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style="width: 100%; text-align: justify; font-size: 96%; padding-left:9%;  word-wrap: break-word;"><b>Account Classification - </b><br> '.DB::table('fundhdms')->where('F_H_id', $workdata->F_H_id)->value('F_H_CODE').'';
        $BillReport .= '</td>';

        $BillReport .= '<td>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;" >Extensions granted, if any, - - - <br>
                        from time to time with - - -<br>reference to authority - - <br><br></p>';

        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;"> Actual date of completion :';
        if ($workdata->actual_complete_date) {

            $Act_dt_compli = date('d/m/Y', strtotime($workdata->actual_complete_date));
            $BillReport .=  "' . $Act_dt_compli .'";
        }
        $BillReport .= '</p></td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">PLAN WORKS</p><br>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">NON-PLAN WORKS<br>Minor Head | ORIGINAL WORKS Communication<br>Head | Repair & Maint (a) Buildings (a)<br>Sub Head or -----------------------Detailed Head</p>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">===============================</p>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">Provisions during the current year         Rs...........</p><br>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">Expenditure incurred </p><br>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">during the current year        Rs..........</p><br>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">Balance available Rs.......</p><br>';
        $BillReport .= '<p style="width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">a) Strike out what is not applicable</p><br>';
        $BillReport .= '</td>';

        $BillReport .= '<td>';
        $BillReport .= '<p style="max-width: 100%; text-align: justify; font-size: 85%; padding-left:5%; word-wrap: break-word;">1) Security Deposit to be recovered as per agreement<br> <br> 2) Security Deposit previously recovered <br><br> 3) Security Deposit to be recovered from this bill<br><br>4) Balance to be recovered</p>';
        $BillReport .= '</td>';
        $BillReport .= '</tr>';

        $BillReport .= '</table>';

        // //PDF SECOND PAGE...............................................................................................................................................

        $BillReport .= '<div style="page-break-before: always;"></div>';
        $BillReport .= '<table>';
        $BillReport .= '<tbody>';

        // $BillReport .= '<tr>';
        // $BillReport .= '<td colspan="6" style=" text-align: left;">' . $workdata->Div . '</td>';
        // $BillReport .= '<td colspan="6" style=" text-align: right;">' . $workdata->Sub_Div . '</td>';
        // $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="6" style="text-align: left;">Name of Work:  </td>';
        $BillReport .= '<td colspan="6" style="text-align: center;">'.$workdata->Work_Nm .'</td>';
        $BillReport .= '</tr><br>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="12" style="text-align: center;"><b> I - Account of  Work Executed  </b></td>';
        $BillReport .= '</tr>';
        $BillReport .= '</tbody>';
        $BillReport .= '</table>';

        // Foreach Header...........................................................................................................
        $BillReport .= '<table class="table table-bordered table-collapse" style="border: 1px solid black; border-collapse: collapse; margin: 0;">';
        // $BillReport .= '<thead>';
        $BillReport .= '<tr>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: left; width: 50px; word-wrap: break-word; font-size: 14px;">Unit</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: justify; width: 100px; word-wrap: break-word; font-size: 14px;">Quantity executed up-to-date as per measurement Book</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; width: 350px; word-wrap: break-word; font-size: 14px;">Item of Work</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; width: 100px; word-wrap: break-word; font-size: 14px;">Bill<br>----------------<br>tender Rate Rs.</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: justify; width: 100px; word-wrap: break-word; font-size: 14px;">Payments of Actual up-to-date Rs.</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: justify; width: 100px; word-wrap: break-word; font-size: 14px;">On the basis of measurements Since the previous Bill Rs.</td>';
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; width: 50px; word-wrap: break-word; font-size: 14px;">Remark</td>';
        $BillReport .= '</tr>';
        // $BillReport .= '</thead>';
        $BillReport .= '<tbody>';
        



        //For Royalty Surcharg Items.............................................................................................
        $royaltylab = ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566",
                "004350", "003940", "003941", "004346", "004348", "004347"];

        $NormalData = DB::table('bil_item')
        ->where('t_bill_id', $tbillid)
        ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
          ->orderBy('t_item_no', 'asc') // Ordering by 'id' in ascending order
        ->get();
        // dd($NormalData);

        if($NormalData){
            $header1=$this->commonforeach($NormalData,$tbillid,$work_id);
            // dd($header1);
            $BillReport .=$header1;
            $abpc = $workdata->A_B_Pc;
            $abobelowatper=$workdata->Above_Below;
            
             $convert=new Commonhelper();

        if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Total Part A Amount</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->part_a_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_part_a_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';
        }

        if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Tender Above Bellow Result: ' . $workdata->A_B_Pc . ' ' . $workdata->Above_Below . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_abeffect) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->a_b_effect) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';
        }

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="4" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
        $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->gst_base) . '</strong></td>';
        $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_gstbase) . '</strong></td>';
        $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="4" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>GST Amount ' . $Billinfo->gst_rt . '%</strong></td>';
        $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->gst_amt) . '</strong></td>';
        $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_gstamt) . '</strong></td>';
        $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
        $BillReport .= '</tr>';

        }
        $RoyaltyData = DB::table('bil_item')
        ->where('t_bill_id', $tbillid)
        ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
         ->orderBy('t_item_no', 'asc') // Ordering by 'id' in ascending order
        ->get();

        //dd($RoyaltyData);
         if (!$RoyaltyData->isEmpty()) {
            // dd("Okkk");
            $header1=$this->commonforeach($RoyaltyData,$tbillid,$work_id);
            // dd($header1);
            $BillReport .=$header1;

            $BillReport .= '<tr>';
            $BillReport .= '<td  colspan="4" style="border: 1px solid black;  padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black;  padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->part_b_amt) . '</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black;  padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_part_b_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';

            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Grand Total</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->bill_amt_gt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_billamtgt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';


            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
            $BillReport .= '<td colspan="1"  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->bill_amt_ro) . '</strong></td>';
            $BillReport .= '<td colspan="1"  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_billamtro) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';


            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
            $BillReport .= '<td colspan="1"  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->net_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1"  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_netamt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';

            $Net_Pre_subtraction=$Billinfo->net_amt-$Billinfo->p_net_amt;

            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="5" style="border: 1px solid black;  padding: 8px;  text-align: right; word-wrap: break-word;">Net value of work or supplies since previous bill (F) :</td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black;  padding: 8px;  text-align: right; word-wrap: break-word;"><strong>' .  $convert->formatIndianRupees($Net_Pre_subtraction).'</strong> </td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';

            // $c_netamt=$this->convertAmountToWords($Billinfo->c_netamt);
            $commonHelper = new CommonHelper();
            $c_netamt = $commonHelper->convertAmountToWords($Billinfo->c_netamt);
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan=7 style="border: 1px solid black;  text-align: center; word-wrap: break-word;"> In  Word<strong>( '.$c_netamt.' ) </strong></td>';
            $BillReport .= '</tr>';

        }


        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="7" style=" text-align:justify; word-wrap: break-word; border: 1px solid white;"> -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br>
        The measurements made by '.$JE_nm->name.' , '.$JE_nm->designation.' on '.$dates.' and are recorded at
         Measurement Book No. '.$tbillid.' No advance payment has been made previously
        without detailed measurements.</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="4" style="text-align: center; word-wrap: break-word; border: 1px solid white;"></td>';

        $BillReport .= '<td colspan="3" style="text-align:center; border: 1px solid white;"><img src="' . $imageSrcDYE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>
        '.$DYE_nm->designation.'<br>
        '.$workdata->Sub_Div.'<br>
        <br> * Dated Signature of Officer preparing bill </td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="7" style="border: 1px solid white;">----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
        $BillReport .= '</tr>';

        $BillReport .= '</tbody>';
        $BillReport .= '</table>';

        $BillReport .= '<table>';
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=6 style="text-align:left; font-size: 85%;">  Dated : </td>';
        $BillReport .= '<td colspan=6 style="text-align:right; font-size: 85%;">  Countersigned </td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        $BillReport .= '<td style="text-align: left;  word-wrap: break-word; font-size: 85%;"> Dated Signature of the Contractor</td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=12 style="margin-left: 90%; text-align:right; font-size: 85%;"><img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=12 style="  margin-left: 90%; text-align:right; font-size: 85%;">'.$EE_nm->designation.'</td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=12 style=" margin-left: 90%;  text-align:right; font-size: 85%;">'.$workdata->Div.'</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td><hr></td>';
        $BillReport .= '</tr>';
        
        $BillReport .= '<tr>';
        $BillReport .= '<td style="font-size: 85%;"> The second signature is only necessary when the officer who prepares the bill is not the officer who makes the payment. </td>';
        $BillReport .= '</tr>';
        $BillReport .= '</table>';

        // // PDF LAST PAGE--------------------------------------------------------------------------------------------------------------
        $BillReport .= '<div style="page-break-before: always;"></div>';
        $BillReport .= '<h6 style="text-align: center; font-weight:bold; word-wrap: break-word;">III - Memorandum of Payments </h6>';

        $BillReport .= '<p style="text-align: left; font-size: 70%;">1. Total Value of work done, as per Account-I, Column 5, Entry (A)</p>';
        $BillReport .= '<p style="text-align: left; font-size: 70%;">2. Deduct Amount withheld :</p>';


        $BillReport .= '<table>';
        $BillReport .= '<tbody>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%; ">----------------<br>Figures for<br> Work abstract<br>-----------------</td>';
        $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: center; font-size: 70%; ">(a) From previous bill as per last Running Account Bill</td>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: center; font-size: 70%;">----------------<br>Rs.&nbsp; &nbsp; &nbsp; &nbsp; Ps.<br>----------------</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
        $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%;">(b) From this bill</td>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: center; font-size: 70%;">----------------</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
        $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%;"> 3. Balance, i.e. "Up-to-date" payments (Items 1 - 2)</td>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;">(K)</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
        $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; word-wrap: break-word;"> Total amount of payments already made as per entry<br>
                        of last Running Account Bill No.<br>
                        forwarded with accounts for</td>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;">(K)</td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
        $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: center; font-size: 70%;">5. Payments now to be made as detailed below :-</td>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: center; font-size: 70%;">-------------------<br> Rs. &nbsp; &nbsp; &nbsp; &nbsp; Ps.<br>------------------</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
        $BillReport .= '<td style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%;"> (a) By recovery of amounts creditable to this work -(a)<br>
                            Value of stock supplied as detailed<br>
                            in the ledger in (a)</td>';
        $BillReport .= '<td style="width: 10%; margin-right: 10px; text-align: left; font-size: 70%;"></td>';
        $BillReport .= '</tr>';
        $BillReport .= '</tbody>';
        $BillReport .= '</table>';


        $BillReport .= '<table>';
        $BillReport .= '<tbody>';
        $BillReport .= '<tr>';
        $BillReport .= '<td style="margin-right: 30px; text-align: left; font-size: 70%; padding-botton:2%;">-----------------------------------------------------------------------------------------------------------------------------------------------</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style="width: 100%; text-align: center; font-size: 70%; padding-botton:2%;">Total 2(b) + 5(a) (G)</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style="width: 70%; margin-right: 10px; text-align: left; font-size: 60%; padding-botton:2%;">-----------------------------------------------------------------------------------------------------------------------------------------------</td>';
        $BillReport .= '</tr>';
        $BillReport .= '</tbody>';
        $BillReport .= '</table>';


        $BillReport .= '<table>';
        $BillReport .= '<tbody>';

        $DedMaster_Info=DB::table('dedmasters')->select('Ded_M_Id')->get();
        //  dd($DedMaster_Info);

        $billDed_Info=DB::table('billdeds')->where('t_bill_Id' , $tbillid)->get('Ded_M_Id');
        // dd($billDed_Info);

        $sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
        // dd($sammarydata);
        $C_netAmt= $sammarydata->c_netamt;
        $chqAmt= $sammarydata->chq_amt;
        $commonHelper = new CommonHelper();
        $amountInWords = $commonHelper->convertAmountToWords($C_netAmt);
        // dd($amountInWords);

        $SecDepositepc = DB::table('dedmasters')->where('Ded_M_Id', 2)->value('Ded_pc') ?: '';
        $CGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 3)->value('Ded_pc') ?: '';
        $SGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 4)->value('Ded_pc') ?: '';
        $Incomepc = DB::table('dedmasters')->where('Ded_M_Id', 5)->value('Ded_pc') ?: '';
        $Insurancepc = DB::table('dedmasters')->where('Ded_M_Id', 7)->value('Ded_pc') ?: '';
        $Labourpc = DB::table('dedmasters')->where('Ded_M_Id', 8)->value('Ded_pc') ?: '';
        $AdditionalSDpc = DB::table('dedmasters')->where('Ded_M_Id', 9)->value('Ded_pc') ?: '';
        $Royaltypc = DB::table('dedmasters')->where('Ded_M_Id', 10)->value('Ded_pc') ?: '';
        $finepc = DB::table('dedmasters')->where('Ded_M_Id', 11)->value('Ded_pc') ?: '';
        $Recoverypc = DB::table('dedmasters')->where('Ded_M_Id', 13)->value('Ded_pc') ?: '';

        // Check if any value is 0 and assign an empty string
        $SecDepositepc = $SecDepositepc != 0 ? $SecDepositepc . '%' : '';
        $CGSTpc = $CGSTpc != 0 ? $CGSTpc . '%' : '';
        $SGSTpc = $SGSTpc != 0 ? $SGSTpc . '%' : '';
        $Incomepc = $Incomepc != 0 ? $Incomepc . '%' : '';
        $Insurancepc = $Insurancepc != 0 ? $Insurancepc . '%' : '';
        $Labourpc = $Labourpc != 0 ? $Labourpc . '%' : '';
        $AdditionalSDpc = $AdditionalSDpc != 0 ? $AdditionalSDpc . '%' : '';
        $Royaltypc = $Royaltypc != 0 ? $Royaltypc . '%' : '';
        $finepc = $finepc != 0 ? $finepc . '%' : '';
        $Recoverypc = $Recoverypc != 0 ? $Recoverypc . '%' : '';



        $deductionAmount=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->get();
        // dd($deductionAmount);
        $additionalSDAmt=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->where('Ded_Head','Additional S.D')->value('Ded_Amt');
        $additionalSDAmt = $additionalSDAmt ? $additionalSDAmt : '0.00';
        // dd($additionalSDAmt);
        $Security=DB::table('billdeds')
        ->where('T_Bill_Id' ,$tbillid)
        ->where('Ded_Head','Security Deposite')
        ->value('Ded_Amt');
        $Security = $Security ? $Security : '0.00';
        // dd($Security);
        $Income=DB::table('billdeds')
        ->where('T_Bill_Id' ,$tbillid)
        ->where('Ded_Head','Income Tax')
        ->value('Ded_Amt');
        $Income = $Income ? $Income : '0.00';
        // dd($Income);
        $CGST=DB::table('billdeds')
        ->where('T_Bill_Id' ,$tbillid)
        ->where('Ded_Head','CGST')
        ->value('Ded_Amt');
        $CGST = $CGST ? $CGST : '0.00';
        // dd($CGST);
        $SGST=DB::table('billdeds')
        ->where('T_Bill_Id' ,$tbillid)
        ->where('Ded_Head','SGST')
        ->value('Ded_Amt');
        $SGST = $SGST ? $SGST : '0.00';
        // dd($SGST);
        $Insurance=DB::table('billdeds')
        ->where('T_Bill_Id' ,$tbillid)
        ->where('Ded_Head','Work Insurance')
        ->value('Ded_Amt');
        $Insurance = $Insurance ? $Insurance : '0.00';
        // dd($Insurance);
        $Labour=DB::table('billdeds')
        ->where('T_Bill_Id' ,$tbillid)
        ->where('Ded_Head','Labour cess')
        ->value('Ded_Amt');
        $Labour = $Labour ? $Labour : '0.00';
        // dd($Labour);
        $Royalty=DB::table('billdeds')
        ->where('T_Bill_Id' ,$tbillid)
        ->where('Ded_Head','Royalty Charges')
        ->value('Ded_Amt');
        $Royalty = $Royalty ? $Royalty : '0.00';
        // dd($Royalty);
        $fine=DB::table('billdeds')
        ->where('T_Bill_Id' ,$tbillid)
        ->where('Ded_Head','fine')
        ->value('Ded_Amt');
        $fine = $fine ? $fine : '0.00';
        // dd($fine);
        $Recovery=DB::table('billdeds')
        ->where('T_Bill_Id' ,$tbillid)
        ->where('Ded_Head','Audit Recovery')
        ->value('Ded_Amt');
        $Recovery = $Recovery ? $Recovery : '0.00';
        // dd($Recovery);
        $BillReport .= '<tr>';
        $BillReport .= '<td>';
        $BillReport .= '<div style="">';
        $BillReport .= '<table style="border: 1px solid black; border-collapse: collapse; ">';
        $BillReport .= '<thead>';
        $BillReport .= '<tr>'; // Open a table row within the thead section
        $BillReport .= '<td style="font-size: 85%; border: 1px solid black; ">Amount</td>';
        $BillReport .= '<td style="font-size: 85%; border: 1px solid black;">Details</td>';
        $BillReport .= '</tr>'; // Close the table row within the thead section
        $BillReport .= '</thead>';
        $BillReport .= '<tbody>';

        $BillReport .='<tr>';
        $BillReport .= '<td style="font-size: 70%; border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($additionalSDAmt) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black;text-align:left;">Additional S.D: &nbsp;&nbsp;&nbsp; '.$AdditionalSDpc.'</td>';
        $BillReport .='</tr>';
        $BillReport .='<tr >';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Security) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black;">Security Deposite: &nbsp;&nbsp;&nbsp; '.$SecDepositepc.'</td>';
        $BillReport .='</tr>';
        $BillReport .='<tr >';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Insurance) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:left;">Insurance: &nbsp;&nbsp;&nbsp; '.$Insurancepc.'</td>';
        $BillReport .='</tr>';
        $BillReport .='<tr >';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Labour) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black;text-align:left;">Labour Cess: &nbsp;&nbsp;&nbsp; '. $Labourpc.'</td>';
        $BillReport .='</tr>';
        $BillReport .='<tr >';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Income) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:left;">Income Tax: &nbsp;&nbsp;&nbsp; '. $Incomepc.'</td>';
        $BillReport .='</tr>';
        $BillReport .='<tr >';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($CGST) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:left;">CGST: &nbsp;&nbsp;&nbsp; '.$CGSTpc.'</td>';
        $BillReport .='</tr>';
        $BillReport .='<tr >';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($SGST) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black;text-align:left;">SGST: &nbsp;&nbsp;&nbsp; '. $SGSTpc.'</td>';
        $BillReport .='</tr>';
        $BillReport .='<tr >';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Royalty) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:left;">Royalty:  charges &nbsp;&nbsp;&nbsp;'. $Royaltypc.'</td>';
        $BillReport .='</tr>';
        $BillReport .='<tr >';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($fine) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:left;">Fine:  &nbsp;&nbsp;&nbsp; '. $finepc.'</td>';
        $BillReport .='</tr>';
        $BillReport .='<tr >';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Recovery) .'</td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black;text-align:left;">Audit Recovery:  &nbsp;&nbsp;&nbsp; '. $Recoverypc.'</td>';
        $BillReport .='</tr>';

        $BillReport .='<tr>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:right;"> '. $commonHelper->formatIndianRupees($chqAmt) .' </td>';
        $BillReport .='<td style="font-size: 70%;border: 1px solid black; text-align:left;">Cheque Amount &nbsp;&nbsp;&nbsp;  </td>';
        $BillReport .='</tr>';
        $BillReport .='<tr>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:right; "> '. $commonHelper->formatIndianRupees($C_netAmt) .' </td>';
        $BillReport .='<td style="font-size: 70%; border: 1px solid black; text-align:left;">Total &nbsp;&nbsp;&nbsp;</td>';
        $BillReport .='</tr>';

        $BillReport .= '</tbody>';
        $BillReport .= '</table>';
        $BillReport .= '</div>';
        $BillReport .= '</td>';

        $BillReport .= '<td>';
        $BillReport .= '<table>';
        $BillReport .= '<thead>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;">(b) By recovery of amounts creditable to other<br> &nbsp; &nbsp; &nbsp; works or heads of account (b)</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;">  1) Security Deposit</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;">  2) Income Tax -  ------%   </td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;">  3) Surcharge - --------%</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;">4) Education cess - %</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;"> 5) M. Vat - 2 / 4 % </td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;"> 6) Royalty</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;"> 7) Insurance - 1 %</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;">8) Deposit</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%;  text-align: left; font-size: 70%; padding-left: 80px;"> 9) </td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%; text-align: left; font-size: 70%; padding-left: 80px;">10)</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="width: 50%; margin-right: 30px; text-align: left; font-size: 70%; padding-left: 80px;"> C) By check </td>';
        $BillReport .= '</tr>';
        $BillReport .= '</thead>';
        $BillReport .= '</table>';
        $BillReport .= '</td>';

        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">Total 5(b) + 5(c)  (H)</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">Pay Rs. *(  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;) By Cheque</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;"> * Here specify the net amount payable [Item 5(c)] &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;(Dated initials of the disbursing officer)</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="text-align: justify; margin-left: 10%;  font-size: 70%;">Received Rs. *(  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ) As per above</td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="text-align: left; font-size: 70%;">Memorandum on account of this work<br>&nbsp; &nbsp;  &nbsp; &nbsp; Dated /  /  </td>';
        $BillReport .= '<td colspan=10 style="text-align: right; font-size: 70%;">Stamp</td>';
        $BillReport .= '</tr>';

        $imagePath = public_path('Uploads/signature/' . $Agency_Pl->agencysign);
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageSrcagency = 'data:image/jpeg;base64,' . $imageData;

        $BillReport .= '<tr>';
        $BillReport .= '<td style=" text-align:left; font-size: 70%; padding-left:10px;"></td>';
        $BillReport .= '<td colspan=10 style=" text-align:right; font-size:  70%;"><img src="' . $imageSrcagency . '" alt="Base64 Encoded Image" style="width: 100px; height: 40px;"><br>(Full signature of the contractor)</td>';
        $BillReport .= '</tr>';



        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=10 style="text-align:left; font-size: 70%; padding-left:10px;">Witness</td>';
        $BillReport .= '</tr>';


        $BillReport .= '</tbody>';
        $BillReport .= '</table>';

        $BillReport .= '<hr style="border-top: 1px dotted #000; margin-top: 5px; margin-bottom: 10px;">'; // Dotted line before the table

        $BillReport .= '<table style="width: 100%;">';
        $BillReport .= '<tbody>';

                
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="11" style="font-size: 70%; text-align: left;">Paid by me, vide cheque No.</td>';
        $BillReport .= '<td style="font-size: 70%; text-align: center;">Dated :&nbsp;&nbsp;&nbsp;&nbsp;</td>';
        $BillReport .= '<td></td>'; // Placeholder cell
        $BillReport .= '</tr>';
        
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="11" style="font-size: 70%; text-align: left;">Cashier</td>';
        $BillReport .= '<td style="font-size: 70%; text-align: right;">(Dated initials of the person actually making the payments.)</td>';
        $BillReport .= '<td></td>'; // Placeholder cell
        $BillReport .= '</tr>';

         
        $BillReport .= '</tbody>';
        $BillReport .= '</table>';

        $BillReport .= '<hr style="border-top: 1px dotted #000; margin-top: 5px; margin-bottom: 10px;">'; // Dotted line before the table

        
        $BillReport .= '<h6 style="text-align: center; font-weight:bold;  padding: 3px; word-wrap: break-word;">IV - Remarks </h6>';
        $BillReport .= '<p style="text-align: justify;  font-size: 60%; padding: 8px;">(This space is reserved for any remarks the disbursing officer or the Executive Engineer may
                        wish to record in respect of the execution of the work, check of measurements or the state of
                        contractor  accounts.) </p>';
       

    }


    // $pdf = new Dompdf();
    // // Image path using the asset helper function
    // $pdf->loadHtml($BillReport);
    // //$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
    // $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

    // // (Optional) Set options for the PDF rendering
    // $options = new Options();
    // $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
    // $pdf->setOptions($options);

    // $pdf->render();
    // return $pdf->stream('Bill-Report'.$tbillid.'-pdf.pdf');

    // return $pdf->stream('Bill-Report-pdf.pdf');



    $mpdf = new \Mpdf\Mpdf(['orientation' => 'P']); // Set orientation to portrait
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;


$logo = public_path('photos/zplogo5.jpeg');

// Set watermark image
$mpdf->SetWatermarkImage($logo);

// Show watermark image
$mpdf->showWatermarkImage = true;

// Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
$mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed


// Write HTML content to PDF
$mpdf->WriteHTML($BillReport);


//$mpdf->WriteHTML($html);


// Determine the total number of pages




//dd($startPageNumber);
// Define the starting number for the displayed page numbers
// Calculate the total number of pages to be displayed in the footer



$totalPages = $mpdf->PageNo();


// Add page numbers to each page starting from the specified page number
for ($i = 2; $i <= $totalPages; $i++) {
// Calculate the displayed page number

// Set the current page for mPDF
$mpdf->page = $i;

if ($i === 1) {
    // Content centered on the first page
    $mpdf->WriteHTML('<div style="position: absolute; top: 50%; left: 50%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div>');
} 
// Write the page number to the PDF
//$mpdf->WriteHTML('<div style="position: absolute; top: 20px; right: 20px;">Page No ' . $pageNumber . '</div>');
//$startPageNumber++;

}

// Determine the total number of pages
$totalPages = $mpdf->PageNo();

// Output PDF as download
$mpdf->Output('Bill-' . $tbillid . '.pdf', 'D');

}
public function commonforeach($NormalData,$tbillid,$work_id)
{
     $convert=new Commonhelper();
     
    $BillReport = '';


    $BillReport .= '<tr>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:left;  word-wrap: break-word;">1</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">2</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">3</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">4</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">5</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">6</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">7</td>';
    $BillReport .= '</tr>';

        foreach($NormalData as $BillData){
            
            $itemno = $BillData->t_item_no . (!empty($BillData->sub_no) ? ' ' . $BillData->sub_no : '');

        $BillReport .= '<tr>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
       $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;">TENDER ITEM NO :' . $itemno . '</td>';        
       $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:left;  word-wrap: break-word;">'.$BillData->item_unit.'</td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:right;  word-wrap: break-word;">'.$BillData->exec_qty.'</td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:justify;  word-wrap: break-word; ">'.$BillData->item_desc.'</td>';

        if($BillData->bill_rt===$BillData->tnd_rt){
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:right;  word-wrap: break-word;">'.$convert->formatIndianRupees($BillData->bill_rt).'</td>';
        }
        else{
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:right;  word-wrap: break-word;">'.$convert->formatIndianRupees($BillData->bill_rt).'<br>_______________<br>'.$convert->formatIndianRupees($BillData->tnd_rt).'</td>';
        }
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:right;  word-wrap: break-word;">'.$convert->formatIndianRupees($BillData->b_item_amt).'</td>';

        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:right;  word-wrap: break-word;">'.$convert->formatIndianRupees($BillData->cur_amt).'</td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:right;  word-wrap: break-word;"></td>';

        $BillReport .= '</tr>';
    }

    //dd($BillReport);

    return $BillReport;


}

public function commonforeachview($NormalData,$tbillid,$work_id)
{
     $convert=new Commonhelper();
    $BillReport = '';

    $BillReport .= '<tr>';
    $BillReport .= '<td style="border: 1px solid black; background-color: #f2f2f2; text-align:center; width: 3%; word-wrap: break-word;">1</td>';
    $BillReport .= '<td style="border: 1px solid black; background-color: #f2f2f2; text-align:justify; width: 8%; word-wrap: break-word;">2</td>';
    $BillReport .= '<td style="border: 1px solid black; background-color: #f2f2f2; text-align:center; width: 55%; word-wrap: break-word;">3</td>';
    $BillReport .= '<td style="border: 1px solid black; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">4</td>';
    $BillReport .= '<td style="border: 1px solid black; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">5</td>';
    $BillReport .= '<td style="border: 1px solid black; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">6</td>';
    $BillReport .= '<td style="border: 1px solid black; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">7</td>';
    $BillReport .= '</tr>';
    // $BillReport .= '</table>';

    // $BillReport .= '<tr>';
    // $BillReport .= '<td style="border: none; padding: 8px; font-size: 70%; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">==================================================================</td>';
    // $BillReport .= '</tr>';

    // $BillReport .= '</thead>';
    // $BillReport .= '<tbody>';
        foreach($NormalData as $BillData){
            // $BillData=DB::table('bil_item')->where('b_item_id' , $BillData1->)->get();
            
            $itemno = $BillData->t_item_no . (!empty($BillData->sub_no) ? ' ' . $BillData->sub_no : '');


        $BillReport .= '<tr>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 3%; word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;"></td>';
       $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 55%; word-wrap: break-word;">TENDER ITEM NO :' . $itemno . '</td>';       
       $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width:8%; word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;"></td>';
        $BillReport .= '</tr>';

        $BillReport .= '<tr>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:left; width: 3%; word-wrap: break-word;">'.$BillData->item_unit.'</td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:right; width: 8%; word-wrap: break-word;">'.$BillData->exec_qty.'</td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:justify; width: 55%; word-wrap: break-word; ">'.$BillData->item_desc.'</td>';
        if($BillData->bill_rt===$BillData->tnd_rt){
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:right; width: 8%; word-wrap: break-word;">'.$convert->formatIndianRupees($BillData->bill_rt).'</td>';
        }
        else{
            $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:right; width: 8%; word-wrap: break-word;">'.$convert->formatIndianRupees($BillData->bill_rt).'<br>_______________<br>'.$convert->formatIndianRupees($BillData->tnd_rt).'</td>';
        }
        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:right; width: 8%; word-wrap: break-word;">'.$convert->formatIndianRupees($BillData->b_item_amt).'</td>';

        $BillReport .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:right; width: 8%; word-wrap: break-word;">'.$convert->formatIndianRupees($BillData->cur_amt).'</td>';
        $BillReport .= '</tr>';

    }
    // $BillReport .= '</tbody>';
    // $BillReport .= '</table>';
    //dd($BillReport);


    return $BillReport;


}



public function form_xivReport($tbillid ){
    // $amoutvalue=123456;

    // dd($callfun);
    $Billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('work_id','gst_amt','gst_rt','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
    'part_b_amt','gst_base','c_abeffect','c_gstbase','gross_amt','net_amt','c_gstamt','c_part_a_gstamt',
    'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','chq_amt')->first();
    // dd($Billinfo);
    $work_id=$Billinfo->work_id;
    //dd($work_id);
    $workdata=DB::table('workmasters')->where('Work_Id' , $work_id)->first();
    $Maxdt=DB::table('embs')->where('t_bill_Id' , $tbillid)->max('measurment_dt');
    $dates = Carbon::createFromFormat('Y-m-d',  $Maxdt)->format('d/m/Y');
    //dd($dates);
    $billDed_Info=DB::table('billdeds')->where('t_bill_Id' , $tbillid)->select('Ded_Head','Ded_Amt')->get();
    // dd($billDed_Info);
    $Agency_Pl=DB::table('agencies')->where('id' , $workdata->Agency_Id)->select('Agency_Pl','Pan_no','Gst_no')->first();
    //dd($Agency_Pl);
    $JE_nm=DB::table('jemasters')->where('jeid' , $workdata->jeid)->select('name','designation')->first();
    $DYE_nm=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();
    $EE_nm=DB::table('eemasters')->where('eeid' , $workdata->EE_id)->first();
    // $DYE_Design=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();
    //dd($DYE_nm);  $DYE_nm->designation
    // $headercheck='Bill';
    $cvno=$Billinfo->cv_no;
    // dd($cvno);
    $isFinalBill=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('final_bill','t_bill_No')->first();
    // dd($isFinalBill);
    $FirstBill=$isFinalBill->t_bill_No;
    $FinalBill=$isFinalBill->final_bill;
    //dd($FirstBill,$FinalBill);
    // $header=$this->commonheader();
    $rbbillno=CommonHelper::formatTItemNo($FirstBill);
    $rbbiill=CommonHelper::getBillType($FinalBill);
 //dd($rbbillno,$rbbiill);
    $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);
    // dd($prev_rbbillno);
    // $rbbillno=CommonHelper::formatTItemNo($FirstBill);
    // $rbbiill=CommonHelper::getBillType($FirstBill);


$commonHelper = new CommonHelper();

    // dd("OKkkkkkk");
    $htmlreport='';
    $htmlreport .= '<h6 style="text-align: center; font-weight:bold;  word-wrap: break-word;">(Form No. XIV)</h6>';
    $htmlreport .= '<p style="margin-left:8%; margin-right:8%; text-align: center;  text-align: left;">Cashier</p>';
    $htmlreport .= '<h5 style="text-align: center; font-weight:bold;  word-wrap: break-word;">SLIP TO ACCOMPLAINT CLAIM FOR MONEY OF DISBURSING</h5>';
    $htmlreport .= '<p style="text-align: center; font-weight:bold;  word-wrap: break-word; ">(To be returned original by F.D./B.D.O)</p>';
   
    $htmlreport .= '<div class="table-responsive">';
    $htmlreport .= '<table style="margin-left:8%; margin-right:8%; margin-bottom:1%;">';
    $htmlreport .= '<thead>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=3 style="text-align: center;  font-weight:bold;">Name of Work  :</td>';
    $htmlreport .= '<td colspan=5 style="text-align: justify; font-weight:bold; paading-bottom:3%"> '.$workdata->Work_Nm.'</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">Major Head-</td>';
    $htmlreport .= '<td colspan=4 style="text-align: left;"> '.DB::table('fundhdms')->where('F_H_id', $workdata->F_H_id)->value('F_H_CODE').' </td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">Minor Head-</td>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">  </td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">Sub Head-</td>';
    $htmlreport .= '<td colspan=4 style="text-align: right;">  </td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: justify;">--------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: center; font-weight:bold;  word-wrap: break-word; padding-bottom:2%;">(To be fixed in  F.D./B.D.O)</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">To,<br> C.A. & F.O.</td>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">To,<br><strong> '.$EE_nm->Designation.',<br></strong>'.$workdata->Div.' </td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: left;"> Zilla Parishad, Sangli.</td>';
    $htmlreport .= '<td colspan=4 style="text-align: left;"> Date as noted as below</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: left;"> Pleas Furnish the Z.P./F.D. Voucher No. and date of the bill sent herewith for encashment.</td>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">Returned with Z.P. Rs. F.D. Voucher o. and as noted below.</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: justify;">-------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: center;"> '.$EE_nm->Designation.'</td>';
    $htmlreport .= '<td colspan=4 style="text-align: center;">C.A.&F.A</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: center;">'.$workdata->Div.'</td>';
    $htmlreport .= '<td colspan=4 style="text-align: center;">Zilla Parishad, Sangli.</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">Bill Particular</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">'.$rbbillno.'  '.$rbbiill.'</td>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">__________________________________________________________</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">Gross Amount</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">'.$commonHelper->formatIndianRupees($Billinfo->c_netamt).'   </td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">T.V. No.</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">___________________________________________</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">Net Amount</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;"> '.$commonHelper->formatIndianRupees($Billinfo->chq_amt).'</td>';
    $htmlreport .= '<td colspan=1 style="text-align: left;">Date</td>';
    $htmlreport .= '<td colspan=3 style="text-align: left;">___________________________________________</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">Agency</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">'.$workdata->Agency_Nm.','.$Agency_Pl->Agency_Pl.'</td>';
    $htmlreport .= '<td colspan=1 style="text-align: left;">Signature</td>';
    $htmlreport .= '<td colspan=3 style="text-align: left;">___________________________________________</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">No</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">____________________________________________</td>';
    $htmlreport .= '<td colspan=1 style="text-align: left;">Amout Areasury Accountant</td>';
    $htmlreport .= '<td colspan=3 style="text-align: left;">___________________________________________</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: justify; padding-top:3%">-----------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
    $htmlreport .= '</tr>';

    $rs__pay=$Billinfo->chq_amt;
    //  dd($rs__pay);

    $cash_rs = $commonHelper->convertAmountToWords($rs__pay);
    // dd($cash_rs);
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: center;"><strong>ACKNOWLEDGEMENT</strong></td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: justify;">Received by cheque/cash Receipt Rs. (<strong>'.$cash_rs.'</strong>)</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: justify;">For the C.A & F.O.Z.P Sangli.                      Date :_____________ on____________</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: justify;">Place _________________________________________________________________________________________________________________________________________________________________                 Date:_________________________________________________________________________________________________  .</td>';
    $htmlreport .= '</tr>';


    $imagePath = public_path('Uploads/signature/' . $EE_nm->sign);
 
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrcEE = 'data:image/jpeg;base64,' . $imageData;
    
    
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=6 style="text-align: center; "></td>';
    $htmlreport .= '<td colspan=2 style="text-align: center;">';
    $htmlreport .= '<div style="width: 180px; height: 50px; display: inline-block;"> <img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 130px; height: 60px;"></div>'; // Placeholder for signature box
    
    $htmlreport .= '</td></tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=6 style="text-align: center; "></td>';
    $htmlreport .= '<td colspan=2 style="text-align: center; ">'.$EE_nm->Designation.'</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=6 style="text-align: center; "></td>';
    $htmlreport .= '<td colspan=2 style="text-align: center;">'.$workdata->Div.'</td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '</thead>';
    $htmlreport .= '</table>';
    $htmlreport .= '</div>';


    //Second View Page-------------------------------------------------------------------------------------------------------------------

$htmlreport .= '<div style="page-break-before: always;"></div>';
    $htmlreport .= '<div class="table-responsive">';

$htmlreport .= '<table style="margin-left:8%; margin-right:8%;  margin-top:5%;">';
$htmlreport .= '<thead>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">1) Tender No.</td>';
$htmlreport .= '<td colspan=12 style="text-align: justify;">'.$workdata->Agree_No.'';
if($workdata->Agree_Dt){
$htmlreport .= 'Date  :'.$workdata->Agree_Dt.' ';
}
$htmlreport .= '</td></tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">2) Exp. register Page No</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">Ad.Sr.No.Of Agency</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">3) Name of the contracter </td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$workdata->Agency_Nm.','.$Agency_Pl->Agency_Pl.'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=4 style="text-align: left;"></td>';
$htmlreport .= '<td colspan=5 style="text-align: left;"> PAN No.:'.$Agency_Pl->Pan_no.'</td>';
$htmlreport .= '<td colspan=10 style="text-align: left;">GST No.:'.$Agency_Pl->Gst_no.'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">4) Sr. No. of the</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$rbbillno.'  '.$rbbiill.'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">5) Major Head</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;"> '.DB::table('fundhdms')->where('F_H_id', $workdata->F_H_id)->value('F_H_CODE').'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">6) Name of Work:</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$workdata->Work_Nm.'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">7) Name of P.S.</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$workdata->Sub_Div.'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">8) Gross amount of Bill</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$commonHelper->formatIndianRupees($Billinfo->c_netamt).' </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">9) Net amount of Bill</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$commonHelper->formatIndianRupees($Billinfo->chq_amt).'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">10) Details of Recovery</td>';
$htmlreport .= '</tr>';


$htmlreport .= '<tr colspan=12 style="margin-top:5%;">';
$htmlreport .= '<td colspan=12 style="text-align: right;">';
$htmlreport .= '<table>';
$htmlreport .= '<thead>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">11)  a)  Roller No. </td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">Recovery </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">12)  b)  Tanker No. </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=3 style="text-align: left;">13)  T.E.O. Register No</td>';
$htmlreport .= '<td colspan=3 style="text-align: left;"> Page No</td>';
$htmlreport .= '<td colspan=3 style="text-align: left;"> Sr No</td>';
$htmlreport .= '<td colspan=3 style="text-align: left;"> Asfalt/Cement/Steel</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">14) Held for Securities</td>';
$htmlreport .= '</tr>';


$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">15)  Security Deposite</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">16)  Work Insuarance</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">17)  Labour charges</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">18)Income Tax </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;"> 19)  T.D.S.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">20) CGST</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">21)S.G.S.T.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left; ">22) Royalty Charges</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left; ">23) Fine Recovery</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">24)Audit Recovery </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">25)Competed works register page No.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '</thead>';
$htmlreport .= '</table>';
$htmlreport .= '</td>';
// Bill Ded Table.......................................

$DedMaster_Info=DB::table('dedmasters')->select('Ded_M_Id')->get();
//  dd($DedMaster_Info);

$billDed_Info=DB::table('billdeds')->where('t_bill_Id' , $tbillid)->get('Ded_M_Id');
// dd($billDed_Info);


// dd($billDed_Info->Ded_M_Id === $billDed_Info->Ded_M_Id);


// dd($tbillid);
$sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
// dd($sammarydata);
$C_netAmt= $sammarydata->c_netamt;
$chqAmt= $sammarydata->chq_amt;
$commonHelper = new CommonHelper();
$amountInWords = $commonHelper->convertAmountToWords($C_netAmt);
// dd($amountInWords);

$SecDepositepc = DB::table('dedmasters')->where('Ded_M_Id', 2)->value('Ded_pc') ?: '';
$CGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 3)->value('Ded_pc') ?: '';
$SGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 4)->value('Ded_pc') ?: '';
$Incomepc = DB::table('dedmasters')->where('Ded_M_Id', 5)->value('Ded_pc') ?: '';
$Insurancepc = DB::table('dedmasters')->where('Ded_M_Id', 7)->value('Ded_pc') ?: '';
$Labourpc = DB::table('dedmasters')->where('Ded_M_Id', 8)->value('Ded_pc') ?: '';
$AdditionalSDpc = DB::table('dedmasters')->where('Ded_M_Id', 9)->value('Ded_pc') ?: '';
$Royaltypc = DB::table('dedmasters')->where('Ded_M_Id', 10)->value('Ded_pc') ?: '';
$finepc = DB::table('dedmasters')->where('Ded_M_Id', 11)->value('Ded_pc') ?: '';
$Recoverypc = DB::table('dedmasters')->where('Ded_M_Id', 13)->value('Ded_pc') ?: '';

// Check if any value is 0 and assign an empty string
$SecDepositepc = $SecDepositepc != 0 ? $SecDepositepc . '%' : '';
$CGSTpc = $CGSTpc != 0 ? $CGSTpc . '%' : '';
$SGSTpc = $SGSTpc != 0 ? $SGSTpc . '%' : '';
$Incomepc = $Incomepc != 0 ? $Incomepc . '%' : '';
$Insurancepc = $Insurancepc != 0 ? $Insurancepc . '%' : '';
$Labourpc = $Labourpc != 0 ? $Labourpc . '%' : '';
$AdditionalSDpc = $AdditionalSDpc != 0 ? $AdditionalSDpc . '%' : '';
$Royaltypc = $Royaltypc != 0 ? $Royaltypc . '%' : '';
$finepc = $finepc != 0 ? $finepc . '%' : '';
$Recoverypc = $Recoverypc != 0 ? $Recoverypc . '%' : '';



$deductionAmount=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->get();
// dd($deductionAmount);
$additionalSDAmt=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->where('Ded_Head','Additional S.D')->value('Ded_Amt');
$additionalSDAmt = $additionalSDAmt ? $additionalSDAmt : '0.00';
// dd($additionalSDAmt);
$Security=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Security Deposite')
->value('Ded_Amt');
$Security = $Security ? $Security : '0.00';
// dd($Security);
$Income=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Income Tax')
->value('Ded_Amt');
$Income = $Income ? $Income : '0.00';
// dd($Income);
$CGST=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','CGST')
->value('Ded_Amt');
$CGST = $CGST ? $CGST : '0.00';
// dd($CGST);
$SGST=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','SGST')
->value('Ded_Amt');
$SGST = $SGST ? $SGST : '0.00';
// dd($SGST);
$Insurance=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Work Insurance')
->value('Ded_Amt');
$Insurance = $Insurance ? $Insurance : '0.00';
// dd($Insurance);
$Labour=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Labour cess')
->value('Ded_Amt');
$Labour = $Labour ? $Labour : '0.00';
// dd($Labour);
$Royalty=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Royalty Charges')
->value('Ded_Amt');
$Royalty = $Royalty ? $Royalty : '0.00';
// dd($Royalty);
$fine=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','fine')
->value('Ded_Amt');
$fine = $fine ? $fine : '0.00';
// dd($fine);
$Recovery=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Audit Recovery')
->value('Ded_Amt');
$Recovery = $Recovery ? $Recovery : '0.00';
// dd($Recovery);

$htmlreport .= '<td colspan=12 style="margin-left:25%;">';
$htmlreport .= '<div style="text-align: center; margin-top: 20px;">';
$htmlreport .= '<table style="border: 1px solid black; border-collapse: collapse; margin: auto; height: 50%;">';
$htmlreport .= '<thead>';
$htmlreport .= '<tr>'; // Open a table row within the thead section
$htmlreport .= '<td style="border: 1px solid black; : 8px;">Amount</td>';
$htmlreport .= '<td style="border: 1px solid black;">Details</td>';
$htmlreport .= '</tr>'; // Close the table row within the thead section
$htmlreport .= '</thead>';
$htmlreport .= '<tbody>';

$htmlreport .='<tr >';
$htmlreport .= '<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($additionalSDAmt).'</td>';
$htmlreport .='<td style="border: 1px solid black;text-align:left;">Additional S.D: &nbsp;&nbsp;&nbsp; '.$AdditionalSDpc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Security) .'</td>';
$htmlreport .='<td style="border: 1px solid black;">Security Deposite: &nbsp;&nbsp;&nbsp; '.$SecDepositepc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Insurance) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Insurance: &nbsp;&nbsp;&nbsp; '.$Insurancepc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Labour) .'</td>';
$htmlreport .='<td style="border: 1px solid black;text-align:left;">Labour Cess: &nbsp;&nbsp;&nbsp; '. $Labourpc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Income) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Income Tax: &nbsp;&nbsp;&nbsp; '. $Incomepc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($CGST) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">CGST: &nbsp;&nbsp;&nbsp; '.$CGSTpc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($SGST) .'</td>';
$htmlreport .='<td style="border: 1px solid black;text-align:left;">SGST: &nbsp;&nbsp;&nbsp; '. $SGSTpc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Royalty) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Royalty:  charges &nbsp;&nbsp;&nbsp;'. $Royaltypc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($fine) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Fine:  &nbsp;&nbsp;&nbsp; '. $finepc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Recovery) .'</td>';
$htmlreport .='<td style="border: 1px solid black;text-align:left;">Audit Recovery:  &nbsp;&nbsp;&nbsp; '. $Recoverypc.'</td>';
$htmlreport .='</tr>';

$htmlreport .='<tr>';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> '. $commonHelper->formatIndianRupees($chqAmt).' </td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Cheque Amount &nbsp;&nbsp;&nbsp;  </td>';
$htmlreport .='</tr>';
$htmlreport .='<tr>';
$htmlreport .='<td style="border: 1px solid black; text-align:right; "> '. $commonHelper->formatIndianRupees($C_netAmt).' </td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Total &nbsp;&nbsp;&nbsp;</td>';
$htmlreport .='</tr>';

// Inner table end.......
$htmlreport .= '</tbody>';
$htmlreport .= '</table>';
$htmlreport .= '</div>';
$htmlreport .= '</td>';

//Main tr..................
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">26)% 9 action Recovery Rs.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">27) 50 Register page No.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">28)23 Register page No.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">29)Work sheet Sr No.</td>';
$htmlreport .= '</tr>';

$DAOId=DB::table('workmasters')->where('Work_Id' , $work_id)->value('DAO_Id');
// dd($DAO_Id);
$sign3=DB::table('daomasters')->where('DAO_Id' , $DAOId)->first();
//dd($sign3);

$imagePath = public_path('Uploads/signature/' . $sign3->sign);
 
$imageData = base64_encode(file_get_contents($imagePath));
$imageSrcAAO = 'data:image/jpeg;base64,' . $imageData;


$htmlreport .= '<tr colspan=12 style="margin-right:3%;">';
$htmlreport .= '<td colspan=20 style="text-align: right; margin-right:3%;">';
$htmlreport .= '<div style="width: 180px; height: 50px; display: inline-block;"> <img src="' . $imageSrcAAO . '" alt="Base64 Encoded Image" style="width: 130px; height: 60px;"></div>'; // Placeholder for signature box

$htmlreport .= '</td></tr>';


$htmlreport .= '<tr colspan=12 style="margin-right:3%;">';
$htmlreport .= '<td colspan=20 style="text-align: right; margin-right:3%;">'. $sign3->designation.'</td>';
$htmlreport .= '</tr>';
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=20 style="text-align: right; margin-right:3%;">'.$workdata->Div.'</td>';
$htmlreport .= '</tr>';
$htmlreport .= '</thead>';
$htmlreport .= '</table>';
    $htmlreport .= '</div>';

// dd($workdata);
$embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
return view('reports/form_xiv',compact('embsection2','htmlreport','workdata','tbillid'));

}


public function form_xiv_pdf_Fun($tbillid){
    // dd("OKKKK");
    $Billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('work_id','gst_amt','gst_rt','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
    'part_b_amt','gst_base','c_abeffect','c_gstbase','gross_amt','net_amt','c_gstamt','c_part_a_gstamt',
    'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','chq_amt')->first();
// dd($Billinfo);
$work_id=$Billinfo->work_id;
$workdata=DB::table('workmasters')->where('Work_Id' , $work_id)->first();
$Maxdt=DB::table('embs')->where('t_bill_Id' , $tbillid)->max('measurment_dt');
$dates = Carbon::createFromFormat('Y-m-d',  $Maxdt)->format('d/m/Y');
//dd($dates);
$billDed_Info=DB::table('billdeds')->where('t_bill_Id', $tbillid)->select('Ded_M_Id')->get();
//  dd($billDed_Info);
$billDed_Info=DB::table('dedmasters')->select('Ded_M_Id')->get();
//dd($billDed_Info);
// dd($billDed_Info->Ded_M_Id);
$Agency_Pl=DB::table('agencies')->where('id' , $workdata->Agency_Id)->select('Agency_Pl','Pan_no','Gst_no')->first();
 //dd($Agency_Pl);
$JE_nm=DB::table('jemasters')->where('jeid' , $workdata->jeid)->select('name','designation')->first();
$DYE_nm=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();
$EE_nm=DB::table('eemasters')->where('eeid' , $workdata->EE_id)->first();
// $DYE_Design=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();
//dd($DYE_nm);  $DYE_nm->designation
// $headercheck='Bill';
$cvno=$Billinfo->cv_no;
// dd($cvno);
$isFinalBill=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('final_bill','t_bill_No')->first();
// dd($isFinalBill);
$FirstBill=$isFinalBill->t_bill_No;
$FinalBill=$isFinalBill->final_bill;
//dd($FirstBill,$FinalBill);
// $header=$this->commonheader();
$rbbillno=CommonHelper::formatTItemNo($FirstBill);
$rbbiill=CommonHelper::getBillType($FinalBill);


// dd($rbbillno,$rbbiill);
$prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);
// dd($prev_rbbillno);
// dd("OKkkkkkk");
$htmlreport='';



$paymentInfo = "$tbillid";
    
    
    
    
$qrCode = QrCode::size(90)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(10)
->generate($paymentInfo);


$commonHelper = new CommonHelper();


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);


$htmlreport .= '<div style="position: absolute; top: 2%; left: 87%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">';

$htmlreport .= '<h5 style="text-align: center; font-weight:bold;  word-wrap: break-word;">(Form No. XIV)</h5>';
$htmlreport .= '<p style="margin-left:8%; margin-right:20%; text-align: center;  text-align: left;">Cashier</p>';
$htmlreport .= '<h3 style="text-align: center; font-weight:bold;  word-wrap: break-word;">SLIP TO ACCOMPLAINT CLAIM FOR MONEY OF DISBURSING</h3>';
$htmlreport .= '<p style="text-align: center; font-weight:bold;  word-wrap: break-word; ">(To be returned original by F.D./B.D.O)</p>';
$htmlreport .= '<h5 style="text-align: center; font-weight:bold;  word-wrap: break-word; ">Name of Work  :  '.$workdata->Work_Nm.'</h5>';


$htmlreport .= '<table style="margin-left:5%; margin-right:20%;">';
$htmlreport .= '<tbody>';

// $htmlreport .= '<tr>';
// $htmlreport .= '<td  style="text-align: center; font-weight:bold;"></td>';
// $htmlreport .= '<td  style="text-align: center;  font-weight:bold; paading-bottom:3%"> '.$workdata->Work_Nm.'</td>';
// $htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left;">Major Head-</td>';
$htmlreport .= '<td  style="text-align: center;"> '.DB::table('fundhdms')->where('F_H_id', $workdata->F_H_id)->value('F_H_CODE').' </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left;">Minor Head-</td>';
$htmlreport .= '<td  style="text-align: left;">  </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left;">Sub Head-</td>';
$htmlreport .= '<td  style="text-align: right;">  </td>';
$htmlreport .= '</tr>';

$htmlreport .= '</tbody>';
$htmlreport .= '</table>';


$pageWidth = 210; // A4 size width in millimeters


$htmlreport .= '<table>';
$htmlreport .= '<tbody>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: justify;">--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
$htmlreport .= '</tr>';
$htmlreport .= '</tbody>';
$htmlreport .= '</table>';

$htmlreport .= '<table>';
$htmlreport .= '<tbody>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=4 style="text-align: center; font-weight:bold;  word-wrap: break-word; padding-bottom:2%;">(To be fixed in  F.D./B.D.O)</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left;">To,<br><b>C.A. & F.O.</b></td>';
$htmlreport .= '<td  style="text-align: left;">To,<br><strong> '.$EE_nm->Designation.',<br></strong>  Date as noted as below</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left; padding-top: 5px;"> Zilla Parishad Sangli.</td>';
$htmlreport .= '<td  style="text-align: left; padding-top: 5px;">'.$workdata->Div.'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left; padding-right: 40px; padding-top: 5px;"> Pleas Furnish the Z.P./F.D. Voucher No. and date of the bill sent herewith for encashment.</td>';
$htmlreport .= '<td  style="text-align: left; padding-top: 5px;">Returned with Z.P. Rs. F.D. Voucher o. and as noted below.</td>';
$htmlreport .= '</tr>';



$htmlreport .= '</tbody>';
$htmlreport .= '</table>';



$htmlreport .= '<table>';
$htmlreport .= '<tbody>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: justify;">----------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
$htmlreport .= '</tr>';


$htmlreport .= '</tbody>';
$htmlreport .= '</table>';

$htmlreport .= '<table>';
$htmlreport .= '<tbody>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  colspan=2 style="text-align: left;"><b> '.$EE_nm->Designation.'</b><br>'.$workdata->Div.'</td>';
$htmlreport .= '<td  style="text-align: left; padding-left:240px;"><b>C.A.&F.O/B.D.O </b><br>Zilla Parishad,Sangli.</td>';
$htmlreport .= '</tr>';

// $htmlreport .= '<tr>';
// $htmlreport .= '<td colspan=4  style="text-align: left;">'.$workdata->Div.'</td>';
// $htmlreport .= '<td  style="text-align: right; padding-left: 50px;">Zilla Parishad,Sangli.</td>';
// $htmlreport .= '</tr>';

$htmlreport .= '</tbody>';
$htmlreport .= '</table>';

$htmlreport .= '<table>';
$htmlreport .= '<tbody>';



$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left; padding-top: 5px;">Bill Particular</td>';
$htmlreport .= '<td  style="text-align: right; padding-top: 5px;">'.$rbbillno.' '.$rbbiill.'</td>';
$htmlreport .= '<td  style="text-align: right; padding-top: 5px;">___________________________________________</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left; padding-top: 5px;">Gross Amount</td>';
$htmlreport .= '<td  style="text-align: right; padding-top: 5px;">'.$commonHelper->formatIndianRupees($Billinfo->c_netamt).'</td>';
$htmlreport .= '<td  style="text-align: right; padding-top: 5px;">T.V. No.__________________________</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left; padding-top: 5px;">Net Amount</td>';
$htmlreport .= '<td  style="text-align: right; padding-top: 5px;"> '.$commonHelper->formatIndianRupees($Billinfo->chq_amt).'</td>';
$htmlreport .= '<td  style="text-align: right; padding-top: 5px;">Date__________________________</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left; padding-top: 9px;">Agency</td>';
$htmlreport .= '<td  style="text-align: right; padding-top: 9px;">'.$workdata->Agency_Nm.','.$Agency_Pl->Agency_Pl.'</td>';
$htmlreport .= '<td  style="text-align: right; padding-top: 9px;">Signature__________________________</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left; padding-top: 9px;">No</td>';
$htmlreport .= '<td  style="text-align: left; padding-top: 9px;">__________________________</td>';
$htmlreport .= '<td  style="text-align: right; padding-left: 20px; padding-top: 10px;">Amout Areasury Accountant__________________________</td>';
$htmlreport .= '</tr>';


$htmlreport .= '<tr style="padding-top: 15px;">';
$htmlreport .= '<td  style="text-align: left; padding-top: 13px;">Signature of Accountant</td>';
$htmlreport .= '<td  style="text-align: left; padding-top: 13px;">__________________________</td>';
$htmlreport .= '<td  style="text-align: left; padding-top: 13px;">__________________________</td>';
$htmlreport .= '</tr>';




$htmlreport .= '</tbody>';
$htmlreport .= '</table>';


$htmlreport .= '<table>';
$htmlreport .= '<tbody>';


$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: justify;">----------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
$htmlreport .= '</tr>';


$htmlreport .= '</tbody>';
$htmlreport .= '</table>';

$rs__pay=$Billinfo->chq_amt;
//  dd($rs__pay);

    $cash_rs = $commonHelper->convertAmountToWords($rs__pay);
// dd($cash_rs);

$htmlreport .= '<table>';
$htmlreport .= '<tbody>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: center;"><strong>ACKNOWLEDGEMENT</strong></td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: justify; padding-top: 15px;">Received by cheque/cash Receipt Rs.<strong>('.$cash_rs.')</strong> For the C.A & F.O.Z.P Sangli.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan="4" style="padding-top: 8px; text-align: justify;">Date : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; on _______________________________________________________________________</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: justify; padding-top: 13px;">Place ____________________________________________                   Date:   ,</td>';
$htmlreport .= '</tr>';

$htmlreport .= '</tbody>';
$htmlreport .= '</table>';

$imagePath = public_path('Uploads/signature/' . $EE_nm->sign);
 
$imageData = base64_encode(file_get_contents($imagePath));
$imageSrcEE = 'data:image/jpeg;base64,' . $imageData;




$htmlreport .= '<p  style="text-align: right; margin-top:22px;"><img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 130px; height: 40px;"><br><strong>'.$EE_nm->Designation.'</strong><br>'.$workdata->Div.'</p>';



// //Second View Page----------------------------------------
$htmlreport .= '<div style="page-break-before: always;"></div>';
$htmlreport .= '<table style="margin-left:8%; margin-right:1%;  margin-top:4%;">';
$htmlreport .= '<thead>';
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">1) Tender No.</td>';
$htmlreport .= '<td colspan=12 style="text-align: justify;">'.$workdata->Agree_No.'';
if($workdata->Agree_Dt){
$htmlreport .= ''.$workdata->Agree_Dt.' ';
}
$htmlreport .= '</td></tr>';
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">2) Exp. register Page No</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">Ad.Sr.No.Of Agency</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">3) Name of the contracter</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$workdata->Agency_Nm.','.$Agency_Pl->Agency_Pl.' </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=10 style="text-align: left;"></td>';

$htmlreport .= '<td colspan=5 style="text-align: left;">PAN No.:  '.$Agency_Pl->Pan_no.'</td>';
$htmlreport .= '<td colspan=10 style="text-align: left;">GST No.:  '.$Agency_Pl->Gst_no.'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">4) Sr. No. of the R.A.Bill</td>';
$htmlreport .= '<td colspan=4 style="text-align: right;">'.$rbbillno.' R.A. Bill</td>';

$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">5) Major Head</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;"> '.DB::table('fundhdms')->where('F_H_id', $workdata->F_H_id)->value('F_H_CODE').'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">6) Name of Work:</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$workdata->Work_Nm.'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">7) Name of P.S.</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$workdata->Sub_Div.'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">8) Gross amount of Bill</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$commonHelper->formatIndianRupees($Billinfo->c_netamt).'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">9) Net amount of Bill</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$commonHelper->formatIndianRupees($Billinfo->chq_amt).'</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=14 style="text-align: left; ">10) Details of Recovery &nbsp;&nbsp;&nbsp; Recovery &nbsp;&nbsp;&nbsp;</td>';
$htmlreport .= '</tr>';



$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=6 style="text-align: left;">11)  a)  Roller No. </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">12)  b)  Tanker No. </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left; ">13)  T.E.O. Register No</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;"> Page No &nbsp;&nbsp;&nbsp; Sr No &nbsp;&nbsp;&nbsp; Asfalt/Cement/Steel</td>';
$htmlreport .= '</tr>';
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: right;">';
$htmlreport .= '<table>';
$htmlreport .= '<thead>';
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left; ">14) Held for Securities</td>';
$htmlreport .= '</tr>';


$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">15)  Security Deposite</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">16)  Work Insuarance</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">17)  Labour charges</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">18)Income Tax </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;"> 19)  T.D.S.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">20) CGST</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">21)S.G.S.T.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left; ">22) Royalty Charges</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left; ">23) Fine Recovery</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">24)Audit Recovery </td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td style="text-align: left;">25)Competed works register page No.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '</thead>';
$htmlreport .= '</table>';
$htmlreport .= '</td>';
// Bill Ded Table.......................................



// dd($tbillid);
$sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
// dd($sammarydata);
$C_netAmt= $sammarydata->c_netamt;
$chqAmt= $sammarydata->chq_amt;
$commonHelper = new CommonHelper();
$amountInWords = $commonHelper->convertAmountToWords($C_netAmt);
// dd($amountInWords);

$SecDepositepc = DB::table('dedmasters')->where('Ded_M_Id', 2)->value('Ded_pc') ?: '';
$CGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 3)->value('Ded_pc') ?: '';
$SGSTpc = DB::table('dedmasters')->where('Ded_M_Id', 4)->value('Ded_pc') ?: '';
$Incomepc = DB::table('dedmasters')->where('Ded_M_Id', 5)->value('Ded_pc') ?: '';
$Insurancepc = DB::table('dedmasters')->where('Ded_M_Id', 7)->value('Ded_pc') ?: '';
$Labourpc = DB::table('dedmasters')->where('Ded_M_Id', 8)->value('Ded_pc') ?: '';
$AdditionalSDpc = DB::table('dedmasters')->where('Ded_M_Id', 9)->value('Ded_pc') ?: '';
$Royaltypc = DB::table('dedmasters')->where('Ded_M_Id', 10)->value('Ded_pc') ?: '';
$finepc = DB::table('dedmasters')->where('Ded_M_Id', 11)->value('Ded_pc') ?: '';
$Recoverypc = DB::table('dedmasters')->where('Ded_M_Id', 13)->value('Ded_pc') ?: '';

// Check if any value is 0 and assign an empty string
$SecDepositepc = $SecDepositepc != 0 ? $SecDepositepc . '%' : '';
$CGSTpc = $CGSTpc != 0 ? $CGSTpc . '%' : '';
$SGSTpc = $SGSTpc != 0 ? $SGSTpc . '%' : '';
$Incomepc = $Incomepc != 0 ? $Incomepc . '%' : '';
$Insurancepc = $Insurancepc != 0 ? $Insurancepc . '%' : '';
$Labourpc = $Labourpc != 0 ? $Labourpc . '%' : '';
$AdditionalSDpc = $AdditionalSDpc != 0 ? $AdditionalSDpc . '%' : '';
$Royaltypc = $Royaltypc != 0 ? $Royaltypc . '%' : '';
$finepc = $finepc != 0 ? $finepc . '%' : '';
$Recoverypc = $Recoverypc != 0 ? $Recoverypc . '%' : '';



$deductionAmount=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->get();
// dd($deductionAmount);
$additionalSDAmt=DB::table('billdeds')->where('T_Bill_Id' ,$tbillid)->where('Ded_Head','Additional S.D')->value('Ded_Amt');
$additionalSDAmt = $additionalSDAmt ? $additionalSDAmt : '0.00';
// dd($additionalSDAmt);
$Security=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Security Deposite')
->value('Ded_Amt');
$Security = $Security ? $Security : '0.00';
// dd($Security);
$Income=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Income Tax')
->value('Ded_Amt');
$Income = $Income ? $Income : '0.00';
// dd($Income);
$CGST=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','CGST')
->value('Ded_Amt');
$CGST = $CGST ? $CGST : '0.00';
// dd($CGST);
$SGST=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','SGST')
->value('Ded_Amt');
$SGST = $SGST ? $SGST : '0.00';
// dd($SGST);
$Insurance=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Work Insurance')
->value('Ded_Amt');
$Insurance = $Insurance ? $Insurance : '0.00';
// dd($Insurance);
$Labour=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Labour cess')
->value('Ded_Amt');
$Labour = $Labour ? $Labour : '0.00';
// dd($Labour);
$Royalty=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Royalty Charges')
->value('Ded_Amt');
$Royalty = $Royalty ? $Royalty : '0.00';
// dd($Royalty);
$fine=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','fine')
->value('Ded_Amt');
$fine = $fine ? $fine : '0.00';
// dd($fine);
$Recovery=DB::table('billdeds')
->where('T_Bill_Id' ,$tbillid)
->where('Ded_Head','Audit Recovery')
->value('Ded_Amt');
$Recovery = $Recovery ? $Recovery : '0.00';
// dd($Recovery);

$htmlreport .= '<td colspan=12 style="margin-left:25%;">';
$htmlreport .= '<div style="text-align: center; ">';
$htmlreport .= '<table style="border: 1px solid black; border-collapse: collapse; margin: auto; ">';
$htmlreport .= '<thead>';
$htmlreport .= '<tr>';
$htmlreport .= '<td style="border: 1px solid black; : 8px;">Amount</td>';
$htmlreport .= '<td style="border: 1px solid black;">Details</td>';
$htmlreport .= '</tr>';
$htmlreport .= '</thead>';
$htmlreport .= '<tbody>';

$htmlreport .='<tr>';
$htmlreport .= '<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($additionalSDAmt).'</td>';
$htmlreport .='<td style="border: 1px solid black;text-align:left;">Additional S.D: &nbsp;&nbsp;&nbsp; '.$AdditionalSDpc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Security) .'</td>';
$htmlreport .='<td style="border: 1px solid black;">Security Deposite: &nbsp;&nbsp;&nbsp; '.$SecDepositepc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Insurance) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Insurance: &nbsp;&nbsp;&nbsp; '.$Insurancepc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Labour) .'</td>';
$htmlreport .='<td style="border: 1px solid black;text-align:left;">Labour Cess: &nbsp;&nbsp;&nbsp; '. $Labourpc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black;text-align:right;"> ' . $commonHelper->formatIndianRupees($Income) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Income Tax: &nbsp;&nbsp;&nbsp; '. $Incomepc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($CGST) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">CGST: &nbsp;&nbsp;&nbsp; '.$CGSTpc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($SGST) .'</td>';
$htmlreport .='<td style="border: 1px solid black;text-align:left;">SGST: &nbsp;&nbsp;&nbsp; '. $SGSTpc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Royalty) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Royalty:  charges &nbsp;&nbsp;&nbsp;'. $Royaltypc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($fine) .'</td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Fine:  &nbsp;&nbsp;&nbsp; '. $finepc.'</td>';
$htmlreport .='</tr>';
$htmlreport .='<tr >';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> ' . $commonHelper->formatIndianRupees($Recovery) .'</td>';
$htmlreport .='<td style="border: 1px solid black;text-align:left;">Audit Recovery:  &nbsp;&nbsp;&nbsp; '. $Recoverypc.'</td>';
$htmlreport .='</tr>';

$htmlreport .='<tr>';
$htmlreport .='<td style="border: 1px solid black; text-align:right;"> '. $commonHelper->formatIndianRupees($chqAmt).' </td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Cheque Amount &nbsp;&nbsp;&nbsp;  </td>';
$htmlreport .='</tr>';
$htmlreport .='<tr>';
$htmlreport .='<td style="border: 1px solid black; text-align:right; "> '. $commonHelper->formatIndianRupees($C_netAmt).' </td>';
$htmlreport .='<td style="border: 1px solid black; text-align:left;">Total &nbsp;&nbsp;&nbsp;</td>';
$htmlreport .='</tr>';

// // Inner table end.......
$htmlreport .= '</tbody>';
$htmlreport .= '</table>';
$htmlreport .= '</div>';
$htmlreport .= '</td>';

// //Main tr..................
$htmlreport .= '</tr>';


$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=14 style="text-align: left;">26)% 9 action Recovery Rs.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">27) 50 Register page No.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">28)23 Register page No.</td>';
$htmlreport .= '</tr>';

$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">29)Work sheet sr No.</td>';
$htmlreport .= '</tr>';

$DAOId=DB::table('workmasters')->where('Work_Id' , $work_id)->value('DAO_Id');
// dd($DAO_Id);
$sign3=DB::table('daomasters')->where('DAO_Id' , $DAOId)->first();

$imagePath = public_path('Uploads/signature/' . $sign3->sign);
 
$imageData = base64_encode(file_get_contents($imagePath));
$imageSrcAAO = 'data:image/jpeg;base64,' . $imageData;


$htmlreport .= '<tr colspan=12 style="margin-right:3%;">';
$htmlreport .= '<td colspan=20 style="text-align: right; margin-right:3%;">';
$htmlreport .= '<div style="width: 180px; height: 50px; display: inline-block;"> <img src="' . $imageSrcAAO . '" alt="Base64 Encoded Image" style="width: 130px; height: 60px;"></div>'; // Placeholder for signature box

$htmlreport .= '</td></tr>';


$htmlreport .= '<tr style="margin-right: 2%;">';
$htmlreport .= '<td colspan=20 style="text-align: right;">'. $sign3->designation.'</td>';
$htmlreport .= '</tr>';
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=20 style="text-align: right;">'.$workdata->Div.'</td>';
$htmlreport .= '</tr>';
$htmlreport .= '</thead>';
$htmlreport .= '</table>';

// $pdf = new Dompdf();

// // Image path using the asset helper function
// $pdf->loadHtml($htmlreport);
// //$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
// $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

// // (Optional) Set options for the PDF rendering
// $options = new Options();
// $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
// $pdf->setOptions($options);
// // $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
// $pdf->render();
// return $pdf->stream('form_xiv-Report-pdf.pdf');

$mpdf = new \Mpdf\Mpdf(['orientation' => 'P']); // Set orientation to portrait
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;


$logo = public_path('photos/zplogo5.jpeg');

// Set watermark image
$mpdf->SetWatermarkImage($logo);

// Show watermark image
$mpdf->showWatermarkImage = true;

// Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
$mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed


// Write HTML content to PDF
$mpdf->WriteHTML($htmlreport);


//$mpdf->WriteHTML($html);


// Determine the total number of pages




//dd($startPageNumber);
// Define the starting number for the displayed page numbers
// Calculate the total number of pages to be displayed in the footer



$totalPages = $mpdf->PageNo();


// Add page numbers to each page starting from the specified page number
for ($i = 2; $i <= $totalPages; $i++) {
// Calculate the displayed page number

// Set the current page for mPDF
$mpdf->page = $i;

if ($i === 1) {
    // Content centered on the first page
    $mpdf->WriteHTML('<div style="position: absolute; top: 50%; left: 50%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div>');
} 
// Write the page number to the PDF
//$mpdf->WriteHTML('<div style="position: absolute; top: 20px; right: 20px;">Page No ' . $pageNumber . '</div>');
//$startPageNumber++;

}

// Determine the total number of pages
$totalPages = $mpdf->PageNo();

// Output PDF as download
$mpdf->Output('form_xiv-' . $tbillid . '.pdf', 'D');

}


////////////////////////////////////////royalty statement report////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

public function royaltystatement(Request $request , $tbillid)
{
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    //dd($workid);
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    $headercheck='Royalty';
    $header=$this->commonheaderview($tbillid , $headercheck);
    // dd($header);
       $RoyaltyReport='';
    $RoyaltyReport .=$header;
        $RoyaltyReport .= '<div class="table-responsive">';

    $RoyaltyReport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;  margin-right: 17px;">';
    $RoyaltyReport .= '<tbody>';


    $royalmdata = DB::table('royal_m')
    ->select('royal_m')
    ->distinct()
    ->where('t_bill_Id', $tbillid)
    ->get();

    $ALLtotal=0;
    //dd($royalmdata);
    foreach($royalmdata as $roydata)
    {

        if($roydata->royal_m == 'R')
        {


            $srno=1;

            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Royalty Charges for various Material</th>';
            $RoyaltyReport .= '</tr>';
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Royalty Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';


                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'R')->get();
                $convert=new CommonHelper;

                foreach($royalRmdatas as $royalRmdata)
                {
                    $RoyaltyReport .= '<tr>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $srno . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->material . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->tot_m_qty . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_rt . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_amt=$convert->formatIndianRupees($royalRmdata->royal_amt) . '</td>';

                    $RoyaltyReport .= '</tr>';

                    $srno++;

                }

                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'R')->sum('royal_amt');
                $ALLtotal += $totalamt;
                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';

                //dd($royalRmdata);

        }

        if($roydata->royal_m == 'S')
        {

            $srno=1;

            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Surcharge @ 2.00% of Royalty charges for all Material</th>';
            $RoyaltyReport .= '</tr>';
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Surcharge Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';


                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'S')->get();
                foreach($royalRmdatas as $royalRmdata)
                {
                    $RoyaltyReport .= '<tr>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $srno . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->material . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->tot_m_qty . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_rt . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_amt=$convert->formatIndianRupees($royalRmdata->royal_amt) . '</td>';
                    $RoyaltyReport .= '</tr>';

                    $srno++;
                }



                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'S')->sum('royal_amt');
                $ALLtotal += $totalamt;
                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';
                //dd($royalRmdata);

        }

        if($roydata->royal_m == 'D')
        {

            $srno=1;

            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Contribution towards D.M.F @ 10% of Royalty</th>';
            $RoyaltyReport .= '</tr>';
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">DMF Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';


                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'D')->get();
                foreach($royalRmdatas as $royalRmdata)
                {
                    $RoyaltyReport .= '<tr>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $srno . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->material . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->tot_m_qty . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_rt . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_amt=$convert->formatIndianRupees($royalRmdata->royal_amt) . '</td>';
                    $RoyaltyReport .= '</tr>';

                    $srno++;

                }

                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'D')->sum('royal_amt');
                $ALLtotal += $totalamt;
                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';

                //dd($royalRmdata);

        }





        //dd($roydata);
    }

$convert=new CommonHelper();

    $RoyaltyReport .= '<tr>';
    $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Grand Total</th>';
    $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $ALLtotal=$convert->formatIndianRupees($ALLtotal) . '</th>';
    $RoyaltyReport .= '</tr>';

    $RoyaltyReport .= '<tr style="line-height: 0;">';
$RoyaltyReport .= '<td colspan="2" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
//$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
if($embsection2->mb_status >= '3')
{

$RoyaltyReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;">';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
$RoyaltyReport .= '</div>';
$RoyaltyReport .= '</td>'; // First cell for signature details
}
$RoyaltyReport .= '<td colspan="3" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
//$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>At the site of checked by me work</strong></div>';
if($embsection2->mb_status >= '4')
{

$RoyaltyReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
$RoyaltyReport .= '</div>';
}
$RoyaltyReport .= '</td>'; // First cell for signature details

$RoyaltyReport .= '</tr>';


    $RoyaltyReport .= '</tbody>';
    $RoyaltyReport .= '</table>';
        $RoyaltyReport .= '</div>';

 //dd($embsection2);
    return view('reports/Royaltystatement' ,compact( 'embsection2' ,'tbillid', 'RoyaltyReport'));

   }


// royalty report pdf function
   public function royaltystatementreport(Request $request , $tbillid)
   {
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    //dd($workid);
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    $headercheck='Royalty';
    //$header=$this->commonheader($tbillid , $headercheck);
    // dd($header);
       $RoyaltyReport='';


       $tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
       //dd($recordentrynos);
       
       $division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
       //dd($tbillid);
       
            $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
            $billType = CommonHelper::getBillType($embsection2->final_bill);
       //dd($formattedTItemNo , $billType);
       
       
       $agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';
       
       
       $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
            $billType = CommonHelper::getBillType($tbilldata->final_bill);
       //dd($formattedTItemNo , $billType);
       
       // $tbillid = 12345;
       // $workid = 56263546723;
       
       $billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
       
       $paymentInfo = "$tbillid";
       
       
       
       
       $qrCode = QrCode::size(90)
       ->backgroundColor(255, 255, 255)
       ->color(0, 0, 0)
       ->margin(10)
       ->generate($paymentInfo);
       
       
       // Convert the QR code SVG data to a plain string without the XML declaration
       $qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);
      
       
       $RoyaltyReport .= '<div style="position: absolute; top: 12%; left: 83%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">
       

       <table style="width: 100%; border-collapse: collapse;">
       
       <tr>
       <td  colspan="1" style="padding: 4px; text-align: left;"><h4><strong>' . $division . '</strong></h4></td>
       <td  colspan="2" style=" padding: 4px; text-align: center; margin: 0 10px;"><h4><strong>MB NO: ' . $workid . '</strong></h4></td>
       <td  style="padding: 4px; text-align: right;"><h4><strong>' . $workdata->Sub_Div . '</strong></h4></td>
       </tr>
              
       <tr>
       <td colspan="14" style="text-align: center;"><h2><strong>ROYALTY STATEMENT</strong></h2></td>
       </tr>
       
       
       <tr>
       <td  colspan="2" style=""></td>
       <td  colspan="2" style="padding: 8px; text-align: left;"><h5><strong>Bill No : ' . $formattedTItemNo . ' ' . $billType . '</strong></h5></td>
       </tr>
       
       
       
       <tr>
       <td  style=""></td>
       <td  style="padding: 8px; text-align: center;"><h5><strong></strong></h5></td>
       </tr>
       
       <tr>
       <td style=""><strong>Name of Work:</strong></td>
       <td colspan="2">' . $workdata->Work_Nm . '</td>
       </tr>
       
       <tr>
       <td  style=""><strong>Agency:</strong></td>
       <td  style="">' . $workdata->Agency_Nm . '</td>
       </tr>';
       
       $RoyaltyReport .= '<tr>';
       $RoyaltyReport .= '<td colspan="3" style="width: 50%;"><strong>Authority:</strong>'.$workdata->Agree_No.'</td>';
       if(!empty($agreementDate))
       {
       $RoyaltyReport .= '<td colspan="" style="width: 50%; text-align: right;"><strong>Date:</strong>' . $agreementDate . '</td>';
       }
       else{
          $RoyaltyReport .= '<td colspan="" style="width: 40%;"></td>';
       
       }
       $RoyaltyReport .= '</tr>';
       
       $workdate=$workdata->Wo_Dt ?? null;
       $workorderdt = date('d-m-Y', strtotime($workdate));
       
       $RoyaltyReport .= '<tr>';
       $RoyaltyReport .= '<td colspan="3" style="width: 60%;"><strong>WorkOrder No:</strong>' . $workdata->WO_No . '</td>';
       $RoyaltyReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>WorkOrder Date:</strong>' . $workorderdt . '</td>';
       $RoyaltyReport .= '</tr>';
       
       
       $normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
       $steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');
       
       $combinedDates = $normalmeas->merge($steelmeas);
       $maxDate = $combinedDates->max();
       $maxdate = date('d-m-Y', strtotime($maxDate));
       
       
       if ($tbilldata->final_bill === 1) {
       $date = $workdata->actual_complete_date ?? null;
       $workcompletiondate = date('d-m-Y', strtotime($date));
       
       $RoyaltyReport .= '<tr>';
       $RoyaltyReport .= '<td colspan="3" style="width: 60%;"><strong>Actual Date of Completion:</strong>' . $workcompletiondate . '</td>';
       $RoyaltyReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
       $RoyaltyReport .= '</tr>';
       
       
       
       } else {
       $date = $workdata->Stip_Comp_Dt ?? null;
       $workcompletiondate = date('d-m-Y', strtotime($date));
       
       $RoyaltyReport .= '<tr>';
       $RoyaltyReport .= '<td colspan="3" style="width: 60%;"><strong>Stipulated Date of Completion:</strong>' . $workcompletiondate . '</td>';
       $RoyaltyReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
       $RoyaltyReport .= '</tr>';
       
       
       }
       $RoyaltyReport .= '</table></div>';


 $convert=new CommonHelper();


    //$RoyaltyReport .=$header;
    $RoyaltyReport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;  margin-right: 17px;">';
    $RoyaltyReport .= '<tbody>';


    $royalmdata = DB::table('royal_m')
    ->select('royal_m')
    ->distinct()
    ->where('t_bill_Id', $tbillid)
    ->get();

    $ALLtotal=0;
    //dd($royalmdata);
    foreach($royalmdata as $roydata)
    {

        if($roydata->royal_m == 'R')
        {


            $srno=1;

            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Royalty Charges for various Material</th>';
            $RoyaltyReport .= '</tr>';
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Royalty Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';


                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'R')->get();
                                $convert=new CommonHelper;

                foreach($royalRmdatas as $royalRmdata)
                {
                    $RoyaltyReport .= '<tr>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $srno . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->material . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->tot_m_qty . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_rt . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_amt=$convert->formatIndianRupees($royalRmdata->royal_amt) . '</td>';
                    $RoyaltyReport .= '</tr>';

                    $srno++;

                }

                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'R')->sum('royal_amt');
                $ALLtotal += $totalamt;

                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';

                //dd($royalRmdata);

        }

        if($roydata->royal_m == 'S')
        {

            $srno=1;

            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Surcharge @ 2.00% of Royalty charges for all Material</th>';
            $RoyaltyReport .= '</tr>';
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Surcharge Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';


                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'S')->get();
                foreach($royalRmdatas as $royalRmdata)
                {
                    $RoyaltyReport .= '<tr>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $srno . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->material . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->tot_m_qty . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_rt . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_amt=$convert->formatIndianRupees($royalRmdata->royal_amt) . '</td>';
                    $RoyaltyReport .= '</tr>';

                    $srno++;
                }



                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'S')->sum('royal_amt');

                $ALLtotal+=$totalamt;

                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';
                //dd($royalRmdata);

        }

        if($roydata->royal_m == 'D')
        {

            $srno=1;

            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Contribution towards D.M.F @ 10% of Royalty</th>';
            $RoyaltyReport .= '</tr>';
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">DMF Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';


                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'D')->get();
                foreach($royalRmdatas as $royalRmdata)
                {
                    $RoyaltyReport .= '<tr>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $srno . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->material . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->tot_m_qty . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_rt . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_amt=$convert->formatIndianRupees($royalRmdata->royal_amt) . '</td>';
                    $RoyaltyReport .= '</tr>';

                    $srno++;

                }

                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'D')->sum('royal_amt');

                $ALLtotal+=$totalamt;
              


                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';

                //dd($royalRmdata);

        }





        //dd($roydata);
    }



    $RoyaltyReport .= '<tr>';
    $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Grand Total</th>';
    $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' .  $ALLtotal=$convert->formatIndianRupees($ALLtotal) . '</th>';
    $RoyaltyReport .= '</tr>';



    $RoyaltyReport .= '<tr style="line-height: 0;">';
$RoyaltyReport .= '<td colspan="2" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
//$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
if($embsection2->mb_status >= '3')
{

$RoyaltyReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;">';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
$RoyaltyReport .= '</div>';
$RoyaltyReport .= '</td>'; // First cell for signature details
}
$RoyaltyReport .= '<td colspan="3" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
//$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>At the site of checked by me work</strong></div>';
if($embsection2->mb_status >= '4')
{

$RoyaltyReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
$RoyaltyReport .= '</div>';
}
$RoyaltyReport .= '</td>'; // First cell for signature details

$RoyaltyReport .= '</tr>';


    $RoyaltyReport .= '</tbody>';
    $RoyaltyReport .= '</table>';
   //dd($html);
// $pdf = new Dompdf();

// // Read the image file and convert it to base64
// //$imagePath = public_path('images/sign.jpg');
// // $imageData = base64_encode(file_get_contents($imagePath));
// //
// //$imageSrc = 'data:image/jpeg;base64,' . $imageData;


// // Image path using the asset helper function
// $pdf->loadHtml($RoyaltyReport);
// //$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
// $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

// // (Optional) Set options for the PDF rendering
// $options = new Options();
// $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
// $pdf->setOptions($options);

// $pdf->render();

// // Output the generated PDF (inline or download)
// return $pdf->stream('Royalty-'.$tbillid.'-pdf.pdf');


$mpdf = new \Mpdf\Mpdf(['orientation' => 'P']); // Set orientation to portrait
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;


$logo = public_path('photos/zplogo5.jpeg');

// Set watermark image
$mpdf->SetWatermarkImage($logo);

// Show watermark image
$mpdf->showWatermarkImage = true;

// Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
$mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed


// Write HTML content to PDF
$mpdf->WriteHTML($RoyaltyReport);


//$mpdf->WriteHTML($html);


// Determine the total number of pages




//dd($startPageNumber);
// Define the starting number for the displayed page numbers
// Calculate the total number of pages to be displayed in the footer



$totalPages = $mpdf->PageNo();


// Add page numbers to each page starting from the specified page number
for ($i = 2; $i <= $totalPages; $i++) {
// Calculate the displayed page number

// Set the current page for mPDF
$mpdf->page = $i;

if ($i === 1) {
    // Content centered on the first page
    $mpdf->WriteHTML('<div style="position: absolute; top: 50%; left: 50%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div>');
} 
// Write the page number to the PDF
//$mpdf->WriteHTML('<div style="position: absolute; top: 20px; right: 20px;">Page No ' . $pageNumber . '</div>');
//$startPageNumber++;

}

// Determine the total number of pages
$totalPages = $mpdf->PageNo();

// Output PDF as download
$mpdf->Output('Royalty-' . $tbillid . '.pdf', 'D');

}



    // public function recoveryreport(Request $request , $tbillid)
    // {
    //     $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    //     return view('reports/RecoveryStatement' ,compact('embsection2'));
    //    }



    //material consumption report view function///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

public function materialconsreport(Request $request , $tbillid)
{
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    $headercheck='materialcons';
    $header=$this->commonheaderview($tbillid , $headercheck);

    $MaterialconReport = '';
    $MaterialconReport .= $header;
    $MaterialconReport .= '<div class="table-responsive">';

    $MaterialconReport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;  margin-right: 17px;">';
    $MaterialconReport .= '<tbody>';



    $matconsmdatas=DB::table('mat_cons_m')->where('t_bill_id' , $tbillid)->get();
    $MaterialconReport .= '<tr>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; width: 6%; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Item No</th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; width: 33%; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Item of Work </th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; width: 8%; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Uptodate Quantity</th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; width: 8%; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Theorotical Rate of Consumption</th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; width: 8%; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Theorotical Consumption</th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; width: 8%; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Actual Rate of Consumption</th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; width: 8%; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Actual Consumption</th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; width: 8%; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Unit</th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; max-width: 5%; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Remark</th>';

    $MaterialconReport .= '</tr>';


    foreach($matconsmdatas as $matconsmdata)
    {




        $MaterialconReport .= '<tr>';
        $MaterialconReport .= '<td colspan="9" style=" padding-left: 50px; text-align: left; word-wrap: break-word;"><h3>' . $matconsmdata->material . '</h3></td>';
        $MaterialconReport .= '</tr>';





    $matdatas=DB::table('mat_cons_d')->where('b_mat_id' , $matconsmdata->b_mat_id)->get();
    foreach($matdatas as $matdata)
    {
        $subno='';
        if($matdata->sub_no)
        {
            $subno=$matdata->sub_no;
        }

        $MaterialconReport .= '<tr>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $matdata->t_item_no . ' ' . $subno . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;">' . $matdata->exs_nm . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $matdata->exec_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $matdata->pc_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $matdata->mat_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $matdata->A_pc_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $matdata->A_mat_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;">' . $matconsmdata->mat_unit . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;">' . $matdata->remark . '</td>';
        $MaterialconReport .= '</tr>';

    }
    //dd($matconsmdata);

    $MaterialconReport .= '<tr>';
    $MaterialconReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
    $MaterialconReport .= '<th colspan="1" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">' . $matconsmdata->tot_t_qty . '</th>';
    $MaterialconReport .= '<th colspan="1" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;"></th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">' . $matconsmdata->tot_a_qty . '</th>';
    $MaterialconReport .= '<th  colspan="2" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;"></th>';
    $MaterialconReport .= '</tr>';


    $MaterialconReport .= '<tr style="line-height: 0;">';
    $MaterialconReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '3')
{

    $MaterialconReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;">';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
    $MaterialconReport .= '</div>';
}
    $MaterialconReport .= '</td>'; // First cell for signature details
    $MaterialconReport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '4')
{

    $MaterialconReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
    $MaterialconReport .= '</div>';
}
    $MaterialconReport .= '</td>'; // First cell for signature details

    $MaterialconReport .= '</tr>';

    }

    $MaterialconReport .= '</tbody>';
    $MaterialconReport .= '</table>';
        $MaterialconReport .= '</div>';


    return view('reports/MaterialConsStatement' ,compact('embsection2' , 'MaterialconReport'));
   }




   //material consumption report pdf function


   public function materialconsreportpdf(Request $request , $tbillid)
{
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    $headercheck='materialcons';
    //$header=$this->commonheader($tbillid , $headercheck);

    $MaterialconReport = '';
    //$MaterialconReport .= $header;




    $tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
    //dd($recordentrynos);
    
    $division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
    //dd($tbillid);
    
         $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
         $billType = CommonHelper::getBillType($embsection2->final_bill);
    //dd($formattedTItemNo , $billType);
    
    
    $agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';
    
    
    $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
         $billType = CommonHelper::getBillType($tbilldata->final_bill);
    //dd($formattedTItemNo , $billType);
    
    // $tbillid = 12345;
    // $workid = 56263546723;
    
    $billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    
    $paymentInfo = "$tbillid";
    
    
    
    
    $qrCode = QrCode::size(90)
    ->backgroundColor(255, 255, 255)
    ->color(0, 0, 0)
    ->margin(10)
    ->generate($paymentInfo);
    
    
    // Convert the QR code SVG data to a plain string without the XML declaration
    $qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);
   
    
    $MaterialconReport .= '<div style="position: absolute; top: 12%; left: 83%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">
    

    <table style="width: 100%; border-collapse: collapse;">
    
    <tr>
    <td  colspan="1" style="padding: 4px; text-align: left;"><h4><strong>' . $division . '</strong></h4></td>
    <td  colspan="2" style=" padding: 4px; text-align: center; margin: 0 10px;"><h4><strong>MB NO: ' . $workid . '</strong></h4></td>
    <td  style="padding: 4px; text-align: right;"><h4><strong>' . $workdata->Sub_Div . '</strong></h4></td>
    </tr>
           
    <tr>
    <td colspan="14" style="text-align: center;"><h2><strong>MATERIAL CONSUMPTION STATEMENT</strong></h2></td>
    </tr>
    
    
    <tr>
    <td  colspan="2" style=""></td>
    <td  colspan="2" style="padding: 8px; text-align: left;"><h5><strong>Bill No : ' . $formattedTItemNo . ' ' . $billType . '</strong></h5></td>
    </tr>
    
    
    
    <tr>
    <td  style=""></td>
    <td  style="padding: 8px; text-align: center;"><h5><strong></strong></h5></td>
    </tr>
    
    <tr>
    <td style=""><strong>Name of Work:</strong></td>
    <td colspan="2">' . $workdata->Work_Nm . '</td>
    </tr>
    
    <tr>
    <td  style=""><strong>Agency:</strong></td>
    <td  style="">' . $workdata->Agency_Nm . '</td>
    </tr>';
    
    $MaterialconReport .= '<tr>';
    $MaterialconReport .= '<td colspan="3" style="width: 50%;"><strong>Authority:</strong>'.$workdata->Agree_No.'</td>';
    if(!empty($agreementDate))
    {
    $MaterialconReport .= '<td colspan="" style="width: 50%; text-align: right;"><strong>Date:</strong>' . $agreementDate . '</td>';
    }
    else{
       $MaterialconReport .= '<td colspan="" style="width: 40%;"></td>';
    
    }
    $MaterialconReport .= '</tr>';
    
    $workdate=$workdata->Wo_Dt ?? null;
    $workorderdt = date('d-m-Y', strtotime($workdate));
    
    $MaterialconReport .= '<tr>';
    $MaterialconReport .= '<td colspan="3" style="width: 60%;"><strong>WorkOrder No:</strong>' . $workdata->WO_No . '</td>';
    $MaterialconReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>WorkOrder Date:</strong>' . $workorderdt . '</td>';
    $MaterialconReport .= '</tr>';
    
    
    $normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
    $steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');
    
    $combinedDates = $normalmeas->merge($steelmeas);
    $maxDate = $combinedDates->max();
    $maxdate = date('d-m-Y', strtotime($maxDate));
    
    
    if ($tbilldata->final_bill === 1) {
    $date = $workdata->actual_complete_date ?? null;
    $workcompletiondate = date('d-m-Y', strtotime($date));
    
    $MaterialconReport .= '<tr>';
    $MaterialconReport .= '<td colspan="3" style="width: 60%;"><strong>Actual Date of Completion:</strong>' . $workcompletiondate . '</td>';
    $MaterialconReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
    $MaterialconReport .= '</tr>';
    
    
    
    } else {
    $date = $workdata->Stip_Comp_Dt ?? null;
    $workcompletiondate = date('d-m-Y', strtotime($date));
    
    $MaterialconReport .= '<tr>';
    $MaterialconReport .= '<td colspan="3" style="width: 60%;"><strong>Stipulated Date of Completion:</strong>' . $workcompletiondate . '</td>';
    $MaterialconReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
    $MaterialconReport .= '</tr>';
    
    
    }
    $MaterialconReport .= '</table></div>';





    // $MaterialconReport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;  margin-right: 10px;">';
    // // $abstractreport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px; ">';
    // $MaterialconReport .= '<thead>';
    // $MaterialconReport .= '<tr>';
    // $MaterialconReport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Tender Item No</th>';
    // $MaterialconReport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Upto Date Quantity</th>';
    // $MaterialconReport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
    // $MaterialconReport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 40%; word-wrap: break-word;">Description of Item</th>';
    // $MaterialconReport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Bill/Tender Rate</th>';
    // $MaterialconReport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width:8%; word-wrap: break-word;">Total Upto Date Amount</th>';
    // $MaterialconReport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;">Now to be Paid Amount</th>';
    // // $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 21%; word-wrap: break-word;">Record Entry No</th>';
    // $MaterialconReport .= '</tr>';
    // $MaterialconReport .= '</thead>';
   // $MaterialconReport .= '<tbody>';



   $matconsmdatas=DB::table('mat_cons_m')->where('t_bill_id' , $tbillid)->get();

   $MaterialconReport .= '<table style="border-collapse: collapse;  border: 1px solid black;">';
   $MaterialconReport .= '<thead>';
   $MaterialconReport .= '<tr>';
   $MaterialconReport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Item No</th>';
   $MaterialconReport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word; width:200px;">Item of Work</th>';
   $MaterialconReport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word; ">Uptodate Quantity</th>';
   $MaterialconReport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word; ">Theoretical Rate of Consumption</th>';
   $MaterialconReport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center; word-wrap: break-word; ">Theoretical Consumption</th>';
   $MaterialconReport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word; ">Actual Rate of Consumption</th>';
   $MaterialconReport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word; ">Actual Consumption</th>';
   $MaterialconReport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word; ">Unit</th>';
   $MaterialconReport .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Remark</th>';
   $MaterialconReport .= '</tr>';
   $MaterialconReport .= '</thead>';
   
    // $MaterialconReport .= '<tr>';
    // $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; width: 6%; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Item No</th>';
    // $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; width: 33%; background-color: #f2f2f2; text-align: left; word-wrap: break-word;">Item of Work </th>';
    // $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; width: 8%; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Uptodate Quantity</th>';
    // $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; width: 8%; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Theorotical Rate of Consumption</th>';
    // $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; width: 8%; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Theorotical Consumption</th>';
    // $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; width: 8%; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Actual Rate of Consumption</th>';
    // $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; width: 8%; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Actual Consumption</th>';
    // $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; width: 8%; background-color: #f2f2f2; text-align: left; word-wrap: break-word;">Unit</th>';
    // $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; min-width: 5%; background-color: #f2f2f2; text-align: left; word-wrap: break-word;">Remark</th>';

    //  $MaterialconReport .= '</tr>';
    //  $MaterialconReport .= '</thead>';
   // $MaterialconReport .= '<tbody>';


    foreach($matconsmdatas as $matconsmdata)
    {




        $MaterialconReport .= '<tr>';
        $MaterialconReport .= '<td colspan="9" style=" padding-left: 50px;  text-align: left; word-wrap: break-word;"><h3>' . $matconsmdata->material . '</h3></td>';
        $MaterialconReport .= '</tr>';





    $matdatas=DB::table('mat_cons_d')->where('b_mat_id' , $matconsmdata->b_mat_id)->get();
    foreach($matdatas as $matdata)
    {
        $subno='';
        if($matdata->sub_no)
        {
            $subno=$matdata->sub_no;
        }

        $MaterialconReport .= '<tr>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 5px;  text-align: right; word-wrap: break-word;">' . $matdata->t_item_no . ' ' . $subno . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 5px; text-align: left; word-wrap: break-word;">' . $matdata->exs_nm . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 5px;  text-align: right; word-wrap: break-word;">' . $matdata->exec_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 5px;  text-align: right; word-wrap: break-word;">' . $matdata->pc_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 5px;  text-align: right; word-wrap: break-word;">' . $matdata->mat_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 5px;  text-align: right; word-wrap: break-word;">' . $matdata->A_pc_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 5px;  text-align: right; word-wrap: break-word;">' . $matdata->A_mat_qty . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 5px;  text-align: left; word-wrap: break-word;">' . $matconsmdata->mat_unit . '</td>';
        $MaterialconReport .= '<td  style="border: 1px solid black; padding: 5px;  text-align: left; word-wrap: break-word;">' . $matdata->remark . '</td>';
        $MaterialconReport .= '</tr>';

    }
    //dd($matconsmdata);

    $MaterialconReport .= '<tr>';
    $MaterialconReport .= '<th colspan="4" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
    $MaterialconReport .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $matconsmdata->tot_t_qty . '</th>';
    $MaterialconReport .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;"></th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $matconsmdata->tot_a_qty . '</th>';
    $MaterialconReport .= '<th  colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;"></th>';
    $MaterialconReport .= '</tr>';


    $MaterialconReport .= '<tr style="line-height: 0;">';
    $MaterialconReport .= '<td colspan="4" style="border: 1px solid black; padding: 5px;  text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '3')
{

    $MaterialconReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;">';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
    $MaterialconReport .= '</div>';
}
    $MaterialconReport .= '</td>'; // First cell for signature details
    $MaterialconReport .= '<td colspan="5" style="border: 1px solid black; padding: 8px;  text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '4')
{

    $MaterialconReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
    $MaterialconReport .= '</div>';
}
    $MaterialconReport .= '</td>'; // First cell for signature details

    $MaterialconReport .= '</tr>';

    }

    $MaterialconReport .= '</tbody>';
    $MaterialconReport .= '</table>';




    $mpdf = new \Mpdf\Mpdf(['orientation' => 'P']); // Set orientation to portrait
    $mpdf->autoScriptToLang = true;
    $mpdf->autoLangToFont = true;
    
    
    $logo = public_path('photos/zplogo5.jpeg');
    
    // Set watermark image
    $mpdf->SetWatermarkImage($logo);
    
    // Show watermark image
    $mpdf->showWatermarkImage = true;
    
    // Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
    $mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed
    
    
    // Write HTML content to PDF
    $mpdf->WriteHTML($MaterialconReport);
    
    
    //$mpdf->WriteHTML($html);
    
    
    // Determine the total number of pages
    
    
    
    
    //dd($startPageNumber);
    // Define the starting number for the displayed page numbers
    // Calculate the total number of pages to be displayed in the footer
    
    
    
    $totalPages = $mpdf->PageNo();
    
    
    // Add page numbers to each page starting from the specified page number
    for ($i = 2; $i <= $totalPages; $i++) {
    // Calculate the displayed page number
    
    // Set the current page for mPDF
    $mpdf->page = $i;
    
    if ($i === 1) {
        // Content centered on the first page
        $mpdf->WriteHTML('<div style="position: absolute; top: 50%; left: 50%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div>');
    } 
    // Write the page number to the PDF
    //$mpdf->WriteHTML('<div style="position: absolute; top: 20px; right: 20px;">Page No ' . $pageNumber . '</div>');
    //$startPageNumber++;
    
    }
    
    // Determine the total number of pages
    $totalPages = $mpdf->PageNo();
    
    // Output PDF as download
    $mpdf->Output('Materialconsumption-' . $tbillid . '.pdf', 'D');
    


    // $pdf = new Dompdf();


    // // Image path using the asset helper function
    // $pdf->loadHtml($MaterialconReport);
    // $pdf->setPaper('A4', 'landscape'); // Set paper size and orientation

    // // (Optional) Set options for the PDF rendering
    // $options = new Options();
    // $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
    // $pdf->setOptions($options);

    // $pdf->render();

    // // Output the generated PDF (inline or download)
    // return $pdf->stream('Materialconsumption-'.$tbillid.'-pdf.pdf');
    }


    public function compcertfreport(Request $request , $tbillid)
    {
        // dd('ok');
        $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
        // dd($embsection2);

        $WorkId=$embsection2->work_id;
        $DBWorkMaster=DB::table('workmasters')
        ->where('Work_Id',$WorkId)
        ->first();

        $workdata=DB::table('workmasters')->where('Work_Id' , $WorkId)->first();
        //dd($workdata);
        $jeid=$workdata->jeid;
        $dyeid=$workdata->DYE_id;
        //dd($dyeid);
        $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
        $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
        // Construct the full file path
        $imagePath = public_path('Uploads/signature/' . $sign->sign);
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageSrc = 'data:image/jpeg;base64,' . $imageData;

        $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
        $imageData2 = base64_encode(file_get_contents($imagePath2));
        $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



        $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
        $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

        $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
        $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

     $agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';
     
     
     $convert=new Commonhelper();

        $certificateHTML = '';

        $certificateHTML .= '
            <!DOCTYPE html>
            <html lang="en">
            <head>
              <meta charset="UTF-8">
              <meta name="viewport" content="width=device-width, initial-scale=1.0">
              <title>Completion Certificate</title>
              <style>
                body {
                  font-family: Arial, sans-serif;
                  margin: 30px; /* Increase the overall margin */
                }

                .certificate {
                  max-width: 800px; /* Increase the maximum width */
                  margin: auto;
                  text-align: center;
                  border: 1px solid #ccc;
                  padding: 30px; /* Increase the padding */
                  border-radius: 10px;
                }

                .signature {
                  margin-top: 40px;
                }

                label {
                  display: inline-block;
                  text-align: left;
                  width: 40%; /* Adjust the width as needed */
                  margin-bottom: 15px; /* Increase the margin */
                }

                input {
                  width: 58%; /* Adjust the width as needed */
                  box-sizing: border-box;
                  margin-bottom: 20px; /* Increase the margin */
                  padding: 8px; /* Increase the padding */
                }
                .signature {
                    margin-top: 40px;
                    display: flex;
                    justify-content: space-between;
                  }

                  .signature p {
                    width: 45%; /* Adjust the width as needed */
                    text-align: left;
                  }
              </style>
            </head>
            <body>

            <div class="certificate">
            <h5> (FORM No. 65)</h5>
            <h5>[See Rule 190]</h5>
              <h3>Completion Certificate of Original Work</h3>

<div class="table-responsive">
              <table>
              <tr>
                  <td><strong>Name of Work:</strong></td>
                  <td style="padding-left: 30px; padding-top: 30px;">' . $DBWorkMaster->Work_Nm . '</td>
              </tr>
              <tr>
                  <td style="padding-top: 20px;"><strong>Authority:</strong></td>
                  <td style="padding-left: 10px; padding-top: 10px;">' . $DBWorkMaster->Agree_No . '   ' . ($agreementDate ? ' Date: ' . $agreementDate : '') . '</td>
              </tr>
              <tr>
                  <td style="padding-top: 20px;"><strong>Estimate No.:</strong></td>
                  <td style="padding-left: 10px; padding-top: 10px;"></td>
              </tr>
              <tr>
                  <td style="padding-top: 20px;"><strong>Plan No.:</strong></td>
                  <td style="padding-left: 10px; padding-top: 10px;"></td>
              </tr>
              <tr>
                  <td style="padding-top: 20px;"><strong>Estimated Cost:</strong></td>
                  <td style="padding-left: 10px; padding-top: 10px;">' . $convert->formatIndianRupees($DBWorkMaster->TS_Amt) . '</td>
              </tr>
              <tr>
                  <td style="padding-top: 20px;"><strong>Tendered Cost:</strong></td>
                  <td style="padding-left: 10px; padding-top: 10px;">' . $convert->formatIndianRupees($DBWorkMaster->Tnd_Amt) . '</td>
              </tr>
          </table>
                        <p style="padding-top: 40px;">Certified that the work mentioned above was completed on ' . \Carbon\Carbon::parse($DBWorkMaster->actual_complete_date)->format('d/m/Y') . '</p>
              <p>and that there have been no material deviations from the sanctioned plan</p>
              <p>and specifications other than those sanctioned by competent authority.</p>';

              $certificateHTML .= '      <table style="width: 100%;>
          <tr style="line-height: 0;">
            <td  style=" padding: 8px; max-width: 50%; text-align: center; line-height: 0;"> ';
                      if($embsection2->mb_status >= '3')
            {
            $certificateHTML .= ' <div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>
            <div style="line-height: 1; margin: 0;">
            <div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>
            <div style="line-height: 1; margin: 0;"><strong>' . $jedesignation .'</strong></div>
            <div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>
            </div> ';
                }

                $certificateHTML .= '  </td>
            <td  style=" padding: 8px; width: 50%; text-align: center; line-height: 0;">';
 if($embsection2->mb_status >= '4')
            {
            $certificateHTML .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>
            <div style="line-height: 1; margin: 0;">
            <div style="line-height: 1; margin: 0;"><strong>' . $sign->name . '</strong></div>
            <div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>
            <div style="line-height: 1; margin: 0;"><strong>' . $dyesubdivision .'</strong></div>
            </div>';
            }

            $certificateHTML .= '  </td>

          </tr>
        </table>


            </div>
          </div>
            </body>
            </html>
        ';

        return view('reports/Certificate' ,compact('embsection2','DBWorkMaster','certificateHTML'));
       }


public function compcertfreportPDF(Request $request , $tbillid)
{
    // dd('ok');

    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    // dd($embsection2);

    $WorkId=$embsection2->work_id;
    $DBWorkMaster=DB::table('workmasters')
    ->where('Work_Id',$WorkId)
    ->first();

    $workdata=DB::table('workmasters')->where('Work_Id' , $WorkId)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    //dd($dyeid);
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = $imagePath;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = $imagePath2;

     $agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';


    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');



    $billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    
    $paymentInfo = "$tbillid";
    
    
    
    
    $qrCode = QrCode::size(90)
    ->backgroundColor(255, 255, 255)
    ->color(0, 0, 0)
    ->margin(10)
    ->generate($paymentInfo);
    
    
    // Convert the QR code SVG data to a plain string without the XML declaration
    $qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);
   
    

$convert=new Commonhelper();




        $certificateHTML = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">
          <title>Completion Certificate</title>
          <style>
            body {
              font-family: Arial, sans-serif;
              margin: 0;
              padding: 0;
              border: 1px solid #ccc;
            }

            .certificate {
                border: 1px solid #ccc;
                max-width: 800px;
                margin: auto;
                text-align: center;
                border-radius: 10px;
                padding: 70px;
                background-color: #fff; /* Adding background color to the certificate */
                    }

            label {
              display: inline-block;
              text-align: left;
              width: 20%;
              margin-bottom: 15px;
            }

            input {
              width: 70%;
              box-sizing: border-box;
              margin-bottom: 20px;
              padding: 8px;
            }
          </style>
        </head>
        <body>
        <div style="position: absolute; top: 7%; left: 77%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">

        <div class="certificate">
        <h5> (FORM No. 65)</h5>
        <h5>[See Rule 190]</h5>
          <h3>Completion Certificate of Original Work</h3>

          <table>
          <tr>
              <td><strong>Name of Work:</strong></td>
              <td style="padding-left: 30px; padding-top: 30px;">' . $DBWorkMaster->Work_Nm . '</td>
          </tr>
          <tr>
              <td style="padding-top: 20px;"><strong>Authority:</strong></td>
              <td style="padding-left: 30px; padding-top: 10px;">' . $DBWorkMaster->Agree_No . '   ' . ($agreementDate ? ' Date: ' . $agreementDate : '') . '</td>
          </tr>
          <tr>
              <td style="padding-top: 20px;"><strong>Estimate No.:</strong></td>
              <td style="padding-left: 30px; padding-top: 10px;"></td>
          </tr>
          <tr>
              <td style="padding-top: 20px;"><strong>Plan No.:</strong></td>
              <td style="padding-left: 30px; padding-top: 10px;"></td>
          </tr>
          <tr>
              <td style="padding-top: 20px;"><strong>Estimated Cost:</strong></td>
              <td style="padding-left: 30px; padding-top: 10px;">' . $convert->formatIndianRupees($DBWorkMaster->TS_Amt) . '</td>
          </tr>
          <tr>
              <td style="padding-top: 20px;"><strong>Tendered Cost:</strong></td>
              <td style="padding-left: 30px; padding-top: 10px;">' . $convert->formatIndianRupees($DBWorkMaster->Tnd_Amt) . '</td>
          </tr>
      </table>
                    <p style="padding-top: 60px;">Certified that the work mentioned above was completed on ' . \Carbon\Carbon::parse($DBWorkMaster->actual_complete_date)->format('d/m/Y') . '</p>
          <p>and that there have been no material deviations from the sanctioned plan</p>
          <p>and specifications other than those sanctioned by competent authority.</p>


          <table style="width: 100%; padding-top: 60px;">
                    <tr>';

        // Check and append first image and signature
        if ($embsection2->mb_status >= '3') {
            $certificateHTML .= '<td style="width: 50%; text-align: center;">
                <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>
                <strong>' . $sign2->name . '</strong><br>
                <strong>' . $jedesignation .'</strong><br>
                <strong>' . $jesubdivision .'</strong>
            </td>';
        } else {
            // Insert an empty cell if the image and signature are not present
            $certificateHTML .= '<td style="width: 50%;"></td>';
        }

        // Check and append second image and signature
        if ($embsection2->mb_status >= '4') {
            $certificateHTML .= '<td style="width: 50%; text-align: center;">
                <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>
                <strong>' . $sign->name . '</strong><br>
                <strong>' . $dyedesignation .'</strong><br>
                <strong>' . $dyesubdivision .'</strong>
            </td>';
        } else {
            // Insert an empty cell if the image and signature are not present
            $certificateHTML .= '<td style="width: 50%;"></td>';
        }

        $certificateHTML .= '</tr></table>';

        // Complete the HTML
        $certificateHTML .= '
            </div></div>
           ';


        // $pdf = new Dompdf();

        // // Read the image file and convert it to base64
        // //$imagePath = public_path('images/sign.jpg');
        // // $imageData = base64_encode(file_get_contents($imagePath));
        // //
        // //$imageSrc = 'data:image/jpeg;base64,' . $imageData;


        // // Image path using the asset helper function
        // $pdf->loadHtml($certificateHTML);
        // //$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
        // $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

        // // (Optional) Set options for the PDF rendering
        // $options = new Options();
        // $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
        // $pdf->setOptions($options);

        // $pdf->render();


        //     return $pdf->stream('Completion-Certificate-'.$tbillid.'.pdf');




        $mpdf = new \Mpdf\Mpdf(['orientation' => 'P']); // Set orientation to portrait
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        
        
        $logo = public_path('photos/zplogo5.jpeg');
        
        // Set watermark image
        $mpdf->SetWatermarkImage($logo);
        
        // Show watermark image
        $mpdf->showWatermarkImage = true;
        
        // Set opacity of the watermark (0 to 1, where 0 is fully transparent and 1 is fully opaque)
        $mpdf->watermarkImageAlpha = 0.1; // Adjust opacity as needed
        
        
        // Write HTML content to PDF
        $mpdf->WriteHTML($certificateHTML);
        
        
        //$mpdf->WriteHTML($html);
        
        
        // Determine the total number of pages
        
        
        
        
        //dd($startPageNumber);
        // Define the starting number for the displayed page numbers
        // Calculate the total number of pages to be displayed in the footer
        
        
        
        $totalPages = $mpdf->PageNo();
        
        
        // Add page numbers to each page starting from the specified page number
        for ($i = 2; $i <= $totalPages; $i++) {
        // Calculate the displayed page number
        
        // Set the current page for mPDF
        $mpdf->page = $i;
        
        if ($i === 1) {
            // Content centered on the first page
            $mpdf->WriteHTML('<div style="position: absolute; top: 50%; left: 50%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div>');
        } 
        // Write the page number to the PDF
        //$mpdf->WriteHTML('<div style="position: absolute; top: 20px; right: 20px;">Page No ' . $pageNumber . '</div>');
        //$startPageNumber++;
        
        }
        
        // Determine the total number of pages
        $totalPages = $mpdf->PageNo();
        
        // Output PDF as download
        $mpdf->Output('Completion-Certificate-' . $tbillid . '.pdf', 'D');
    

       }





// function for the work hand over certificate view 
public function workhandovereport(Request $request , $tbillid)
{
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();


    $docpath = public_path('Uploads/Workhandovercertificates/' . $embsection2->WHOCdocument);
      // Get the file extension
      $extension = pathinfo($docpath, PATHINFO_EXTENSION);

      if (in_array($extension, ['jpg', 'jpeg'])) {
        // Pass the image path to the view
        $imagePath = '/Uploads/Workhandovercertificates/' . $embsection2->WHOCdocument;
        return view('reports.WorkhandoverCertificate', compact('imagePath' , 'embsection2'));
    } elseif ($extension === 'pdf') {
        // Force download the PDF file
        return response()->download($docpath);
    }


    // Handle cases where the file does not exist or the extension is not supported
abort(404);

}






public function createPDF() {
        // Generate PDF logic
        $pdf = new Dompdf();

          // Read the image file and convert it to base64
          $imagePath = public_path('images/sign.jpg');
          $imageData = base64_encode(file_get_contents($imagePath));
          $imageSrc = 'data:image/jpeg;base64,' . $imageData;


        // Image path using the asset helper function
        $pdf->loadHtml('

        <style>
        .table{
            border: 1px solid black;
            color: red;
            width: 100%;
        }
        </style>
        <h1>Hello, World!</h1>
        <table class="table">
                    <thead>
                        <tr>
                            <th>Firstname</th>
                            <th>Lastname</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                        <td>John</td>
                        <td>Doe</td>
                        <td>john@example.com</td>
                        </tr>
                        <tr>
                        <td>Mary</td>
                        <td>Moe</td>
                        <td>mary@example.com</td>
                        </tr>
                        <tr>
                        <td>July</td>
                        <td>Dooley</td>
                        <td>july@example.com</td>
                        </tr>
                    </tbody>
                    </table>
                    <img src="' . public_path('images/image.jpg') . '" alt="Header Image - JPG">
                    <img src="' . $imageSrc . '" alt="Base64 Encoded Image">
                    <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 200px; height: auto;">

                    ');

        $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

        // (Optional) Set options for the PDF rendering
$options = new Options();
$options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
$pdf->setOptions($options);

        $pdf->render();

        // Output the generated PDF (inline or download)
        return $pdf->stream('generated-pdf.pdf');
}



public function measdata($tbillid , $recdate)
{
    $html ='';

    $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
    for ($i = 0; $i < count($billitemdata); $i++) {
        $itemdata = $billitemdata[$i];
        //dd( $itemdata);

        $bitemId=$itemdata->b_item_id;
        $html .= '<tr>';
        $html .= '<table style="border-collapse: collapse;  border: 1px solid black;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 10%;">Item No: ' . $bitemId . ' ' . $bitemId . '</th>';
        $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 90%; text-align: justify;"> ' . $bitemId . '</th>';
        // Add more table headers as needed
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '</table>';
        $html .= '</tr>';
       // dd( $itemdata);
    }

    return $html;
}





}
