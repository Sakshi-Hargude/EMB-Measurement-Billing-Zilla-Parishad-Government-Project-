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


//PDF reports dowload and view
class ReportController extends Controller
{

    //public array varaible declared
    public $latestRecordData = [];
    public $lastupdatedRecordData = [];


 //reports open to individual bill id
public function reportbill(Request $request , $tbillid)
{

    //dd($tbillid);

     // Store the dynamic $tbillid value in the session
     $request->session()->put('global_tbillid', $tbillid);

     //bill data
$embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

//change date format
$newmeasdtfrformat = $embsection2->meas_dt_from ?? null;
$newmeasdtfr = date('d-m-Y', strtotime($newmeasdtfrformat));
$newmessuptoformat=$embsection2->meas_dt_upto ?? null;
$newmessupto = date('d-m-Y', strtotime($newmessuptoformat));
$formatpreviousbilldt=$embsection2->previousbilldt ?? null;
$previousbilldt = date('d-m-Y', strtotime($formatpreviousbilldt));
//dd($embsection2);

//return to report view page
 return view('Report', compact('embsection2' , 'newmeasdtfr' , 'newmessupto' , 'previousbilldt'));
 }

//common header ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//common header of all report pdf
public function commonheader($tbillid , $headercheck)
{
 //html declare
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


//check case wise which report is
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

    //change format of item no  and bill type
     $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
     $billType = CommonHelper::getBillType($tbilldata->final_bill);
//dd($formattedTItemNo , $billType);

//check condition of MB report and update html
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
$html .= '<td style=""><strong>Tender Id:</strong></td>';
$html .= '<td colspan="2">' . $workdata->Tender_Id . '</td>';
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

//get workdata
$workdate=$workdata->Wo_Dt ?? null;
$workorderdt = date('d-m-Y', strtotime($workdate));

$html .= '<tr>';
$html .= '<td colspan="2" style="width: 60%;"><strong>WorkOrder No:</strong>' . $workdata->WO_No . '</td>';
$html .= '<td colspan="" style="width: 40%; text-align: right;"><strong>WorkOrder Date:</strong>' . $workorderdt . '</td>';
$html .= '</tr>';

//take measurements normal or steel
$normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

//merge the both datas
$combinedDates = $normalmeas->merge($steelmeas);

//maxdata
$maxDate = $combinedDates->max();
$maxdate = date('d-m-Y', strtotime($maxDate));

//update workcompletion date according to bill type
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
//return html
return $html;
}


//common header report on view page
public function commonheaderview($tbillid , $headercheck)
{
   //html declaration
    $html='';


    //tbillid related data
    $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
$workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($recordentrynos);

//workid related data
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


//casewise check which report is there
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

     //bill type conversion
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
$html .= '<td colspan="6"><strong>Tender Id:</strong></td>';
$html .= '<td colspan="9" style="text-align: justify;">' . $workdata->Tender_Id . '</td>';
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

//normal data and steel data
$normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

//combine both data
$combinedDates = $normalmeas->merge($steelmeas);
$maxDate = $combinedDates->max();
$maxdate = date('d-m-Y', strtotime($maxDate));

//workcompletion date depends upon bill
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


//return html
return $html;
}






////MB report PDF functions/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


//public $latestRecordEntryNos = [];

 // Method to generate MB report
public function mbreport(Request $request , $tbillid)
{
     // Fetching bill details
 $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
//dd($tbillid);
 $html='';

 // Fetching work ID associated with the bill
 $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
 // Fetching work data
 $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
 //dd($workdata);
 $jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;
     // Fetching Deputy Engineer and Junior Engineer details
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
 // Construct the full file path
  // Fetching and encoding images for signatures
 $imagePath = public_path('Uploads/signature/' . $sign->sign);
 //dd($imagePath);
 $imageData = base64_encode(file_get_contents($imagePath));
 $imageSrc = 'data:image/jpeg;base64,' . $imageData;

 $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
 $imageData2 = base64_encode(file_get_contents($imagePath2));
 $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


  // Fetching designations and subdivisions
 $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
 $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

 $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
 $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

  // Fetching Executive Engineer details
     $EE_id=DB::table('workmasters')->where('Work_Id' , $workid)->value('EE_id');
     //dd($EE_id);

     //dd($dyeid);
     $sign3=DB::table('eemasters')->where('eeid' , $EE_id)->first();
     $designation=DB::table('designations')->where('Designation' , $sign3->Designation)->value('Designation');
    // dd($sign3->Designation);
     $division=DB::table('divisions')->where('div_id' , $sign3->divid)->value('div');

      // Fetching the accounting year
     $DBacYr = DB::table('acyrms')
    ->whereDate('Yr_St', '<=', $embsection2->Bill_Dt)
    ->whereDate('Yr_End', '>=', $embsection2->Bill_Dt)
    ->value('Ac_Yr');

    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
//dd($recordentrynos);
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();


     // Generating HTML for the report header
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



  // Looping through each record entry

 foreach($recordentrynos as $recordentrydata)
 {
    $recdate=$recordentrydata->Rec_date;

    // 1 table

    //dd($recordentrydata);

    $rdate=$recordentrydata->Rec_date ?? null;
    $recordentrycdate = date('d-m-Y', strtotime($rdate));


        // Adding item measurements
    $itemmeas=$this->itemmeasdata($tbillid , $recdate);


      // Adding record entry details
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
  //$html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box

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
  //$html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box

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


//convert itemno and bill type format
$formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
$billType = CommonHelper::getBillType($embsection2->final_bill);



// Check if there is a last record entry

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
  // Loop through each bill item
    foreach($billitems as $itemdata)
    {
           // Retrieve item unit for the current bill item
        $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

        $bitemId=$itemdata->b_item_id;

          // Retrieve measurement data for the current bill item
        $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
        $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $lastRecordEntry->Rec_date)->get();

         // Add HTML for item details
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


  // Check if the item has not been executed (exec_qty is 0)
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

        // Check if item ID ends with specific values
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
            {
                     // Retrieve steel measurement data for the current bill item
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $lastRecordEntry->Rec_date)->get();

                 // Retrieve bill RCC member data
                $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                // dd($stldata , $bill_rc_data);

              // List of ldiam columns

                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


              // Loop through each steel data row and swap bar_length with the first non-null ldiam column value
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


            // Initialize sums for each ldiam column
                $sums = array_fill_keys($ldiamColumns, 0);

                foreach ($stldata as $row) {
                     foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                     }
                }//dd($stldata);

               // Retrieve bill RCC member data where member exists in stlmeas table
                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemId) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemId);
                })
                ->get();

            // Get RCC member IDs for the current bill item
            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

         // Loop through each bill RCC member
        foreach ($bill_member as $index => $member) {
                //dd($member);
                    $rcmbrid=$member->rc_mbr_id;
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $lastRecordEntry->Rec_date)->get();
                //dd($memberdata);

                   // Check if member data is not empty
            if ( !$memberdata->isEmpty()) {


                $html .= '<tr>';
                $html .= '<table style="border-collapse: collapse; width: 100%;"><thead>';
                $html .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
                $html .= '</thead></table>';
                $html .= '</tr>';


                    // Add HTML for RCC member measurements
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

        // Loop through each RCC member data row and add HTML for measurements
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

      // Increment the corresponding bar length variable based on diameter
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


            //normal measurement data get by tbillid and bitemid
            $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
            $totalQty = 0;

            //loop for normaldata
                foreach($normaldata as $nordata)
                {

                    // dd($unit);
                           //formaul check
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

                  // Fetch the t_item_id from the bil_item table based on the b_item_id
                  $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

                  // Fetch the QtyDcml_Ro for the t_item_id from the tnditems table
                  $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

              // Calculate total quantity based on various filters and round it according to Qtydec
                  $totalQty=round(DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('notforpayment' , 0)->where('measurment_dt' , $lastRecordEntry->Rec_date)->sum('qty') ,  $Qtydec);
                  $totalQty=number_format($totalQty ,  3, '.', '');


                // Format the total quantity with 3 decimal places
                  $qtyaspersamerec = round(DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
                  ->sum('qty') , $Qtydec);

                  // Format the quantity for the same record date with 3 decimal places
                  $qtyaspersamerec=number_format($qtyaspersamerec ,  3, '.', '');

                //dd($qtyaspersamerec);
                   // Fetch the maximum measurement date for the given filters
                  $maxdate = DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
                  ->max('measurment_dt');

                  // Fetch the record entry number for the maximum measurement date
                  $recordentryno=DB::table('recordms')
                  ->where('t_bill_id', $tbillid)
                  ->where('Rec_date', $maxdate)
                  ->value('Record_Entry_No');
     //dd($maxdate);
                  $recqty = 0; // Initialize $recqty
 //dd($qtyaspersamerec);
    //$
 //$recqty = number_format($qtyaspersamerec + $totalQty, 3);

 // Initialize the recqty variable
 $Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
 $recqty = number_format($Recqty ,  3, '.', '');

 // Prepare item number including sub-number if available
 $itemno = $itemdata->t_item_no;

 if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
     $itemno .= $itemdata->sub_no;
 }

 // Format the previous bill quantity
 $itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);


 $itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');



 // Convert formatted recqty to float and add it to the previous bill quantity
 $recqty_float = floatval(str_replace(',', '', $recqty));
 $totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;

 $Totalquantityasper = number_format($totalquantityasper, 3, '.', '');




   // Initialize TotalQuantity
 $TotalQuantity=0;


 // Generate HTML based on different conditions
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


// Check if previous bill quantity is not zero and quantity for the same record date is zero
 if($itemdata->prv_bill_qty != 0 && $qtyaspersamerec == 0)
 {

     // Calculate Total Quantity by adding current total quantity and previous bill quantity
    $TotalQuantity=number_format($totalQty+$itemdata_prv_bill_qty, 3, '.', '');

       // If current total quantity is greater than zero, display the total quantity
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

    // Check if previous bill quantity is zero
    if($itemdata_prv_bill_qty==0)
    {
        // If previous bill quantity is zero, display "Not Executed" status
        $html .= '<tr>';
        $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;"></td>';
        $html .='</tbody>';
        $html .= '</tr>';
    }
    else{

        // If previous bill quantity is not zero
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
// If Total Quantity does not match previous bill quantity, display both previous bill quantity and Total Uptodate Quantity
  $html .= '<tr>';
  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per previous bill: </td>';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $itemdata_prv_bill_qty.' </td>';
  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 15%; font-weight: bold;">' . $unit . '</td>';
  $html .='</tbody>';
  $html .= '</tr>';



  // Display Total Uptodate Quantity
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


//lastrecordentry check by JE and EE sign
 $html .= '<tr style="line-height: 0;">';
 $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
 $html .= '<tbody>';
 $html .= '<td colspan="3" style="border: 1px solid black; padding: 8px; width: 50%; text-align: center; line-height: 0;">';
 if($embsection2->mb_status >= '3')
{
 $html .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
  //$html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
   $html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box
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
  //$html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
   $html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box
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




 $html .= '</tbody>';
 $html .= '</table>';

  // Fetch agency ID for the work
$agencyid=DB::table('workmasters')->where('Work_Id' , $workid)->value('Agency_Id');
// Fetch agency data
$agencydata=DB::table('agencies')->where('id' , $agencyid)->first();
 // Fetch bill data for agency check
$agencyceck=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
$originalDate = $agencyceck->Agency_Check_Date;
$newDate = date("d-m-Y", strtotime($originalDate));

// If agency check is approved
if($agencyceck->Agency_Check == '1')
{
 // Process agency signature image
$imagePath4 = public_path('Uploads/signature/' . $agencydata->agencysign);

    $imageData4 = base64_encode(file_get_contents($imagePath4));
    $imageSrc4 = 'data:image/jpeg;base64,' . $imageData4;

  // Create a table for agency check details
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
         $html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box
     //$html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc4 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
     $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $agencydata->agency_nm . ' , '. $agencydata->Agency_Pl .'</strong></div>';
     $html .= '</div>';
     $html .= '</td>'; // First cell for signature details
        $html .= '</tbody>';
      $html .= '</table>';
 }



 // Add a small space at the end of the table
 $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black; line-height: 1.5;">';
 $html .= '<tr>';
 $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;"></td>';
 $html .= '</tr>';
 $html .='</table>';



 // Fetch bill items and executive engineer check data
 $billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
 $eecheckdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('ee_check' , 1)->get();

 // Process signature image for displaying
    $imagePath3 = public_path('Uploads/signature/' . $sign3->sign);

    $imageData3 = base64_encode(file_get_contents($imagePath3));
    $imageSrc3 = 'data:image/jpeg;base64,' . $imageData3;


    // If there are executive engineer checks, display the relevant section
    //commented ee cheked
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

  // Initialize checked measurement amount
 $checked_mead_amt=0;
  // Fetch bill item data for the current bill
 $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
    // Fetch total bill amount
 $b_item_amt= DB::table('bills')
 ->where('t_bill_id', $tbillid)
 ->value('bill_amt');
 //dd($billitemdata);

  // Loop through each bill item
 foreach($billitemdata as $itemdata)
 {
     //dd($itemdata);
     $bitemId=$itemdata->b_item_id;
     //dd($bitemId);

     // Fetch measurement data where EE check is true
     $meassr = DB::table('embs')
     ->select('sr_no', 'ee_chk_qty')
     ->where('b_item_id', $bitemId)
     ->where('ee_check', 1)
     ->get();

          // If there are measurements with EE check, display the data
      if (!$meassr->isEmpty() ) {
          // if ($measnormaldata ) {
          //dd($measnormaldata->sr_no);

          $html .= '<tr>';

          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;"> ' . $itemdata->exs_nm . '</td>';




          // $html .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 30%;">' .
          // $meassr .'</td>';

            // Display measurement numbers
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
          // Display unit
          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;"> ' . $itemdata->item_unit . '</td>';

  $html .= '</tr>';



 // Now you can use $html as needed, for example, output it in your view or send it as a response.


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



//measurement table completed
    $html .= '</table>';


 // Calculate the percentage of checked measurements
 $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
 //dd($Checked_Percentage);
 //Format the result to have only three digits after the decimal point
 $Checked_Percentage = number_format($Checked_Percentage1, 2);

 // Format checked measurements amount to two decimal places
 $checked_meas_amt = number_format($checked_mead_amt, 2);
 // dd($Checked_Percentage);

// Image processing
  $convert= new CommonHelper();



// Start the HTML footer section

// Display checked measurements percentage and amount , ee check sign



        $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<thead>';
    $html .= '<tr style="text-align: center;">';
    $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%;"> Checked measurements ' . $embsection2->EEChk_percentage . '% . (Value Rs . ' . $convert->formatIndianRupees($embsection2->EEChk_Amt) . ')</th>';
    $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%;">';
    // $html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
    // $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
    // $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
    // $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';

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


 }



// Fetch and process additional report data
 $recdata = $this->latestRecordData;

 $lastrecentrydata = $this->lastupdatedRecordData;


 //Abstract report data
 $data=$this->abstractreportdata($tbillid , $recdata , $lastrecentrydata);


//bind abstract data to html
 $html .=$data;

 // Fetch summary data and convert amounts
  $sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
//   dd($sammarydata);
  $C_netAmt= $sammarydata->c_netamt;
  $chqAmt= $sammarydata->chq_amt;
$commonHelper = new CommonHelper();
$amountInWords = $commonHelper->convertAmountToWords($C_netAmt);

$C_netAmt=$commonHelper->formatIndianRupees($C_netAmt);

// If the status is above a certain threshold, display summary

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
     $html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box
     //$html .= '<div style="text-align: center; width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
     $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';
      $html .= '</div>';
     $html .= '</th>'; // First cell for signature details
         $html .= '</tr>';
        $html .= '</tbody></table>';


// Process deduction summary
    $commonHelperDeduction = new CommonHelper();
    // Call the function using the instance
    $htmlDeduction = $commonHelperDeduction->DeductionSummaryDetails($tbillid);
    //bind to html deduction summary
    $html .= $htmlDeduction;

      // Add closing remarks
    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-top:20px;">';
    $html .= '<tbody>';
    $html .= '<td style="padding: 8px; width: 50%; text-align: center; line-height: 0;"></td>';
 // Display signature details
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


// Return the view with the generated HTML
 return view('reports/Mb' ,compact('embsection2' , 'html'));
}



// MB report  pdf download function
public function mbreportpdf(Request $request , $tbillid)
{
     // Fetch the first record from the 'bills' table with the specified 't_bill_Id'
 $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
 //dd($embsection2);

$html='';

 // Get the 'work_id' associated with the 'tbillid' from the 'bills' table
$workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
  // Fetch the first record from the 'workmasters' table with the specified 'Work_Id'
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
// Get the 'jeid' and 'DYE_id' from the 'workmasters' table
$jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;
     // Fetch the 'dyemasters' record with the specified 'dye_id'
   $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    // Fetch the 'jemasters' record with the specified 'jeid'
   $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
// Construct the full file path
$imagePath = public_path('Uploads/signature/' . $sign->sign);
$imageSrc = $imagePath;

$imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
$imageSrc2 = $imagePath2;


 // Get the designation and subdivision details for the 'jemasters' and 'dyemasters' records
$jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
$jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

$dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
$dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

 // Get the 'EE_id' from the 'workmasters' table with the specified 'Work_Id'
    $EE_id=DB::table('workmasters')->where('Work_Id' , $workid)->value('EE_id');
    //dd($EE_id);

      // Fetch the 'eemasters' record with the specified 'eeid'
    $sign3=DB::table('eemasters')->where('eeid' , $EE_id)->first();
    $designation=DB::table('designations')->where('Designation' , $sign3->Designation)->value('Designation');
    $division=DB::table('divisions')->where('div_id' , $sign3->divid)->value('div');

    // Get the financial year based on the 'Bill_Dt' from the 'bills' record
    $DBacYr = DB::table('acyrms')
   ->whereDate('Yr_St', '<=', $embsection2->Bill_Dt)
   ->whereDate('Yr_End', '>=', $embsection2->Bill_Dt)
   ->value('Ac_Yr');



     // Create HTML content for the report header
    $html .= '
     <div class="container" style="margin-bottom: 50px; text-align: center; ">
         <div class="row justify-content-center" style="margin-top:100px;">
             <div class="col-md-8" style="height: 950px;">
                 <div class="card" style="border: 3px solid #000; border-width: 3px 1px;">

                     <div class="card-body text-center" style="height: 350px; margin-top: 15px;">
                      <div class="text-center" style="">
                         <h2 style="font-weight: bold;">'.$division.'</h2>
                     </div>
                     <div class="text-center">
                         <h3 style="font-weight: bold;">'.$dyesubdivision.'</h3>
                     </div>
                         <h2 style="margin-top: 20px;">FORM NO-52</h2>
                         <h3 style="margin-top: 20px;">MEASUREMENT BOOK</h3>
                         <h3 style="margin-top: 20px;">MB NO : '.$workid.'</h3>
                         <h4 style="margin-top: 50px;">'.$sign2->name.' , '.$jedesignation.'</h4>
                         <h5 style="margin-top: 20px;">YEAR : '.$DBacYr.'</h5>
                         <h2>Name of Work : '.$workdata->Work_Nm.'</h2>
                         <!-- Add more lines or customize as needed -->
                     </div>
                 </div>

                 <div class="text-center">
                     <p style="">(Printed Under DREAMS)</p>
                 </div>
             </div>
         </div>
     </div>';
//   <h4>Pages : From {START_PAGES}  To {TOTAL_PAGES}</h4>
$headercheck='MB';

 // Fetch the 'bills' record with the specified 't_bill_Id'
$tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
//dd($recordentrynos);

 // Get the division name from the 'divisions' table with the specified 'Div_Id'
$division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
//dd($tbillid);

 // Format the 't_bill_No' and get the bill type
     $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
     $billType = CommonHelper::getBillType($embsection2->final_bill);
//dd($formattedTItemNo , $billType);

 // Format the agreement date
$agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';



// Create HTML content for the report details
$html .= '<div class="" style="margin-top:20px; border-collapse: collapse;">

<table style="width: 100%; border-collapse: collapse;">


<tr>
<td  style=""></td>
<td  style="padding: 8px; text-align: center;"><h5><strong></strong></h5></td>
</tr>

<tr>
<td style=""><strong>Name of Work:</strong></td>
<td colspan="2">' . $workdata->Work_Nm . '</td>
</tr>

<tr>
<td style=""><strong>Tender Id:</strong></td>
<td colspan="2">' . $workdata->Tender_Id . '</td>
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

    // Format the work order date
$workdate=$workdata->Wo_Dt ?? null;
$workorderdt = date('d-m-Y', strtotime($workdate));

$html .= '<tr>';
$html .= '<td colspan="2" style="width: 60%;"><strong>Work Order No:</strong>' . $workdata->WO_No . '</td>';
$html .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Work Order Date:</strong>' . $workorderdt . '</td>';
$html .= '</tr>';

  // Get the measurement dates from 'embs' and 'stlmeas' tables
$normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

 // Combine and find the maximum measurement date
$combinedDates = $normalmeas->merge($steelmeas);
$maxDate = $combinedDates->max();
$maxdate = date('d-m-Y', strtotime($maxDate));


 // Check if the bill is final and format the completion date
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

  // Placeholder for further implementation
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

 // Fetch all record entries for the given bill, ordered by Record_Entry_Id in ascending order
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


   // Loop through each record entry except the last one
foreach($recordentrynos as $recordentrydata)
{
 $recdate=$recordentrydata->Rec_date;

 // 1 table

 //dd($recordentrydata);
 // Format the record entry date
 $rdate=$recordentrydata->Rec_date ?? null;
 $recordentrycdate = date('d-m-Y', strtotime($rdate));
 // Get item measurements data for the given bill ID and record entry date
 $itemmeas=$this->itemmeasdatapdf($tbillid , $recdate);

  // Add record entry number and date to HTML
 $html .= '<tr>';
 $html .= '<th  colspan="5" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align:left;">Record Entry No :' . $recordentrydata->Record_Entry_No . '</th>';
 $html .= '<th  colspan="4" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Date :' . $recordentrycdate . '</th>';
 $html .= '</tr>';

// 1 table end


// Add item measurements data to HTML
$html .=$itemmeas;

// Splitting a single cell into two equal-sized cells for signature
$html .= '<tr style="line-height: 0;">';
$html .= '<td colspan="5" style="border: 1px solid black; padding: 5px;  text-align: center; line-height: 0;">';
if($embsection2->mb_status >= '3')
{

$html .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
//$html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box

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
//$html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box

$html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
}
$html .= '</div>';
$html .= '</td>'; // First cell for signature details
$html .= '</tr>';


}



  // Format bill number and type
  $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
  $billType = CommonHelper::getBillType($embsection2->final_bill);


// Add the last record entry details if it exists
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


  // Loop through each bill item
foreach($billitems as $itemdata)
{
 // Get the unit of the bill item
    $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

        $bitemId=$itemdata->b_item_id;
      // Get normal and steel measurements data for the bill item on the last record entry date
    $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
    $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $lastRecordEntry->Rec_date)->get();

    // Add item number and description to HTML
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

   // Check if the item ID matches specific values to determine if steel data should be processed
        $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
    //dd($itemid);
        if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
        {
              // Fetch steel data and bill RCC members
            $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $lastRecordEntry->Rec_date)->get();

            $bill_rc_data = DB::table('bill_rcc_mbr')->get();

            //dd($stldata , $bill_rc_data);


            // Swap ldiam values with bar_length if they differ
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

                // Fetch updated steel data after swaps
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

       // Fetch the rc_mbr_ids for the given b_item_id from the 'bill_rcc_mbr' table
        $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

        // Initialize an array to hold members with data
        $membersWithData = [];
        foreach ($bill_member as $index => $member) {
            $rcmbrid = $member->rc_mbr_id;
             // Fetch measurement data for each RCC member for the specified date
            $memberdata = DB::table('stlmeas')->where('rc_mbr_id', $rcmbrid)->where('date_meas', $lastRecordEntry->Rec_date)->get();
            if (!$memberdata->isEmpty()) {
                 // Add rc_mbr_id to the array if measurement data exists
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
       // Fetch measurement data for each RCC member for the specified date
        if ( !$memberdata->isEmpty()) {
        // Start a new row in the HTML table for each member with data
            $html .= '<tr>';
            $html .= '<th colspan="2" style="border: 1px solid black;  background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
            $html .= '<th colspan="3" style="border: 1px solid black;  background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
            $html .= '<th colspan="2" style="border: 1px solid black;  background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
            $html .= '</tr>';

          // Start a nested table for bar data
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

            // Iterate over steel data to populate the bar details
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

                  // Fetch steel data for the given b_item_id and measurement date
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

                         // Iterate over the steel data and accumulate total lengths based on diameter
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

                         // Pattern matching to extract diameter and total length
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

                // Accumulate total lengths based on diameter
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


      // Fetching data from the 'embs' table based on specific conditions
        $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
        $totalQty = 0;
            foreach($normaldata as $nordata)
            {

                // dd($unit);
                     // Retrieve the formula from the data
                        $formula= $nordata->formula;


                    // Starting a new table row
                        $html .= '<tr>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->sr_no . '</td>';
                        $html .= '<td colspan="2" style="border: 1px solid black;  padding:5px; word-wrap: width: 100%; break-word; max-width: 200px;">' . $nordata->parti . '</td>';
                    if($formula)
                    {
                           // If formula exists, display formula-related data
                        $html .= '<td colspan="4" style="border: 1px solid black; padding:5px;">' . $nordata->formula . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->qty . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $unit . '</td>';



                    }
                    else
                    { // If formula does not exist, display other data
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->number . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->length . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->breadth . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->height . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->qty . '</td>';
                        $html .= '<td style="border: 1px solid black; padding:5px;">' . $unit . '</td>';

                    }
                        $html .= '</tr>';



               }

               // Get the related 't_item_id' from 'bil_item' table
              $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

              // Get the decimal rounding precision for the quantity
              $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

           // Calculate the total quantity up to the current date and round it
              $totalQty=round(DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('notforpayment' , 0)->where('measurment_dt' , $lastRecordEntry->Rec_date)->sum('qty') ,  $Qtydec);
              $totalQty=number_format($totalQty ,  3, '.', '');

            // Calculate the quantity as per the same record but for dates before the current date
              $qtyaspersamerec = round(DB::table('embs')
              ->where('t_bill_id', $tbillid)
              ->where('b_item_id', $itemdata->b_item_id)
              ->where('notforpayment' , 0)
              ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
              ->sum('qty') , $Qtydec);
              $qtyaspersamerec=number_format($qtyaspersamerec ,  3, '.', '');

            //dd($qtyaspersamerec);
              // Get the latest measurement date before the current date
              $maxdate = DB::table('embs')
              ->where('t_bill_id', $tbillid)
              ->where('b_item_id', $itemdata->b_item_id)
              ->where('notforpayment' , 0)
              ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
              ->max('measurment_dt');

              // Get the record entry number for the max date
              $recordentryno=DB::table('recordms')
              ->where('t_bill_id', $tbillid)
              ->where('Rec_date', $maxdate)
              ->value('Record_Entry_No');
 //dd($maxdate);
              $recqty = 0; // Initialize $recqty
//dd($qtyaspersamerec);
//$
//$recqty = number_format($qtyaspersamerec + $totalQty, 3);

// Calculate the total recorded quantity
$Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
$recqty = number_format($Recqty ,  3, '.', '');

// Prepare the item number
$itemno = $itemdata->t_item_no;

if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
 $itemno .= $itemdata->sub_no;
}

// Format the previous billed quantity
$itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);


$itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');



// Calculate the total quantity as per the same record
$recqty_float = floatval(str_replace(',', '', $recqty));
$totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;

$Totalquantityasper = number_format($totalquantityasper, 3, '.', '');




// Conditional display for total quantities
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

// Check if previous bill quantity is not zero and current quantity is zero
if($itemdata->prv_bill_qty != 0 && $qtyaspersamerec == 0)
{

     // Calculate the total quantity formatted to 3 decimal places
$TotalQuantity=number_format($totalQty+$itemdata_prv_bill_qty, 3, '.', '');

// Display the total quantity if $totalQty is greater than 0
if($totalQty>0)
{
//     $previousbillrqty = number_format($prevbillsqty , 3);
$html .= '<tr>';
$html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  ">' . $unit . '</td>';
$html .= '</tr>';
}

    // Check if the previous bill quantity is zero
if($itemdata_prv_bill_qty==0)
{

    $html .= '<tr>';
    $html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;   text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">Not Executed </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;"></td>';
    $html .= '</tr>';
}
else{

     // Compare TotalQuantity with itemdata_prv_bill_qty
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

 // Display previous bill quantity and total uptodate quantity

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



  //Je and dye check measurement sign

  // Splitting a single cell into two equal-sized cells for signature
$html .= '<tr style="line-height: 0;">';
$html .= '<td colspan="5" style="border: 1px solid black; padding: 5px;  text-align: center; line-height: 0;">';
if($embsection2->mb_status >= '3')
{

$html .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
//$html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box

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
//$html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box
$html .= '<br>'; // Placeholder for signature box

$html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
$html .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
}
$html .= '</div>';
$html .= '</td>'; // First cell for signature details
$html .= '</tr>';




   $html .= '</tbody></table>';



  //Agency check and sign

  // Retrieve agency details for the given work ID
    $agencyid=DB::table('workmasters')->where('Work_Id' , $workid)->value('Agency_Id');
// Fetch agency data using the retrieved agency ID
$agencydata=DB::table('agencies')->where('id' , $agencyid)->first();
 // Check agency check status and date for the given bill ID
$agencyceck=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
$originalDate = $agencyceck->Agency_Check_Date;
$newDate = date("d-m-Y", strtotime($originalDate));

// If agency check is 1 (accepted), proceed to display agency signature
if($agencyceck->Agency_Check == '1')
{

     // Construct the image path for the agency signature
$imagePath4 = public_path('Uploads/signature/' . $agencydata->agencysign);
 $imageSrc4 = $imagePath4;

 // HTML for agency signature and details
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

     //$html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc4 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
     $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
     $html .= '<br>'; // Placeholder for signature box
     $html .= '<br>'; // Placeholder for signature box
     $html .= '<br>'; // Placeholder for signature box

     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $agencydata->agency_nm . ' , '. $agencydata->Agency_Pl .'</strong></div>';
     $html .= '</div>';
     $html .= '</th>'; // First cell for signature details
     $html .= '</tr>';
     $html .= '</tbody></table>';


}



// Fetch bill items and EE check data
 $billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
 $eecheckdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('ee_check' , 1)->get();
    $imagePath3 = public_path('Uploads/signature/' . $sign3->sign);

    $imageSrc3 = $imagePath3;

    // Check if EE check data is not empty
    //EE check Comment
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

  // Get bill amount for the given bill ID
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

    // Fetch measurements data
    $meassr = DB::table('embs')
    ->select('sr_no', 'ee_chk_qty')
    ->where('b_item_id', $bitemId)
    ->where('ee_check', 1)
    ->get();

    //dd($meassr);
      if (!$meassr->isEmpty() ) {
         // if ($measnormaldata ) {
         //dd($measnormaldata->sr_no);

          // Display measurement numbers
         $html .= '<tr>';

          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;"> ' . $itemdata->exs_nm . '</td>';




         // $html .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 30%;">' .
         // $meassr .'</td>';
     // Display quantity
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

     // Retrieve the quantity where `ee_check` is 1 for the given bill ID
      $qty = DB::table('embs')
          ->where('t_bill_id', $tbillid)
          ->where('ee_check', 1)
          ->value('qty');
      //dd($qty);

      // Retrieve the bill rate for the given bill ID
      $bill_rt = DB::table('bil_item')
          ->where('t_bill_id', $tbillid)
          ->value('bill_rt');
      //dd($bill_rt);

 // Calculate the measurement amount by multiplying bill rate and quantity
      $meas_amt=$bill_rt * $qty;

     // Accumulate the measurement amount to the total checked measurement amount
      $checked_mead_amt=$checked_mead_amt+$meas_amt;

  // Store the accumulated checked measurement amount in an array
      $result[]=$checked_mead_amt;
     //dd($result);
      // dd($checked_mead_amt);
      //dd($bitemid,$measid,$qty,$bill_rt,$meas_amt);

 }

 // dd($checked_mead_amt);
 // Calculate the percentage of checked measurements relative to the total bill amount
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

      // HTML for Executive Engineer Checking
    $html .= '</table>';

    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%; text-align: left;"> Checked measurements  ' . $embsection2->EEChk_percentage . '% . (Value Rs . ' . $convert->formatIndianRupees($embsection2->EEChk_Amt) . ')</th>';
    $html .= '<th style="border: 1px solid black; padding: 5px; width: 50%;">';
  // $html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
      $html .= '<br>'; // Placeholder for signature box
    $html .= '<br>'; // Placeholder for signature box
    $html .= '<br>'; // Placeholder for signature box

    // $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
    // $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
    // $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';

    $html .= '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '</table>';

}


// Fetch additional record data
$recdata = $this->latestRecordData;

$lastrecentrydata = $this->lastupdatedRecordData;


// Generate and append additional HTML content based on record data

//get abstract data bind to html
$data=$this->abstractpdfdata($tbillid , $recdata , $lastrecentrydata);


 $html .= $data;



 $sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
//   dd($sammarydata);
  $C_netAmt= $sammarydata->c_netamt;
  $chqAmt= $sammarydata->chq_amt;
$commonHelper = new CommonHelper();

//amount conversion to words
$amountInWords = $commonHelper->convertAmountToWords($C_netAmt);
$C_netAmt=$commonHelper->formatIndianRupees($C_netAmt);

// dd($amountInWords);

// Check if the `mb_status` is greater than 10
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

    //$html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
    $html .= '<br>'; // Placeholder for signature box
    $html .= '<br>'; // Placeholder for signature box
    $html .= '<br>'; // Placeholder for signature box

    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
    $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';
     $html .= '</div>';
     $html .= '</th>'; // First cell for signature details
         $html .= '</tr>';
        $html .= '</tbody></table>';;

         // Create an instance of CommonHelper
    $commonHelperDeduction = new CommonHelper();
    // Call the function using the instance
    //deduction summary get data
    $htmlDeduction = $commonHelperDeduction->DeductionSummaryDetails($tbillid);
    //bind deduction summary data
    $html .= $htmlDeduction;

    // Append HTML for footer with C.A & F.O details
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



// Initialize mPDF object
  $mpdf = new Mpdf([
    'margin_top' => 30,  // No top margin for the first page
    'margin_left' => 28.2, // Left margin
    'margin_right' => 5, // right margin
]);
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
// Common header content for all pages

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
                      $mpdf->WriteHTML('<div style="position: absolute; top: 33%; left: 45%; right: 15%;  transform: translateX(-50%); font:weight; font-size: 12px;"><h3>(Page No from ' . $startPageNumber . ' to ' . $totalFooterPages . ')</h3></div>');


                // Deputy on top-left and Issued For SO on top-right
    $mpdf->WriteHTML('
    <div style="position: absolute; top: 10px; width: 100%; padding: 0; margin: 0; right: -2%; font-weight: bold;">
            <p style="margin: 0; font-size: 12px; ">Deputy Engineer : '.$sign->name. ' , ' .$sign->designation.'</p>
        </div>
        <div style="position: absolute; top: 10px; left: 65%; font-weight: bold;">
            <p style="margin: 0; font-size: 12px;">Issued for : '.$sign2->name.  ' , ' .$sign2->designation.  '</p>
        </div>
   ');

    }


}

//get measurement dates of normal and steel measurement
$Normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$Steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');


//combinedates both
$combinedDates = $Normalmeas->merge($Steelmeas);
$Maxdate = $combinedDates->max();
$Maxdate = date('d-m-Y', strtotime($Maxdate));


$billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();



// Define the background image in CSS
//$backgroundImagePath = 'path/to/your/image.jpg';
$mpdf->SetHTMLHeader('<div class="background-image" style="background-image: url(' . $imageSrc3 . ');"></div>');


    // Set the top margin and header only from page 2 onwards
// Add page numbers to each page starting from the specified page number
for ($i = 2; $i <= $totalPages; $i++) {


    // Calculate the displayed page number
    $pageNumber = $startPageNumber;

    //billinformation inside qrcode
$paymentInfo = "$workid" . PHP_EOL . "$pageNumber" . PHP_EOL . "$sign2->name";

//qrcode generated
$qrCode = QrCode::size(60)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(1)
->generate($paymentInfo);


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);

    // Set the current page for mPDF
    $mpdf->page = $i;


       $mpdf->WriteHTML('<div style="position: absolute; top: 3%; left: 89%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div>');

        $mpdf->WriteHTML('<div style="position: absolute; top: 3%; left: 8%; transform: translateX(-50%); font:weight;"><div style="text-align: center; font-weight: bold;">
        <table style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th colspan="1" style="padding: 4px; text-align: left;"><h4><strong>Book NO: ' . $workid . '</strong></h4></th>
                    <th style="text-align: left;"><h3><strong>' . $division . '</strong></h3></th>
                </tr>
                <tr>
                    <th colspan="14" style="text-align: center; padding: 2px;"><h4><strong>FORM NO-52</strong></h4></th>
                </tr>
                <tr>
                    <th colspan="14" style="text-align: center;"><h2><strong>MEASUREMENT BOOK</strong></h2></th>
                </tr>
            </thead>
        </table>
    </div></div>');


    // Write the page number to the PDF
$mpdf->WriteHTML('<div style="position: absolute; top: 20px; right: 20px; font-size: 12px; padding-bottom: 10px;">
        Page No ' . $pageNumber . ' out of ' . $totalFooterPages . '
    </div><br><br>'); // दोन ब्रेक टॅगने अतिरिक्त जागा मिळेल

    $startPageNumber++;

}


 $mpdf->Output('MB-' . $tbillid . '.pdf', 'D');
//return $pdf->stream('MB-' . $tbillid . '-pdf.pdf');
}






// public function mbreportpdfcopy(Request $request , $tbillid)
// {
//  $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
//  //dd($embsection2);

// $html='';

// $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
// //dd($workid);
// $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
// //dd($workdata);
// $jeid=$workdata->jeid;
// $dyeid=$workdata->DYE_id;
//    //dd($jeid);
//    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
//    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
// // Construct the full file path
// $imagePath = public_path('Uploads/signature/' . $sign->sign);
// $imageData = base64_encode(file_get_contents($imagePath));
// $imageSrc = 'data:image/jpeg;base64,' . $imageData;

// $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
// $imageData2 = base64_encode(file_get_contents($imagePath2));
// $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;



// $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
// $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

// $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
// $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

// //dd($workid);
//     $EE_id=DB::table('workmasters')->where('Work_Id' , $workid)->value('EE_id');
//     //dd($EE_id);

//     //dd($dyeid);
//     $sign3=DB::table('eemasters')->where('eeid' , $EE_id)->first();
//     $designation=DB::table('designations')->where('Designation' , $sign3->Designation)->value('Designation');
//     $division=DB::table('divisions')->where('div_id' , $sign3->divid)->value('div');


//     $DBacYr = DB::table('acyrms')
//    ->whereDate('Yr_St', '<=', $embsection2->Bill_Dt)
//    ->whereDate('Yr_End', '>=', $embsection2->Bill_Dt)
//    ->value('Ac_Yr');




//    $html .= '<div class="container" style="margin-bottom: 50px; text-align: center;">
//    <div class="row justify-content-center">
//        <div class="col-md-8">
//            <div class="card" style="border: 3px solid #000; border-width: 3px 1px; height: 950px;">

//            <div class="text-center" style="margin-top:40px;">
//            <h2 style="font-weight: bold;">'.$division.'</h2>
//            </div>
//            <div class="text-center">
//            <h3 style="font-weight: bold;">'.$dyesubdivision.'</h3>
//            </div>

//            <div class="card-body text-center" style="height: 400px; margin-top: 80px;">
//                    <h2 style="margin-top: 20px;">FORM NO-52</h2>
//                    <h3 style="margin-top: 20px;">MEASUREMENT BOOK</h3>
//                    <h3 style="margin-top: 20px;">MB NO : '.$workid.'</h3>
//                  <h4 style="margin-top: 20px;">'.$sign2->name.' , '.$jedesignation.'</h4>
//                  <h5 style="margin-top: 20px;">YEAR : '.$DBacYr.'</h5>
//                    <h2>Name of Work : '.$workdata->Work_Nm.'</h2>
//                    <!-- Add more lines or customize as needed -->
//                </div>
//            </div>
//        </div>
//    </div>
//   </div>';
// //   <h4>Pages : From {START_PAGES}  To {TOTAL_PAGES}</h4>
// $headercheck='MB';
// $header=$this->commonheader($tbillid , $headercheck);

// $html .=$header;
// //dd($header);




// // // Read the image file and convert it to base64
// // $imagePath = public_path('images/sign.jpg');
// // $imageData = base64_encode(file_get_contents($imagePath));
// // $imageSrc = 'data:image/jpeg;base64,' . $imageData;

// // $imagePath2 = public_path('images/sign2.jpg');
// // $imageData2 = base64_encode(file_get_contents($imagePath2));
// // $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


// $bitemid=null;

// $recdate=null;



//      //main table
//      $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
//      $html .= '<thead>';
//      $html .= '<tr>';
//      $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//      $html .= '<thead>';
//      $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 5%;">Sr NO</th>';
//      $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 30%;">Particulars</th>';
//      $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Number</th>';
//      $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Length</th>';
//      $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Breadth</th>';
//      $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Height</th>';
//      $html .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; width: 10%;">Quantity</th>';
//      $html .= '<th style="border: 1px solid black; padding: 9px; background-color: #f2f2f2; width: 15%;">Unit</th>';

//      $html .= '</thead>';
//      $html .= '</table>';
//      $html .= '</tr>';
//      // Add more table headers as needed
//      $html .= '</thead>';
//      $html .= '<tbody>';






//     // Fetch all record entries
// $recordentrynos = DB::table('recordms')
// ->where('t_bill_id', $tbillid)
// ->orderBy('Record_Entry_Id', 'asc')
// ->get();

// // If there are records, exclude the last one
// if ($recordentrynos->isNotEmpty()) {
// $lastRecordId = $recordentrynos->pop()->Record_Entry_Id; // Remove and get the last record ID
// $recordentrynos = $recordentrynos->values(); // Re-index the collection
// }

// // Fetch the last record entry
// $lastRecordEntry = DB::table('recordms')
// ->where('t_bill_id', $tbillid)
// ->where('Record_Entry_Id', $lastRecordId ?? null) // Fetch if lastRecordId is set
// ->first();





// foreach($recordentrynos as $recordentrydata)
// {
//  $recdate=$recordentrydata->Rec_date;

//  // 1 table

//  //dd($recordentrydata);

//  $rdate=$recordentrydata->Rec_date ?? null;
//  $recordentrycdate = date('d-m-Y', strtotime($rdate));
//  //$itemmeas ='';
//  $itemmeas=$this->itemmeasdata($tbillid , $recdate);
//  $html .= '<tr>';
//  $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//  $html .= '<thead>';
//  $html .= '<th  style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 50%;">Record Entry No :' . $recordentrydata->Record_Entry_No . '</th>';
//  $html .= '<th  style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 50%;">Date :' . $recordentrycdate . '</th>';
//  $html .= '</thead>';
//  $html .= '</table>';
//  $html .= '</tr>';

// // 1 table end


// //dd($result);
// $html .=$itemmeas;

// // Splitting a single cell into two equal-sized cells for signature
// $html .= '<tr style="line-height: 0;">';
// $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
// $html .= '<tbody>';
// $html .= '<td colspan="3" style="border: 1px solid black; padding: 5px; max-width: 40%; text-align: center; line-height: 0;">';
// if($embsection2->mb_status >= '3')
// {

// $html .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
// $html .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
// $html .= '<div style="line-height: 1; margin: 0;">';
// $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
// $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
// $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
// $html .= '</div>';
// }
// $html .= '</td>'; // First cell for signature details
// $html .= '<td colspan="6" style="border: 1px solid black; padding: 5px; max-width: 60%; text-align: center; line-height: 0;">';
// if($embsection2->mb_status >= '4')
// {

// $html .= '<div style="line-height: 1; margin: 0;"><strong>Checked by me at the site of work</strong></div>';
// $html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
// $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
// $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
// $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
// $html .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
// }
// $html .= '</div>';
// $html .= '</td>'; // First cell for signature details
//   $html .= '</tbody>';
// $html .= '</table>';
// $html .= '</tr>';
// }



// $html .= '<tr>'; // Start a new row for record entry details
// $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
// $html .= '<thead>';
// $html .= '<tr>'; // <tr> should be inside <thead>
// $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 100%;"></th>';
// $html .= '</tr>'; // Close the <tr>
// $html .= '</thead>';
// $html .= '</table>'; // Close the <table>
// $html .= '</tr>'; // Close the outer <tr>


// $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
// $billType = CommonHelper::getBillType($embsection2->final_bill);


// if ($lastRecordEntry) {
//     // Add your table view here
//     $recno=DB::table('recordms')->where('t_bill_id' , $tbillid)->where('Rec_date' , $lastRecordEntry->Rec_date)->value('Record_Entry_No');

//     // Convert Rec_date to dd mm yyyy format
// $dateFormatted = date('d-m-Y', strtotime($lastRecordEntry->Rec_date));

// $html .= '<tr>'; // Start a new row for record entry details
// $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
// $html .= '<thead>';
// $html .= '<tr>'; // <tr> should be inside <thead>
// $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 50%;">Bill: ' . $formattedTItemNo . ' ' . $billType . '</th>';
// $html .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 50%;">Date of measurement: ' . $dateFormatted . '</th>';
// $html .= '</tr>'; // Close the <tr>
// $html .= '</thead>';
// $html .= '</table>'; // Close the <table>
// $html .= '</tr>'; // Close the outer <tr>


//     $billitems = DB::table('bil_item')->where('t_bill_id', $tbillid)->orderBy('t_item_no', 'asc')->get();

//     // Now you can use $data as needed
//     // For example, you can pass it to another function or manipulate it
//     // You can also access individual elements of the array like $data['key']
//     //dd($data); // Assuming dd is a function f

//     foreach($billitems as $itemdata)
//     {

//         $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

//             $bitemId=$itemdata->b_item_id;
//         //dd($bitemId);
//         $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
//         $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $lastRecordEntry->Rec_date)->get();

//         $html .= '<tr>';
//         $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//         $html .= '<tr>';
//         $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Item No: ' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</th>';
//         $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 90%; text-align: justify;"> ' . $itemdata->item_desc . '</th>';
//         // Add more table headers as needed
//         $html .= '</tr>';
//         $html .= '</table>';
//         $html .= '</tr>';


//         $data = $this->latestRecordData;

// //dd($data);

// // Assuming $data is an associative array with keys as item numbers
// // if (isset($data[$itemdata->b_item_id])) {
// //     $recordentryno = $data[$itemdata->b_item_id]['Record_Entry_No'];
// //     $Totalqty= $data[$itemdata->b_item_id]['Total_Uptodate_Quantity'];
// //     $b_item_id=$data[$itemdata->b_item_id]['b_item_id'];

// //     $embsCount = DB::table('embs')->where('b_item_id', $b_item_id)->count();
// //     $stlmeasCount = DB::table('stlmeas')->where('b_item_id', $b_item_id)->count();

// //     if ($embsCount == 0 && $stlmeasCount == 0)  {
// //         // Add HTML for the case where measurements are not found
// //         $html .= '<tr>';
// //         $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
// //         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 80%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
// //         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed</td>';
// //         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%; font-weight: bold;"></td>';
// //         $html .='</tbody>';
// //         $html .= '</tr>';
// //     } else {
// //         // Add HTML for the case where measurements are executed
// //         $html .= '<tr>';
// //         $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
// //         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 80%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity as per this MB Record Entry No:'.$recordentryno.'</td>';
// //         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $Totalqty.' </td>';
// //         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%; font-weight: bold;">' . $unit . '</td>';
// //         $html .='</tbody>';
// //         $html .= '</tr>';
// //     }
// // }

//         //meas data check


//                     // 2 table
//             // Create a table inside the main table cell

//         // 2 table end

//         // 3 rd table


//             $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
//         //dd($itemid);
//             if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
//             {
//                 $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $lastRecordEntry->Rec_date)->get();

//                 $bill_rc_data = DB::table('bill_rcc_mbr')->get();

//                 // dd($stldata , $bill_rc_data);







//                     $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
//             'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];


//            foreach ($stldata as &$data) {
//             if (is_object($data)) {
//                 foreach ($ldiamColumns as $ldiamColumn) {
//                     if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

//                         $temp = $data->$ldiamColumn;
//                         $data->$ldiamColumn = $data->bar_length;
//                         $data->bar_length = $temp;
//                        // dd($data->bar_length , $data->$ldiamColumn);
//                         break; // Stop checking other ldiam columns if we found a match
//                     }
//                 }
//             }
//         }


//                 $sums = array_fill_keys($ldiamColumns, 0);

//                 foreach ($stldata as $row) {
//                      foreach ($ldiamColumns as $ldiamColumn) {
//                         $sums[$ldiamColumn] += $row->$ldiamColumn;
//                      }
//                 }//dd($stldata);


//                 $bill_member = DB::table('bill_rcc_mbr')
//                 ->whereExists(function ($query) use ($bitemId) {
//                 $query->select(DB::raw(1))
//                 ->from('stlmeas')
//                 ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
//                 ->where('bill_rcc_mbr.b_item_id', $bitemId);
//                 })
//                 ->get();


//             $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();


//         foreach ($bill_member as $index => $member) {
//                 //dd($member);
//                     $rcmbrid=$member->rc_mbr_id;
//                         $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $lastRecordEntry->Rec_date)->get();
//                 //dd($memberdata);

//             if ( !$memberdata->isEmpty()) {


//                 $html .= '<tr>';
//                 $html .= '<table style="border-collapse: collapse; width: 100%;"><thead>';
//                 $html .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
//                 $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
//                 $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
//                 $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
//                 $html .= '</thead></table>';
//                 $html .= '</tr>';


//                 $html .= '<tr>
//                 <table style="border-collapse: collapse; width: 100%; border: 1px solid black;">
//                <thead>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 5%;  min-width: 5%;">Sr No</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 13%; min-width: 13%;">Bar Particulars</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">No of Bars</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Length of Bars</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">6mm</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">8mm</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">10mm</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">12mm</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">16mm</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">20mm</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 9%;">25mm</th>
//                <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 9%;">28mm</th>
//                </thead><tbody>';

//                 foreach ($stldata as $bar) {

//                     if ($bar->rc_mbr_id == $member->rc_mbr_id) {

//                     //dd($bar);// Assuming the bar data is within a property like "bar_data"
//                     $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));


//                      $html .=   '<tr><td style="border: 1px solid black; padding: 5px; width: 5%;  min-width: 5%;">'. $bar->bar_sr_no .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 13%; min-width: 13%;">'. $bar->bar_particulars.'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 10%; min-width: 10%;">'. $bar->no_of_bars .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 10%; min-width: 10%;">'. $bar->bar_length .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam6 .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam8 .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam10 .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam12 .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam16 .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 7%; min-width: 7%;">'. $bar->ldiam20 .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 9%; min-width: 9%;">'. $bar->ldiam25 .'</td>
//                      <td style="border: 1px solid black; padding: 5px; width: 9%; min-width: 9%;">'. $bar->ldiam28 .'</td></tr>';



//                         }

//                         $html .='</tbody></table> </tr>';


//                     }




//                 }


//             }

//             $embssteeldata = DB::table('embs')->where('b_item_id', $bitemId)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
//     //dd($embssteeldata);

//     $barlengthl6=0;
//             $barlengthl8=0;
//             $barlengthl10=0;
//             $barlengthl12=0;
//             $barlengthl16=0;
//             $barlengthl20=0;
//             $barlengthl25=0;
//             $barlengthl28=0;
//             $barlengthl32=0;
//             $barlengthl36=0;
//             $barlengthl40=0;
//             $barlengthl45=0;

//        foreach($embssteeldata as $embdata)
//        {
//         $particular=$embdata->parti;
//         $firstThreeChars = substr($particular, 0, 3);

//         // Set $sec_type based on the first 3 characters
//         if ($firstThreeChars === "HCR") {
//             $sec_type = "HCRM/CRS Bar";
//         } else {
//             $sec_type = "TMT Bar";
//         }

//         //dd($particular);
//         if ($sec_type == "HCRM/CRS Bar") {
//             $pattern = '/HCRM\/CRS Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
//         } else {
//             $pattern = '/TMT Bar - (\d+(\.\d+)?) mm dia Total Length (\d+(\.\d+)?) R.Mt.& Weight (\d+(\.\d+)?) Kg\/R.Mt./';
//         }
//         if (preg_match($pattern, $particular, $matches)) {
//             // $matches[1] contains the diameter value
//             // $matches[3] contains the total length value
//             $diameter = $matches[1];
//             $totalLength = $matches[3];
//    // dd($diameter , $totalLength);

//     if ($diameter == '6') {
//         $barlengthl6 += $totalLength;
//     }
//     if ($diameter == '8') {
//         $barlengthl8 += $totalLength;
//     }
//     if ($diameter == '10') {
//         $barlengthl10 += $totalLength;
//     }
//     if ($diameter == '12') {
//         $barlengthl12 += $totalLength;
//     }
//     if ($diameter == '16') {
//         $barlengthl16 += $totalLength;
//     }
//     if ($diameter == '20') {
//         $barlengthl20 += $totalLength;
//     }
//     if ($diameter == '25') {
//         $barlengthl25 += $totalLength;
//     }
//     if ($diameter == '28') {
//         $barlengthl28 += $totalLength;
//     }
//     if ($diameter == '32') {
//         $barlengthl32 += $totalLength;
//     }
//     if ($diameter == '36') {
//         $barlengthl36 += $totalLength;
//     }
//     if ($diameter == '40') {
//         $barlengthl40 += $totalLength;
//     }
//     if ($diameter == '45') {
//         $barlengthl45 += $totalLength;
//     }
//             // Output the extracted values

//         }
//        }


//        $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; font-size: 13px;">
//        <thead><tr>
//            <th style="padding: 5px; width: 5%; background-color: #f2f2f2; min-width: 5%;"></th>
//            <th style="padding: 5px; background-color: #f2f2f2; width: 13%; min-width: 13%;"></th>
//            <th style="padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;"></th>
//            <th style="padding: 5px; background-color: #f2f2f2; width: 10%; min-width: 10%;">Total</th>
//            <th style="border: 1px solid black; padding: 5px; width: 7%; background-color: #f2f2f2;">'. number_format($barlengthl6, 3) .'</th>
//            <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl8, 3) .'</th>
//            <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl10, 3) .'</th>
//            <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl12, 3) .'</th>
//            <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl16, 3) .'</th>
//            <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 7%; min-width: 7%;">'. number_format($barlengthl20, 3) .'</th>
//            <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 7%;">'. number_format($barlengthl25, 3) .'</th>
//            <th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 9%; min-width: 7%;">'. number_format($barlengthl28, 3) .'</th>
//            <tr></thead>
//    </table>';



//     }



//             $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $lastRecordEntry->Rec_date)->get();
//             $totalQty = 0;
//                 foreach($normaldata as $nordata)
//                 {

//                     // dd($unit);

//                             $formula= $nordata->formula;

//                                 $html .= '<tr>';
//                                 $html .= '<table style="border-collapse: collapse; width: 100%;"><tbody>';
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 5%;">' . $nordata->sr_no . '</td>';
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 30%; word-wrap: break-word; max-width: 200px;">' . $nordata->parti . '</td>';
//                             if($formula)
//                             {

//                                 $html .= '<td colspan="4" style="border: 1px solid black; padding: 5px; width: 40%;">' . $nordata->formula . '</td>';
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->qty . '</td>';
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 15%;">' . $unit . '</td>';



//                             }
//                             else
//                             {
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->number . '</td>';
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->length . '</td>';
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->breadth . '</td>';
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->height . '</td>';
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->qty . '</td>';
//                                 $html .= '<td style="border: 1px solid black; padding: 5px; width: 15%;">' . $unit . '</td>';

//                             }
//                                 $html .='</tbody></table>';
//                                 $html .= '</tr>';



//                   }

//                   $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

//                   $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

//                //dd($Qtydec);
//                   $totalQty=round(DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('notforpayment' , 0)->where('measurment_dt' , $lastRecordEntry->Rec_date)->sum('qty') ,  $Qtydec);
//                   $totalQty=number_format($totalQty ,  3, '.', '');


//                   $qtyaspersamerec = round(DB::table('embs')
//                   ->where('t_bill_id', $tbillid)
//                   ->where('b_item_id', $itemdata->b_item_id)
//                   ->where('notforpayment' , 0)
//                   ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
//                   ->sum('qty') , $Qtydec);
//                   $qtyaspersamerec=number_format($qtyaspersamerec ,  3, '.', '');

//                 //dd($qtyaspersamerec);

//                   $maxdate = DB::table('embs')
//                   ->where('t_bill_id', $tbillid)
//                   ->where('b_item_id', $itemdata->b_item_id)
//                   ->where('notforpayment' , 0)
//                   ->where('measurment_dt', '<', $lastRecordEntry->Rec_date)
//                   ->max('measurment_dt');

//                   $recordentryno=DB::table('recordms')
//                   ->where('t_bill_id', $tbillid)
//                   ->where('Rec_date', $maxdate)
//                   ->value('Record_Entry_No');
//      //dd($maxdate);
//                   $recqty = 0; // Initialize $recqty
//  //dd($qtyaspersamerec);
//     //$
//  //$recqty = number_format($qtyaspersamerec + $totalQty, 3);

//  $Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
//  $recqty = number_format($Recqty ,  3, '.', '');

//  $itemno = $itemdata->t_item_no;

//  if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
//      $itemno .= $itemdata->sub_no;
//  }

//  $itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);


//  $itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');




//  $recqty_float = floatval(str_replace(',', '', $recqty));
//  $totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;

//  $Totalquantityasper = number_format($totalquantityasper, 3, '.', '');





//  $TotalQuantity=0;


//  if($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0 && $totalQty == 0)
//  {


//        $html .= '<tr>';
//        $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
//        $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
//        $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;"></td>';
//        $html .='</tbody>';
//        $html .= '</tr>';





//  }
//  elseif($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0 )
//  {

//     $TotalQuantity=$totalQty;
//     $html .= '<tr>';
//     $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//     $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
//     $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
//     $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
//     $html .='</tbody>';
//     $html .= '</tr>';


//  }



// if($qtyaspersamerec != 0)
// {

//     $TotalQuantity=number_format($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '');

// //dd($qtyaspersamerec , $totalQty);
// if($totalQty>0)
// {
// $html .= '<tr>';
// $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
// $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total:</td>';
// $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
// $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
// $html .='</tbody>';
// $html .= '</tr>';

// }

// if($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty == 0)
// {
//     //dd($TotalQuantity);
//     $html .= '<tr>';
//     $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//     $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
//     $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
//     $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;"></td>';
//     $html .='</tbody>';
//     $html .= '</tr>';
// }
// else{


// if($TotalQuantity == number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '') && $TotalQuantity > 0)
// {
//     $html .= '<tr>';
//     $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//     $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity(As per this MB Record Entry No:'.$recordentryno.')</td>';
//     $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
//     $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
//     $html .='</tbody>';
//     $html .= '</tr>';


// }

// else
// {
//                   $html .= '<tr>';
//                   $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//                   $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per this MB Record Entry No:'.$recordentryno.',  Item No:'.$itemno.'</td>';
//                   $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '').' </td>';
//                   $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
//                   $html .='</tbody>';
//                   $html .= '</tr>';



//                   $html .= '<tr>';
// $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
// $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
// $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
// $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
// $html .='</tbody>';
// $html .= '</tr>';
// //dd($TotalQuantity);

// }

// }

// }





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

//  if($itemdata->prv_bill_qty != 0 && $qtyaspersamerec == 0)
//  {

//     $TotalQuantity=number_format($totalQty+$itemdata_prv_bill_qty, 3, '.', '');

//     if($totalQty>0)
//     {
// //     $previousbillrqty = number_format($prevbillsqty , 3);
// $html .= '<tr>';
// $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
// $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total:</td>';
// $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
// $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
// $html .='</tbody>';
// $html .= '</tr>';
//     }


//     if($itemdata_prv_bill_qty==0)
//     {

//         $html .= '<tr>';
//         $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity</td>';
//         $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">Not Executed </td>';
//         $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;"></td>';
//         $html .='</tbody>';
//         $html .= '</tr>';
//     }
//     else{

//     if($TotalQuantity == $itemdata_prv_bill_qty && $TotalQuantity > 0)
// {
//     $html .= '<tr>';
//     $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//     $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity(As per previous bill)</td>';
//     $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
//     $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
//     $html .='</tbody>';
//     $html .= '</tr>';


// }

// else
// {

//   //dd($itemdata);

//   $html .= '<tr>';
//   $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//   $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per previous bill: </td>';
//   $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $itemdata_prv_bill_qty.' </td>';
//   $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
//   $html .='</tbody>';
//   $html .= '</tr>';



//   //dd($TotalQuantity);
// //dd($totalQty+$itemdata_prv_bill_qty);
//   $html .= '<tr>';
//                   $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//                   $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity: </td>';
//                   $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $TotalQuantity.' </td>';
//                   $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%;">' . $unit . '</td>';
//                   $html .='</tbody>';
//                   $html .= '</tr>';



// }



// }
//                 }

// // 3 table end
// // 3 table end

// $nordata=DB::table('embs')->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $lastRecordEntry->Rec_date)->count();
// $steeldata=DB::table('stlmeas')->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $lastRecordEntry->Rec_date)->count();

// if($nordata > 0 || $steeldata > 0)
// {
// $this->lastupdatedRecordData[$itemdata->b_item_id] = [
//     'Record_Entry_No' => $recno,
//     't_item_no' => $itemdata->t_item_no, // Include t_item_no for comparison
//     'Total_Uptodate_Quantity' => $TotalQuantity,
//     'b_item_id' => $itemdata->b_item_id, // Include b_item_id
// ];

// }





//     }

//     $html .= '</tbody>';
//     $html .= '</table>';
//     $html .= '</tr>';
// }


// $html .= '</tbody>';
// $html .= '</table>';





//  // Priyanka Edits..............................................................................................................

//  $billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
//  $eecheckdata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('ee_check' , 1)->get();
//     $imagePath3 = public_path('Uploads/signature/' . $sign3->sign);

//     $imageData3 = base64_encode(file_get_contents($imagePath3));
//     $imageSrc3 = 'data:image/jpeg;base64,' . $imageData3;

//  if ($eecheckdata->isNotEmpty())
//  {


//  $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
//  $html .= '<thead>';
//  $html .= '<tr>';
//  $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 100%;" colspan="3"><h4>Executive Engineer Checking:</h4></th>';
//  $html .= '</tr>';
//  $html .= '</thead>';

//  $html .= '<thead>';
//  $html .= '<tr>';
//  $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 10%;">Item No</th>';
//  $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 50%;">Item Description</th>';
//  $html .= '<th style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; width: 40%;">Measurements</th>';
//  $html .= '</tr>';
//  $html .= '</thead>';

//  $html .= '<tbody>';


//  $checked_mead_amt=0;
//  $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();

//  $b_item_amt= DB::table('bills')
//  ->where('t_bill_id', $tbillid)
//  ->value('bill_amt');
//  //dd($billitemdata);

//  foreach($billitemdata as $itemdata)
//  {
//      //dd($itemdata);
//      $bitemId=$itemdata->b_item_id;
//      //dd($bitemId);
//      //  $measnormaldata=DB::table('embs')->where('ee_check',1)->get();
//     //dd($measnormaldata);
//      $meassr=DB::table('embs')->select('sr_no')->where('b_item_id' , $bitemId)->where('ee_check',1)->get();
//     //dd($meassr);
//       if (!$meassr->isEmpty() ) {
//          // if ($measnormaldata ) {
//          //dd($measnormaldata->sr_no);

//          $html .= '<tr>';

//           $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
//           $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: justify;"> ' . $itemdata->exs_nm . '</td>';




//          // $html .= '<td style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; width: 30%;">' .
//          // $meassr .'</td>';

//          $html .= '<td style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">';

//          $numericValues = Str::of($meassr)
//              ->matchAll('/\d+/')
//              ->map(function ($match) {
//                  return (int)$match;
//              })
//              ->toArray();

//          // Check if there are numeric values
//          if (!empty($numericValues)) {
//              $html .= '<br> ' . implode(', ', $numericValues);


//          }

//          // Close the table cell
//          $html .= '</td>';


//  $html .= '</tr>';

//  // Now you can use $html as needed, for example, output it in your view or send it as a response.

//          //

//              // dd($meassr[]);


//          preg_match_all('/\d+/', $meassr, $numeric_values);

//          // Convert the extracted numeric values to a comma-separated string
//          $comma_separated_values = implode(',', $numeric_values[0]);
//          // dd($numeric_values);

//       }


//      //  $measid=$itemdata->meas_id;
//      //     //dd($measid);

//       $qty = DB::table('embs')
//           ->where('t_bill_id', $tbillid)
//           ->where('ee_check', 1)
//           ->value('qty');
//       //dd($qty);

//       $bill_rt = DB::table('bil_item')
//           ->where('t_bill_id', $tbillid)
//           ->value('bill_rt');
//       //dd($bill_rt);

//       $meas_amt=$bill_rt * $qty;
//      //  dd($meas_amt);
//       $checked_mead_amt=$checked_mead_amt+$meas_amt;
//      //  //dd($checked_mead_amt);
//       $result[]=$checked_mead_amt;
//      //dd($result);
//       // dd($checked_mead_amt);
//       //dd($bitemid,$measid,$qty,$bill_rt,$meas_amt);

//  }

//  // dd($checked_mead_amt);

//  $Checked_Percentage1=$checked_mead_amt/$b_item_amt*100;
//  //dd($Checked_Percentage);
//  //Format the result to have only three digits after the decimal point
//  $Checked_Percentage = number_format($Checked_Percentage1, 2);

//  $checked_meas_amt = number_format($checked_mead_amt, 2);
//  // dd($Checked_Percentage);

//  //Image........

//      //dd($workid);
//      // Construct the full file path
//      //dd($sign3);
//     // dd($si);





//     $html .= '<tfoot>';
//     $html .= '<tr>';
//     $html .= '<td colspan="3" style="text-align: center;">';
//     $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
//     $html .= '<thead>';
//     $html .= '<tr>';
//     $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%; text-align: center;"> Checked measurements  ' . $embsection2->EEChk_percentage . '% . (Value Rs .' . $embsection2->EEChk_Amt . ')</th>';
//     $html .= '<th style="border: 1px solid black; padding: 5px;  width: 50%;">';
//     $html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
//     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
//     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
//     $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';

//     $html .= '<div style="line-height: 1; margin: 0;">';
//     $html .= '<p style="line-height: 4; margin: 0;"></p>';
//     $html .= '<p style="line-height: 4; margin: 0;"></p>';
//     $html .= '</div>';

//     $html .= '</th>';
//     $html .= '</tr>';
//     $html .= '</thead>';
//     $html .= '<tbody>';
//     $html .= '</tbody>';
//     $html .= '</table>';
//     $html .= '</td>';
//     $html .= '</tr>';
//     $html .= '</tfoot>';

//     $html .= '</table>';
// }

//     $agencyid=DB::table('workmasters')->where('Work_Id' , $workid)->value('Agency_Id');
//  //dd($agencyid);
// $agencydata=DB::table('agencies')->where('id' , $agencyid)->first();
//  //dd($agencydata);
// $agencyceck=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
// $originalDate = $agencyceck->Agency_Check_Date;
// $newDate = date("d-m-Y", strtotime($originalDate));
// //dd($newDate);
// if($agencyceck->Agency_Check == '1')
// {

// $imagePath4 = public_path('Uploads/signature/' . $agencydata->agencysign);

//     $imageData4 = base64_encode(file_get_contents($imagePath4));
//     $imageSrc4 = 'data:image/jpeg;base64,' . $imageData4;


//     $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
//     $html .= '<tbody>';
//        $html .= '<tr>';
// $html .= '<td colspan="2" style="text-align: right;">';
// $html .= '<strong>I have Checked all the measurements and I accept the measurements</strong>';
// $html .= '</td>';
// $html .= '</tr>';
//      $html .= '<tr>';
//      $html .= '<th style=" padding: 5px;  width: 50%; text-align: center;">';
//      $html .= '</th>';

//      $html .= '<th>';

//      $html .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc4 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
//      $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
//      $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $agencydata->agency_nm . ' , '. $agencydata->Agency_Pl .'</strong></div>';
//      $html .= '</div>';
//      $html .= '</th>'; // First cell for signature details
//          $html .= '</tr>';
//         $html .= '</tbody></table>';









// }

// $recdata = $this->latestRecordData;

// $lastrecentrydata = $this->lastupdatedRecordData;
// //dd($lastrecentrydata);
// $data=$this->abstractpdfdata($tbillid , $recdata , $lastrecentrydata);
// //dd($data);

// $html .= $data;



//  $sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
// //   dd($sammarydata);
//   $C_netAmt= $sammarydata->c_netamt;
//   $chqAmt= $sammarydata->chq_amt;
// $commonHelper = new CommonHelper();
// $amountInWords = $commonHelper->convertAmountToWords($C_netAmt);
// // dd($amountInWords);

//   if($sammarydata->mb_status > 10)
//   {
//     // dd('ok');

//    $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
//     $html .= '<tbody>';
//        $html .= '<tr>';
// $html .= '<td colspan="2" style="text-align: right;">';
// $html .= '<div style="line-height: 1; margin: 0;"><strong>Passed for Rs.'.$C_netAmt.' (' . $amountInWords . ')</strong></div>';
// $html .= '</td>';
// $html .= '</tr>';
//      $html .= '<tr>';
//      $html .= '<th style=" padding: 5px;  width: 50%; text-align: center;">';
//      $html .= '</th>';

//      $html .= '<th>';

//      $html .= '<div style="line-height: 1; width: 50%; margin: 0;"><strong></div>';

//     $html .= '<div style=" width: 150px; height: 60px; display: inline-block;"> <img src="' . $imageSrc3 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>';
//     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign3->name .'</strong></div>';
//     $html .= '<div style="line-height: 1; margin: 0;"><strong>' . $designation .'</strong></div>';
//     $html .= '<div style="line-height: 1; margin: 0;"><strong>'. $division .'</strong></div>';
//      $html .= '</div>';
//      $html .= '</th>'; // First cell for signature details
//          $html .= '</tr>';
//         $html .= '</tbody></table>';;

//     $commonHelperDeduction = new CommonHelper();
//     // Call the function using the instance
//     $htmlDeduction = $commonHelperDeduction->DeductionSummaryDetails($tbillid);
//     $html .= $htmlDeduction;

//     $html .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-top: 20px;">';
//     $html .= '<tbody>';
//     $html .= '<td style="padding: 8px; width: 50%; text-align: center; line-height: 0;"></td>';

//     $html .= '<td style="padding: 8px; width: 50%; text-align: center; line-height: 0;">';
//     $html .= '<div style="line-height: 3; margin: 0;"></div>';
//     $html .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
//     $html .= '<div style="margin: 0; padding-top: 50px;"><strong>C.A & F.O</strong></div>'; // Adjusted padding-top
//     $html .= '<div style="margin: 0;"><strong>' . $division . '</strong></div>';
//     $html .= '</div>';
//     $html .= '</td>'; // First cell for signature details
//          $html .= '</tbody>';

//     $html .= '</table>';

//   }



//   $mpdf = new Mpdf();
//   $mpdf->autoScriptToLang = true;
//   $mpdf->autoLangToFont = true;
//   //print_r($chunks)

//   //$mpdf->SetFont('MarathiFont');
//   ///dd($chunks);
//  // Write HTML chunks iteratively
//  //foreach ($chunks as $chunk) {

//      $mpdf->WriteHTML('<h1>hello world</h1>');
//  //}
//  // Output PDF as download
//  $mpdf->Output('Subdivisionchecklist-' . $tbillid . '.pdf', 'D');


// // //main table close
// // //dd($html);
// // $pdf = new Dompdf();

// // // Read the image file and convert it to base64
// // //$imagePath = public_path('images/sign.jpg');
// // // $imageData = base64_encode(file_get_contents($imagePath));
// // //
// // //$imageSrc = 'data:image/jpeg;base64,' . $imageData;


// // // Image path using the asset helper function
// // $pdf->loadHtml($html);
// // //$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
// // $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

// // // (Optional) Set options for the PDF rendering
// // $options = new Options();
// // $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
// // $pdf->setOptions($options);

// // // $startpages=0;
// // // $totalPages=0;
// // // $Totalpages=0;
// // // if($embsection2->t_bill_No == 1)
// // // {
// // //     $startpages = 1;
// // //  $html = str_replace('{START_PAGES}', $startpages, $html);
// // //  //dd($startpages);


// // // }
// // // else{
// // //     $startpages = $embsection2->pg_from;
// // //     $html = str_replace('{START_PAGES}', $startpages, $html);

// // //     //dd($totalPages);
// // // //$Totalpages=$startpages+$totalPages;

// // // }
// // //dd($startpages);
// // $pdf->loadHtml($html);


// //  $pdf->render();
// // // if($embsection2->t_bill_No == 1)
// // // {
// // //     $totalPages = $pdf->getCanvas()->get_page_count();
// // //     $Totalpages = $totalPages;
// // // }
// // // else
// // // {
// // //     $totalPages = $pdf->getCanvas()->get_page_count();
// // //     $Totalpages = $totalPages+$startpages;
// // // }

// // // $html .= str_replace('{TOTAL_PAGES}', $Totalpages, $html);

// // // DB::table('bills')->where('t_bill_Id' , $tbillid)->update(['pg_upto' => $Totalpages]);
// // $totalPages = $pdf->getCanvas()->get_page_count();
// // $font = $pdf->getFontMetrics()->getFont("Arial Unicode MS");
// // $pdf->getCanvas()->page_text(510, 10, "Page: {PAGE_NUM}  of  $totalPages", $font, 12, array(0, 0, 0));
// // //     }
// // // $totalPages = $pdf->getCanvas()->get_page_count();



// // // // Add page numbers manually to each page
// // // for ($pageNumber = 2; $pageNumber <= $totalPages; $pageNumber++) {
// // //     // Go to the specific page
// // //     //$pdf->getCanvas()->set_page($pageNumber);

// // //     // Set position and text for page number
// // //     $x = 520; // X-coordinate
// // //     $y = 10; // Y-coordinate
// // //     $text = "Page: $pageNumber of $totalPages"; // Text to display
// // //     $font = $pdf->getFontMetrics()->getFont("helvetica", "regular"); // Font

// // //     // Add the page number text to the current page
// // //     $pdf->getCanvas()->text($x, $y, $text, $font, 10, array(0, 0, 0));

// // //     // If it's not the last page, add the placeholder for the next page number
// // //     if ($pageNumber !== $totalPages) {
// // //         $pdf->getCanvas()->page_text(520, 10, "Page: {PAGE_NUM}", $font, 10, array(0, 0, 0));
// // //     }
// // // }// Set the encoding (UTF-8 in this example)

// // Output the generated PDF (inline or download)
// return $pdf->stream('MB-' . $tbillid . '-pdf.pdf');
// }


//bill item related all measurement get function
public function itemmeasdatapdf($tbillid , $recdate)
{
 // Initialize an empty string to hold HTML content
            $html ='';

              // Fetch bill item data based on the provided bill ID
            $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
              // Fetch the work ID related to the provided bill ID
$workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');



   // Loop through each item in the bill item data
   foreach($billitemdata as $itemdata)
   {

       // Fetch record entry number for the given bill ID and record date
    $recno=DB::table('recordms')->where('t_bill_id' , $tbillid)->where('Rec_date' , $recdate)->value('Record_Entry_No');

       // Fetch the unit of the item for the given bill ID and item ID
    $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

            $bitemId=$itemdata->b_item_id;
         // Fetch measurement data from 'embs' table for the given item ID and measurement date
        $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $recdate)->get();
        $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $recdate)->get();
        //dd($meassteeldata);
         // Check if either normal or steel measurement data exists
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

           //item id related given bitemid
            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
        //dd($itemid);

         // Check if the item ID ends with specific values indicating special handling
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
            {

                //get a steel data
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->get();

                //bill rcc member related tbillid and bitemid
                $bill_rc_data = DB::table('bill_rcc_mbr')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->get();

                 //dd($stldata , $bill_rc_data);

              //array dimeters

                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

           //loop inside steel data
           foreach ($stldata as &$data) {
            if (is_object($data)) {
                //length diameter columns in loop
                foreach ($ldiamColumns as $ldiamColumn) {
                    if (property_exists($data, $ldiamColumn) && $data->$ldiamColumn !== null && $data->$ldiamColumn !== $data->bar_length) {

                        //swap the barlength and length diameter
                        $temp = $data->$ldiamColumn;
                        $data->$ldiamColumn = $data->bar_length;
                        $data->bar_length = $temp;
                       // dd($data->bar_length , $data->$ldiamColumn);
                        break; // Stop checking other ldiam columns if we found a match
                    }
                }
            }
        }

        // Initialize an array to store sums for each diameter column

                $sums = array_fill_keys($ldiamColumns, 0);

                // Loop through the data to sum up values for each diameter column
                foreach ($stldata as $row) {
                     foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                     }
                }//dd($stldata);

// Retrieve bill members where there exists a matching record in 'stlmeas'
                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemId) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemId);
                })
                ->get();
//dd($bill_member);
          // Retrieve all rc_mbr_ids for the given b_item_id
            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

// Initialize an array to store members with associated data
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
                    // Retrieve member data for the specific rc_mbr_id, b_item_id, and date
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('b_item_id', $bitemId)->where('date_meas' , $recdate)->get();
                //dd($memberdata);

            if ( !$memberdata->isEmpty()) {

  // Append row for member details
                $html .= '<tr>';
                $html .= '<th colspan="2" style="border: 1px solid black;  background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
                $html .= '<th colspan="3" style="border: 1px solid black;  background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black;  background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
                $html .= '</tr>';

             // Append row for member data in a nested table
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

     // Loop through the stldata to match with current member
   foreach ($stldata as $bar) {
   if ($bar->rc_mbr_id == $member->rc_mbr_id) {
   // dd($bar->rc_mbr_id , $member->rc_mbr_id);

        // Assuming the bar data is within a property like "bar_data"
        $formattedDateMeas = date('d-m-Y', strtotime($bar->date_meas));
   // Append row for each bar data
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



        // Calculate total lengths from 'embs' table data
    $embssteeldata = DB::table('embs')->where('b_item_id', $bitemId)->where('measurment_dt' , $recdate)->get();
    //dd($embssteeldata);
  // Initialize variables for total lengths
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

  // Accumulate total lengths based on diameter
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

             // Append total lengths row if the member is the last one

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
          // Close the nested table
   $html .='</tbody></table></td></tr>';




                }

            }












    }

        //dd($html);

        // Retrieve data from the 'embs' table based on the given conditions
            $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();
            // Initialize variable to hold total quantity
            $totalQty = 0;
            // Iterate through each record in the retrieved data
                foreach($normaldata as $nordata)
                {
                      // Extract formula from the current record
                            $formula= $nordata->formula;

                               // Add serial number to the row
                                $html .= '<tr>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->sr_no . '</td>';
                                   // Add description to the row with word wrap and max width
                                $html .= '<td colspan="2" style="border: 1px solid black;  padding:5px; word-wrap: width: 100%; break-word; max-width: 200px;">' . $nordata->parti . '</td>';
                            // Check if formula exists
                                if($formula)
                            {

                                $html .= '<td colspan="4" style="border: 1px solid black; padding:5px;">' . $nordata->formula . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $unit . '</td>';



                            }
                            else
                            {   // If no formula, display number, length, breadth, height, and quantity fields
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->number . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->length . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->breadth . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->height . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding:5px;">' . $unit . '</td>';

                            }
                             // End the current row
                                $html .= '</tr>';



                  }



               // Retrieve the 't_item_id' from 'bil_item' table based on 'b_item_id'
                  $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

                  // Retrieve decimal places for quantity from 'tnditems' table based on 't_item_id'
                  $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

             // Calculate the total quantity for the current item
                  $totalQty=round(DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('notforpayment' , 0)->where('measurment_dt' , $recdate)->sum('qty') ,  $Qtydec);
                  $totalQty=number_format($totalQty ,  3, '.', '');

             // Calculate quantity for records with the same measurement date
                  $qtyaspersamerec = round(DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $recdate)
                  ->sum('qty') , $Qtydec);
                  $qtyaspersamerec=number_format($qtyaspersamerec ,  3, '.', '');

                //dd($qtyaspersamerec);

         // Retrieve the maximum measurement date for the current item
                  $maxdate = DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $recdate)
                  ->max('measurment_dt');

                  // Retrieve the record entry number based on the maximum measurement date
                  $recordentryno=DB::table('recordms')
                  ->where('t_bill_id', $tbillid)
                  ->where('Rec_date', $maxdate)
                  ->value('Record_Entry_No');
     //dd($maxdate);
               // Initialize the quantity record variable
$recqty = 0;

// Calculate total quantity including previous and current records

 $Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
 $recqty = number_format($Recqty ,  3, '.', '');

 // Construct item number for the output
 $itemno = $itemdata->t_item_no;

 if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
     $itemno .= $itemdata->sub_no;
 }

 // Format the previous bill quantity
 $itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);
 $itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');

// Calculate the total quantity considering previous bill quantities
 $recqty_float = floatval(str_replace(',', '', $recqty));
 $totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;
 $Totalquantityasper = number_format($totalquantityasper, 3, '.', '');

 $TotalQuantity=0;

// Check if quantity from previous records and current bill are both zero
 if($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0)
 {

    $TotalQuantity=$totalQty;
    $html .= '<tr>';
    $html .= '<td colspan="7" style="border: 1px solid black; padding: 5px;  text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  font-weight: bold;">'. $totalQty.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;">' . $unit . '</td>';
    $html .= '</tr>';


 }

// Check if there are previous records to add to the total quantity
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

// Check if there are previous bill quantities but no quantity for current measurement date
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
// Store the latest record data in the class property and session
$this->latestRecordData[$itemdata->b_item_id] = [
    'Record_Entry_No' => $recno,
    't_item_no' => $itemdata->t_item_no, // Include t_item_no for comparison
    'Total_Uptodate_Quantity' => $TotalQuantity,
    'b_item_id' => $itemdata->b_item_id, // Include b_item_id
];
$_SESSION['latestRecordData'] = $this->latestRecordData;



       }




    }

// Return the constructed HTML
   $returnHTML = $html;
   //dd($returnHTML);
return $returnHTML;
}

//bill item related all measurements data for view page
public function itemmeasdata($tbillid , $recdate)
{

            $html ='';

             // Retrieve bill item data for the given bill ID
            $billitemdata=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();
         $workid=DB::table('bills')->where('t_bill_id' , $tbillid)->value('work_id');



     // Loop through each item in the bill
   foreach($billitemdata as $itemdata)
   {

     // Get the record entry number for the given bill ID and record date
    $recno=DB::table('recordms')->where('t_bill_id' , $tbillid)->where('Rec_date' , $recdate)->value('Record_Entry_No');

       // Get the unit of measurement for the current item
    $unit=DB::table('bil_item')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->value('item_unit');

            $bitemId=$itemdata->b_item_id;
        // Get measurement data from 'embs' and 'stlmeas' tables for the current item and date
        $measnormaldata=DB::table('embs')->where('b_item_id' , $bitemId)->where('measurment_dt' , $recdate)->get();
        $meassteeldata=DB::table('stlmeas')->where('b_item_id' , $bitemId)->where('date_meas' , $recdate)->get();

          // Check if there is any measurement data available
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

        // Check if the item ID matches specific criteria
            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemId)->get()->value('item_id');
        //dd($itemid);
            if (in_array(substr($itemid, -6), ["000092", "000093", "001335", "001336", "002016", "002017", "002023", "002024", "003351", "003352", "003878"]))
            {
                // Retrieve steel measurement data for the given bill ID and item ID
                $stldata=DB::table('stlmeas')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('date_meas' , $recdate)->get();

                     // Retrieve bill RCC member data
                $bill_rc_data = DB::table('bill_rcc_mbr')->get();

                // dd($stldata , $bill_rc_data);

                 // Columns for different diameters
                    $ldiamColumns = ['ldiam6', 'ldiam8', 'ldiam10', 'ldiam12', 'ldiam16', 'ldiam20', 'ldiam25',
            'ldiam28', 'ldiam32', 'ldiam36', 'ldiam40', 'ldiam45'];

            // Swap values of bar_length and specific diameter columns if needed
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

                   // Calculate sums for each diameter column
                foreach ($stldata as $row) {
                     foreach ($ldiamColumns as $ldiamColumn) {
                        $sums[$ldiamColumn] += $row->$ldiamColumn;
                     }
                }//dd($stldata);

              // Retrieve RCC member data related to the current item
                $bill_member = DB::table('bill_rcc_mbr')
                ->whereExists(function ($query) use ($bitemId) {
                $query->select(DB::raw(1))
                ->from('stlmeas')
                ->whereColumn('stlmeas.rc_mbr_id', 'bill_rcc_mbr.rc_mbr_id')
                ->where('bill_rcc_mbr.b_item_id', $bitemId);
                })
                ->get();

           // Retrieve RCC member IDs related to the current item
            $rc_mbr_ids = DB::table('bill_rcc_mbr')->where('b_item_id', $bitemId)->pluck('rc_mbr_id')->toArray();

         // Loop through each RCC member
        foreach ($bill_member as $index => $member) {
                //dd($member);
                    $rcmbrid=$member->rc_mbr_id;
                        $memberdata=DB::table('stlmeas')->where('rc_mbr_id' , $rcmbrid)->where('date_meas' , $recdate)->get();
                //dd($memberdata);

                     // Check if there is any member data available
            if ( !$memberdata->isEmpty()) {
           // Create HTML for RCC member information
                $html .= '<tr>';
                $html .= '<table style="border-collapse: collapse; width: 100%;"><thead>';
                $html .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Sr No :' . $member->member_sr_no . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">RCC Member :' . $member->rcc_member . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">Member Particular :' . $member->member_particulars . '</th>';
                $html .= '<th colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2;">No Of Members :' . $member->no_of_members . '</th>';
                $html .= '</thead></table>';
                $html .= '</tr>';

             // Create HTML table for bar data
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

                  // Loop through each bar data
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
    // Get additional measurement data from 'embs' table for the current item
    $embssteeldata = DB::table('embs')->where('b_item_id', $bitemId)->where('measurment_dt' , $recdate)->get();
    //dd($embssteeldata);
     // Initialize variables for bar lengths
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

               // Loop through each bar data
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

        // Determine the regex pattern based on the section type
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
  // Accumulate total lengths based on diameter
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

           // Start creating the HTML table for displaying the results
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


            // Retrieve data from the database for a given bill and item
            $normaldata=DB::table('embs')->where('t_bill_id' , $tbillid)->where('b_item_id' , $itemdata->b_item_id)->where('measurment_dt' , $recdate)->get();
            $totalQty = 0;

            // Process each record from the database
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
                                 // Display formula and quantity if available
                                $html .= '<td colspan="4" style="border: 1px solid black; padding: 5px; width: 40%;">' . $nordata->formula . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 5px; width: 10%;">' . $nordata->qty . '</td>';
                                $html .= '<td style="border: 1px solid black; padding: 2px; width: 15%;">' . $unit . '</td>';



                            }
                            else
                            {     // Display other details if formula is not available
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
                // Get item ID and quantity details for the current item
                  $titemid=DB::table('bil_item')->where('b_item_id' , $itemdata->b_item_id)->value('t_item_id');

                  $Qtydec=DB::table('tnditems')->where('t_item_id' , $titemid)->value('QtyDcml_Ro');

                 // Calculate total quantity and quantity as per same record date
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

                // Retrieve the maximum measurement date before the current record date for the specified bill and item
                  $maxdate = DB::table('embs')
                  ->where('t_bill_id', $tbillid)
                  ->where('b_item_id', $itemdata->b_item_id)
                  ->where('notforpayment' , 0)
                  ->where('measurment_dt', '<', $recdate)
                  ->max('measurment_dt');

                  // Get the record entry number for the maximum measurement date from the 'recordms' table
                  $recordentryno=DB::table('recordms')
                  ->where('t_bill_id', $tbillid)
                  ->where('Rec_date', $maxdate)
                  ->value('Record_Entry_No');
     //dd($maxdate);
     // Initialize the quantity variable
                  $recqty = 0; // Initialize $recqty
 //dd($qtyaspersamerec);
    //$
 //$recqty = number_format($qtyaspersamerec + $totalQty, 3);
 // Calculate the total quantity and format it
 $Recqty = floatval(str_replace(',', '', $qtyaspersamerec + $totalQty));
 $recqty = number_format($Recqty ,  3, '.', '');

 $itemno = $itemdata->t_item_no;

 if (!empty($itemdata->sub_no) && $itemdata->sub_no != 0) {
     $itemno .= $itemdata->sub_no;
 }

 $itemdata_prv_bill_qty = round($itemdata->prv_bill_qty , $Qtydec);


 $itemdata_prv_bill_qty = number_format($itemdata_prv_bill_qty, 3, '.', '');



// Calculate total quantity including previous bill quantity
 $recqty_float = floatval(str_replace(',', '', $recqty));
 $totalquantityasper = $itemdata_prv_bill_qty + $recqty_float;

 $Totalquantityasper = number_format($totalquantityasper, 3, '.', '');

 // Initialize total quantity
 $TotalQuantity=0;

// Check if both quantities are zero
 if($qtyaspersamerec == 0 && $itemdata->prv_bill_qty == 0)
 {
  // Append total quantity to HTML table
    $TotalQuantity=$totalQty;
    $html .= '<tr>';
    $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total Uptodate Quantity:</td>';
    $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
    $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
    $html .='</tbody>';
    $html .= '</tr>';


 }

// Check if there is a previous quantity
if($qtyaspersamerec != 0)
{

    $TotalQuantity=number_format($totalQty+$qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '');

 // Append total quantity and other details to HTML table
$html .= '<tr>';
$html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Total:</td>';
$html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. $totalQty.' </td>';
$html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
$html .='</tbody>';
$html .= '</tr>';

                  // Append quantity as per record entry to HTML table
                  $html .= '<tr>';
                  $html .='<table style="border-collapse: collapse; width: 100%; border: 1px solid black;">';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 75%; text-align: right ; font-weight: bold; ">Quantity as per this MB Record Entry No:'.$recordentryno.',  Item No:'.$itemno.'</td>';
                  $html .= '<td style="border: 1px solid black; padding: 5px;  width: 10%;font-weight: bold;">'. number_format($qtyaspersamerec+$itemdata_prv_bill_qty, 3, '.', '').' </td>';
                  $html .= '<td style="border: 1px solid black; padding: 2px;  width: 15%; ">' . $unit . '</td>';
                  $html .='</tbody>';
                  $html .= '</tr>';


        // Append total uptodate quantity to HTML table
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

// Check if there is a previous bill quantity and no previous record quantity
 if($itemdata->prv_bill_qty != 0 && $qtyaspersamerec == 0)
 {
           // Calculate total quantity including previous bill quantity
    $TotalQuantity=number_format($totalQty+$itemdata_prv_bill_qty, 3, '.', '');

  // Append total quantity and previous bill quantity to HTML table
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

// Store the latest record data for the current item
$this->latestRecordData[$itemdata->b_item_id] = [
    'Record_Entry_No' => $recno,
    't_item_no' => $itemdata->t_item_no, // Include t_item_no for comparison
    'Total_Uptodate_Quantity' => $TotalQuantity,
    'b_item_id' => $itemdata->b_item_id, // Include b_item_id
];

// Save latest record data to session
$_SESSION['latestRecordData'] = $this->latestRecordData;



        }




   }

// Return the generated HTML
   $returnHTML = $html;
   //dd(  $returnHTML);
return $returnHTML;
}




////Abstract report PDF functions/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




//common abstract data function
public function abstractreport(Request $request , $tbillid)
{

    // Retrieve the bill details for the given bill ID
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    // Initialize variable to store the abstract report content
    $abstractreport='';

      // Fetch all record entry numbers related to the given bill ID
       $recordentrynos=DB::table('recordms')->where('t_bill_id' , $tbillid)->get();

  // Define the header text for the abstract report
$headercheck='Abstract';

// Generate the common header view using the bill ID and header text
$header=$this->commonheaderview($tbillid , $headercheck);

  // Append the generated header to the abstract report content
$abstractreport .=$header;

  // Retrieve the detailed data for the abstract report using the bill ID
$data=$this->abstractreportdata($tbillid);

  // Append the detailed data to the abstract report content
$abstractreport .=$data;

 // Return the view for the abstract report, passing the bill details and report content
    return view('reports/AbstractReport' ,compact('embsection2' , 'abstractreport'));

}


//common function for abstract report pdf download function
// public function abstractreportpdf($tbillid)
// {

//   // Fetching the bill details based on the provided t_bill_Id
//     $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

//     // Initialize the abstract report content
//     $abstractreport='';

//       // Fetch record entry numbers for the given t_bill_Id
//     $recordentrynos=DB::table('recordms')->where('t_bill_id' , $tbillid)->get();

//      // Set header check to 'Abstract' and generate the header content
// $headercheck='Abstract';
// $header=$this->commonheader($tbillid , $headercheck);
// //dd($header);

// $abstractreport .=$header;

//      // Fetch bill data and items for the provided t_bill_Id
// $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

// $billitems=DB::table('bil_item')->where('t_bill_id' , $tbillid)->get();


//     // Fetch work ID from the bill and then get work data
// $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
// //dd($workid);
// $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

//     // Fetch signature data for the DYE and JE from their respective tables
// $jeid=$workdata->jeid;
// $dyeid=$workdata->DYE_id;
// //dd($dyeid);
// $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
// $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
// // Convert signatures to base64 for embedding in the PDF
// $imagePath = public_path('Uploads/signature/' . $sign->sign);
// $imageData = base64_encode(file_get_contents($imagePath));
// $imageSrc = 'data:image/jpeg;base64,' . $imageData;

// $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
// $imageData2 = base64_encode(file_get_contents($imagePath2));
// $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


//   // Fetch designation and subdivision for the DYE and JE
// $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
// //dd($jedesignation);
// $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

// $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
// $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');




//         // // Read the image file and convert it to base64
//         // $imagePath = public_path('images/sign.jpg');
//         // $imageData = base64_encode(file_get_contents($imagePath));
//         // $imageSrc = 'data:image/jpeg;base64,' . $imageData;

//         // $imagePath2 = public_path('images/sign2.jpg');
//         // $imageData2 = base64_encode(file_get_contents($imagePath2));
//         // $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


// // Initialize the report HTML
// $abstractreport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black; margin-left: 22px; margin-right: 17px;">';
// $abstractreport .= '<thead>';
// $abstractreport .= '<tr style=" width: 100%;">';
// $abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 5%; word-wrap: break-word;">Tender Item No</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Upto Date Quantity</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 24%; word-wrap: break-word;">Description of Item</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Bill/Tender Rate</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Total Upto Date Amount</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Now to be Paid Amount</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 6px; background-color: #f2f2f2; text-align: center; width: 17%; word-wrap: break-word;">Remark</th>';
// $abstractreport .= '</tr>';
// $abstractreport .= '</thead>';
// $abstractreport .= '<tbody>';

// // Loop through your data to generate table rows
// foreach ($billitems as $itemdata) {
//     $bitemId = $itemdata->b_item_id;
//     $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->value('item_id');

//     if (
//         !in_array(substr($itemid, -6), [
//             "001992", "003229", "002047", "002048", "004349", "001991",
//             "004345", "002566", "004350", "003940", "003941", "004346",
//             "004348", "004347"
//         ]) && !(substr($itemid, 0, 4) === "TEST")
//     ) {
//         // Generate table rows with data
//         $abstractreport .= '<tr>';
//         $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 5%; text-align: center; word-wrap: break-word;">' . $itemdata->t_item_no . ' ' . $itemdata->sub_no . '</td>';
//         $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $itemdata->exec_qty . '</td>';
//         $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 5%; text-align: right; word-wrap: break-word;">' . $itemdata->item_unit . '</td>';
//         $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 24%; word-wrap: break-word;">' . $itemdata->exs_nm . '</td>';
//         $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $itemdata->bill_rt . '</td>';
//         $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $itemdata->b_item_amt . '</td>';
//         $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 8%; text-align: center; word-wrap: break-word;">' . $itemdata->cur_amt . '</td>';
//         $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 17%; text-align: center; word-wrap: break-word;"></td>';
//         $abstractreport .= '</tr>';
//     }
// }

//    // Fetch work data again to check specific conditions
// $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
// $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

// $abpc = $workdata->A_B_Pc;
// //dd($abpc);
// $abobelowatper=$workdata->Above_Below;
//   // Add total row if A_B_Pc is not '0.00' and Above_Below is not 'At Per'
// if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {
//     // Row will be generated only when abpc is not equal to 0 or 'At Per'
//     $abstractreport .= '<tr>';
//     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total Part A Amount</strong></td>';
//     $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->part_a_amt . '</strong></td>';
//     $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_part_a_amt . '</strong></td>';
//     $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; word-wrap: break-word;"></td>';
//     $abstractreport .= '</tr>';
// }


// // dd($workdata);
// //above below effect
// if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {

//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Tender Above bellow Result : '.$workdata->A_B_Pc.' '.$workdata->Above_Below.'</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->a_b_effect . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_abeffect . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';

// }

// //gst base
//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->gst_base . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_gstbase . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';

// //gst amount
//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>GST Amount '.$tbilldata->gst_rt.'%</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->gst_amt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_gstamt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';

//  $hasMatchingId =false;

//  //royalty bill items in loop
// foreach ($billitems as $roylabitem) {
//     $bitemid = $roylabitem->b_item_id;
//     $itemid = DB::table('bil_item')->where('b_item_id', $bitemid)->value('item_id');

//     //check item id of given arrays id
//     if (in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])  || (substr($itemid, 0, 4) === "TEST")   ) {
//         $hasMatchingId = true;
//         // If any ID matches, set the flag to true
//         break; // No need to continue checking if we've found a match
//     }
// }

// if($hasMatchingId)
// {
// //Part a gst amount
//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->part_a_gstamt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_part_a_gstamt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';

// }
// //  $abstractreport .= '</tbody>';
// // $abstractreport .= '</table>';



// if($hasMatchingId)
// {

// //headings for  part b items
// $abstractreport .= '<tr style=" width: 100%;">';
// $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 5%; word-wrap: break-word;">Tender Item No</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Upto Date Quantity</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;">Unit</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 24%; word-wrap: break-word;">Description of Item</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Bill/Tender Rate</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Total Upto Date Amount</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 8%; word-wrap: break-word;">Now to be Paid Amount</th>';
// $abstractreport .= '<th style="border: 1px solid black; padding: 3px; background-color: #f2f2f2; text-align: center; width: 15%; word-wrap: break-word;">Remark</th>';
// $abstractreport .= '</tr>';

// }


// // Iterate through bill items
//  foreach($billitems as $roylabitem)
//  {
//     //dd($itemdata);
//     $bitemid=$roylabitem->b_item_id;
//             $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->get()->value('item_id');

//     // Check if the item ID matches specific values or starts with "TEST"
//                  if (
//                     in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"]) || (substr($itemid, 0, 4) === "TEST")

//                 )
//                 {   // Add a row to the report table for the current item

//                     $abstractreport .= '<tr>';
//                     $abstractreport .= '<td  style="border: 1px solid black; padding: 3px; width: 5%; text-align:center; word-wrap: break-word;">' . $roylabitem->t_item_no . ' '.$roylabitem->sub_no.'</td>';
//                     $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->exec_qty . '</td>';
//                     $abstractreport .= '<td style="border: 1px solid black; padding: 6px; width: 5%; text-align: right; word-wrap: break-word;">' . $itemdata->item_unit . '</td>';
//                     $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 24%; word-wrap: break-word;">' . $roylabitem->exs_nm . '</td>';
//                     $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->bill_rt . '</td>';
//                     $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->b_item_amt . '</td>';
//                     $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;">' . $roylabitem->cur_amt . '</td>';
//                     $abstractreport .= '<td  style="border: 1px solid black; padding: 3px; width: 15%; word-wrap: break-word;"></td>';
//                     $abstractreport .= '</tr>';


//                 }
//  }

// // Add a row for the total amount if there are matching items
//  if ($hasMatchingId) {


//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->part_b_amt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_part_b_amt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';

// }

// else
// {

// }
//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Grand Total(effective Part A + Part B)</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->bill_amt_gt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_billamtgt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';


//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->bill_amt_ro . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_billamtro . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';


//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->net_amt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->c_netamt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';

//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Previously Paid Amount</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->p_net_amt . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';

// // Calculate the amount to be paid now and format it
//  $nowpayamounttotal = number_format($tbilldata->net_amt - $tbilldata->p_net_amt, 2);
//  $nowpayamountcurrent = number_format($tbilldata->c_netamt, 2);

//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Now to be paid Amount</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $nowpayamounttotal . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>'.$nowpayamountcurrent.'</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';


// // Add row for the amount to be paid now
//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>**Total Deduction</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->p_tot_ded . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->tot_ded . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';

// // Add rows for total deductions and recoveries
//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Recovery</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->p_tot_recovery . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->tot_recovery . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';


//  $chequeamttotal=number_format($tbilldata->net_amt-$tbilldata->p_tot_ded , 2);
//  $chequeamtcurrent=number_format($tbilldata->c_netamt-$tbilldata->tot_ded , 2);


//  $abstractreport .= '<tr>';
//  $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Cheque Amount</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $chequeamtcurrent . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
//  $abstractreport .= '</tr>';

//  $abstractreport .= '<tr style="line-height: 0;">';
//  $abstractreport .= '<td colspan="4" style="border: 1px solid black; padding: 6px; text-align: center; line-height: 0;">';


// //sgnature of junioe engineer and deputy engineer
//  if($embsection2->mb_status >= '3')
//  {
//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and abstracted by me</strong></div>';

//  $abstractreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
//  $abstractreport .= '<div style="line-height: 1; margin: 0;">';
//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
//   $abstractreport .= '</div>';
//  }
//  $abstractreport .= '</td>'; // First cell for signature details
//  $abstractreport .= '<td colspan="4" style="border: 1px solid black; padding: 6px;  text-align: center; line-height: 0;">';
//  if($embsection2->mb_status >= '4')
//  {

//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Checked by me</strong></div>';

//  $abstractreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
//  $abstractreport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
//  $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
//   $abstractreport .= '</div>';
//  }
//  // Close the first table cell for signature details
//  $abstractreport .= '</td>'; // First cell for signature details

//  // Close the table row
//  $abstractreport .= '</tr>';

//  // Close the table body and table
//       $abstractreport .= '</tbody></table>';

//      // Add a section header for Deduction Details
//       $abstractreport .= '<div style=" margin-top: 20px; margin-left: 22px; margin-right: 17px;"><h4>**Deduction Details :</h4></div>';

//       // Start a new table for deduction details
//       $abstractreport .= '<table style="border-collapse: collapse; width: 30%; border: 1px solid black; margin-top:20px; margin-left: 22px; margin-right: 17px;">';
//       $abstractreport .= '<thead>';
//       $abstractreport .= '<tr>';
//       $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Deductions</th>';
//       $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Percentage</th>';
//       $abstractreport .= '<th style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">Amount</th>';
//       $abstractreport .= '</tr>';
//           // Sub-columns under Excess and Saving headings
//       $abstractreport .= '</thead>';
//       $abstractreport .= '<tbody>';


// // Fetch deduction data from the database
//       $deductiondata=DB::table('billdeds')->where('T_Bill_Id' , $tbillid)->get();

// // Iterate through each deduction item and add to the report
//       foreach($deductiondata as $deduction)
//       {
//       $abstractreport .= '<tr>';
//       $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>'.$deduction->Ded_Head.'</strong></td>';
//       $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>'.$deduction->Ded_pc.'</strong></td>';
//       $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>'.$deduction->Ded_Amt.'</strong></td>';
//       $abstractreport .= '</tr>';
//       }
//      // Add a row for the total deductions
//       $abstractreport .= '<tr>';
//       $abstractreport .= '<td colspan=2 style="border: 1px solid black; padding: 8px;  text-align:center; word-wrap: break-word;"><strong>Total</strong></td>';
//       $abstractreport .= '<td  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $tbilldata->tot_ded . '</strong></td>';
//       $abstractreport .= '</tr>';

//       // Close the table body and table
//       $abstractreport .= '</tbody>';
//       $abstractreport .= '</table>';


// //main table close
// //dd($html);
// $pdf = new Dompdf();

// // Read the image file and convert it to base64
// //$imagePath = public_path('images/sign.jpg');
// // $imageData = base64_encode(file_get_contents($imagePath));
// //
// //$imageSrc = 'data:image/jpeg;base64,' . $imageData;


// // Image path using the asset helper function
// $pdf->loadHtml($abstractreport);
// //$pdf->setPaper('auto', 'auto'); // Set paper size and orientation
// $pdf->setPaper('A4', 'portrait'); // Set paper size and orientation

// // (Optional) Set options for the PDF rendering
// $options = new Options();
// $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing
// $pdf->setOptions($options);

// $pdf->render();

// // Output the generated PDF (inline or download)
// return $pdf->stream('Abstract-'.$tbillid.'-pdf.pdf');
// }


//common function for abstract data report pdf data
public function abstractpdfdata($tbillid , $recdata , $lastrecdata)
{


       // Fetch bill data using the provided t_bill_Id
     $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

       // Get the work_id associated with the bill
     $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');

 // Fetch work data using the retrieved work_id
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

  // Extract JE and DYE ids from work data
$jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;

// Fetch signature data for DYE and JE
$sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
$sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();


 // Construct file paths for the signatures
$imagePath = public_path('Uploads/signature/' . $sign->sign);
$imageSrc = $imagePath;

$imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
$imageSrc2 = $imagePath2;


  // Fetch designations and subdivisions for JE and DYE
$jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
//dd($sign2->designation);
$jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

$dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
$dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

 // Initialize CommonHelper instance for formatting
 $convert=new CommonHelper();

 // Start building the abstract report
  $abstractreport='';

  $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
  $abstractreport .= '<h2 style="text-align: center;">Abstract</h2>';

    // Add header section for the abstract report
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

  // Fetch and process bill items associated with the bill
  $billitems = DB::table('bil_item')
  ->where('t_bill_id', $tbillid)
  ->orderBy('t_item_no', 'asc')
  ->get();

// Loop through your data to generate table rows
foreach ($billitems as $itemdata) {
    $bitemId = $itemdata->b_item_id;
    $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->value('item_id');

     // Exclude specific items and test items
    if (
        !in_array(substr($itemid, -6), [
            "001992", "003229", "002047", "002048", "004349", "001991",
            "004345", "002566", "004350", "003940", "003941", "004346",
            "004348", "004347"
        ]) && !(substr($itemid, 0, 4) === "TEST")
    ) {
       // Add row for each item that is not excluded
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

  // Check conditions and add summary rows to the report
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


 // Add row for Total Part A Amount if conditions are met

if ($abpc !== '0.00' && $abobelowatper !== 'At Per') {

 $abstractreport .= '<tr>';
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Tender Above bellow Result : '.$workdata->A_B_Pc.' '.$workdata->Above_Below.'</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->a_b_effect) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_abeffect) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';

}
  // Add row for Tender Above/Below Result if conditions are met
 $abstractreport .= '<tr>';
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->gst_base) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_gstbase) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';

    // Add row for Total Part B Amount if conditions are met

 $abstractreport .= '<tr>';
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 6%; text-align:right; word-wrap: break-word;"><strong>GST Amount '.$tbilldata->gst_rt.'%</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->gst_amt) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_gstamt) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';

 // Initialize flag to check if any item ID matches the criteria
 $hasMatchingId =false;

 // Loop through each bill item
foreach ($billitems as $roylabitem) {
    $bitemid = $roylabitem->b_item_id;

     // Retrieve item_id from the bil_item table based on b_item_id
    $itemid = DB::table('bil_item')->where('b_item_id', $bitemid)->value('item_id');

       // Check if the item_id matches any of the specified values or starts with "TEST"
    if (in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"])  || (substr($itemid, 0, 4) === "TEST")   ) {
        $hasMatchingId = true;
        // If any ID matches, set the flag to true
        break; // No need to continue checking if we've found a match
    }
}

// Generate the abstract report if any matching item ID is found
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


// Add the table headers if any matching item ID is found
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


// Loop through each bill item to add rows to the abstract report
 foreach($billitems as $roylabitem)
 {
    //dd($itemdata);
    $bitemid=$roylabitem->b_item_id;
    // Retrieve item_id from the bil_item table based on b_item_id
            $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->get()->value('item_id');
            //dd($itemid);

             // Check if the item_id matches any of the specified values or starts with "TEST"
                 if (
                    in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566", "004350", "003940", "003941", "004346", "004348", "004347"]) || (substr($itemid, 0, 4) === "TEST")

                )
                {
                       // Add a row for each matching bill item
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

// Add rows for total amounts if any matching item ID is found
 if ($hasMatchingId) {

 // Add the total row for Part B Amount
 $abstractreport .= '<tr>';
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->part_b_amt) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_part_b_amt) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';

}

// Add rows for grand total and other amounts
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
// Calculate the total amount to be paid now by subtracting previously paid amount from the net amount
 $nowpayamounttotal = $tbilldata->net_amt - $tbilldata->p_net_amt;
 $nowpayamountcurrent = $tbilldata->c_netamt;

 // Add a row to the report for the "Now to be paid Amount"
 $abstractreport .= '<tr>';
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Now to be paid Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($nowpayamounttotal) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>'.$convert->formatIndianRupees($nowpayamountcurrent).'</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';


// Add a row to the report for the "Total Deduction"
 $abstractreport .= '<tr>';
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>**Total Deduction</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_tot_ded) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->tot_ded) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';

// Add a row to the report for the "Total Recovery"
 $abstractreport .= '<tr>';
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Recovery</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_tot_recovery) . '</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->tot_recovery) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';

// Calculate the cheque amount by subtracting total deductions from net amount
 $chequeamttotal=$tbilldata->net_amt-$tbilldata->p_tot_ded;
 // Calculate the current cheque amount by subtracting total deductions from current net amount
 $chequeamtcurrent=$tbilldata->c_netamt-$tbilldata->tot_ded;

//cheque amount bind
 $abstractreport .= '<tr>';
 $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 6px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Cheque Amount</strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
 $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($chequeamtcurrent) . '</strong></td>';
//  $abstractreport .= '<td  style="border: 1px solid black; padding: 6px; width: 20%; "></td>';
 $abstractreport .= '</tr>';

 $abstractreport .= '<tr style="line-height: 0;">';
 $abstractreport .= '<td colspan="4" style="border: 1px solid black; padding: 6px; text-align: center; line-height: 0;">';


//sign of junior engineer and deputy engineer
 if($embsection2->mb_status >= '3')
 {
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and abstracted by me</strong></div>';

 //$abstractreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
 $abstractreport .= '<br>'; // Placeholder for signature box
$abstractreport .= '<br>'; // Placeholder for signature box
$abstractreport .= '<br>'; // Placeholder for signature box
$abstractreport .= '<br>'; // Placeholder for signature box

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

 //$abstractreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
 $abstractreport .= '<br>'; // Placeholder for signature box
$abstractreport .= '<br>'; // Placeholder for signature box
$abstractreport .= '<br>'; // Placeholder for signature box
$abstractreport .= '<br>'; // Placeholder for signature box

 $abstractreport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
 $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
  $abstractreport .= '</div>';
 }
 $abstractreport .= '</td>'; // First cell for signature details

 $abstractreport .= '</tr>';

 $abstractreport .= '</tbody></table>';

//return html varaible
return $abstractreport;

}

//common functiom for abstract data for view page
public function abstractreportdata($tbillid , $recdata , $lastrecdata)
{

       // Retrieve the bill details for the given t_bill_Id
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

      // Get the work_id associated with the given bill
       $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');

 // Retrieve work data based on the work_id
$workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

   // Extract necessary IDs from the work data
$jeid=$workdata->jeid;
$dyeid=$workdata->DYE_id;

// Get the signature information for the DYE and JE
$sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
$sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();

 // Convert the signature images to base64 for embedding in the report
$imagePath = public_path('Uploads/signature/' . $sign->sign);
$imageData = base64_encode(file_get_contents($imagePath));
$imageSrc = 'data:image/jpeg;base64,' . $imageData;

$imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
$imageData2 = base64_encode(file_get_contents($imagePath2));
$imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;

   // Helper for formatting currency
$convert=new CommonHelper();


$jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
//dd($sign2->designation);
$jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

$dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
$dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

  // Initialize the abstract report HTML
    $abstractreport='';

       // Table headers
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

     // Retrieve and process the bill items
        $billitems = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->orderBy('t_item_no', 'asc')
    ->get();

      // Retrieve and process the bill items
    foreach ($billitems as $itemdata) {
        $bitemId = $itemdata->b_item_id;
        $itemid = DB::table('bil_item')->where('b_item_id', $bitemId)->get()->value('item_id');

          // Check if the item ID is in the exclusion list or is a test item
        if (
            !in_array(substr($itemid, -6), [
                "001992", "003229", "002047", "002048", "004349", "001991",
                "004345", "002566", "004350", "003940", "003941", "004346",
                "004348", "004347"
            ])  && !(substr($itemid, 0, 4) === "TEST")
        ) {

              // Append item details to the report
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

  // Calculate and add totals to the report if certain conditions are met
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


// Check if any bill item matches the criteria
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

     // If there's a matching ID, add the first set of rows to the report
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




    // Add matching bill items to the report
     foreach($billitems as $roylabitem)
     {
        //dd($itemdata);
        $bitemid=$roylabitem->b_item_id;

           //Item id take using bitemid
                $itemid=DB::table('bil_item')->where('b_item_id' , $bitemid)->get()->value('item_id');
                //dd($itemid);

                //check item id  in array data
                     if (
                        in_array(substr($itemid, -6), ["001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566",
                    "004350", "003940", "003941", "004346", "004348", "004347"]) || (substr($itemid, 0, 4) === "TEST")
                    )
                    {
                        // raylaty data adda
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


    //part b amaount
     if($hasMatchingId){
     $abstractreport .= '<tr>';
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->part_b_amt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_part_b_amt) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     }
     // bill amount grand total
     $abstractreport .= '<tr>';
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Grand Total(effective Part A + Part B)</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->bill_amt_gt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_billamtgt) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     // bill amount round of
     $abstractreport .= '<tr>';
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->bill_amt_ro) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_billamtro) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

 //total net amount
     $abstractreport .= '<tr>';
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->net_amt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->c_netamt) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     //previous net amount
     $abstractreport .= '<tr>';
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Previously Paid Amount</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_net_amt) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     $nowpayamounttotal = $tbilldata->net_amt - $tbilldata->p_net_amt;
     $nowpayamountcurrent = $tbilldata->c_netamt;

     //now to pay amount
     $abstractreport .= '<tr>';
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Now to be paid Amount</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($nowpayamounttotal) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>'. $convert->formatIndianRupees($nowpayamountcurrent) .'</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

     ///dd($tbilldata->p_tot_ded);

// total deduction
     $abstractreport .= '<tr>';
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>**Total Deduction</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_tot_ded) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->tot_ded) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

   //total recovery
     $abstractreport .= '<tr>';
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Total Recovery</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->p_tot_recovery) . '</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($tbilldata->tot_recovery) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';

     $chequeamttotal=$tbilldata->net_amt-$tbilldata->p_tot_ded;
     $chequeamtcurrent=$tbilldata->c_netamt-$tbilldata->tot_ded;

    //cheque amount current
     $abstractreport .= '<tr>';
     $abstractreport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; width: 64%; text-align:right; word-wrap: break-word;"><strong>Cheque Amount</strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
     $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($chequeamtcurrent) . '</strong></td>';
    //  $abstractreport .= '<td  style="border: 1px solid black; padding: 8px; width: 20%; "></td>';
     $abstractreport .= '</tr>';


     // sign add junior engineer and deputy engineer
    $abstractreport .= '<tr style="line-height: 0;">';
    $abstractreport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; max-width: 40%; text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '3')
    {
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
    $abstractreport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and abstracted by me</strong></div>';
    $abstractreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box
    //$abstractreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
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
    $abstractreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box
    //$abstractreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
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




   // return abstract data
    return $abstractreport;
}










////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




// exceess saving report view page
  public function excesssavingreport(Request $request , $tbillid)
  {

    // Initialize variables
    $excessreport = '';
    $headercheck='Excess';
$excessreport=$this->commonheaderview($tbillid , $headercheck);

  // Fetch bill and related data
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_Id' ,$tbillid)->value('work_id');

        $billitems=DB::table('bil_item')->where('t_bill_id' ,$tbillid)->orderby('t_item_no' , 'asc')->get();



    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    //dd($workid);
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    // Fetch JE and DYE details
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


  // Fetch designations and subdivisions
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

   // Initialize report content
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

    // Loop through bill items and generate rows
    foreach($billitems as $bilitem)
    {

        // Initialize the row
        $excessreport .= '<tr>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $bilitem->t_item_no . ' ' . $bilitem->sub_no . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px;">' . $bilitem->exs_nm . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:left;">' . $bilitem->item_unit . '</td>';

            // Retrieve tender data for the specific work ID and item ID
        $tnddata=DB::table('tnditems')->join('bil_item', 'tnditems.t_item_id', '=', 'bil_item.t_item_id')->where('tnditems.work_Id', $workid)
        ->where('tnditems.t_item_id', $bilitem->t_item_id)->first();

        // $tnddata=DB::table('tnditems')
        // ->where('tnditems.t_item_id', $bilitem->t_item_id)->first();

      // Retrieve bill data for the specific bill ID
       $billdata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
      // dd($billdata);

         // Append tender data to the row
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $tnddata->tnd_qty . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($tnddata->tnd_rt) . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($tnddata->t_item_amt) . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $bilitem->exec_qty . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($bilitem->bill_rt) . '</td>';
        $excessreport .= '<td style="border: 1px solid black; padding: 8px; text-align:right;">' . $convert->formatIndianRupees($bilitem->b_item_amt) . '</td>';

        // Calculate total tender item amount
        $totalTItemAmt += $tnddata->t_item_amt;
        // Calculate the differences in quantity and amount
        $ResultQuantity = $tnddata->tnd_qty - $bilitem->exec_qty;
        $resultAmount = $tnddata->t_item_amt - $bilitem->b_item_amt;
   // Determine saving and excess quantities and amounts
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
         // Append saving and excess data to the row based on the calculated results

        if ($resultAmount > 0)
        {
            $savingAmount=$resultAmount;
            $totalSavingAmount += $resultAmount;

        }
        elseif ($resultAmount < 0)
        {
            // Append remarks to the row
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

    // Calculate the net effect of total saving and excess amounts
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
     // Append the signature rows   junior engineer and deputy engineer
    if($embsection2->mb_status >= '3')
    {

    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
    //$excessreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $excessreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br></div>'; // Placeholder for signature box
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

    //$excessreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $excessreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br></div>'; // Placeholder for signature box
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

    // returen to html to view page
    return view('reports/ExcessSavingStatement' ,compact('excessreport' , 'embsection2'));
   }


   //excess saving report pdf download
public function excessreportpdf(Request $request , $tbillid)
{

     // Initialize variables
    $excessreport = '';
    $headercheck='Excess';
//$excessreport=$this->commonheader($tbillid , $headercheck);

// Retrieve bill data by tbillid
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    $workid=DB::table('bills')->where('t_bill_Id' ,$tbillid)->value('work_id');

     // Retrieve bill items by tbillid
        $billitems=DB::table('bil_item')->where('t_bill_id' ,$tbillid)->orderby('t_item_no' , 'asc')->get();



    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
    // Retrieve work data by workid
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    //dd($workdata);
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
      // Retrieve signature data for DYE and JE
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
   // Construct the full file path for DYE signature
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


     // Retrieve designation and subdivision for JE
    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

        // Retrieve designation and subdivision for DYE
    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');




    $tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
//dd($recordentrynos);
  // Retrieve division
$division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
//dd($tbillid);

     $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
     $billType = CommonHelper::getBillType($embsection2->final_bill);
//dd($formattedTItemNo , $billType);


$agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';


    // Format bill number and type
$formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
     $billType = CommonHelper::getBillType($tbilldata->final_bill);
//dd($formattedTItemNo , $billType);

// $tbillid = 12345;
// $workid = 56263546723;

$billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();


    // Retrieve payment info
$paymentInfo = "$tbillid";



// generate qr code
$qrCode = QrCode::size(60)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(3)
->generate($paymentInfo);


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);


// Start constructing the excess report common header
$excessreport .= '<div style="position: absolute; top: 12%; left: 91%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">

<table style="width: 100%; border-collapse: collapse; margin-left: 22px; margin-right: 17px;">
 // Add header row
<tr>
<td  colspan="2" style="padding: 4px; text-align: left;"><h3><strong>' . $division . '</strong></h3></td>
<td  colspan="1" style=" padding: 4px; text-align: right; margin: 0 10px;"><h3><strong>MB NO: ' . $workid . '</strong></h3></td>
<td  style="padding: 4px; text-align: right;"><h3><strong>' . $workdata->Sub_Div . '</strong></h3></td>
</tr>

<tr>
<td colspan="14" style="text-align: center;"><h2><strong>EXCESS SAVING STATEMENT</strong></h2></td>
</tr>


<tr>
<td  colspan="2" style=""></td>
<td  style="padding: 8px; text-align: right;"><h5><strong>Bill No : ' . $formattedTItemNo . ' ' . $billType . '</strong></h5></td>
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
<td style=""><strong>Tender Id:</strong></td>
<td colspan="2">' . $workdata->Tender_Id . '</td>
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
$excessreport .= '<td colspan="3" style="width: 60%;"><strong>Work Order No:</strong>' . $workdata->WO_No . '</td>';
$excessreport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Work Order Date:</strong>' . $workorderdt . '</td>';
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


// start gnearate excess report
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

     // Add bill items data
    foreach($billitems as $bilitem)
    {

       // Get the tender data for the current bill item
        $tnddata=DB::table('tnditems')->join('bil_item', 'tnditems.t_item_id', '=', 'bil_item.t_item_id')->where('tnditems.work_Id', $workid)
        ->where('tnditems.t_item_id', $bilitem->t_item_id)->first();

          // Get the bill data
       $billdata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
      // dd($billdata);



         // Accumulate the total tender item amount
        $totalTItemAmt += $tnddata->t_item_amt;
       // Calculate the quantity and amount differences
        $ResultQuantity = $tnddata->tnd_qty - $bilitem->exec_qty;
        $resultAmount = $tnddata->t_item_amt - $bilitem->b_item_amt;

        // Initialize excess and saving report variables
        $excessreport1= '';
        $excessreport2= '';
        $excessreport3= '';
        $excessreport4= '';

         // Calculate saving and excess quantities
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

 // Calculate saving and excess amounts
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
 // Generate the excess and saving reports based on the amounts
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
// Prepare the excess report row
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

    // Calculate the net effect
    $netEffect = $totalSavingAmount - $totalExcessAmount;

    // prepare for total row
    $excessreport .= '<tr>';
    $excessreport .= '<td colspan="4" style="border: 1px solid black; padding: 4px; background-color: #f2f2f2; text-align:right; width: 10%; word-wrap: break-word; font-weight: bold;">TOTAL</td>';
    $excessreport .= '<td colspan="2" style="border: 1px solid black; padding: 4px; text-align:right; width: 4%; font-weight: bold;">' . $convert->formatIndianRupees($totalTItemAmt) . '</td>';
    $excessreport .= '<td colspan="3" style="border: 1px solid black; padding: 4px; text-align:right; width: 4%;"></td>';
    $excessreport .= '<td colspan="2" style="border: 1px solid black; padding: 4px; text-align:right; width: 4%; font-weight: bold;">' . $convert->formatIndianRupees($totalExcessAmount) . '</td>';
    $excessreport .= '<td colspan="2" style="border: 1px solid black; padding: 4px; text-align:right; width: 4%; font-weight: bold;">' . $convert->formatIndianRupees($totalSavingAmount) . '</td>';
    $excessreport .= '<td  style="border: 1px solid black; padding: 4px; text-align:right; width: 5%; font-weight: bold;">' . $convert->formatIndianRupees($netEffect) . '</td>';
    $excessreport .= '</tr>';

    // add signature row in that junior engineer and deputy engineer
    $excessreport .= '<tr style="line-height: 0;">';
    $excessreport .= '<td colspan="7" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '3')
    {

    $excessreport .= '<div style="line-height: 1; margin: 0;"><strong></strong></div>';
   // $excessreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
     $excessreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br><br></div>'; // Placeholder for signature box
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

    //$excessreport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $excessreport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br><br></div>'; // Placeholder for signature box
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





  // create object of the mpdf class
    $mpdf = new \Mpdf\Mpdf(['orientation' => 'L',  'margin_left' => 26.5,
     'margin_right' => 6,]); // Set orientation to landscape
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


// total pages
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





   ///recovery report pdf//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

   ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   public function recoveryreportpdf(Request $request , $tbillid)
   {

      // Fetch details of the bill using the provided bill ID
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    // Get the work ID related to the bill
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
   // Fetch work data using the retrieved work ID
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
      // Retrieve related IDs for signature
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
  // Fetch the signature details for the DYE and JE
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    // Convert the JE signature image to base64
    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


      // Get designations and subdivisions for DYE and JE
    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');


    // Set the header check type and generate common header
    $headercheck='Recovery';

    // common header create
$header=$this->commonheader($tbillid , $headercheck);


  // Fetch bill data using the bill ID
$tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
//dd($recordentrynos);

    // Get division details for the work
$division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
//dd($tbillid);

 // Format the bill number and get bill type
     $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
     $billType = CommonHelper::getBillType($embsection2->final_bill);
//dd($formattedTItemNo , $billType);

   // Format agreement date
$agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';


$formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
     $billType = CommonHelper::getBillType($tbilldata->final_bill);
//dd($formattedTItemNo , $billType);

// $tbillid = 12345;
// $workid = 56263546723;

$billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

 // Set payment info for QR code generation
$paymentInfo = "$tbillid";



// qrcode generate
$qrCode = QrCode::size(60)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(3)
->generate($paymentInfo);


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);


//recovery report
$RecoveryReport='';


    // Add QR code to the report
$RecoveryReport .= '<div style="position: absolute; top: 12%; left: 91%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">

<table style="width: 100%; border-collapse: collapse; margin-left: 22px; margin-right: 17px;">
 // Start the table structure for the report
<tr>
<td  colspan="2" style="padding: 4px; text-align: left;"><h3><strong>' . $division . '</strong></h3></td>
<td  colspan="1" style=" padding: 4px; text-align: right; margin: 0 10px;"><h3><strong>MB NO: ' . $workid . '</strong></h3></td>
<td  style="padding: 4px; text-align: right;"><h3><strong>' . $workdata->Sub_Div . '</strong></h3></td>
</tr>

<tr>
<td colspan="14" style="text-align: center;"><h2><strong>RECOVERY STATEMENT</strong></h2></td>
</tr>


<tr>
<td  colspan="2" style=""></td>
<td  style="padding: 8px; text-align: right;"><h5><strong>Bill No : ' . $formattedTItemNo . ' ' . $billType . '</strong></h5></td>
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
<td style=""><strong>Tender Id:</strong></td>
<td colspan="2">' . $workdata->Tender_Id . '</td>
</tr>

<tr>
<td  style=""><strong>Agency:</strong></td>
<td  style="">' . $workdata->Agency_Nm . '</td>
</tr>';

 // Add authority and date to the report
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

  // Format work order date
$workdate=$workdata->Wo_Dt ?? null;
$workorderdt = date('d-m-Y', strtotime($workdate));


    // Add work order number and date to the report
$RecoveryReport .= '<tr>';
$RecoveryReport .= '<td colspan="3" style="width: 60%;"><strong>Work Order No:</strong>' . $workdata->WO_No . '</td>';
$RecoveryReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Work Order Date:</strong>' . $workorderdt . '</td>';
$RecoveryReport .= '</tr>';


    // Retrieve measurement dates for normal and steel measurements
$normalmeas = DB::table('embs')->where('t_bill_id', $tbillid)->pluck('measurment_dt');
$steelmeas = DB::table('stlmeas')->where('t_bill_id', $tbillid)->pluck('date_meas');

 // Combine and get the latest measurement date
$combinedDates = $normalmeas->merge($steelmeas);
$maxDate = $combinedDates->max();
$maxdate = date('d-m-Y', strtotime($maxDate));

// Add completion date and measurement date to the report
if ($tbilldata->final_bill === 1) {
$date = $workdata->actual_complete_date ?? null;
$workcompletiondate = date('d-m-Y', strtotime($date));

$RecoveryReport .= '<tr>';
$RecoveryReport .= '<td colspan="3" style="width: 60%;"><strong>Actual Date of Completion:</strong>' . $workcompletiondate . '</td>';
$RecoveryReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
$RecoveryReport .= '</tr>';



} else {

    // work completion data
$date = $workdata->Stip_Comp_Dt ?? null;
$workcompletiondate = date('d-m-Y', strtotime($date));

$RecoveryReport .= '<tr>';
$RecoveryReport .= '<td colspan="3" style="width: 60%;"><strong>Stipulated Date of Completion:</strong>' . $workcompletiondate . '</td>';
$RecoveryReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Date of Measurement:</strong>' . $maxdate . '</td>';
$RecoveryReport .= '</tr>';


}
$RecoveryReport .= '</table></div>';



//common helper instance ceated
$convert=new Commonhelper();










    // dd($header);
//$RecoveryReport .=$header;
// Start generating the HTML table for the recovery report
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

// Retrieve recovery-related data for the given bill
$RecoverytbillrelatedData = DB::table('recoveries')
    ->where('t_bill_id', $tbillid)
    ->get();

    // $TotalRecovery=DB::table('bills')
    // ->where('t_bill_id', $tbillid)
    // ->value('tot_recovery') ?? 0;
    // // dd($TotalRecovery);

    $TotalRecovery=0;

// Loop through each recovery record and add it to the table
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

// Add a row for total recovery amount
$RecoveryReport .= '<tr>';
$RecoveryReport .= '<td colspan="10" style="border: 1px solid black; padding: 8px;  text-align: right; font-weight:bold;"> Total Recovery';
$RecoveryReport .= '</td>';
$RecoveryReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px;  text-align: center; font-weight:bold;">' . $convert->formatIndianRupees($TotalRecovery) . '</td>';
$RecoveryReport .= '<td colspan="3" style="border: 1px solid black; padding: 8px;  text-align: center; font-weight:bold;"></td>';

$RecoveryReport .= '</tr>';


// Add signature and checking details section
$RecoveryReport .= '<tr style="line-height: 0;">';
$RecoveryReport .= '<td colspan="6" style="border: 1px solid black; padding: 8px;  text-align: center; line-height: 0;">';
//$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>Measured and recorded by me at the site  of work </strong></div>';
if($embsection2->mb_status >= '3')
{

//$RecoveryReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RecoveryReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"><br><br><br></div>'; // Placeholder for signature box
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
//$RecoveryReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RecoveryReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"><br><br><br></div>'; // Placeholder for signature box
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

// Initialize mPDF with landscape orientation
$mpdf = new \Mpdf\Mpdf(['orientation' => 'L',  'margin_left' => 28.5,
'margin_right' => 6,]);  // Set orientation to landscape
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



   // recovery report for view page
public function recoveryreport(Request $request , $tbillid)
{
     // Retrieve the bill details using the provided bill ID
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

     // Get the work ID associated with the bill
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');

      // Retrieve work details using the work ID
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();

      // Extract JE ID and DYE ID from work details
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
     // Get signatures for JE and DYE
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


// Get designations and subdivisions for JE and DYE
    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    // Define the header for the report
    $headercheck='Recovery';
    $header=$this->commonheaderview($tbillid , $headercheck);
    // dd($header);

      // Initialize the Recovery Report variable
       $RecoveryReport='';
    $RecoveryReport .=$header;

        // Create an instance of Commonhelper for currency formatting
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

     // Retrieve recovery data related to the bill
    $RecoverytbillrelatedData = DB::table('recoveries')
        ->where('t_bill_id', $tbillid)
        ->get();

        // $TotalRecovery=DB::table('bills')
        // ->where('t_bill_id', $tbillid)
        // ->value('tot_recovery') ?? 0;
        // // dd($TotalRecovery);

          // Initialize total recovery amount
    $TotalRecovery=0;


 // Loop through each recovery record and add to the report
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

// Add total recovery row to the report
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

     // Append signatures and designations juniorengineer and deputy enginer
  //$RecoveryReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
  $RecoveryReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br></div>'; // Placeholder for signature box

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
  //$RecoveryReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
  $RecoveryReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br></div>'; // Placeholder for signature box
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
//         $BillReport .= '<th  colspan="2" style=" width: 60%; text-align: justify; ">Name of Work:   </th>';
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


//bill report for the view page
public function billreport(Request $request , $tbillid)
{
    // Fetching the bill section details from the database
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    $BillItemRt=DB::table('bil_item')->where('t_bill_Id' , $tbillid)->select('tnd_rt','bill_rt');

     // Fetching detailed bill information
    $Billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('work_id','bill_amt_gt','bill_amt_ro','net_amt','gst_amt','gst_rt','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
                                                                        'part_b_amt','gst_base','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',


                                                                        'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')->first();
    // dd($Billinfo);
    $work_id=$Billinfo->work_id;


       // Fetching work data based on the work_id
    $workdata=DB::table('workmasters')->where('Work_Id' , $work_id)->first();
    $Maxdt=DB::table('embs')->where('t_bill_Id' , $tbillid)->max('measurment_dt');
    $dates = Carbon::createFromFormat('Y-m-d',  $Maxdt)->format('d/m/Y');
    //dd($dates);

       // Fetching agency and signature details
    $Agency_Pl=DB::table('agencies')->where('id' , $workdata->Agency_Id)->select('Agency_Pl','agencysign')->first();
    $JE_nm=DB::table('jemasters')->where('jeid' , $workdata->jeid)->select('name','designation','sign')->first();
    $DYE_nm=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation','sign')->first();
    $EE_nm=DB::table('eemasters')->where('eeid' , $workdata->EE_id)->select('name','subname','designation','sign')->first();

      // Converting signatures to base64 for embedding in the report
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
    // Getting bill details like CV number and final bill status
    $headercheck='Bill';
    $cvno=$Billinfo->cv_no;
    // dd($cvno);
    $isFinalBill=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('final_bill','t_bill_No')->first();
    // dd($isFinalBill);
    $FirstBill=$isFinalBill->t_bill_No;
    $FinalBill=$isFinalBill->final_bill;
    //dd($FirstBill,$FinalBill);
    // $header=$this->commonheader();


    // Formatting bill numbers and types
    $rbbillno=CommonHelper::formatTItemNo($FirstBill);
    $rbbiill=CommonHelper::getBillType($FinalBill);

    $rbbillno=CommonHelper::formatNumbers($FirstBill);
    $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);


    // dd($prev_rbbillno);
    $BillReport= '';

      // Generating bill report header based on first and final bill status
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

     // Adding details about the bill
    $BillReport .= '<tr>';
    $BillReport .= '<td style="padding-left: 200px; width: 50%;text-align: justify;>';
    $BillReport .= '<p style="padding: 8px; width: 50%;>(For Contractors and suppliers :- To be used when a single payment is made for a job or contract, i.e. only on its completion. A single form may be used generally for making first & final payments several works or supplies if they pertain to the same time. A single form may also be used for making first & final payment to several piece-workers or suppliers if they relate to the same work and billed at the same time. In this case column 2 should be subdivided into two parts, the first part for "Name of Contractor / Piece-worker / Supplier: ABC Constructions, Sangli" and the second for "Items of work" etc.) and the space in Remarks column used for obtaining acceptance of the bill & acknowledgments of amount paid to different piece-workers or suppliers.</p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 50px; width: 50%;>';

    $BillReport .= '<p style="width: 50%;"> Cash Book Voucher No';
        // Displaying CV number and date
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

       // Adding work details and payment conditions
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

 // Adding additional notes and account classifications
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


    // royal b bill items

    $royaltylab = [ "001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566",
                "004350", "003940", "003941", "004346", "004348", "004347"];

                //get bill items
    $NormalData = DB::table('bil_item')
    ->select('exec_qty', 'item_unit', 'item_desc', 'bill_rt', 'b_item_amt', 't_bill_id', 'item_id', 't_item_no', 'sub_no')
    ->where('t_bill_id', $tbillid)
    ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();
    // dd("Okkkkk");
    //  dd($NormalData);

// Fetch the work ID associated with the given bill ID
    $DBWorkId=DB::table('bills')
    ->where('t_bill_Id',$tbillid)
    ->value('work_id');

   // Fetch details related to Above/Below and Percentage from workmasters
    $DBaboveBellow=DB::table('workmasters')
    ->select('Above_Below','A_B_Pc')
    ->where('Work_Id',$DBWorkId)
    ->first();
    // dd($DBaboveBellow);


// Fetch final bill details based on the bill ID and filter by royalty lab
    $FINALBILL =DB::table('bil_item')
    ->select('exec_qty','item_unit','item_desc','bill_rt','b_item_amt','t_bill_id','item_id','t_item_no','sub_no')
    ->where('t_bill_id',$tbillid)
    ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();
    // dd($FINALBILL);

// Fetch billing data from the bills table
        $DBbillTablegetData=DB::table('bills')
        ->select('c_part_a_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
        'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','c_netamt')
        ->where('t_bill_Id',$tbillid)
        ->first();
        // dd($DBbillTablegetData);

// Convert the net amount to words using a helper function
    $commonHelper = new CommonHelper();
$amountInWords = $commonHelper->convertAmountToWords($DBbillTablegetData->c_netamt);

   // Start building the HTML report
$BillReport .= '<div class="table-responsive">';
    $BillReport .= '<table style="" >';
    // Table header for work details
$BillReport .= '<tr>';
    $BillReport .= '<th colspan=1 >Name of Work : </th>';
    $BillReport .= '<th colspan=5 >'. $workdata->Work_Nm . '</th>';
    $BillReport .= '</tr>';
    // Table headers for bill details
    $BillReport .= '<tr>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 1%;  text-align: center; word-wrap: break-word;">Quantity</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px; width: 30%;  text-align: center; word-wrap: break-word;">Item of Work or supplies (grouped under sub-head or sub-works of estimates)</th>';
    $BillReport .= '<th  style="border: 1px solid black; padding: 8px;  width: 2%; text-align: center; word-wrap: break-word;">Rate
      (Rs.)</th>';
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
// Loop through normal data and append each item to the table
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
    // Append rows for totals and other billing calculations
$BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total Part A Amount</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_a_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // Deduct for Above/Below as per tender
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Deduct for  '.$DBaboveBellow->Above_Below.' '.$DBaboveBellow->A_B_Pc.'  as per Tender</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_abeffect).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

// Append GST base amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> GST Base	</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_gstbase).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // Append Part A GST amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> GST Amount 18%</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_gstamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // current part a gst amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_a_gstamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';

    $BillReport .= '</tr>';


//apply condition if final bill append that data
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
    // Append Part B Amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Total Part B Amount</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_b_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // Append final bill amount (GST)
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Grand Total</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_billamtgt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // Append final royalty bill amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Add for rounding off</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_billamtro).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // Append net payable amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Final Total	 </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_netamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // Append the final amount in words
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;"> Previously Paid Amount From Per Bill </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->p_net_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px; width: 10%; text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    //append total net amount
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
    //using commonhelper instance convert amount in words
    $amountInWords = $commonHelper->convertAmountToWords($DBbillTablegetData->c_netamt);

    // Append the row for amount in words
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=6 style="border: 1px solid black;  padding: 8px; width: 10%; text-align: center; word-wrap: break-word;"> In  Word (' .$amountInWords.')  </td>';
    $BillReport .= '</tr>';

// Start a new table section
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
//$BillReport .= '<img src="' . $imageSrcDYE  . '" alt="Base64 Encoded Image" style="width: 100px; "><br>'.$DYE_nm->designation.'<br> '.$workdata->Sub_Div.'';
$BillReport .= '<br><br><div  alt="Base64 Encoded Image" style="width: 100px; "></div><br>'.$DYE_nm->designation.'<br> '.$workdata->Sub_Div.'';
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; text-align: right; height: 20px; text-align:center">'; // Half width for signature
//$BillReport .= '<img src="' . $imageSrcEE  . '" alt="Base64 Encoded Image" style="width: 100px;"><br>'.$EE_nm->designation.'<br>'.$workdata->Div.'';
$BillReport .= '<br><br><div alt="Base64 Encoded Image" style="width: 100px;"></div><br>'.$EE_nm->designation.'<br>'.$workdata->Div.'';
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '</td>';

// Append the receipt and signature section
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


// Append additional sections with flex layout for payment and officer authorizing payment
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

// Additional space for officer authorizing payment

$BillReport .= '<div style="display: flex; ">'; // Flexbox for layout control
$BillReport .= '<div style="width: 50%;">'; // Half width for date
$BillReport .= 'Paid by me by cash / vide cheque No.<br>Dated:'; // Your date content here
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; text-align: right;">'; // Half width for signature
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
// Additional space for initials of person making the payment
$BillReport .= '<div style="display: flex; padding-top: 20px;">'; // Flexbox for layout control
$BillReport .= '<div style="width: 50%;  height: 160px; text-align:left">'; // Half width for date
$BillReport .= 'Dated Initials of person making the payment';
$BillReport .= '</div>';
$BillReport .= '<div style="width: 50%; text-align: right; height: 20px; text-align:center">'; // Half width for signature
$BillReport .= '</div>';
$BillReport .= '</div>'; // End flexbox
$BillReport .= '</td>';

$BillReport .= '</tr>';


// End of table
    $BillReport .= '</table>';

    $BillReport .= '</tr>';


    $BillReport .= '</table>';

    $BillReport .= '</div>';

    // If the condition for non-existence of data is met
    }
    else
    {

         // Append title and description for Z. P. FORM - 58 - C
        $BillReport .= '<h5 style="text-align: center; font-weight:bold; font-size:25px; padding: 8px; word-wrap: break-word;">Z. P. FORM - 58 - C </h5>';
        $BillReport .= '<h1 style="text-align: center; font-size:20px; word-wrap: break-word;">(See Rule 174)</h1>';
        $BillReport .= '<h1 style="text-align: center; margin-bottom:50px; font-size:20px; word-wrap: break-word;">'.$workdata->Div.'</h1>';
        $BillReport .= '<div class="table-responsive">';
        $BillReport .= '<table>';

         // Header row with Notes and corresponding data
        $BillReport .= '<tr>';
        $BillReport .= '<th style="width: 50%; text-align: center;  word-wrap: break-word;">Notes</th>';
        $BillReport .= '<th  style="padding-left: 200px; width: 50%; word-wrap: break-word;">'.$workdata->Sub_Div.'</th>';
        $BillReport .= '</tr>';
        $BillReport .= '<tbody>';

          // Notes and information section
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



        // table of bill item information
        $BillReport .= '<table class="table table-bordered table-collapse" style="border: 1px solid black; border-collapse: collapse; margin: 0;">';
        $BillReport .= '<thead>';
        // $BillReport .= '<br><br><br><table>';
        //row for the work data
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


                // all bill items data royalty
        $NormalData = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->where(DB::raw("SUBSTRING(item_id, 1, 4)"), '!=', 'TEST')
    ->orderBy('t_item_no', 'asc')
    ->get();
        // dd($NormalData);

        if($NormalData){

            // bill item data get get common function
            $header1=$this->commonforeachview($NormalData,$tbillid,$work_id);
            //dd($header1);
        $BillReport .=$header1;

        $abpc = $workdata->A_B_Pc;
        $abobelowatper=$workdata->Above_Below;

         $convert = new Commonhelper();


         // above below percentage append

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
            $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->a_b_effect) . '</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_abeffect) . '</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';
        }

        // gst base append
        $BillReport .= '<tr>';
        $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>Total(GST Base)</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->gst_base) . '</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_gstbase) . '</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
        $BillReport .= '</tr>';


        // gst amount append
        $BillReport .= '<tr>';
        $BillReport .= '<td  colspan="4" style="border: 1px solid black; padding: 8px; width: 66%; text-align:right; word-wrap: break-word;"><strong>GST Amount ' . $Billinfo->gst_rt . '%</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->gst_amt) . '</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_gstamt) . '</strong></td>';
        $BillReport .= '<td  colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong></strong></td>';
        $BillReport .= '</tr>';
        }
// royalty data bill items
       $testData = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->where('item_id', 'LIKE', 'TEST%')
    ->get();

$royaltyData = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();

$combinedData = $testData->merge($royaltyData);

// Sort combined data by 't_item_no' in ascending order and get all data
$sortedData = $combinedData->sortBy('t_item_no')->values();

        //dd($RoyaltyData);
        if (!$sortedData->isEmpty()) {
            // dd("Okkk");
            $header1=$this->commonforeachview($sortedData,$tbillid,$work_id);
            // dd($header1);
            $BillReport .=$header1;
            // $BillReport .= '<table>';
            // $BillReport .= '<tbody>';

            //part b bill amount bind
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->part_b_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_part_b_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
            $BillReport .= '</tr>';

            //bill amount grand total
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Grand Total</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->bill_amt_gt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_billamtgt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
            $BillReport .= '</tr>';

            // bill amount round of
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->bill_amt_ro) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_billamtro) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
            $BillReport .= '</tr>';

            // bill amount round of
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; width:66%; text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->net_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_netamt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';
            $BillReport .= '</tr>';

            // $c_netamt=$this->convertAmountToWords($Billinfo->c_netamt);
            //current net amount
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

        // measuremant details description
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

    //  // // sign and details of officers
    //  //Dye sign detaials comment

    //     $BillReport .= '<tr>';
    //     $BillReport .= '<td colspan="5" style="padding: 8px; width: 8%; text-align:right; word-wrap: break-word;"></td>';

    //     $BillReport .= '<td colspan="2" style="width: 200px; height: 60px; text-align:center"> <img src="' . $imageSrcDYE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;">';

    //     $BillReport .= '<br>'.$DYE_nm->designation.'';

    //     $BillReport .= '<br>'.$workdata->Sub_Div.'';

    //     $BillReport .= '<br> * Dated Signature of Officer preparing bill';
    //     $BillReport .= '</tr>';
    //     // $BillReport .= '</tbody>';
    //     // $BillReport .= '</table>';

    //     // $BillReport .= '<table>';
    //     $BillReport .= '<tr>';
    //     $BillReport .= '<td colspan="7" style="text-align: center;">-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
    //     $BillReport .= '</tr>';

    //     $BillReport .= '<tr>';
    //     $BillReport .= '<td colspan="5" style="border-collapse: collapse; text-align:left;">  Dated : </td>';
    //     $BillReport .= '<td colspan="2" style="border-collapse: collapse; text-align:center;">  Countersigned  </td>';
    //     // $BillReport .= '<td colspan="1" style="border-collapse: collapse; text-align:left;"> </td>';
    //     $BillReport .= '</tr>';

        $BillReport .= '<br><br><br><tr>';
        $BillReport .= '<td colspan="5" style="border-collapse: collapse; text-align:bottom;"> Dated Signature of the Contractor </td>';
        //$BillReport .= '<td colspan="2" style="height: 60px; text-align:center;"> <img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>'.$EE_nm->designation.'<br>  '.$workdata->Div.'</td>';
        $BillReport .= '<td colspan="2" style="height: 60px; text-align:center;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div><br>'.$EE_nm->designation.'<br>  '.$workdata->Div.'</td>';

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

//convert amount in words
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
    // $BillReport .= '<p style="width:100%; text-align:right;"><img src="' . $imageSrcAgency . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>(Full signature of the contractor)</p>';
    $BillReport .= '<p style="width:100%; text-align:right;"><img  alt="" style=""><br>(Full signature of the contractor)</p>';

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

    // bill information for the given bill id
    $Billinfo=DB::table('bills')
    ->where('t_bill_Id' , $tbillid)
    ->select('work_id','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
            'part_b_amt','c_abeffect','c_gstbase','c_gstamt','c_part_a_gstamt',
            'c_part_b_amt','bill_amt_gt','c_billamtgt','bill_amt_ro','c_billamtro','net_amt','gst_base','c_netamt','p_net_amt','c_gstbase','gst_rt','gst_amt')->first();
    // dd($Billinfo);
    // $Billinfo->gst_base=$Billinfo->gst_base=0;
    $work_id=$Billinfo->work_id;
    //dd($work_id);
    //work information
    $workdata=DB::table('workmasters')->where('Work_Id' , $work_id)->first();
    $Maxdt=DB::table('embs')->where('t_bill_Id' , $tbillid)->max('measurment_dt');
    $dates = Carbon::createFromFormat('Y-m-d',  $Maxdt)->format('d/m/Y');
    //dd($dates);

    // all user details related given work
    $Agency_Pl=DB::table('agencies')->where('id' , $workdata->Agency_Id)->select('Agency_Pl','agencysign')->first();
    $JE_nm=DB::table('jemasters')->where('jeid' , $workdata->jeid)->select('name','designation','sign')->first();
    $DYE_nm=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation','sign')->first();
    $EE_nm=DB::table('eemasters')->where('eeid' , $workdata->EE_id)->select('name','subname','designation','sign')->first();
    // $DYE_Design=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();


    // images and signature of work related users
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

    //change format numbers
    $rbbillno=CommonHelper::formatNumbers($FirstBill);
    $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);



    // $header=$this->commonheader();
    $rbbillno=CommonHelper::formatTItemNo($FirstBill);
    $rbbiill=CommonHelper::getBillType($FinalBill);

    $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);


    //array of royalty items
    $royaltylab = [ "001992", "003229", "002047", "002048", "004349", "001991", "004345", "002566",
                "004350", "003940", "003941", "004346", "004348", "004347"];


                //bill items data
    $NormalData = DB::table('bil_item')
    ->select('exec_qty', 'item_unit', 'item_desc', 'bill_rt', 'b_item_amt', 't_bill_id', 'item_id', 't_item_no', 'sub_no')
    ->where('t_bill_id', $tbillid)
    ->whereNotIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();
    // dd("Okkkkk");
    //  dd($NormalData);

    //bills information tbillid related
    $DBWorkId=DB::table('bills')
    ->where('t_bill_Id',$tbillid)
    ->value('work_id');
    // dd($DBWorkId);


    $DBaboveBellow=DB::table('workmasters')
    ->select('Above_Below','A_B_Pc')
    ->where('Work_Id',$DBWorkId)
    ->first();
    // dd($DBaboveBellow);

    //royalt lab bill items
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

    // qr code information
    $paymentInfo = "$tbillid";



    //qrcode generate class
$qrCode = QrCode::size(90)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(10)
->generate($paymentInfo);


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);

// qrcode apply to html
$BillReport .= '<div style="position: absolute; top: 2%; left: 87%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">';


    // $BillReport .=$header;

    // append html condition if bill is first and final
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

    //append cv no and cv date
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


    // work details
    $BillReport .= '<tr>';
    $BillReport .= '<td style="text-align: justify;">';
    $BillReport .= '<p style="text-align: justify;">2. In the case of works, the accounts of which are kept by subheads, the amount relating to all items of work following under the same "sub-head" should be totaled in red ink.<br><br><br></p>';
    $BillReport .= '</td>';
    $BillReport .= '<td style="padding-left: 15px; text-align: justify;">';
    $BillReport .= '<p>Name of work : '. $workdata->Work_Nm . '</p>';
    $BillReport .= '</td>';
    $BillReport .= '</tr>';


    // payments details
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


    // Account details
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


    //royalty item ids in array
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
    // amount to words convert
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


    // bill items in for loop
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
    //part a amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Total Part A Amount</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_a_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // above below effect
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Deduct for  '.$DBaboveBellow->Above_Below.' '.$DBaboveBellow->A_B_Pc.'  as per Tender</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_abeffect).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

//gst base
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> GST Base	</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_gstbase).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    //gst amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> GST Amount 18%</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_gstamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    //part a gst amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Total </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> ' . $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_a_gstamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';

    $BillReport .= '</tr>';


  //check final bill condition
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
    //part b amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Total Part B Amount</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_part_b_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    //bill amount grand total
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Grand Total</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_billamtgt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // current bill amount round of
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Add for rounding off</td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_billamtro).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // current total net amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Final Total	 </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->c_netamt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    // previous net amount
    $BillReport .= '<tr>';
    $BillReport .= '<th colspan=4 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;"> Previously Paid Amount From Per Bill </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' .  $commonHelper->formatIndianRupees($DBbillTablegetData->p_net_amt).' </td>';
    $BillReport .= '<td colspan=1 style="border: 1px solid black; padding: 8px;  text-align: left; word-wrap: break-word;"></td>';
    $BillReport .= '</tr>';

    //current net amount
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

// signature and details
$BillReport .= '<table>'; // Flexbox for layout control
$BillReport .= '<tbody>'; // Flexbox for layout control
$BillReport .= '<tr>'; // Flexbox for layout control
$BillReport .= '<td style="text-align: left;">'; // Flexbox for layout control
//$BillReport .= '<img src="' . $imageSrcDYE  . '" alt="Base64 Encoded Image" style="width: 80px; text-align: right;">';
$BillReport .= '<br><br>';
$BillReport .= '<br>'.$DYE_nm->designation.'<br>'.$workdata->Sub_Div.'';

$BillReport .= '</td>'; // Flexbox for layout control
$BillReport .= '<td style="text-align: right;">'; // Flexbox for layout control
//$BillReport .= '<img src="' . $imageSrcEE  . '" alt="Base64 Encoded Image" style="width: 80px; text-align: right;">';
$BillReport .= '<br><br>';
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
// payment details
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

        //form no 58 pdf report


        //work data information
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
         ->where(DB::raw("SUBSTRING(item_id, 1, 4)"), '!=', 'TEST')
         ->orderBy('t_item_no', 'asc') // Ordering by 'id' in ascending order
        ->get();
        // dd($NormalData);

        // royalty bill items generate
        if($NormalData){
            $header1=$this->commonforeach($NormalData,$tbillid,$work_id);
            // dd($header1);
            $BillReport .=$header1;
            $abpc = $workdata->A_B_Pc;
            $abobelowatper=$workdata->Above_Below;

             $convert=new Commonhelper();

             //above below percentage condition checking
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
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->a_b_effect) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_abeffect) . '</strong></td>';
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

        //royalty data
        // royalty data bill items
   $testData = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->where('item_id', 'LIKE', 'TEST%')
    ->get();

$royaltyData = DB::table('bil_item')
    ->where('t_bill_id', $tbillid)
    ->whereIn(DB::raw("SUBSTRING(item_id, -6)"), $royaltylab)
    ->get();

$combinedData = $testData->merge($royaltyData);

// Sort combined data by 't_item_no' in ascending order and get all data
$sortedData = $combinedData->sortBy('t_item_no')->values();


        //dd($RoyaltyData);
         if (!$sortedData->isEmpty()) {
            // dd("Okkk");

            //common bill item data
            $header1=$this->commonforeach($sortedData,$tbillid,$work_id);
            // dd($header1);
            $BillReport .=$header1;


            //part b amount
            $BillReport .= '<tr>';
            $BillReport .= '<td  colspan="4" style="border: 1px solid black;  padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Total Part B Amount</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black;  padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->part_b_amt) . '</strong></td>';
            $BillReport .= '<td  colspan="1" style="border: 1px solid black;  padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_part_b_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';

            // bill amount grant total
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Grand Total</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->bill_amt_gt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_billamtgt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';

          // bill a,ount round of
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Add/Ded for Round Up value</strong></td>';
            $BillReport .= '<td colspan="1"  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->bill_amt_ro) . '</strong></td>';
            $BillReport .= '<td colspan="1"  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_billamtro) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';


            // net amount
            $BillReport .= '<tr>';
            $BillReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>Final Total</strong></td>';
            $BillReport .= '<td colspan="1"  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->net_amt) . '</strong></td>';
            $BillReport .= '<td colspan="1"  style="border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong>' . $convert->formatIndianRupees($Billinfo->c_netamt) . '</strong></td>';
            $BillReport .= '<td colspan="1" style=" border: 1px solid black; padding: 8px;  text-align:right; word-wrap: break-word;"><strong></strong></td>';
            $BillReport .= '</tr>';

            $Net_Pre_subtraction=$Billinfo->net_amt-$Billinfo->p_net_amt;

            // net amount substract from previous bill
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

      // Create a new table row and a cell with specific text and formatting
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="7" style=" text-align:justify; word-wrap: break-word; border: 1px solid white;"> -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br>
        The measurements made by '.$JE_nm->name.' , '.$JE_nm->designation.' on '.$dates.' and are recorded at
         Measurement Book No. '.$tbillid.' No advance payment has been made previously
        without detailed measurements.</td>';
        $BillReport .= '</tr>';

        ////Dye Comment details
        // $BillReport .= '<tr>';
        // $BillReport .= '<td colspan="4" style="text-align: center; word-wrap: break-word; border: 1px solid white;"></td>';

        // $BillReport .= '<td colspan="3" style="text-align:center; border: 1px solid white;"><img src="' . $imageSrcDYE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>
        // '.$DYE_nm->designation.'<br>
        // '.$workdata->Sub_Div.'<br>
        // <br> * Dated Signature of Officer preparing bill </td>';
        // $BillReport .= '</tr>';

        // Create a separator row
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan="7" style="border: 1px solid white;">----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
        $BillReport .= '</tr>';

        // End the table body and table
        $BillReport .= '</tbody>';
        $BillReport .= '</table>';

        // Create a new table with additional information
        $BillReport .= '<table>';
        $BillReport .= '<tr>';
        // $BillReport .= '<td colspan=6 style="text-align:left; font-size: 85%;">  Dated : </td>';
        // $BillReport .= '<td colspan=6 style="text-align:right; font-size: 85%;">  Countersigned </td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        $BillReport .= '<td style="text-align: left;  word-wrap: break-word; font-size: 85%;"> Dated Signature of the Contractor</td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        //$BillReport .= '<td colspan=12 style="margin-left: 90%; text-align:right; font-size: 85%;"><img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></td>';
        $BillReport .= '<td colspan=12 style="margin-left: 90%; text-align:right; font-size: 85%;"><br><br><br><div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=12 style="  margin-left: 90%; text-align:right; font-size: 85%;">'.$EE_nm->designation.'</td>';
        $BillReport .= '</tr>';
        $BillReport .= '<tr>';
        $BillReport .= '<td colspan=12 style=" margin-left: 90%;  text-align:right; font-size: 85%;">'.$workdata->Div.'</td>';
        $BillReport .= '</tr>';

        // Create a separator row with an HR tag
        $BillReport .= '<tr>';
        $BillReport .= '<td><hr></td>';
        $BillReport .= '</tr>';

        // Add a note about the second signature
        $BillReport .= '<tr>';
        $BillReport .= '<td style="font-size: 85%;"> The second signature is only necessary when the officer who prepares the bill is not the officer who makes the payment. </td>';
        $BillReport .= '</tr>';
        $BillReport .= '</table>';

        // // PDF LAST PAGE--------------------------------------------------------------------------------------------------------------
       // Create a new table with specific text and formatting
        $BillReport .= '<div style="page-break-before: always;"></div>';
        $BillReport .= '<h6 style="text-align: center; font-weight:bold; word-wrap: break-word;">III - Memorandum of Payments </h6>';

        $BillReport .= '<p style="text-align: left; font-size: 70%;">1. Total Value of work done, as per Account-I, Column 5, Entry (A)</p>';
        $BillReport .= '<p style="text-align: left; font-size: 70%;">2. Deduct Amount withheld :</p>';

     // Create a new table with additional information and separators
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
     //get deduction master data
        $DedMaster_Info=DB::table('dedmasters')->select('Ded_M_Id')->get();
        //  dd($DedMaster_Info);

        $billDed_Info=DB::table('billdeds')->where('t_bill_Id' , $tbillid)->get('Ded_M_Id');
        // dd($billDed_Info);

        //sammary data
        $sammarydata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
        // dd($sammarydata);
        //current net amoount
        $C_netAmt= $sammarydata->c_netamt;
        $chqAmt= $sammarydata->chq_amt;

        //create instance for the converted amounts
        $commonHelper = new CommonHelper();
        $amountInWords = $commonHelper->convertAmountToWords($C_netAmt);
        // dd($amountInWords);

        // Fetch Deduction Percentages
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


         // deduction amounts
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
       //bill html create
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
        //$BillReport .= '<td colspan=10 style=" text-align:right; font-size:  70%;"><img src="' . $imageSrcagency . '" alt="Base64 Encoded Image" style="width: 100px; height: 40px;"><br>(Full signature of the contractor)</td>';
        $BillReport .= '<td colspan=10 style=" text-align:right; font-size:  70%;"><br><div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div><br>(Full signature of the contractor)</td>';

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


     // instance for mpdf report
    $mpdf = new \Mpdf\Mpdf(['orientation' => 'P',  'margin_left' => 28.5,
     'margin_right' => 6]); // Set orientation to portrait
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;

// watermark logo
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

// Function to generate HTML table rows for bill item data to be used in PDF
public function commonforeach($NormalData,$tbillid,$work_id)
{
      // Initialize the helper class for formatting rupee amounts
     $convert=new Commonhelper();

     // Initialize an empty string to store the HTML content
    $BillReport = '';

    // Add the header row with column labels
    $BillReport .= '<tr>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:left;  word-wrap: break-word;">1</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">2</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">3</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">4</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">5</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">6</td>';
    $BillReport .= '<td style="border: 1px solid black; 8px;  background-color: #f2f2f2; text-align:center;  word-wrap: break-word;">7</td>';
    $BillReport .= '</tr>';

      // Loop through each item in NormalData
        foreach($NormalData as $BillData){

             // Combine item number and sub number if available
            $itemno = $BillData->t_item_no . (!empty($BillData->sub_no) ? ' ' . $BillData->sub_no : '');

             // Add a row for the item
        $BillReport .= '<tr>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
       $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;">TENDER ITEM NO :' . $itemno . '</td>';
       $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:center;  word-wrap: break-word;"></td>';
        $BillReport .= '</tr>';

          // Add the item details row
        $BillReport .= '<tr>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:left;  word-wrap: break-word;">'.$BillData->item_unit.'</td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:right;  word-wrap: break-word;">'.$BillData->exec_qty.'</td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px;  text-align:justify;  word-wrap: break-word; ">'.$BillData->item_desc.'</td>';

          // Check if bill rate is equal to tender rate
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

    // Return the generated HTML content

    return $BillReport;


}

// Function to generate HTML table rows for bill item data to be used in a view
public function commonforeachview($NormalData,$tbillid,$work_id)
{
    // Initialize the helper class for formatting rupee amounts
     $convert=new Commonhelper();
       // Initialize an empty string to store the HTML content
    $BillReport = '';

      // Add the header row with column labels
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
      // Loop through each item in NormalData
        foreach($NormalData as $BillData){
            // $BillData=DB::table('bil_item')->where('b_item_id' , $BillData1->)->get();
              // Combine item number and sub number if available
            $itemno = $BillData->t_item_no . (!empty($BillData->sub_no) ? ' ' . $BillData->sub_no : '');


               // Add a row for the item
        $BillReport .= '<tr>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 3%; word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;"></td>';
       $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 55%; word-wrap: break-word;">TENDER ITEM NO :' . $itemno . '</td>';
       $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 8%; word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width:8%; word-wrap: break-word;"></td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center; width: 5%; word-wrap: break-word;"></td>';
        $BillReport .= '</tr>';

         // Add the item details row
        $BillReport .= '<tr>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:left; width: 3%; word-wrap: break-word;">'.$BillData->item_unit.'</td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:right; width: 8%; word-wrap: break-word;">'.$BillData->exec_qty.'</td>';
        $BillReport .= '<td style=" border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:justify; width: 55%; word-wrap: break-word; ">'.$BillData->item_desc.'</td>';
           // Check if bill rate is equal to tender rate
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

  // Return the generated HTML content
    return $BillReport;


}


// form xiv report for view
public function form_xivReport($tbillid ){
    // $amoutvalue=123456;

      // Fetch bill information based on the given bill ID
    $Billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('work_id','gst_amt','gst_rt','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
    'part_b_amt','gst_base','c_abeffect','c_gstbase','gross_amt','net_amt','c_gstamt','c_part_a_gstamt',
    'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','chq_amt')->first();
    // Get the work ID from the bill information
    $work_id=$Billinfo->work_id;
     // Fetch work data based on the work ID
    $workdata=DB::table('workmasters')->where('Work_Id' , $work_id)->first();
       // Get the latest measurement date for the given bill ID
    $Maxdt=DB::table('embs')->where('t_bill_Id' , $tbillid)->max('measurment_dt');
      // Format the date in 'd/m/Y' format
    $dates = Carbon::createFromFormat('Y-m-d',  $Maxdt)->format('d/m/Y');
     // Fetch bill deduction information based on the given bill ID
    $billDed_Info=DB::table('billdeds')->where('t_bill_Id' , $tbillid)->select('Ded_Head','Ded_Amt')->get();
     // Fetch agency details based on the agency ID from work data
    $Agency_Pl=DB::table('agencies')->where('id' , $workdata->Agency_Id)->select('Agency_Pl','Pan_no','Gst_no')->first();
      // Fetch Junior Engineer (JE) , deputy engineer and executive engineer details based on  IDs from work data
    $JE_nm=DB::table('jemasters')->where('jeid' , $workdata->jeid)->select('name','designation')->first();
    $DYE_nm=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();
    $EE_nm=DB::table('eemasters')->where('eeid' , $workdata->EE_id)->first();
    // $DYE_Design=DB::table('dyemasters')->where('DYE_id' , $workdata->DYE_id)->select('name','designation')->first();
    //dd($DYE_nm);  $DYE_nm->designation
    // $headercheck='Bill';
    $cvno=$Billinfo->cv_no;
     // Check if the bill is the final bill and get the bill number
    $isFinalBill=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('final_bill','t_bill_No')->first();
    // dd($isFinalBill);
    $FirstBill=$isFinalBill->t_bill_No;
    $FinalBill=$isFinalBill->final_bill;
    //dd($FirstBill,$FinalBill);
     // Format the bill number and determine the bill type
    $rbbillno=CommonHelper::formatTItemNo($FirstBill);
    $rbbiill=CommonHelper::getBillType($FinalBill);
 //dd($rbbillno,$rbbiill);
    $prev_rbbillno=CommonHelper::formatNumbers($FirstBill-1);
    // dd($prev_rbbillno);
    // $rbbillno=CommonHelper::formatTItemNo($FirstBill);
    // $rbbiill=CommonHelper::getBillType($FirstBill);

  // Initialize CommonHelper instance
$commonHelper = new CommonHelper();

      // Start building the HTML report
    $htmlreport='';
    $htmlreport .= '<h6 style="text-align: center; font-weight:bold;  word-wrap: break-word;">(Form No. XIV)</h6>';
    $htmlreport .= '<p style="margin-left:8%; margin-right:8%; text-align: center;  text-align: left;">Cashier</p>';
    $htmlreport .= '<h5 style="text-align: center; font-weight:bold;  word-wrap: break-word;">SLIP TO ACCOMPLAINT CLAIM FOR MONEY OF DISBURSING</h5>';
    $htmlreport .= '<p style="text-align: center; font-weight:bold;  word-wrap: break-word; ">(To be returned original by F.D./B.D.O)</p>';

    $htmlreport .= '<div class="table-responsive">';
    $htmlreport .= '<table style="margin-left:8%; margin-right:8%; margin-bottom:1%;">';
    $htmlreport .= '<thead>';

      // Add work name row
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=3 style="text-align: center;  font-weight:bold;">Name of Work  :</td>';
    $htmlreport .= '<td colspan=5 style="text-align: justify; font-weight:bold; paading-bottom:3%"> '.$workdata->Work_Nm.'</td>';
    $htmlreport .= '</tr>';

        // Add major head row
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">Major Head-</td>';
    $htmlreport .= '<td colspan=4 style="text-align: left;"> '.DB::table('fundhdms')->where('F_H_id', $workdata->F_H_id)->value('F_H_CODE').' </td>';
    $htmlreport .= '</tr>';

    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">Minor Head-</td>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">  </td>';
    $htmlreport .= '</tr>';

     // Add sub head row
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">Sub Head-</td>';
    $htmlreport .= '<td colspan=4 style="text-align: right;">  </td>';
    $htmlreport .= '</tr>';

    // Add separator line
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: justify;">--------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
    $htmlreport .= '</tr>';

       // Add note row
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: center; font-weight:bold;  word-wrap: break-word; padding-bottom:2%;">(To be fixed in  F.D./B.D.O)</td>';
    $htmlreport .= '</tr>';
  // Add recipient rows
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

     // Add separator line
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=8 style="text-align: justify;">-------------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
    $htmlreport .= '</tr>';
 // Add signatures row
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: center;"> '.$EE_nm->Designation.'</td>';
    $htmlreport .= '<td colspan=4 style="text-align: center;">C.A.&F.A</td>';
    $htmlreport .= '</tr>';

      // Add divisions row
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=4 style="text-align: center;">'.$workdata->Div.'</td>';
    $htmlreport .= '<td colspan=4 style="text-align: center;">Zilla Parishad, Sangli.</td>';
    $htmlreport .= '</tr>';

    // Add bill particulars row
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">Bill Particular</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">'.$rbbillno.'  '.$rbbiill.'</td>';
    $htmlreport .= '<td colspan=4 style="text-align: left;">__________________________________________________________</td>';
    $htmlreport .= '</tr>';

    //Add gross amount
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">Gross Amount</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">'.$commonHelper->formatIndianRupees($Billinfo->c_netamt).'   </td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">T.V. No.</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">___________________________________________</td>';
    $htmlreport .= '</tr>';

    //Add Net amount
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">Net Amount</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;"> '.$commonHelper->formatIndianRupees($Billinfo->chq_amt).'</td>';
    $htmlreport .= '<td colspan=1 style="text-align: left;">Date</td>';
    $htmlreport .= '<td colspan=3 style="text-align: left;">___________________________________________</td>';
    $htmlreport .= '</tr>';

    // add agency
    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">Agency</td>';
    $htmlreport .= '<td colspan=2 style="text-align: left;">'.$workdata->Agency_Nm.','.$Agency_Pl->Agency_Pl.'</td>';
    $htmlreport .= '<td colspan=1 style="text-align: left;">Signature</td>';
    $htmlreport .= '<td colspan=3 style="text-align: left;">___________________________________________</td>';
    $htmlreport .= '</tr>';

    //Amount treasury account
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
     //convert amount in words
    $cash_rs = $commonHelper->convertAmountToWords($rs__pay);
    // dd($cash_rs);

    //Acknowledgement line
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


    // signature line
    $imagePath = public_path('Uploads/signature/' . $EE_nm->sign);

    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrcEE = 'data:image/jpeg;base64,' . $imageData;


    $htmlreport .= '<tr>';
    $htmlreport .= '<td colspan=6 style="text-align: center; "></td>';
    $htmlreport .= '<td colspan=2 style="text-align: center;">';
   // $htmlreport .= '<div style="width: 180px; height: 50px; display: inline-block;"> <img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 130px; height: 60px;"></div>'; // Placeholder for signature box

    $htmlreport .= '<div style="width: 180px; height: 50px; display: inline-block;"> <br><br></div>'; // Placeholder for signature box


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

//add aggrement no
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

//add agency name and agency place
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">3) Name of the contracter </td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$workdata->Agency_Nm.','.$Agency_Pl->Agency_Pl.'</td>';
$htmlreport .= '</tr>';

//Agency pan no and gst no
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=4 style="text-align: left;"></td>';
$htmlreport .= '<td colspan=5 style="text-align: left;"> PAN No.:'.$Agency_Pl->Pan_no.'</td>';
$htmlreport .= '<td colspan=10 style="text-align: left;">GST No.:'.$Agency_Pl->Gst_no.'</td>';
$htmlreport .= '</tr>';

// sr no add
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">4) Sr. No. of the</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$rbbillno.'  '.$rbbiill.'</td>';
$htmlreport .= '</tr>';

// fh code add
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">5) Major Head</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;"> '.DB::table('fundhdms')->where('F_H_id', $workdata->F_H_id)->value('F_H_CODE').'</td>';
$htmlreport .= '</tr>';

//work name
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">6) Name of Work:</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$workdata->Work_Nm.'</td>';
$htmlreport .= '</tr>';

//Name pf Ps
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">7) Name of P.S.</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$workdata->Sub_Div.'</td>';
$htmlreport .= '</tr>';

//Gross amount add
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">8) Gross amount of Bill</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$commonHelper->formatIndianRupees($Billinfo->c_netamt).' </td>';
$htmlreport .= '</tr>';

//add net amount
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=12 style="text-align: left;">9) Net amount of Bill</td>';
$htmlreport .= '<td colspan=12 style="text-align: left;">'.$commonHelper->formatIndianRupees($Billinfo->chq_amt).'</td>';
$htmlreport .= '</tr>';

// Details recovery
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

//Signature and details
$DAOId=DB::table('workmasters')->where('Work_Id' , $work_id)->value('DAO_Id');
// dd($DAO_Id);
$sign3=DB::table('daomasters')->where('DAO_Id' , $DAOId)->first();
//dd($sign3);

$imagePath = public_path('Uploads/signature/' . $sign3->sign);

$imageData = base64_encode(file_get_contents($imagePath));
$imageSrcAAO = 'data:image/jpeg;base64,' . $imageData;


$htmlreport .= '<tr colspan=12 style="margin-right:3%;">';
$htmlreport .= '<td colspan=20 style="text-align: right; margin-right:3%;">';
//$htmlreport .= '<div style="width: 180px; height: 50px; display: inline-block;"> <img src="' . $imageSrcAAO . '" alt="Base64 Encoded Image" style="width: 130px; height: 60px;"></div>'; // Placeholder for signature box
$htmlreport .= '<div style="width: 180px; height: 50px; display: inline-block;"> <br><br></div>'; // Placeholder for signature box


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

// form xiv report pdf  function download
public function form_xiv_pdf_Fun($tbillid){
  //tbillid related bill information
    $Billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->select('work_id','gst_amt','gst_rt','cv_no','cv_dt','part_a_amt','a_b_effect','c_part_a_amt',
    'part_b_amt','gst_base','c_abeffect','c_gstbase','gross_amt','net_amt','c_gstamt','c_part_a_gstamt',
    'c_part_b_amt','c_billamtgt','c_billamtro','c_netamt','p_net_amt','chq_amt')->first();
// dd($Billinfo);

//work data information
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
 //je,ee,dye related all data get from ids
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


//qrcode data
$paymentInfo = "$tbillid";



  //qr code generated
$qrCode = QrCode::size(90)
->backgroundColor(255, 255, 255)
->color(0, 0, 0)
->margin(10)
->generate($paymentInfo);


$commonHelper = new CommonHelper();


// Convert the QR code SVG data to a plain string without the XML declaration
$qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);

//bind qrcode string to html
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
//Add major head
$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left;">Major Head-</td>';
$htmlreport .= '<td  style="text-align: center;"> '.DB::table('fundhdms')->where('F_H_id', $workdata->F_H_id)->value('F_H_CODE').' </td>';
$htmlreport .= '</tr>';
//Add minor Head
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

// add designation
$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: left;">To,<br><b>C.A. & F.O.</b></td>';
$htmlreport .= '<td  style="text-align: left;">To,<br><strong> '.$EE_nm->Designation.',<br></strong>  Date as noted as below</td>';
$htmlreport .= '</tr>';

//Add division
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
  // Add recipient rows
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
 // Add separator line

$htmlreport .= '<tr>';
$htmlreport .= '<td  style="text-align: justify;">----------------------------------------------------------------------------------------------------------------------------------------------------------------</td>';
$htmlreport .= '</tr>';


$htmlreport .= '</tbody>';
$htmlreport .= '</table>';

$rs__pay=$Billinfo->chq_amt;
//  dd($rs__pay);
//Convert amount to words
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

//Executive engineer sign
$imagePath = public_path('Uploads/signature/' . $EE_nm->sign);

$imageData = base64_encode(file_get_contents($imagePath));
$imageSrcEE = 'data:image/jpeg;base64,' . $imageData;




//$htmlreport .= '<p  style="text-align: right; margin-top:22px;"><img src="' . $imageSrcEE . '" alt="Base64 Encoded Image" style="width: 130px; height: 40px;"><br><strong>'.$EE_nm->Designation.'</strong><br>'.$workdata->Div.'</p>';
$htmlreport .= '<p  style="text-align: right; margin-top:22px;"><br><br><br><strong>'.$EE_nm->Designation.'</strong><br>'.$workdata->Div.'</p>';




// //Second View Page----------------------------------------
$htmlreport .= '<div style="page-break-before: always;"></div>';
$htmlreport .= '<table style="margin-left:8%; margin-right:1%;  margin-top:4%;">';
$htmlreport .= '<thead>';
//add agreement no and agreement date
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
//Add agency data
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

// Add work data and fund head Data
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



//summary data and amounts
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
//Amounts and conversion in indian rupees
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
//$htmlreport .= '<div style="width: 180px; height: 50px; display: inline-block;"> <img src="' . $imageSrcAAO . '" alt="Base64 Encoded Image" style="width: 130px; height: 60px;"></div>'; // Placeholder for signature box
$htmlreport .= '<div style="width: 180px; height: 50px; display: inline-block;"> <br><br><br></div>'; // Placeholder for signature box
$htmlreport .= '</td></tr>';


$htmlreport .= '<tr style="margin-right: 2%;">';
$htmlreport .= '<td colspan=20 style="text-align: right;">'. $sign3->designation.'</td>';
$htmlreport .= '</tr>';
$htmlreport .= '<tr>';
$htmlreport .= '<td colspan=20 style="text-align: right;">'.$workdata->Div.'</td>';
$htmlreport .= '</tr>';
$htmlreport .= '</thead>';
$htmlreport .= '</table>';

//  Create instance of the pdf class
$mpdf = new \Mpdf\Mpdf(['orientation' => 'P',  'margin_left' => 25.5,
'margin_right' => 4,]);  // Set orientation to portrait
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
//Royalty report for view page
public function royaltystatement(Request $request , $tbillid)
{
      // Fetch bill details for the given bill ID
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

       // Get the work ID related to the given bill ID
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
      // Get work data for the fetched work ID
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    // Get JE and DYE IDs from the work data
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    // Fetch signatures for DYE and JE
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


  // Fetch designations and subdivisions for JE and DYE
    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    // Get the common header view for the report
    $headercheck='Royalty';
    $header=$this->commonheaderview($tbillid , $headercheck);
    // Initialize the royalty report
       $RoyaltyReport='';
    $RoyaltyReport .=$header;
        $RoyaltyReport .= '<div class="table-responsive">';

    $RoyaltyReport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;  margin-right: 17px;">';
    $RoyaltyReport .= '<tbody>';

   // Fetch distinct royalty types for the given bill ID
    $royalmdata = DB::table('royal_m')
    ->select('royal_m')
    ->distinct()
    ->where('t_bill_Id', $tbillid)
    ->get();


    // Initialize total amount
    $ALLtotal = 0;

    // Loop through each royalty type and generate the report
    foreach($royalmdata as $roydata)
    {
      // Handle 'R' royalty type (Royalty Charges for various Material)
        if($roydata->royal_m == 'R')
        {


            $srno=1;
         // Add header for Royalty Charges
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Royalty Charges for various Material</th>';
            $RoyaltyReport .= '</tr>';
               // Add column headers
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Royalty Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';


                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'R')->get();
                $convert=new CommonHelper;
//dd($royalRmdatas);
                  // Loop through each royalty data and add to report
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

                  // Calculate total amount for 'R' type
                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'R')->sum('royal_amt');
                $ALLtotal += $totalamt;
                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';

                //dd($royalRmdata);

        }
       // Handle 'S' royalty type (Surcharge @ 2.00% of Royalty charges for all Material)
        if($roydata->royal_m == 'S')
        {

            $srno=1;

               // Add header for Surcharge
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Surcharge @ 2.00% of Royalty charges for all Material</th>';
            $RoyaltyReport .= '</tr>';
            $RoyaltyReport .= '<tr>';
             // Add column headers
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Surcharge Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';

              // Query to fetch royalty data with 'S' type from 'royal_m' table based on the bill ID
                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'S')->get();
                // Loop through each record and generate HTML for 'S' type royalty dat
                foreach($royalRmdatas as $royalRmdata)
                {
                    $RoyaltyReport .= '<tr>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $srno . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->material . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->tot_m_qty . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_rt . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_amt=$convert->formatIndianRupees($royalRmdata->royal_amt) . '</td>';
                    $RoyaltyReport .= '</tr>';

                      // Increment serial number
                    $srno++;
                }


               // Calculate total royalty amount for 'S' type
                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'S')->sum('royal_amt');
                $ALLtotal += $totalamt;
                // Append total amount row to report
                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';
                //dd($royalRmdata);

        }

        // Check if royalty type is 'D'
        if($roydata->royal_m == 'D')
        {

            $srno=1;

              // Add header row for 'D' type royalty contribution
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

                 // Query to fetch royalty data with 'D' type from 'royal_m' table based on the bill ID
                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'D')->get();

                   // Loop through each record and generate HTML for 'D' type royalty data
                foreach($royalRmdatas as $royalRmdata)
                {
                    $RoyaltyReport .= '<tr>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $srno . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->material . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->tot_m_qty . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_rt . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_amt=$convert->formatIndianRupees($royalRmdata->royal_amt) . '</td>';
                    $RoyaltyReport .= '</tr>';

                      // Increment serial number
                    $srno++;

                }
                    // Calculate total royalty amount for 'D' type
                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'D')->sum('royal_amt');
                $ALLtotal += $totalamt;
                  // Append total amount row to report
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
// Check if embsection2 status is 3 or higher to include signature
if($embsection2->mb_status >= '3')
{

//$RoyaltyReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;">';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
$RoyaltyReport .= '</div>';
$RoyaltyReport .= '</td>'; // First cell for signature details
}
$RoyaltyReport .= '<td colspan="3" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
//$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>At the site of checked by me work</strong></div>';
// Check if embsection2 status is 4 or higher to include signature
if($embsection2->mb_status >= '4')
{

//$RoyaltyReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
$RoyaltyReport .= '</div>';
}
$RoyaltyReport .= '</td>'; // First cell for signature details

$RoyaltyReport .= '</tr>';

     // Close the table and div tags
    $RoyaltyReport .= '</tbody>';
    $RoyaltyReport .= '</table>';
        $RoyaltyReport .= '</div>';

 //dd($embsection2);
    return view('reports/Royaltystatement' ,compact( 'embsection2' ,'tbillid', 'RoyaltyReport'));

   }


// royalty report pdf function
   public function royaltystatementreport(Request $request , $tbillid)
   {
    // Fetch bill details for the given bill ID
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
  // Get the work ID related to the given bill ID
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
     // Get work data for the fetched work ID
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
     // Get JE and DYE IDs from the work data
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
   // Fetch signatures for DYE and JE
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


     // Fetch designations and subdivisions for JE and DYE
    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

     // Get the common header view for the report
    $headercheck='Royalty';
    //$header=$this->commonheader($tbillid , $headercheck);
    // dd($header);
    // Initialize the royalty report
       $RoyaltyReport='';

    //bill data
       $tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
       //dd($recordentrynos);
       //division data
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
       //bill information
       $billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

       //qr code information
       $paymentInfo = "$tbillid";



       //qrcode generated
       $qrCode = QrCode::size(60)
       ->backgroundColor(255, 255, 255)
       ->color(0, 0, 0)
       ->margin(3)
       ->generate($paymentInfo);


       // Convert the QR code SVG data to a plain string without the XML declaration
       $qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);

       //common header html add
       $RoyaltyReport .= '<div style="position: absolute; top: 10%; left: 88%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">


       <table style="width: 100%; border-collapse: collapse;">


       <tr>
       <td  colspan="1" style="padding: 4px; text-align: left;"><h4><strong>' . $division . '</strong></h4></td>
       <td  colspan="2" style=" padding: 4px; text-align: right; margin: 0 10px;"><h4><strong>MB NO: ' . $workid . '</strong></h4></td>
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
       <td style=""><strong>Tender Id:</strong></td>
       <td colspan="2">' . $workdata->Tender_Id . '</td>
       </tr>
       <tr>
       <td  style=""><strong>Agency:</strong></td>
       <td  style="">' . $workdata->Agency_Nm . '</td>
       </tr>';

       // agreement date and no
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
       $RoyaltyReport .= '<td colspan="3" style="width: 60%;"><strong>Work Order No:</strong>' . $workdata->WO_No . '</td>';
       $RoyaltyReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Work Order Date:</strong>' . $workorderdt . '</td>';
       $RoyaltyReport .= '</tr>';

       //Measurement details
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

   // Fetch distinct royalty types for the given bill ID

    $royalmdata = DB::table('royal_m')
    ->select('royal_m')
    ->distinct()
    ->where('t_bill_Id', $tbillid)
    ->get();

    // Initialize total amount
    $ALLtotal=0;
     // Loop through each royalty type and generate the report
    foreach($royalmdata as $roydata)
    {
        // Handle 'R' royalty type (Royalty Charges for various Material)
        if($roydata->royal_m == 'R')
        {


            $srno=1;
         // Add header for Royalty Charges
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Royalty Charges for various Material</th>';
            $RoyaltyReport .= '</tr>';
             // Add column headers
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Royalty Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';


                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'R')->get();
                                $convert=new CommonHelper;
                 // Loop through each royalty data and add to report
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
                 // Calculate total amount for 'R' type
                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'R')->sum('royal_amt');
                $ALLtotal += $totalamt;

                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';

                //dd($royalRmdata);

        }
       // Handle 'S' royalty type (Surcharge @ 2.00% of Royalty charges for all Material)

        if($roydata->royal_m == 'S')
        {

            $srno=1;
          // Add header for Surcharge
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  colspan="5" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">Surcharge @ 2.00% of Royalty charges for all Material</th>';
            $RoyaltyReport .= '</tr>';
            // Add column headers
            $RoyaltyReport .= '<tr>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Sr.No</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Material</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Quantity</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Surcharge Rate</th>';
            $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align:center;">Amount</th>';
            $RoyaltyReport .= '</tr>';

                         // Query to fetch royalty data with 'S' type from 'royal_m' table based on the bill ID
                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'S')->get();
                 // Loop through each record and generate HTML for 'S' type royalty dat
                foreach($royalRmdatas as $royalRmdata)
                {
                    $RoyaltyReport .= '<tr>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $srno . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->material . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->tot_m_qty . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_rt . '</td>';
                    $RoyaltyReport .= '<td  style="border: 1px solid black; padding: 8px;  text-align: right; word-wrap: break-word;">' . $royalRmdata->royal_amt=$convert->formatIndianRupees($royalRmdata->royal_amt) . '</td>';
                    $RoyaltyReport .= '</tr>';

                     // Increment serial number
                    $srno++;
                }


                // Calculate total royalty amount for 'S' type
                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'S')->sum('royal_amt');

                $ALLtotal+=$totalamt;
                // Append total amount row to report
                $RoyaltyReport .= '<tr>';
                $RoyaltyReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
                $RoyaltyReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $totalamt=$convert->formatIndianRupees($totalamt) . '</th>';
                $RoyaltyReport .= '</tr>';
                //dd($royalRmdata);

        }
        // Check if royalty type is 'D'

        if($roydata->royal_m == 'D')
        {

            $srno=1;

            // Add header row for 'D' type royalty contribution
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

               // Query to fetch royalty data with 'D' type from 'royal_m' table based on the bill ID
                $royalRmdatas=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'D')->get();
                                   // Loop through each record and generate HTML for 'D' type royalty data
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
          // Calculate total royalty amount for 'D' type
                $totalamt=DB::table('royal_m')->where('t_bill_Id' , $tbillid)->where('royal_m' , 'D')->sum('royal_amt');

                $ALLtotal+=$totalamt;


             // Append total amount row to report
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
// Check if embsection2 status is 3 or higher to include signature

if($embsection2->mb_status >= '3')
{

//$RoyaltyReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br><br></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;">';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
$RoyaltyReport .= '</div>';
$RoyaltyReport .= '</td>'; // First cell for signature details
}
$RoyaltyReport .= '<td colspan="3" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
//$RecoveryReport .= '<div style="line-height: 1; margin: 0;"><strong>At the site of checked by me work</strong></div>';
// Check if embsection2 status is 4 or higher to include signature

if($embsection2->mb_status >= '4')
{

//$RoyaltyReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br><br></div>'; // Placeholder for signature box
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
$RoyaltyReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
$RoyaltyReport .= '</div>';
}
$RoyaltyReport .= '</td>'; // First cell for signature details

$RoyaltyReport .= '</tr>';

      // Close the table and div tags
    $RoyaltyReport .= '</tbody>';
    $RoyaltyReport .= '</table>';
   //dd($html);
// $pdf = new Dompdf();

//create instance for the pdf

$mpdf = new \Mpdf\Mpdf(['orientation' => 'P',  'margin_left' => 28.5,
'margin_right' => 6]); // Set orientation to portrait]); // Set orientation to portrait
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

    //Material consumption report view page
public function materialconsreport(Request $request , $tbillid)
{
        // Retrieve the main billing section for the given bill ID
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    // Get the work ID associated with the given bill ID
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
     // Retrieve work data for the obtained work ID
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
     // Get IDs for junior engineer and dye master from the work data
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    // Retrieve signature details for the dye master and junior engineer
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

      // Construct the full file path for the dye master's signature image
    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


 // Retrieve designations and subdivisions for both the dye master and junior engineer
    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    $headercheck='materialcons';
    //  // Define the header for the report
    $header=$this->commonheaderview($tbillid , $headercheck);

     // Initialize the report content
    $MaterialconReport = '';
    $MaterialconReport .= $header;
    $MaterialconReport .= '<div class="table-responsive">';

    $MaterialconReport .= '<table style="border-collapse: collapse; width: 100%; border: 1px solid black;  margin-right: 17px;">';
    $MaterialconReport .= '<tbody>';


  // Retrieve material consumption data for the given bill ID
    $matconsmdatas=DB::table('mat_cons_m')->where('t_bill_id' , $tbillid)->get();
       // Define the table headers
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

     // Iterate over each material consumption data entry
    foreach($matconsmdatas as $matconsmdata)
    {
               // Add a row for each material category
        $MaterialconReport .= '<tr>';
        $MaterialconReport .= '<td colspan="9" style=" padding-left: 50px; text-align: left; word-wrap: break-word;"><h3>' . $matconsmdata->material . '</h3></td>';
        $MaterialconReport .= '</tr>';




     // Retrieve detailed data for the current material category
    $matdatas=DB::table('mat_cons_d')->where('b_mat_id' , $matconsmdata->b_mat_id)->get();

      // Iterate over each detail entry
    foreach($matdatas as $matdata)
    {    // Prepare sub number if available
        $subno='';
        if($matdata->sub_no)
        {
            $subno=$matdata->sub_no;
        }
       // Add a row for each detail entry
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
    // Add a row for the total quantities
    $MaterialconReport .= '<tr>';
    $MaterialconReport .= '<th colspan="4" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
    $MaterialconReport .= '<th colspan="1" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">' . $matconsmdata->tot_t_qty . '</th>';
    $MaterialconReport .= '<th colspan="1" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;"></th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;">' . $matconsmdata->tot_a_qty . '</th>';
    $MaterialconReport .= '<th  colspan="2" style="border: 1px solid black; padding: 8px; background-color: #f2f2f2; text-align: center; word-wrap: break-word;"></th>';
    $MaterialconReport .= '</tr>';

 // Cell for JE signature details
    $MaterialconReport .= '<tr style="line-height: 0;">';
    $MaterialconReport .= '<td colspan="4" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '3')
{

    //$MaterialconReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;">';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
    $MaterialconReport .= '</div>';
}
// Cell for DYE signature details
    $MaterialconReport .= '</td>'; // First cell for signature details
    $MaterialconReport .= '<td colspan="5" style="border: 1px solid black; padding: 8px; max-width: 50%; text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '4')
{

    //$MaterialconReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <div  alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div></div>'; // Placeholder for signature box

    $MaterialconReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
    $MaterialconReport .= '</div>';
}
    $MaterialconReport .= '</td>'; // First cell for signature details

    // End of the table row for signatures
    $MaterialconReport .= '</tr>';

    }

    $MaterialconReport .= '</tbody>';
    $MaterialconReport .= '</table>';
    // Close the div for table responsiveness
        $MaterialconReport .= '</div>';


    return view('reports/MaterialConsStatement' ,compact('embsection2' , 'MaterialconReport'));
   }




   //material consumption report pdf function


   public function materialconsreportpdf(Request $request , $tbillid)
{
        // Retrieve the main billing section for the given bill ID
    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    // Get the work ID associated with the given bill ID
    $workid=DB::table('bills')->where('t_bill_Id' , $tbillid)->value('work_id');
     // Retrieve work data for the obtained work ID
    $workdata=DB::table('workmasters')->where('Work_Id' , $workid)->first();
    // Get IDs for junior engineer and dye master from the work data
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
    // Retrieve signature details for the dye master and junior engineer
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = 'data:image/jpeg;base64,' . $imageData;

      // Construct the full file path for the dye master's signature image
    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;

   // Retrieve designations and subdivisions for both the dye master and junior engineer

    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

    //header name
    $headercheck='materialcons';
    //$header=$this->commonheader($tbillid , $headercheck);

    //initiliase string
    $MaterialconReport = '';
    //$MaterialconReport .= $header;



  //bill data related tbillid
    $tbilldata=DB::table('bills')->where('t_bill_Id' , $embsection2->t_bill_Id)->first();
    //dd($recordentrynos);

    //division data
    $division=DB::table('divisions')->where('div_id' , $workdata->Div_Id)->value('div');
    //dd($tbillid);

         $formattedTItemNo = CommonHelper::formatTItemNo($embsection2->t_bill_No);
         $billType = CommonHelper::getBillType($embsection2->final_bill);
    //dd($formattedTItemNo , $billType);

     //Agreement Details
    $agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';


    $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
         $billType = CommonHelper::getBillType($tbilldata->final_bill);
    //dd($formattedTItemNo , $billType);

    // $tbillid = 12345;
    // $workid = 56263546723;

    //Bill information
    $billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    //Qr code details
    $paymentInfo = "$tbillid";



    //create qr code
    $qrCode = QrCode::size(60)
    ->backgroundColor(255, 255, 255)
    ->color(0, 0, 0)
    ->margin(3)
    ->generate($paymentInfo);


    // Convert the QR code SVG data to a plain string without the XML declaration
    $qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);

    //work details
    $MaterialconReport .= '<div style="position: absolute; top: 10%; left: 89%; transform: translateX(-50%); font:weight;">' . $qrCodeString . '</div><div class="" style="margin-top:20px; border-collapse: collapse;">


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
    <td style=""><strong>Tender Id:</strong></td>
    <td colspan="2">' . $workdata->Tender_Id . '</td>
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
    $MaterialconReport .= '<td colspan="3" style="width: 60%;"><strong>Work Order No:</strong>' . $workdata->WO_No . '</td>';
    $MaterialconReport .= '<td colspan="" style="width: 40%; text-align: right;"><strong>Work Order Date:</strong>' . $workorderdt . '</td>';
    $MaterialconReport .= '</tr>';

    //Measurement data
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


  // Retrieve material consumption data for the given bill ID
   $matconsmdatas=DB::table('mat_cons_m')->where('t_bill_id' , $tbillid)->get();
   // Define the table headers
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

     // Iterate over each material consumption data entry
    foreach($matconsmdatas as $matconsmdata)
    {
               // Add a row for each material category
        $MaterialconReport .= '<tr>';
        $MaterialconReport .= '<td colspan="9" style=" padding-left: 50px;  text-align: left; word-wrap: break-word;"><h3>' . $matconsmdata->material . '</h3></td>';
        $MaterialconReport .= '</tr>';




    // Retrieve detailed data for the current material category
    $matdatas=DB::table('mat_cons_d')->where('b_mat_id' , $matconsmdata->b_mat_id)->get();

     // Iterate over each detail entry
    foreach($matdatas as $matdata)
    {     // Prepare sub number if available
        $subno='';
        if($matdata->sub_no)
        {
            $subno=$matdata->sub_no;
        }
          // Add a row for each detail entry
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
       // Add a row for the total quantities
    $MaterialconReport .= '<tr>';
    $MaterialconReport .= '<th colspan="4" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">Total</th>';
    $MaterialconReport .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $matconsmdata->tot_t_qty . '</th>';
    $MaterialconReport .= '<th colspan="1" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;"></th>';
    $MaterialconReport .= '<th  style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;">' . $matconsmdata->tot_a_qty . '</th>';
    $MaterialconReport .= '<th  colspan="2" style="border: 1px solid black; padding: 5px; background-color: #f2f2f2; text-align: right; word-wrap: break-word;"></th>';
    $MaterialconReport .= '</tr>';

 // Start of the table row for signatures
    $MaterialconReport .= '<tr style="line-height: 0;">';
    $MaterialconReport .= '<td colspan="4" style="border: 1px solid black; padding: 5px;  text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '3')
{

    //$MaterialconReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br><br></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;">';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jedesignation . '</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>';
    $MaterialconReport .= '</div>';
}
// Cell for DYE signature details
    $MaterialconReport .= '</td>'; // First cell for signature details
    $MaterialconReport .= '<td colspan="5" style="border: 1px solid black; padding: 8px;  text-align: center; line-height: 0;">';
    if($embsection2->mb_status >= '4')
{

    //$MaterialconReport .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style=" width: 150px; height: 50px; display: inline-block;"> <br><br><br></div>'; // Placeholder for signature box
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;">'; // Adjusted line height and margin
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $sign->name .'</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>';
    $MaterialconReport .= '<div style="line-height: 1; margin: 0;"><strong> '. $dyesubdivision .'</strong></div>';
    $MaterialconReport .= '</div>';
}
// End of the table row for signatures
    $MaterialconReport .= '</td>'; // First cell for signature details

    $MaterialconReport .= '</tr>';

    }
 // Close the table body and table
    $MaterialconReport .= '</tbody>';
    $MaterialconReport .= '</table>';



  // create instance for the pdf create
    $mpdf = new \Mpdf\Mpdf(['orientation' => 'P',  'margin_left' => 27,
     'margin_right' => 6,]); // Set orientation to portrait
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

  //completion certificate report  view
    public function compcertfreport(Request $request , $tbillid)
    {
      // Retrieve the bill record based on the provided $tbillid
        $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
        // dd($embsection2);

        $WorkId=$embsection2->work_id;
           // Retrieve the work master details based on the work ID
        $DBWorkMaster=DB::table('workmasters')
        ->where('Work_Id',$WorkId)
        ->first();
      // Retrieve additional work data based on the work ID
        $workdata=DB::table('workmasters')->where('Work_Id' , $WorkId)->first();
        // Extract JE ID and DYE ID from the work data
        $jeid=$workdata->jeid;
        $dyeid=$workdata->DYE_id;
        // Retrieve the DYE signature details based on the DYE ID
        $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
         // Retrieve the JE signature details based on the JE ID
        $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
        // Construct the full file path
        $imagePath = public_path('Uploads/signature/' . $sign->sign);
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageSrc = 'data:image/jpeg;base64,' . $imageData;

        $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
        $imageData2 = base64_encode(file_get_contents($imagePath2));
        $imageSrc2 = 'data:image/jpeg;base64,' . $imageData2;


       // Retrieve designation and subdivision details for JE
        $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
        $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

          // Retrieve designation and subdivision details for DYE
        $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
        $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');

         // Format the agreement date if it exists
     $agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';

       // Create an instance of Commonhelper to use its methods
     $convert=new Commonhelper();
      // Initialize HTML for the certificate
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

               // Add signature section with conditional display based on status
              $certificateHTML .= '      <table style="width: 100%;>
          <tr style="line-height: 0;">
            <td  style=" padding: 8px; max-width: 50%; text-align: center; line-height: 0;"> ';
                      if($embsection2->mb_status >= '3')
            {
            // $certificateHTML .= ' <div style=" width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>
            // <div style="line-height: 1; margin: 0;">
            // <div style="line-height: 1; margin: 0;"><strong>' . $sign2->name . '</strong></div>
            // <div style="line-height: 1; margin: 0;"><strong>' . $jedesignation .'</strong></div>
            // <div style="line-height: 1; margin: 0;"><strong>' . $jesubdivision .'</strong></div>
            // </div> ';
             $certificateHTML .= ' <div style=" width: 150px; height: 50px; display: inline-block;"> <br><br></div>
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
            // $certificateHTML .= '<div style="width: 150px; height: 50px; display: inline-block;"> <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"></div>
            // <div style="line-height: 1; margin: 0;">
            // <div style="line-height: 1; margin: 0;"><strong>' . $sign->name . '</strong></div>
            // <div style="line-height: 1; margin: 0;"><strong>' . $dyedesignation .'</strong></div>
            // <div style="line-height: 1; margin: 0;"><strong>' . $dyesubdivision .'</strong></div>
            // </div>';
            $certificateHTML .= '<div style="width: 150px; height: 50px; display: inline-block;"> <br><br></div>
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

         // Return the view with the report data
        return view('reports/Certificate' ,compact('embsection2','DBWorkMaster','certificateHTML'));
       }

// completion certificate report pdf download
public function compcertfreportPDF(Request $request , $tbillid)
{
    // Retrieve the bill record based on the provided $tbillid

    $embsection2=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
    // dd($embsection2);

    $WorkId=$embsection2->work_id;
       // Retrieve the work master details based on the work ID
    $DBWorkMaster=DB::table('workmasters')
    ->where('Work_Id',$WorkId)
    ->first();
     // Retrieve additional work data based on the work ID
    $workdata=DB::table('workmasters')->where('Work_Id' , $WorkId)->first();
   // Extract JE ID and DYE ID from the work data
    $jeid=$workdata->jeid;
    $dyeid=$workdata->DYE_id;
  // Retrieve the DYE signature details based on the DYE ID
    $sign=DB::table('dyemasters')->where('dye_id' , $dyeid)->first();
     // Retrieve the JE signature details based on the JE ID
    $sign2=DB::table('jemasters')->where('jeid' , $jeid)->first();
    // Construct the full file path
    $imagePath = public_path('Uploads/signature/' . $sign->sign);
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageSrc = $imagePath;

    $imagePath2 = public_path('Uploads/signature/' . $sign2->sign);
    $imageData2 = base64_encode(file_get_contents($imagePath2));
    $imageSrc2 = $imagePath2;

     $agreementDate = $workdata->Agree_Dt ? date('d/m/Y', strtotime($workdata->Agree_Dt)) : '';

      // Retrieve designation and subdivision details for JE
    $jedesignation=DB::table('designations')->where('Designation' , $sign2->designation)->value('Designation');
    $jesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign2->subdiv_id)->value('Sub_Div');

      // Retrieve designation and subdivision details for DYE
    $dyedesignation=DB::table('designations')->where('Designation' , $sign->designation)->value('Designation');
    $dyesubdivision=DB::table('subdivms')->where('Sub_Div_Id' , $sign->subdiv_id)->value('Sub_Div');



    $billinfo=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();

    //qrcode information
    $paymentInfo = "$tbillid";



    //qr code created
    $qrCode = QrCode::size(90)
    ->backgroundColor(255, 255, 255)
    ->color(0, 0, 0)
    ->margin(10)
    ->generate($paymentInfo);


    // Convert the QR code SVG data to a plain string without the XML declaration
    $qrCodeString = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $qrCode);


  // Create an instance of Commonhelper to use its methods
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
            // $certificateHTML .= '<td style="width: 50%; text-align: center;">
            //     <img src="' . $imageSrc2 . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>
            //     <strong>' . $sign2->name . '</strong><br>
            //     <strong>' . $jedesignation .'</strong><br>
            //     <strong>' . $jesubdivision .'</strong>
            // </td>';
            $certificateHTML .= '<td style="width: 50%; text-align: center;">
                <br><br><br>
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
            // $certificateHTML .= '<td style="width: 50%; text-align: center;">
            //     <img src="' . $imageSrc . '" alt="Base64 Encoded Image" style="width: 100px; height: 60px;"><br>
            //     <strong>' . $sign->name . '</strong><br>
            //     <strong>' . $dyedesignation .'</strong><br>
            //     <strong>' . $dyesubdivision .'</strong>
            // </td>';
            $certificateHTML .= '<td style="width: 50%; text-align: center;">
                <br><br><br>
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



    // create instance of pdf create
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
    //bill data given tbill id related
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
