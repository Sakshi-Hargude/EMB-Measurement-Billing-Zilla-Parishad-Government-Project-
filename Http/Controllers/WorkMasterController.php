<?php

namespace App\Http\Controllers;

use DB;
use DateTime;
use App\AllExcelsheet;
Use App\Models\Workmaster;
use App\Models\Tnditem;
//import excel file
//tempary table of tenser of item
use App\Models\Subdivms;
use App\Models\MBIssueSO;
use App\Models\MBIssueDiv;
use App\Models\Temptnditem;
use App\Helpers\ExcelReader;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use App\Helpers\PublicDivisionId;
use App\Imports\TempatnditemImport;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
use RealRashid\SweetAlert\Facades\Alert;
use App\Request as AppRequest; // Import the App\Request class
use App\Helpers\CommonHelper;

//workmaster data , insert , update  ,view 
class WorkMasterController extends Controller
{
//insert Records
public function funshowImportData(Request $request,$Work_Id)
{
// dd('ok');
$divisionId = PublicDivisionId::DIVISION_ID;
$DivisionOffer = $divisionId ."0";

  // Retrieve work data based on Work_Id
    $workData = DB::table('workmasters')
    ->select('Work_Id','div','Sub_Div','Sub_Div_Id', 'F_H_Code','F_H_id', 'Work_Nm','Work_Nm_M','Work_Type',
    'Work_Area', 'Tnd_Amt', 'Agency_Id', 'Agency_Nm', 
    'AA_No','AA_Dt','AA_Amt','AA_Authority','TS_No','TS_Dt','TS_Amt','TS_Authority',
    'SSR_Year','WO_No','Wo_Dt','Period','Perd_Unit',
    'A_B_Pc','Above_Below','WO_Amt','Tal','Village_ID','Ps_Consti','Zp_Consti',
    'MP_Con_Id','MLA_Con_Id','EE_id','DYE_id','jeid','SDC_id','PB_Id','DAO_Id','AB_Id',
    'Tot_Exp','work_comp','actual_complete_date','Tal_Id','Agree_No','Agree_Dt')
    ->where('Work_Id', $Work_Id)
    ->first(); 
    // dd($workData->Perd_Unit);
    // dd($workData->SDC_id);

      // Check if work data exists
    if ($workData) 
    {
        // $workData->AA_Dt = date('d/m/Y', strtotime($workData->AA_Dt));
        // $workData->TS_Dt = date('d/m/Y', strtotime($workData->TS_Dt));
        // $workData->Wo_Dt = date('Y/m/d', strtotime($workData->Wo_Dt));
        // $workData->Agree_Dt = null;
        // $workData->Agree_Dt = $workData->Agree_Dt !== null ? $workData->Agree_Dt : null;

        // dd($workData);
    } 
    // dd($workData->TS_Dt,$workData->AA_Dt,$workData->Wo_Dt);

    // Retrieve division details
    $divisions = DB::table('divisions')
    ->select('div')
    ->where('div', 'Zilla Parishad, Sangli')
    ->get();
       // Retrieve subdivision details based on divisionId
    $Subdivisions = DB::table('subdivms')
    ->select('Sub_Div')
    ->where('Div_Id', $divisionId)
    ->get();
// dd($Subdivisions);
    $DBagencies = DB::table('agencies')
    ->select('agency_nm')
    ->where('id', $workData->Agency_Id)
    ->get();
    // dd($DBagencies);
    
      // Retrieve Fund Head Name based on workData's F_H_id
        $FHCODEName=DB::table('fundhdms')->where('F_H_id',$workData->F_H_id)->value('Fund_Hd_M');
        // dd($workData->F_H_id);
    // dd($FHCODEName,$workData->F_H_id);

      // Retrieve list of all agencies
    $DBagengieslist=DB::table('agencies')
    ->select('agency_nm')
    ->get();
// dd($DBagengieslist);

   // Retrieve district ID of the division
    $DBdistiddivision = DB::table('divisions')
        ->select('dist_id')
    ->where('div_id',$divisionId)
    ->get();
    // dd($DBdistiddivision);




   // Retrieve subdivisions for a specific Sub_Div_Id
    $Subdivisionsforje = DB::table('subdivms')
    ->select('Sub_Div_Id')
    ->where('Sub_Div_Id', 1471)
    ->get();
    // dd($Subdivisionsforje);

// $dbso=DB::table('jemasters')
// ->select('name')
// ->where('div_id',147)
// ->get();
// // dd($dbso);

  // Retrieve divisional office details
$divisionaloffice = DB::table('subdivms')
    ->select('Sub_Div_Id')
    ->where('Sub_Div_Id', $DivisionOffer) // Replace 'SomeColumn' with the actual column name you want to compare against
    ->get();
// dd($divisionaloffice);

 // Retrieve EE details based on workData's EE_id
$EEexcel=DB::table('eemasters')
->select('name')
->where('eeid',$workData->EE_id)
->get();
// dd($EEexcel);

   // Retrieve list of EEs based on divisionId
$DBEE=DB::table('eemasters')
->select('name')
->where('divid',$divisionId)
->get();
// dd($DBEE);
 

    // Retrieve DYE details based on workData's DYE_id
$DYEexcel=DB::table('dyemasters')
->select('name')
->where('dye_id',$workData->DYE_id)
->get();
// dd($DYEexcel);

  // Retrieve list of DYEs based on divisionId and Sub_Div_Id
$DBDYE=DB::table('dyemasters')
->select('name')
->where('div_id',$divisionId)
->where('subdiv_id',$workData->Sub_Div_Id)
->get();
// dd($DBDYE);

 // Retrieve JE details based on workData's jeid
$SOexcel=DB::table('jemasters')
->select('name')
->where('jeid',$workData->jeid)
->get();
// dd($SOexcel);

 // Retrieve list of JEs based on divisionId and Sub_Div_Id
$DBSO=DB::table('jemasters')
->select('name')
->where('div_id',$divisionId)
->where('subdiv_id',$workData->Sub_Div_Id)
->get();
// dd($DBSO);

 // Retrieve SDC details based on workData's SDC_id
$SDCexcel=DB::table('sdcmasters')
->select('name')
->where('SDC_id',$workData->SDC_id)
->get();
// dd($SDCexcel);

    // Retrieve list of SDCs based on divisionId and Sub_Div_Id
$DBSDC=DB::table('sdcmasters')
->select('name')
->where('div_id',$divisionId)
->where('subdiv_id',$workData->Sub_Div_Id)
->get();
// dd($DBSDC);

$DivisionOffer = $divisionId ."0";
// dd($DivisionOffer);

  // Retrieve PA details based on divisionId and DivisionOffer
$DBPA = DB::table('dyemasters')
    ->select('name')
    ->where('div_id',$divisionId)
    ->where('subdiv_id', $DivisionOffer) // Replace 'SomeColumn' with the actual column name you want to compare against
    ->get();
// dd($DBPO);

// dd($divisionId);
 // Retrieve PO details based on divisionId and DivisionOffer
$DBPO = DB::table('jemasters')
    ->select('name')
    ->where('div_id',$divisionId)
    ->where('subdiv_id', $DivisionOffer) // Replace 'SomeColumn' with the actual column name you want to compare against
    ->get();
// dd($DBPA);

 // Retrieve DAO details based on workData's DAO_Id
$DAOexcel=DB::table('daomasters')
->select('name')
->where('DAO_id',$workData->DAO_Id)
->get();
// dd($DAOexcel);

  // Retrieve list of DAOs based on divisionId
$DBDAO=DB::table('daomasters')
->select('name')
->where('div_id',$divisionId)
->get();
// dd($DBDAO);

   // Retrieve Audit details based on workData's AB_Id
$Auditorexcel=DB::table('abmasters')
->select('name')
->where('AB_Id',$workData->AB_Id)
->get();
// dd($Auditorexcel);

 // Retrieve list of Auditors based on divisionId
$DBAB=DB::table('abmasters')
->select('name')
->where('div_id',$divisionId)
->get();
// dd($DBAB);
// Retrieve Tal details based on workData's Tal_Id
$DBtalselected = DB::table('talms')
->select('Tal')
->where('Tal_Id', $workData->Tal_Id)
->get();
// dd($DBtalselected);
 // Retrieve list of Tal details based on district ID
$DBtal = DB::table('talms')
->select('Tal')
->where('Dist_Id', 2735)
->get();
// dd($DBtal);
// Retrieve village details based on workData's Village_ID
$DBvillage=DB::table('villagemasters')
->select('Village')
->where('Village_Id',$workData->Village_ID)
->get();
//  dd($DBvillage);
//  dd($workData->Tal_Id);

// $villagelist = DB::table('villagemasters')
//     ->select('Village', 'Tal_Id')
//     ->where('Tal_Id', $workData->Tal_Id)
//     ->get();

 // Retrieve list of village details based on Tal_Id
$villagelist = DB::table('villagemasters')
    ->select('Village', 'Tal_Id')
    ->where('Tal_Id', $workData->Tal_Id)
    ->get();
    //  dd($villagelist);

      // Retrieve MP Constituency ID based on Village_ID
$mpid=DB::table('villagemasters')
->select('MP_Con_Id')
->where('Village_Id',$workData->Village_ID)
->get();
// dd($mpid);

    // Retrieve selected MP Constituency based on workData's MP_Con_Id
$DBMPselect=DB::table('mpconsts')
->select('MP_Con')
->where('MP_Con_Id',$workData->MP_Con_Id)
->get();
//  dd($DBMPselect);

 // Retrieve list of all MP Constituencies
$DBMP=DB::table('mpconsts')
->select('MP_Con')
->get();
// dd($DBMP);


$DBMLAselect=DB::table('mlaconsts')
->where('MLA_Con_Id',$workData->MLA_Con_Id)
->select('MLA_Con')
->get();
// dd($DBMLAselect);

 // Retrieve selected MLA Constituency based on workData's MLA_Con_Id
$DBMLA=DB::table('mlaconsts')
->select('MLA_Con')
->get();
// dd($DBMLA);


    // Retrieve selected PS Constituency based on workData's Ps_Consti
$DBPSselect=DB::table('psconsts')
->where('PS_Con_Id',$workData->Ps_Consti)
->select('PS_Con')
->get();
// dd($DBPSselect);

 // Retrieve list of all PS Constituencies
$DBps=DB::table('psconsts')
->select('PS_Con')
->get();
// dd($DBps);

 // Retrieve selected ZP Constituency based on workData's Zp_Consti
$DBZPselect=DB::table('zpconsts')
->where('ZP_Con_Id',$workData->Zp_Consti)
->select('ZP_Con')
->get();
// dd($DBZPselect);

 // Retrieve list of all ZP Constituencies
$DBzp=DB::table('zpconsts')
    ->select('ZP_Con')
    ->get();
// dd($DBzp);



  // If workData's Village_ID is not empty, retrieve related details
if(!empty($workData->Village_ID))
{
$DBvillageAllid = DB::table('villagemasters')
    ->select('MP_Con_Id', 'MLA_Con_Id', 'ZP_Con_Id', 'PS_con_id')
    ->where('Village_Id', $workData->Village_ID)
    ->first();
//  dd($DBvillageAllid,$DBvillageAllid->MP_Con_Id);


        // Check if village details exist
 if ($DBvillageAllid) {
     // Retrieve selected MP Constituency based on village details
    $DBMPselect=DB::table('mpconsts')
        ->select('MP_Con')
        ->where('MP_Con_Id',$DBvillageAllid->MP_Con_Id)
        ->get();
    // dd($DBvillageAllid, $DBvillageAllid->MP_Con_Id);
} else {
    // Retrieve list of all MP Constituencies
    $DBMPselect=DB::table('mpconsts')
    ->select('MP_Con')
    ->get();
    // dd("No matching records found for Village_ID: {$workData->Village_ID}");
}
if ($workData->MLA_Con_Id) {
    $DBMLAselect = DB::table('mlaconsts')
        ->where('MLA_Con_Id', $workData->MLA_Con_Id)
        ->select('MLA_Con')
        ->get();
    // dd($DBMLAselect);
} else {
    $DBMLAselect = DB::table('mlaconsts')
        ->select('MLA_Con')
        ->get();
    // dd("No matching records found for MLA_Con_Id: {$workData->MLA_Con_Id}");
}
}

// Fetch paginated items related to the specified Work_Id
    $DStnditems = DB::table('tnditems')
    ->select('t_item_no','sub_no', 'item_desc', 'tnd_qty', 'item_unit', 'tnd_rt', 't_item_amt')
    ->where('work_Id', $Work_Id)
    // ->get();
    ->paginate(10);

    // Check if the request is an AJAX request
if ($request->ajax()) {
return response()->json([
    'data' => $DStnditems->items(), // Return only the paginated items
    'links' => $DStnditems->links('pagination::bootstrap-4')->toHtml() // Return pagination links HTML
]);
}
    
    // If not an AJAX request, pass data to the view

        return view('workmaster', [
            'workData' => $workData,
            'FHCODEName'=>$FHCODEName,
            'DStnditems' => $DStnditems,
            'divisions' => $divisions,
            'Subdivisions' => $Subdivisions,
            'DBagencies' => $DBagencies,
            'DBagengieslist'=>$DBagengieslist,
            'DBtalselected'=>$DBtalselected,
            'DBtal' => $DBtal,
            'DBvillage'=>$DBvillage,
            'villagelist' => $villagelist,
            'DBps'=>$DBps,
            'DBPSselect'=>$DBPSselect,
            'DBzp'=>$DBzp,
            'DBZPselect'=>$DBZPselect,
            // 'dbso' => $dbso,
            'DBPO' => $DBPO,
            'DBPA'=>$DBPA,
            'EEexcel'=>$EEexcel,
            'DBEE' => $DBEE,
            'DYEexcel'=>$DYEexcel,
            'DBDYE' => $DBDYE,
            'SOexcel'=>$SOexcel,
            'DBSO'=>$DBSO,
            'SDCexcel'=>$SDCexcel,
            'DBSDC' => $DBSDC,
            'DAOexcel'=>$DAOexcel,
            'DBDAO' => $DBDAO,
            'Auditorexcel'=>$Auditorexcel,
            'DBAB' => $DBAB,
            'DBMP'=>$DBMP,
            'DBMPselect'=>$DBMPselect,
            'DBMLAselect'=>$DBMLAselect,
            'DBMLA'=>$DBMLA,

        ]);    
    }

    public function funCreate(Request $data)
    {
        try
        {
                     // Retrieve work_id from the request
                    $Work_Id = $data->input('work_id');
                     // Retrieve division from the request
                    $div=$data->input('Div');

                 // Retrieve work_id from the request again (duplicate)
                    $workid=$data->input('work_id');

                     // Retrieve subdivision from the request
                    $subdiv=$data->input('Sub_Div');

                     // Retrieve fund head code from the request
                    $fundhd=$data->input('FH_Code');
                                        // dd($fundhd);
                    // Extract numeric part of the fund head code
                    $FHcodeStartNO = preg_replace("/[^0-9]/", '', $fundhd);

                      // Get the fund head ID by matching the numeric part
                    $FHCODEid = DB::table('fundhdms')->where('F_H_CODE', 'LIKE', $FHcodeStartNO . '%')->value('F_H_id');
                    // dd($fundhd,$FHCODEid);
                     // Retrieve various other inputs from the request
                    $Work_Nm=$data->input('Work_Nm');
                    $Work_Nm_M=$data->input('Work_Nm_M');
                    $workType=$data->input('workType');
                    $workarea=$data->input('workarea');
                    $SSR_year=$data->input('SSR_year');
                    $Tnd_Amt=$data->input('Tnd_Amt');
                    $agency=$data->input('agency');
                    // dd($agency);
                     // Get the agency ID by matching the agency name
                    $agencyId=DB::table('agencies')
                    ->where('agency_nm',$agency)
                    ->value('id');
                    // dd( $agencyId);
                    // dd($div,$workid,$subdiv,$fundhd,$Work_Nm,$Work_Nm_M,$workType,$workarea,$SSR_year,$Tnd_Amt,$agency);
                     // Retrieve AA number and date from the request
                    $AA_No=$data->input('AA_No');
                    $AA_Date=$data->input('AA_Date');
                    // dd($AA_Date);
                    // $convertedAADate = date("Y-m-d", strtotime($AA_Date));
                    // $convertedAADate = DateTime::createFromFormat('d/m/Y', $AA_Date)->format('Y-m-d');
                    if ($AA_Date !== null) {
                          // Convert AA date to the required format, if provided
                        $convertedAADate=$AA_Date;
                    } else {
                        $convertedAADate = null;
                    }

                       // Retrieve AA amount and authority from the request
                    $AA_Amount=$data->input('AA_Amount');
                    $AA_Authority=$data->input('AA_Authority');

                    // Retrieve TS number and date from the request, set to empty string if null
                    $TS_No=$data->input('TS_No');
                    $TS_No = ($TS_No === null) ? '' : $TS_No;
                    // dd($TS_No);
                    $TS_Date=$data->input('TS_Date');
                    // dd($TS_Date);
                    if ($TS_Date !== null) {
                        // $convertedTSDate = DateTime::createFromFormat('d/m/Y', $TS_Date)->format('Y-m-d');
                        $convertedTSDate=$TS_Date;
                    } else {
                        $convertedTSDate = null;
                    }
                    // dd($convertedTSDate);

                                        
                    //  dd($TS_Date);
                    // $convertedTSDate = date("Y-m-d", strtotime($TS_Date));
                    // $convertedTSDate = DateTime::createFromFormat('d/m/Y', $TS_Date)->format('Y-m-d');
                    // dd($AA_Date,$convertedAADate,$TS_Date,$convertedTSDate);

                     // Retrieve TS amount and authority from the request, set to empty string if null
                    $TS_Amount=$data->input('TS_Amount');
                    $TS_Authority=$data->input('TS_Authority');
                    $TS_Authority = ($TS_Authority === null) ? '' : $TS_Authority;

                     // Retrieve various work order details from the request
                    $WO_No=$data->input('WO_No');
                    $Wo_Dt=$data->input('Wo_Dt');
                    $WO_Amt=$data->input('WO_Amt');
                    // dd($WO_Amt);
                    $Agree_No=$data->input('Agree_No');
                    $Agree_Dt=$data->input('Agree_Dt');
                    $a_b_effect=$data->input('a_b_effect');
                    $Above_Below=$data->input('Above_Below');
                    $A_B_Pc=$data->input('A_B_Pc');
                    // dd($A_B_Pc);
                    $Period=$data->input('Period');
                    $Perd_Unit=$data->input('Perd_Unit');
                    // dd($Perd_Unit);
                    $Stip_Comp_Dt=$data->input('Stip_Comp_Dt');
                    // dd($Stip_Comp_Dt);
                    $tm_lm_extension=$data->input('tm_lm_extension');
                    $rs_dt_comp=$data->input('rs_dt_comp');

                       // Retrieve and map Taluka
                    $talu=$data->input('Taluka');
                    $Tal=DB::table('talms')
                    ->select('Tal')
                    ->where('Tal',$talu)
                    ->value('Tal');

                      // Retrieve and map village
                    $village=$data->input('village');
                    // dd($village);
                    $DBvillageId=DB::table('villagemasters')
                    ->select('Village_Id')
                    ->where('Village',$village)
                    ->value('Village_Id');

                      // Retrieve and map MP constituency
                    $DBMP=$data->input('mp');
                    // dd($DBMP);
                    $mpId=DB::table('mpconsts')
                    ->select('MP_Con_Id')
                    ->where('MP_Con',$DBMP)
                    ->value('MP_Con_Id');
                    // dd($mpId);

                       // Retrieve and map MLA constituency
                    $mla=$data->input('mla_Name');
                    // dd($mla);
                    $mlaId=DB::table('mlaconsts')
                    ->select('MLA_Con_Id')
                    ->where('MLA_Con',$mla)
                    ->value('MLA_Con_Id');
                    // dd($mlaId);

                   // Retrieve and map ZP constituency
                    $Zp=$data->input('ZP_Constituency');
                    // dd($mla);
                    $zpId=DB::table('zpconsts')
                    ->select('ZP_Con_Id')
                    ->where('ZP_Con',$Zp)
                    ->value('ZP_Con_Id');
                    // dd($zpId);

                     // Retrieve and map PS constituency
                    $ps=$data->input('PS_Constituency');
                    // dd($ps);
                    $psId=DB::table('psconsts')
                    ->select('PS_Con_Id')
                    ->where('PS_Con',$Zp)
                    ->value('PS_Con_Id');
                    // dd($psId);


  // Retrieve and map various engineer and official IDs

                    $DBEE=$data->input('EE_Name');
                    // dd($DBEE);
                    $DBEEID=DB::table('eemasters')
                    ->select('eeid')
                    ->where('name',$DBEE)
                    ->value('eeid');
                    // dd($DBEEID);
                    $DBDYE=$data->input('DyE_Name');
                    // dd($DBDYE);
                    $DBDYEID=DB::table('dyemasters')
                    ->select('dye_id')
                    ->where('name',$DBDYE)
                    ->value('dye_id');
// dd($DBDYEID);
                    $DBSO=$data->input('SO_Name');
                    // dd($DBSO);
                    $DBSOID=DB::table('jemasters')
                    ->select('jeid')
                    ->where('name',$DBSO)
                    ->value('jeid');
                    // dd($DBSOID);

                    $DBSDC=$data->input('SDC_Name');
                    // dd($DBSDC);
                    $DBSDCID=DB::table('sdcmasters')
                    ->select('SDC_id')
                    ->where('name',$DBSDC)
                    ->value('SDC_id');
                    // dd($DBSDCID);

                    $DBPO=$data->input('PO_Name');
                    // dd($DBPO);
                    $DBPOID=DB::table('jemasters')
                    ->select('jeid')
                    ->where('name',$DBPO)
                    ->value('jeid');
                    // dd($DBPOID);


                    $DBPA=$data->input('panm');
                    // dd($DBPA);
                    $DBPAID=DB::table('dyemasters')
                    ->select('dye_id')
                    ->where('name',$DBPA)
                    ->value('dye_id');
                    // dd($DBPAID);


                    $DBDAO_Name=$data->input('DAO_Name');
                    // dd($DBDAO_Name);
                    $DBDAOID=DB::table('daomasters')
                    ->select('DAO_id')
                    ->where('name',$DBDAO_Name)
                    ->value('DAO_id');
                    // dd($DBDAOID);
                    $DBAB=$data->input('AB_Name');
                    // dd($DBAB);
                    $DBABID=DB::table('abmasters')
                    ->select('AB_Id')
                    ->where('name',$DBAB)
                    ->value('AB_Id');
                    // dd($DBABID);


                    //Tender Id
                    $tenderid=$data->input('tenderid');

                     // Check if the work is completed
                    $workComp = $data->has('work_comp') ? 1 : 0;


                   // Update the workmaster record with the collected data
                  $result=DB::table('workmasters')->where('Work_Id' , $Work_Id)->update([
                        'Work_Id'=>$Work_Id,
                        'Div' => $div,
                        'Sub_Div'=>$subdiv,
                        'F_H_Code' => $fundhd,
                         'F_H_id'=>$FHCODEid,
                        'Work_Nm'=>$Work_Nm,
                        'Work_Nm_M'=>$Work_Nm_M,
                        'Work_Type'=>$workType,
                        'Work_Area'=>$workarea,
                        'SSR_Year'=>$SSR_year,
                        'Tnd_Amt'=>$Tnd_Amt,
                        'Agency_Nm'=>$agency,
                        'Agency_Id'=>$agencyId,
                        'AA_No'=>$AA_No,
                        'AA_Dt'=>$convertedAADate,
                        'AA_Amt'=>$AA_Amount,
                        'AA_Authority'=>$AA_Authority,
                        'TS_No'=>$TS_No,
                        'TS_Dt'=>$convertedTSDate,
                        'TS_Amt'=>$TS_Amount,
                        'TS_Authority'=>$TS_Authority,
                        'WO_No'=>$WO_No,
                        'Wo_Dt'=>$Wo_Dt,
                        'Above_Below' => $A_B_Pc,
                        'A_B_Pc' => $Above_Below,
                        'WO_Amt'=>$WO_Amt,
                        'Agree_No'=>$Agree_No,
                        'Agree_Dt'=>$Agree_Dt,
                        'abeffect'=>$a_b_effect,
                        'Period' => $Period,
                        'Perd_Unit'=>$Perd_Unit,
                        'Stip_Comp_Dt'=>$Stip_Comp_Dt,
                        // 'tm_lm_extension'=>$tm_lm_extension,
                        // 'rs_dt_comp'=>$rs_dt_comp,
                        'Tal'=>$data->Taluka,
                        'Village_ID'=>$DBvillageId,
                        'Ps_Consti' => $psId,
                        'Zp_Consti' =>$zpId,
                        'EE_id'=>$DBEEID,

                        'DYE_id'=>$DBDYEID,
                        'jeid'=>$DBSOID,
                        'SDC_id'=>$DBSDCID,
                        'PB_Id'=>$DBPOID,
                        'PA_Id'=>$DBPAID,
                        'DAO_Id'=>$DBDAOID,
                        'AB_Id'=>$DBABID,
                        'MP_Con_Id'=>$mpId,
                        'MLA_Con_Id'=>$mlaId,
                        'Tot_Exp'=>$data->Tot_Exp,
                        'work_comp'=>$data->$workComp,
                        'actual_complete_date'=>$data->actual_complete_date,
                        'Tender_Id' => $tenderid

                    ]);
                    
                    
                     //MB Issued data insert 

                    $divid=DB::table('divisions')->where('div' , $div)->value('div_id');
                    $dyenm=DB::table('dyemasters')->where('dye_id' , $DBDYEID)->value('name');
                    MBIssueDiv::insert([
                        'Div_Id'=>$divid,
                        'AAO_Id'=>$DBDAOID,
                        'EE_Id'=>$DBEEID,
                        'Dye_Id'=>$DBDYEID,
                        'Dye_Nm'=>$dyenm,
                        'MB_No'=>$Work_Id,
                        'Issue_Dt'=>$Wo_Dt,
                        'Pg_from'=>1
                    ]);
                    $subdivid=DB::table('subdivms')->where('Sub_Div' , $subdiv)->value('Sub_Div_Id');
                    $jenm=DB::table('jemasters')->where('jeid' , $DBSOID)->value('name');

                    MBIssueSO::insert([
                        'Div_Id'=>$divid,
                        'Sub_Div_Id'=>$subdivid,
                        'Dye_Id'=>$DBDYEID,
                        'JE_Id'=>$DBSOID,
                        'JE_Nm'=>$jenm,
                        'MB_No'=>$Work_Id,
                        'Issue_Dt'=>$Wo_Dt,
                        'Pg_from'=>1
                    ]);
                    
                    // dd($result);
                    Alert::success('Congrats', 'You\'ve Successfully Registered');
                    // return view('workmaster', compact('workData', 'FunCall'));
                    return redirect('listworkmasters')->with('success','Record Updated successfully.');
            
                }
                                catch (\Exception $e) {
                    Log::error('Error in Workmaster: ' . $e->getMessage());
                    // Redirect back with an error message
                       return redirect()->back()->with('error', 'An server side error occurred while processing Work Id Inserting Workmaster Page  '.$e->getMessage());

                }
            }


//  Display All records
public function index() 
{
try
{
     // Retrieve the authenticated user model
    $user = Auth::user(); // Retrieve the authenticated user model

     // Retrieve user ID with a default of 0 if not set
    $uid = $user->id??0;
//    dd($uid);
   
  // Retrieve user type with a default empty string if not set
    $usertype = $user->usertypes??'';
    //dd($usertype);
    $userid=null;
    switch($usertype)
    {

        case 'EE':
             // Fetch the EE-specific user ID and corresponding workmaster data
            $userid=DB::table('eemasters')->where('userid' , $uid)->value('eeid');
            $workmasterdata=DB::table('workmasters')
            ->where('EE_id' , $userid)
            ->whereNotNull('WO_No')
            ->where('WO_No', '!=', '')
            ->whereNotNull('Wo_Dt')
            ->where('Wo_Dt', '!=', '')
            ->whereNotNull('WO_Amt')
            ->where('WO_Amt', '!=', '')
            ->orderBy('workmasters.Wo_Dt', 'desc')->paginate(10);

            break;

         case 'DYE':
            // Fetch the DYE-specific user ID and corresponding workmaster data
            $userid=DB::table('dyemasters')->where('userid' , $uid)->value('dye_id');
             //dd($userid);
            $workmasterdata=DB::table('workmasters')->where('DYE_id' , $userid)
            ->whereNotNull('WO_No')
            ->where('WO_No', '!=', '')
            ->whereNotNull('Wo_Dt')
            ->where('Wo_Dt', '!=', '')
            ->whereNotNull('WO_Amt')
            ->where('WO_Amt', '!=', '')

            ->orderBy('workmasters.Wo_Dt', 'desc')->paginate(10);
            //dd($workmasterdata);
            break;   

        case 'PA':
            //dd($uid);
             // Fetch the PA-specific user ID and corresponding workmaster data
            $userid=DB::table('dyemasters')->where('userid' , $uid)->value('dye_id');
            $workmasterdata=DB::table('workmasters')->where('PA_Id' , $userid)
            ->whereNotNull('WO_No')
            ->where('WO_No', '!=', '')
            ->whereNotNull('Wo_Dt')
            ->where('Wo_Dt', '!=', '')
            ->whereNotNull('WO_Amt')
            ->where('WO_Amt', '!=', '')
            ->orderBy('workmasters.Wo_Dt', 'desc')->paginate(10);

            break;   

        case 'SO':
            $userid=DB::table('jemasters')->where('userid' , $uid)->value('jeid');
           // Fetch the SO-specific user ID and corresponding workmaster data
            $workmasterdata=DB::table('workmasters')->where('jeid' , $userid)
            ->whereNotNull('WO_No')
            ->where('WO_No', '!=', '')
            ->whereNotNull('Wo_Dt')
            ->where('Wo_Dt', '!=', '')
            ->whereNotNull('WO_Amt')
            ->where('WO_Amt', '!=', '')
            ->orderBy('workmasters.Wo_Dt', 'desc')->paginate(10);
            // $workmasterdata=DB::table('workmasters')->where('SO_Id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();


            //dd($workmasterdata);

            break;
            
        case 'PO':
            $userid=DB::table('jemasters')->where('userid' , $uid)->value('jeid');
             // Fetch the PO-specific user ID and corresponding workmaster data
            $workmasterdata=DB::table('workmasters')->where('PB_Id' , $userid)
            ->whereNotNull('WO_No')
            ->where('WO_No', '!=', '')
            ->whereNotNull('Wo_Dt')
            ->where('Wo_Dt', '!=', '')
            ->whereNotNull('WO_Amt')
            ->where('WO_Amt', '!=', '')

            ->orderBy('workmasters.Wo_Dt', 'desc')->paginate(10);
            //dd($workmasterdata);

            break; 
            
        case 'AAO':
            $userid=DB::table('daomasters')->where('userid' , $uid)->value('DAO_id');
            // Fetch the AAO-specific user ID and corresponding workmaster data
            $workmasterdata=DB::table('workmasters')->where('DAO_Id' , $userid)
            ->whereNotNull('WO_No')
            ->where('WO_No', '!=', '')
            ->whereNotNull('Wo_Dt')
            ->where('Wo_Dt', '!=', '')
            ->whereNotNull('WO_Amt')
            ->where('WO_Amt', '!=', '')

            ->orderBy('workmasters.Wo_Dt', 'desc')->paginate(10);
            //dd($workmasterdata);

            break;
            
        case 'audit':
               // Fetch the audit-specific user ID and corresponding workmaster data
            $userid=DB::table('abmasters')->where('userid' , $uid)->value('AB_Id');
            //dd($userid);
            $workmasterdata=DB::table('workmasters')->where('AB_Id' , $userid)
            ->whereNotNull('WO_No')
            ->where('WO_No', '!=', '')
            ->whereNotNull('Wo_Dt')
            ->where('Wo_Dt', '!=', '')
            ->whereNotNull('WO_Amt')
            ->where('WO_Amt', '!=', '')
            ->orderBy('workmasters.Wo_Dt', 'desc')->paginate(10);
            //dd($workmasterdata);

            break;
            
        case 'SDC':
             // Fetch the SDC-specific user ID and corresponding workmaster data
            $userid=DB::table('sdcmasters')->where('userid' , $uid)->value('SDC_id');
            //dd($userid);
            $workmasterdata=DB::table('workmasters')->where('SDC_id' , $userid)
            ->whereNotNull('WO_No')
            ->where('WO_No', '!=', '')
            ->whereNotNull('Wo_Dt')
            ->where('Wo_Dt', '!=', '')
            ->whereNotNull('WO_Amt')
            ->where('WO_Amt', '!=', '')
            ->orderBy('workmasters.Wo_Dt', 'desc')->paginate(10);
            //dd($workmasterdata);

            break;   


            case 'Agency':
                  // Fetch the Agency-specific user ID and corresponding workmaster data
                $userid=DB::table('agencies')->where('userid' , $uid)->value('id');
               // dd($userid);
                $workmasterdata=DB::table('workmasters')->where('Agency_Id' , $userid)
                    ->whereNotNull('WO_No')
                    ->where('WO_No', '!=', '')
                    ->whereNotNull('Wo_Dt')
                    ->where('Wo_Dt', '!=', '')
                    ->whereNotNull('WO_Amt')
                    ->where('WO_Amt', '!=', '')
                    ->orderBy('workmasters.Wo_Dt', 'desc')->paginate(10);
                //dd($workmasterdata);
    
                break;   
    

    }

    $users = $workmasterdata;

    $TotalworkIds = [];
    
    // Loop through the collection and push each 'Work_Id' into the array
    foreach ($users as $user) {
        $TotalworkIds[] = $user->Work_Id;
    }
    
    $NotAvailableDataTable = [];
    
    foreach ($TotalworkIds as $totworkids) {
        // Query the 'tnditems' table to check if the 'Work_Id' exists
        $tnditem = DB::table('tnditems')->where('work_Id', $totworkids)->value('work_Id');
        $itemcons=DB::table('itemcons')->where('Work_Id',$totworkids)->value('Work_Id');
        // dd($itemcons);
        // Check if the result is null, indicating the 'Work_Id' is not found
        if ($tnditem === null &&  $itemcons === null) 
        {
            $NotAvailableDataTable[] = $totworkids;
        }
    }
    
    // dd($NotAvailableDataTable);
    //     $usercode = $user->usercode??'';
//     $divid = $user->Div_id??0;
//     $subdivid = $user->Sub_Div_id??0;
// // dd($subdivid,$uid);
//     $DSFoundhd = DB::table('userperms')
//     ->select('F_H_CODE','Sub_Div_Id','Work_Id')
//     ->where('User_Id', '=',$uid)
//     ->where('Removed','=',1)
//      ->get();

//     // dd($DSFoundhd);
//     $UseUserPermission = json_decode($DSFoundhd);

    
//       //A.A. Count Query --------------------------------------------------------------
//       $queryAARegisterNewTotalCount = '';
//       $queryAARegisterPendingCount = '';
//       $queryTSRegisterNewTotalCount = '';
//       $queryTSRegisterPendingCount = '';


//       if($UseUserPermission){
//         //Get All Estimates
//         $queryAARegisterNewTotalCount = DB::table('workmasters')
//         ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id');
        

//                 $initCount = 0;
//                 foreach(json_decode($DSFoundhd) as $rsFound){ //User Permission
//                     $rsFound->F_H_CODE;
//                     $rsFound->Sub_Div_Id;
//                     $rsFound->Work_Id;
//                     $foundcount = strlen($rsFound->F_H_CODE);
//                     dd($rsFound->Sub_Div_Id);

//                 //echo "Step0"; exit;
//                 if(strtolower($rsFound->F_H_CODE) == 'all' && strtolower($rsFound->Sub_Div_Id) == 'all' && strtolower($rsFound->Work_Id) == 'all'){
//                     $queryAARegisterNewTotalCount = '';
//                     $queryAARegisterNewTotalCount = DB::table('workmasters')
//                     ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
//                     ->where('workmasters.Div_Id' ,'=',$divid)
//                     ->orderBy('workmasters.Wo_Dt', 'desc');
//                     $projectTotalAAProject =$queryAARegisterNewTotalCount->get();
//                     break;
//                 }else{
//                 // echo "Step2"; exit;
//                     // If work id
//                     if(strtolower($rsFound->Work_Id) != 'all' && isset($rsFound->Work_Id)){
//                         //Calculate Count
//                         $workIds = explode(',', $rsFound->Work_Id);

//                 $queryAARegisterNewTotalCount->where('workmasters.Div_Id', '=', $divid);

//                 foreach ($workIds as $workId) {
//                     if ($initCount == 0) {
//                         $queryAARegisterNewTotalCount->where('workmasters.Work_Id', '=', $workId)->orderBy('workmasters.Wo_Dt', 'desc');
//                     } else {
//                         $queryAARegisterNewTotalCount->orWhere('workmasters.Work_Id', '=', $workId)->orderBy('workmasters.Wo_Dt', 'desc');
//                     }
//                 }
//                     }else{

//                         if(strtolower($rsFound->F_H_CODE) != 'all' && isset($rsFound->F_H_CODE) && strlen($rsFound->F_H_CODE) >= 4){
//                             //Calculate Count
//                             if($initCount == 0){
                           
//                             $queryAARegisterNewTotalCount->where('workmasters.Div_Id', '=' ,$divid);
//                             $queryAARegisterNewTotalCount->where(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount))->orderBy('workmasters.Wo_Dt', 'desc');
//                             }else{
                           
//                             $queryAARegisterNewTotalCount->orWhere(DB::raw('left(`workmasters`.`F_H_Code`, '.$foundcount.')'),'=',substr($rsFound->F_H_CODE, 0, $foundcount))->orderBy('workmasters.Wo_Dt', 'desc');
//                             }

//                         }
//                         if(strtolower($rsFound->Sub_Div_Id) != 'all' && isset($rsFound->Sub_Div_Id)){
//                             //Calculate Count
                           
//                             $queryAARegisterNewTotalCount->Where('workmasters.Sub_Div_Id','=',$rsFound->Sub_Div_Id)->orderBy('workmasters.Wo_Dt', 'desc');
//                         }

//                     }

//                 }
               
//                 $projectTotalAAProject = $queryAARegisterNewTotalCount
//                 ->get();
//             $initCount++;
//             }
//     }else{
//             $projectTotalAAProject =DB::table('workmasters')
//                 ->leftJoin('subdivms', 'subdivms.Sub_Div_Id', '=', 'workmasters.Sub_Div_Id')
//                 ->orderBy('workmasters.Wo_Dt', 'desc')
//                 ->get();

                

//     }
   // dd($projectTotalAAProject);
    //$users = $projectTotalAAProject;
    //  dd($users);
     $perPage = 10;
    $page = request()->get('page', 1); // Get the current page number from the request
    
   // $users = \Illuminate\Support\Facades\DB::table('workmasters')->paginate($perPage, ['*'], 'page', $page);
    // dd($users);

                     return view('workmasterview',compact('users','NotAvailableDataTable'));
    
}

catch (\Exception $e) {
    Log::error('Error in Billlist: ' . $e->getMessage());

 // Redirect to the URL directly with an error message
 return redirect('listworkmasters')->with('error', 'An error occurred while processing the request in Workmaster Index Page.');
}

}



 //function find the workmaster 
                            
public function Funfindiworkmaster(Request $request)
{
    try
    {
         // Retrieve the input value for 'work_id_value' from the request
    $WorkIdInput = $request->input('work_id_value');
    //  dd($WorkIdInput);
       // Retrieve the authenticated user model
        $user = Auth::user(); // Retrieve the authenticated user model

         // Get user ID or default to 0 if not set
    $uid = $user->id??0;
//    dd($uid);
    // Get user type or default to an empty string if not set
    $usertype = $user->usertypes??'';
    //dd($usertype);
     // Initialize user ID variable
    $userid=null;

      // Switch based on user type to fetch relevant workmaster data
    switch($usertype)
    {

        case 'EE':
            $userid=DB::table('eemasters')->where('userid' , $uid)->value('eeid');
            $workmasterdata=DB::table('workmasters')->where('EE_id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();

            break;

         case 'DYE':
            $userid=DB::table('dyemasters')->where('userid' , $uid)->value('dye_id');
             //dd($userid);
            $workmasterdata=DB::table('workmasters')->where('DYE_id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();
            //dd($workmasterdata);
            break;   

        case 'PA':
            //dd($uid);
            $userid=DB::table('dyemasters')->where('userid' , $uid)->value('dye_id');
            $workmasterdata=DB::table('workmasters')->where('PA_Id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();

            break;   

        case 'SO':
            $userid=DB::table('jemasters')->where('userid' , $uid)->value('jeid');
            //dd($userid);
            $workmasterdata=DB::table('workmasters')->where('jeid' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();
            // $workmasterdata=DB::table('workmasters')->where('SO_Id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();


            //dd($workmasterdata);

            break;
            
        case 'PO':
            $userid=DB::table('jemasters')->where('userid' , $uid)->value('jeid');
            //dd($userid);
            $workmasterdata=DB::table('workmasters')->where('PB_Id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();
            //dd($workmasterdata);

            break; 
            
        case 'AAO':
            $userid=DB::table('daomasters')->where('userid' , $uid)->value('DAO_id');
            //dd($userid);
            $workmasterdata=DB::table('workmasters')->where('DAO_Id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();
            //dd($workmasterdata);

            break;
            
        case 'audit':
            $userid=DB::table('abmasters')->where('userid' , $uid)->value('AB_Id');
            //dd($userid);
            $workmasterdata=DB::table('workmasters')->where('AB_Id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();
            //dd($workmasterdata);

            break;
            
        case 'SDC':
            $userid=DB::table('sdcmasters')->where('userid' , $uid)->value('SDC_id');
            //dd($userid);
            $workmasterdata=DB::table('workmasters')->where('SDC_id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();
            //dd($workmasterdata);

            break;   


            case 'Agency':
                $userid=DB::table('agencies')->where('userid' , $uid)->value('id');
               // dd($userid);
                $workmasterdata=DB::table('workmasters')->where('Agency_Id' , $userid)->orderBy('workmasters.Wo_Dt', 'desc')->get();
                //dd($workmasterdata);
    
                break;   
    

    }
                   // Collect all 'Work_Id' from the retrieved workmaster data
                 $DSworkmaster = $workmasterdata;

                                    // Initialize an array to hold 'Work_Id's not available in certain tables
                                    $TotalworkIds = [];
    
                                    // Loop through the collection and push each 'Work_Id' into the array
                                    foreach ($DSworkmaster as $user) {
                                        $TotalworkIds[] = $user->Work_Id;
                                    }
                                    
                                    $NotAvailableDataTable = [];
                                    
                                    foreach ($TotalworkIds as $totworkids) 
                                    {
                                        // Query the 'tnditems' table to check if the 'Work_Id' exists
                                        $tnditem = DB::table('tnditems')->where('work_Id', $totworkids)->value('work_Id');
                                        $itemcons=DB::table('itemcons')->where('Work_Id',$totworkids)->value('Work_Id');
                                            // dd($itemcons);
                                            // Check if the result is null, indicating the 'Work_Id' is not found
                                            if ($tnditem === null &&  $itemcons=== null) 
                                            {
                                            $NotAvailableDataTable[] = $totworkids;
                                            }
                                    }
                                    
                                    // dd($NotAvailableDataTable);


                                    // Retrieve additional input values
                                    $fhcodevalue = $request->input('fhcode_value');
                                    $workIdValue = $request->input('work_id_value');
                                    $workNMValue = $request->input('work_NM_value');
                                    // dd($fhcodevalue,$workIdValue,$workNMValue);


                                  // Fetch additional datasets
                                $DSfundhdms = DB::table('fundhdms')
                                    ->select('Fund_Hd_M')->get();
                                    $DSsubdiv = DB::table('subdivms')
                                    ->select('Sub_Div')
                                    ->where('Sub_Div_Id', 1471)
                                    ->get();
                                  $DSagencies = DB::table('agencies')
                                    ->select('agency_nm')->get();
                            
                                       // Initialize query for filtering workmasters data
                                $query = DB::table('workmasters')
                                    ->select('Work_Id', 'Tender_Id', 'F_H_Code', 'Sub_Div', 'Agency_Nm','Work_Nm','Tnd_Amt','WO_No','Wo_Dt','A_B_Pc','WO_Amt','Stip_Comp_Dt','Tot_Exp','actual_complete_date');
                            

                                      // Apply filters based on request inputs
                                if ($request->has('work_id_checkbox') && $request->filled('work_id_value')) 
                                {
                                    $workIdValue = $request->input('work_id_value');
                                    // dd($workIdValue);
                                    $query->where('Work_Id', 'LIKE', '%' . $workIdValue . '%');
                                
                                }
                                if ($request->has('fhcode_checkbox') && $request->input('fhcode_value')) 
                                {
                                    $fhcodevalue = $request->input('fhcode_value');
                                    // dd($fhcodevalue);
                                    $F_H_CODEID=DB::table('fundhdms')->where('Fund_Hd_M',$fhcodevalue)->value('F_H_CODE');
                                    // dd($fhcodevalue,$F_H_CODEID);
                                        $query->where('F_H_Code', $F_H_CODEID);
                                }  
                                if($request->has('subdiv_checkbox')&& $request->input('subdiv_value'))
                                {
                                    $subdivvalue=$request->input('subdiv_value');
                                    $query->where('Sub_Div',$subdivvalue);
                                }
                                if($request->has('agency_checkbox')&& $request->input('agency_value'))
                                {
                                    $agencyvalue=$request->input('agency_value');
                                    $query->where('Agency_Nm',$agencyvalue);
                                }
                                   // Check if work_NM_checkbox is checked and work_NM_value is provided
                                if ($request->has('work_NM_checkbox') && $request->input('work_NM_value')) 
                                {
                                    $workNMValue = $request->input('work_NM_value');
                                    // dd($workNMValue);
                                    $query->where('Work_Nm', 'LIKE',  $workNMValue . '%');
                                }
                                
                                  // Check if Tender_Id checkbox is checked and Tender_Id value is provided
        if ($request->has('tender_id_checkbox') && $request->input('tender_id_value')) {
            $TenderIdValue = $request->input('tender_id_value');
            $query->where('Tender_Id', 'LIKE', '%' . $TenderIdValue . '%');
        }

                                // Retrieve filtered workmasters data
                                $filteredData = $query->get();

                                 // Return view with the data
                                return view('viewfindworkmaster', compact('DSworkmaster','NotAvailableDataTable', 'DSfundhdms', 'DSsubdiv', 'DSagencies', 'filteredData'));

                            }
                                                        catch (\Exception $e) {
                                // Log the error with detailed information
                                Log::error('Error in Funfindiworkmaster: ' . $e->getMessage(), [
                                    'trace' => $e->getTraceAsString(),
                                    'line' => $e->getLine(),
                                    'file' => $e->getFile()
                                ]);
                        
                                // Return a response with a custom error view
                                return redirect()->back()->with([
                                    'error' => 'An error occurred while processing your request.',
                                    'error_file' => $e->getFile(),
                                    'error_line' => $e->getLine()
                                ]);
                            }
                        }




                            

                         //function to find workid
                            public function FunworkidFind(Request $request)
                            {
                                  // Retrieve input values from the request
                                $WorkIdInput = $request->input('work_id_value');
                                $workNameInput = $request->input('work_NM_value');
                                // dd($workNameInput);
                                $fhcodeValue = $request->input('fhcode_value');
                                $subdivValue = $request->input('subdiv_value');
                                $agencyValue = $request->input('agency_value');
                                // dd($fhcodeValue,$subdivValue,$agencyValue);

                                 // Initialize query for filtering workmasters data
                            $query = DB::table('workmasters')
                            ->select('Work_Id', 'F_H_Code', 'Sub_Div', 'Agency_Nm', 'Work_Nm', 'Tnd_Amt', 'WO_No', 'Wo_Dt', 'A_B_Pc', 'WO_Amt', 'Stip_Comp_Dt', 'Tot_Exp', 'actual_complete_date');
                    
                        // Adjust the query based on the length of the input
                        if (strlen($WorkIdInput) >= 3) 
                        {
                            $query->where('Work_Id', 'LIKE', '%' . $WorkIdInput . '%');
                        }

                        if (strlen($workNameInput) > 0) 
                        {
                            $query->where('Work_Nm', 'LIKE',  $workNameInput . '%');
                            // dd($query);
                        }
                        if ($fhcodeValue) 
                        {
                            $F_H_CODEID = DB::table('fundhdms')->where('Fund_Hd_M', $fhcodeValue)->value('F_H_CODE');
                            $query->where('F_H_Code', $F_H_CODEID);
                        }
                    
                        if ($subdivValue) 
                        {
                            $query->where('Sub_Div', $subdivValue);
                        }
                    
                        if ($agencyValue) 
                        {
                            $query->where('Agency_Nm', $agencyValue);
                        }
                    
                    
                        $workidgetdata = $query->get();
                        // dd($workidgetdata);
                        $TotalworkIds = [];
    
                        // Loop through the collection and push each 'Work_Id' into the array
                        foreach ($workidgetdata as $user) 
                        {
                            $TotalworkIds[] = $user->Work_Id;
                        }
                        
                        $NotAvailableDataTable = [];
                        
                        foreach ($TotalworkIds as $totworkids) 
                        {
                            // Query the 'tnditems' table to check if the 'Work_Id' exists
                            $tnditem = DB::table('tnditems')->where('work_Id', $totworkids)->value('work_Id');
                            $itemcons=DB::table('itemcons')->where('Work_Id',$totworkids)->value('Work_Id');
                                // dd($itemcons);
                                // Check if the result is null, indicating the 'Work_Id' is not found
                                if ($tnditem === null &&  $itemcons=== null) 
                                {
                                $NotAvailableDataTable[] = $totworkids;
                                }
                        }
                        
                        // dd($NotAvailableDataTable);

                                // dd($workidgetdata);
                                return response()->json([
                                    'workidgetdata' => $workidgetdata,
                                    'NotAvailableDataTable' =>$NotAvailableDataTable,
                                ]);

                      }
                            





//insert Excelfile sheet1 and sheet4 in temptnditem table on import button
                            public function uploadExcel(Request $request)
                            {
                                // dd($request);
                                // dd("ok");
                                $excelHelper = new AllExcelsheet();
                                $excelHelper->Excelsheet1($request);
                                // dd($excelHelper->Excelsheet1($request));
                                $excelHelper->Excelsheet4($request);

                            }


//insert Excel sheet2 sheet1 sheet4 in tempsheet2excels table at button new click

                            public function funnewbtnajaxsheet2read(Request $request)
                            {

                                //  dd($request);
                                $workid = $request->input('workid');
                                // dd($request,$workid);                                
                                
                                //class object  create the  to read excel sheet
                                $excelHelper = new AllExcelsheet();

                                //condition wise excelsheet read
                                if (isset($workid) && !empty($workid) && $workid !== "null") 
                                {
                                    // dd($workid);
                                    $excel2=$excelHelper->Excelsheet2($request,$workid);
                                    $excel1=$excelHelper->Excelsheet1($request);
                                    $excel4=$excelHelper->Excelsheet4($request);
                                    // dd($excel2,$excel1);
                                    // dd($excel2);
                                    // dd($excel4);


                                } 
                                elseif($workid === "null")
                                {
                                    // dd($workid);
                                   $excel2=$excelHelper->Excelsheet2($request,$workid);
                                    $excel1=$excelHelper->Excelsheet1($request);
                                    $excel4=$excelHelper->Excelsheet4($request);
                                    // dd($excel2);
                                    // dd($excel1);
                                    //dd($excel4);
                                   
                                }
                  
                                return response()->json(['excel2' => $excel2 ,'excel1' => $excel1 ,'excel4' => $excel4]);      
                                
                                //dd($excelHelper);
                }
// insert data in table tempsheet2excels from excel sheet2
//show indivisual record
     public function show(Request $request,$Work_Id)
     {
        // Retrieve workmaster data based on the provided Work_Id
        $DSWorkmaster=DB::table('workmasters')->where('Work_Id' ,$Work_Id)->first();

        // Check if the workmaster data exists and format date fields
        if ($DSWorkmaster) 
        {
            $DSWorkmaster->AA_Dt = date('d/m/Y', strtotime($DSWorkmaster->AA_Dt));
            $DSWorkmaster->TS_Dt = date('d/m/Y', strtotime($DSWorkmaster->TS_Dt));
            // dd($workData);
        } 

         // Retrieve related items from the tnditems table and paginate the results
        $DStnditems=DB::table('tnditems')
        ->select('t_item_no','sub_no','item_desc','tnd_qty','item_unit','tnd_rt','t_item_amt')
        ->where('work_Id' ,$Work_Id)
        // ->get();
        ->paginate(10);
        // dd($DStnditems);
        
         // Format the item amounts in Indian Rupees using a helper class
           $convert=new CommonHelper;

        foreach($DStnditems as $item)
        {
            $item->t_item_amt=$convert->formatIndianRupees($item->t_item_amt);
        }
         // Check if the request is AJAX
  // Check if the request is AJAX
  // Check if the request is an AJAX request
  if ($request->ajax()) {
        // Return paginated items and pagination links as JSON
    return response()->json([
        'data' => $DStnditems->items(), // Return only the paginated items
        'links' => $DStnditems->links('pagination::bootstrap-4')->toHtml() // Return pagination links HTML
    ]);
}

 // Retrieve various IDs related to the Work_Id from the workmasters table
$DBVIllageID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('Village_ID');
        // DD($DBVIllageID);
        $DBVIllagename=DB::table('villagemasters')
        ->select('Village')
        ->where('Village_Id',$DBVIllageID)
        ->first();
        // dd($DBVIllagename);
        $DBEEID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('EE_id');

        // dd($DBEEID);
        $DBEENAME=DB::table('eemasters')
        ->select('name')
        ->where('eeid' ,$DBEEID)
        ->value('name');
        // dd($DBEENAME);


        $DBDYEID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('DYE_id');

        // dd($DBEEID);
        $DBDYENAME=DB::table('dyemasters')
        ->select('name')
        ->where('dye_id' ,$DBDYEID)
        ->value('name');
        // dd($DBDYENAME);


        $DBSOID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('jeid');
        // dd($DBSOID);
        $DBSONAME=DB::table('jemasters')
        ->select('name')
        ->where('jeid' ,$DBSOID)
        ->value('name');
        // dd($DBSONAME);

        $DBSDCID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('SDC_id');
        $DBSDCNAME=DB::table('sdcmasters')
        ->select('name')
        ->where('SDC_id' ,$DBSDCID)
        ->value('name');
        // dd($DBSDCNAME);


        $DBPOID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('PB_Id');
        $DBPONAME=DB::table('jemasters')
        ->select('name')
        ->where('jeid' ,$DBPOID)
        ->value('name');
        // dd($DBPONAME);


        $DBPAID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('PA_Id');
        // dd($DBPAID);
        $DBPANAME=DB::table('dyemasters')
        ->select('name')
        ->where('dye_id' ,$DBPAID)
        ->value('name');
        // dd($DBPANAME);




        $DBDAOID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('DAO_Id');
        $DBDAONAME=DB::table('daomasters')
        ->select('name')
        ->where('DAO_id' ,$DBDAOID)
        ->value('name');
        // dd($DBDAONAME);

        $DBABID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('AB_Id');
        $DBABNAME=DB::table('abmasters')
        ->select('name')
        ->where('AB_Id' ,$DBABID)
        ->value('name');
        // dd($DBABNAME);

        $DBMPID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('MP_Con_Id');
        // dd($DBMPID);
        $DBMPNAME=DB::table('mpconsts')
        ->select('MP_Con')
        ->where('MP_Con_Id' ,$DBMPID)
        ->value('MP_Con');
        // dd($DBMPNAME);


        $DBMLAID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('MLA_Con_Id');
// dd($DBMLAID);
        $DBMLANAME=DB::table('mlaconsts')
        ->select('MLA_Con')
        ->where('MLA_Con_Id' ,$DBMLAID)
        ->value('MLA_Con');
        // dd($DBMLANAME);

        $DBps_constID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('Ps_Consti');
        // dd($DBps_constID);
        $DBPSCONNAME=DB::table('psconsts')
        ->select('PS_Con')
        ->where('PS_Con_Id' ,$DBps_constID)
        ->value('PS_Con');
        // dd($DBPSCONNAME);

        $DBZPConstID=DB::table('workmasters')
        ->where('Work_Id' ,$Work_Id)
        ->value('Zp_Consti');
        // dd($DBZPConstID);
        $DBZPCONNAME=DB::table('zpconsts')
        ->select('ZP_Con')
        ->where('ZP_Con_Id' ,$DBZPConstID)
        ->value('ZP_Con');
        // dd($DBZPCONNAME);

         // Return the view with all retrieved data
        return view('showworkmaster', compact('DSWorkmaster', 'DStnditems','DBVIllagename',
        'DBEENAME','DBDYENAME','DBSONAME','DBSDCNAME','DBPONAME','DBPANAME','DBDAONAME','DBABNAME',
        'DBMPNAME','DBMLANAME' ,'DBPSCONNAME','DBZPCONNAME'));
     }

     //get villages
     public function getVillages(Request $request)
     {
        // dd('ok');
         // Get the selected taluka from the request
         $selectedTaluka = $request->input('taluka');
        //  dd($Tal);
         $taluid=DB::table('talms')
         ->select('Tal_Id')
         ->where('Tal',$selectedTaluka)
         ->value('Tal_Id');
        //  dd($taluid);
 
         // Query the VillageMaster table to retrieve villages for the selected taluka
         $villages = DB::table('villagemasters')
         ->select('Village')
         ->where('Tal_Id', $taluid)
         ->get();
        //  dd($villages);
 
         // Return the villages as JSON response
         return response()->json($villages);
     }

     // WorkMasterController.php
public function getRelatedIdsByVillage(Request $request)
{
    // dd('ok');
    // Get the selected village from the request
    $selectedVillage = $request->input('village');
// dd($selectedVillage);
$villageId=DB::table('villagemasters')
->select('Village_Id')

->where('Village',$selectedVillage)
->value('Village_Id');
// dd($villageId);
    // Query your database to fetch related IDs based on the selected village
    $GetallId = DB::table('villagemasters')
    ->select('Village', 'MLA_Con_Id', 'MP_Con_Id', 'PS_con_id', 'ZP_Con_Id')
    ->where('Village_Id', $villageId)
    ->get();

      // Extract related IDs from the retrieved data
foreach ($GetallId as $item) {
    $village = $item->Village;
    $mlaConId = $item->MLA_Con_Id;
    $mpConId = $item->MP_Con_Id;
    $psConId = $item->PS_con_id;
    $zpConId = $item->ZP_Con_Id;

    // Use these variables as needed within the loop
}
  // Retrieve names associated with the extracted IDs from various tables
$DBMLAname=DB::table('mlaconsts')
->select('MLA_Con')
->where('MLA_Con_Id',$mlaConId)
->value('MLA_Con');
// dd($DBMLAname);

$DBMPname=DB::table('mpconsts')
->select('MP_Con')
->where('MP_Con_Id',$mpConId)
->value('MP_Con');
// dd($DBMLAname,$DBMPname);

$DBpsname=DB::table('psconsts')
->select('PS_Con')
->where('PS_Con_Id',$psConId)
->value('PS_Con');
// dd($DBpsname);

$DBZPname=DB::table('zpconsts')
->select('ZP_Con')
->where('ZP_Con_Id',$zpConId)
->value('ZP_Con');
// dd($DBZPname);

   $subdivision = $request->sub_div;

$subdivid=DB::table('subdivms')->where('Sub_Div' , $subdivision)->first();

$dye=DB::table('dyemasters')->where('subdiv_id' , $subdivid->Sub_Div_Id)->value('name');

$sdc=DB::table('sdcmasters')->where('subdiv_id' , $subdivid->Sub_Div_Id)->value('name');

$EE=DB::table('eemasters')->where('divid' , $subdivid->Div_Id)->value('name');

$AAO=DB::table('daomasters')->where('div_id' , $subdivid->Div_Id)->value('name');

//dd($subdivid);


    // Return the data as a JSON response
    return response()->json([
        'DBMLAname' => $DBMLAname,
        'DBMPname' => $DBMPname,
        'DBpsname' => $DBpsname,
        'DBZPname' => $DBZPname,
        'dyenm'    => $dye,
        'sdcnm'    => $sdc,
        'eenm'    => $EE,
        'aaonm'    => $AAO,
    ]);
    }


    //MP selected
    public function getMLAByMP(Request $request)
    {
        // Get the selected MP from the request input
        $selectedMP = $request->input('mp');
        // Retrieve the MP_Con_Id for the selected MP from the 'mpconsts' table
        $slectedMPID=DB::table('mpconsts')
        ->select('MP_Con_Id')
        ->where('MP_Con',$selectedMP)
        ->value('MP_Con_Id');
         // Retrieve associated MLA data based on the MP_Con_Id
        $displaymla=DB::table('mlaconsts')
        ->select('MLA_Con','MP_Con_Id')
        ->where('MP_Con_Id',$slectedMPID)
        ->get('MLA_Con');
        // dd($displaymla);
        // Query your database to fetch related MLA data based on the selected MP
        return response()->json($displaymla);
    }
    
//agency find
public function FunagencyNameSearch(Request $request)
{
    $agencyName = $request->input('agencyName');
      // Search for agencies where the agency name starts with the provided input
    $DBagencyname = DB::table('agencies')
        ->where('agency_nm', 'like', $agencyName . '%')
        ->get();
        //return agency
    return response()->json([
        'DBagencyname' => $DBagencyname,
    ]);
}

//place name search
public function FunplaceNameSearch(Request $request)
{ // Get the place name from the request input
    $placeName = $request->input('placeName');
    // Search for agencies where the place name starts with the provided input
    $DBplace = DB::table('agencies')
    ->where('Agency_Pl', 'like', $placeName . '%')
    ->get();

    // Return the matching places as a JSON response
    return response()->json([
        'DBplace' => $DBplace,
    ]);
}

//contact search
public function FuncontactSearch(Request $request)
{
     // Get the contact person name from the request input
    $contact = $request->input('contact');


    // Search for agencies where the contact person name starts with the provided input
    $DBcontact = DB::table('agencies')
        ->where('Contact_Person1', 'like', $contact . '%')
        ->get();
    return response()->json([
        'DBcontact' => $DBcontact,
    ]);
}

//pan card search
public function FunpanSearch(Request $request)
{
      // Get the PAN card number from the request input
    $pan=$request->input('pancard');

       // Search for agencies where the PAN number starts with the provided input
    $DBPan = DB::table('agencies')
    ->where('Pan_no', 'like', $pan . '%')
    ->get();

return response()->json([
    'DBPan' => $DBPan,
]);
}

//Agency name list open
public function FunAgencynameResult(Request $request)
{
   //get request inputs
    $agencyName = $request->input('agencyName');
    // dd($agencyName);
    $agencyCheckbox = $request->has('agencyCheckbox') && $request->filled('agencyName');

    $placeName = $request->input('placeName');
    $placeCheckbox = $request->has('placeCheckbox') && $request->filled('placeName');

    $contact = $request->input('contact');
    $contactCheckbox = $request->has('contactCheckbox') && $request->filled('contact');

    $pancard = $request->input('pancard');
    $pancardCheckbox = $request->has('pancardCheckbox') && $request->filled('pancard');
    // dd($agencyName,$placeName,$contact,$pancard);

    //agency list
    $query = DB::table('agencies')
        ->select('agency_nm', 'Agency_Pl', 'Contact_Person1', 'Pan_no');


        //check condition matches
    if ($agencyCheckbox) {
        $query->where('agency_nm', $agencyName);
    }

    if ($placeCheckbox) {
        $query->where('Agency_Pl', $placeName);
    }

    if ($contactCheckbox) {
        $query->where('Contact_Person1', $contact);
    }

    if ($pancardCheckbox) {
        $query->where('Pan_no', $pancard);
    }

    $data = $query->get();

    // dd($data);
    // Return the data as JSON
    return response()->json($data);
}



 //Edit workmaster data
        public function editworkmaster(Request $request,$Work_Id)
        {
            try
            {
                
             // Retrieve the division ID
            $divisionId = PublicDivisionId::DIVISION_ID;
            // dd($divisionId);
            $DivisionOffer = $divisionId ."0";
            // dd($divisionId,$DivisionOffer);

                   // Fetch the workmaster details for the given Work_Id
            $DSWorkmaster=DB::table('workmasters')
            ->where('Work_Id' ,$Work_Id)
            ->first();
            // dd($DSWorkmaster);
            // dd($DSWorkmaster->Stip_Comp_Dt)
            // dd($DSWorkmaster->Perd_Unit)
                        // dd($DSWorkmaster->Div_Id);
                        // dd($DSWorkmaster->Sub_Div_Id);


            // if ($DSWorkmaster) 
            // {
            //     $DSWorkmaster->AA_Dt = date('d/m/Y', strtotime($DSWorkmaster->AA_Dt));
                // $DSWorkmaster->TS_Dt = date('d/m/Y', strtotime($DSWorkmaster->TS_Dt));
            //     // dd($workData);
            //     $DSWorkmaster->Agree_Dt = null;

            // } 
            // dd($DSWorkmaster->TS_Dt);

            // Set Agree_Dt to null if it's not set
            $DSWorkmaster->Agree_Dt = $DSWorkmaster->Agree_Dt !== null ? $DSWorkmaster->Agree_Dt : null;
            // dd($DSWorkmaster->Agree_Dt);


          // Fetch divisions and subdivisions related to the workmaster
            $divisions = DB::table('divisions')
            ->select('div')
            ->where('div', 'Zilla Parishad, Sangli')
            ->get();

              // List of all subdivisions for the given division
            $Subdivisions = DB::table('subdivms')
            ->select('Sub_Div')
            ->where('Sub_Div_Id',$DSWorkmaster->Sub_Div_Id)
            ->where('Sub_Div' ,$DSWorkmaster->Sub_Div)
            ->get();
            // dd($Subdivisions);
            $Subdivisions = DB::table('subdivms')
            ->where('Sub_Div_Id',$DSWorkmaster->Sub_Div_Id)
            ->where('Sub_Div' ,$DSWorkmaster->Sub_Div)
            ->value('Sub_Div');
            // dd($Subdivisions);    

            $Subdivisionslist = DB::table('subdivms')
            ->select('Sub_Div')
            ->where('Div_Id', $divisionId)
            ->get();
            // dd($Subdivisionslist);

            // Fetch agency details related to the workmaster
            $DBagencies = DB::table('agencies')
            ->select('agency_nm')
            ->where('id', $DSWorkmaster->Agency_Id)
            ->get();
            
              // List of all agencies
          $DBagengieslist=DB::table('agencies')
            ->select('agency_nm')
            ->get();

           // Fetch taluka and village details
            $DBTal = DB::table('workmasters')
            ->select('Tal')
            ->where('Work_Id', $Work_Id)
            ->get();
            // dd($DBTal);
            $DBTalukalist=DB::table('talms')
            ->select('Tal')
            ->where('Dist_Id',2735)
            ->get();

            $DBvillId = DB::table('workmasters')
            ->select('Village_ID')
            ->where('Work_Id', $Work_Id)
            ->get();
            // dd($DBvillId);
            $villageIds = [];

            foreach ($DBvillId as $record) 
            {
                $villageIds[] = $record->Village_ID; // Extract Village_Id values
            }
            
$DBtalid=DB::table('workmasters')
->select('Tal_Id')
->where('Work_Id', $Work_Id)
->get();
// dd($DBtalid);

$talIds = $DBtalid->pluck('Tal_Id')->toArray();

 // Fetch village names based on Tal_Id
$DBtalidinVillageTable = DB::table('villagemasters')
    ->select('Village')
    ->whereIn('Tal_Id', $talIds) // Use whereIn with the array of Tal_Id values
    ->get();

// dd($DBtalidinVillageTable);

            $dbvillagename=DB::table('villagemasters')
            ->select('Village')
            ->where('Village_Id',$villageIds)
            ->get();
            // dd($dbvillagename);

            
            $dbVillagelis=DB::table('villagemasters')
            ->select('Village')
            ->where('Tal_Id',$DSWorkmaster->Tal_Id)
            ->get();
// dd($dbVillagelis);




          // Fetch political constituency details
            $DBPS = DB::table('workmasters')
            ->select('Ps_Consti')
            ->where('Work_Id', $Work_Id)
            ->get();
            // dd($DBPS);

            $psConstiIds = $DBPS->pluck('Ps_Consti')->toArray();

            $psName = DB::table('psconsts')
                ->select('PS_Con')
                ->whereIn('PS_Con_Id', $psConstiIds) // Use whereIn with the array of Ps_Consti values
                ->get();         
            // dd($psName);
            $DBpsconstlist=DB::table('psconsts')
            ->select('PS_Con')
            ->get();
            // DD($DBpsconstlist);

            $DBzp = DB::table('workmasters')
            ->select('Zp_Consti')
            ->where('Work_Id', $Work_Id)
            ->get();
            // dd($DBzp);
            $zpConstiIds = $DBzp->pluck('Zp_Consti')->toArray();

            $DBZPname = DB::table('zpconsts')
                ->select('ZP_Con')
                ->whereIn('ZP_Con_Id', $zpConstiIds) // Use whereIn with the array of Zp_Consti values
                ->get();
            
            // dd($DBZPname);

            $DBZPconstlist=DB::table('zpconsts')
            ->select('ZP_Con')
            ->get();

              // Fetch various IDs and names related to the workmaster
            $DBEE = DB::table('workmasters')
            ->select('EE_id')
            ->where('Work_Id', $Work_Id)
            ->value('EE_id');
            // dd($DBEE);

            $PreviousselectedEE=DB::table('eemasters')
            ->where('eeid', $DBEE)
            ->value('name');
            // DD($PreviousselectedEE);

            $DBEElist=DB::table('eemasters')
            ->select('name')
            ->where('divid',$DSWorkmaster->Div_Id)
            ->get();
            // DD($DBEElist);

            $DBDYE = DB::table('workmasters')
            ->select('DYE_id')
            ->where('Work_Id', $Work_Id)
            ->value('DYE_id');
            // dd($DBDYE);

            $preselectDYE=DB::table('dyemasters')
            ->select('name')
            ->where('dye_id',$DBDYE)
            ->value('name');
            // dd($preselectDYE);
            $DBDYElist=DB::table('dyemasters')
            ->select('name')
            ->where('div_id',$DSWorkmaster->Div_Id)
            ->where('subdiv_id',$DSWorkmaster->Sub_Div_Id)
            ->get();
            // DD($DBDYElist);


            $DBSO = DB::table('workmasters')
            ->select('jeid')
            ->where('Work_Id', $Work_Id)
            ->value('jeid');
            // dd($DBSO);

            $PreviousseleSO = DB::table('jemasters')
            ->where('jeid', $DBSO)
            ->value('name');
        // dd($PreviousseleSO);
            $DBSOlist=DB::table('jemasters')
            ->select('name')
            ->where('div_id',$DSWorkmaster->Div_Id)
            ->where('subdiv_id',$DSWorkmaster->Sub_Div_Id)
            ->get();
            // DD($DBSOlist);


            $DBSDC = DB::table('workmasters')
            ->select('SDC_id')
            ->where('Work_Id', $Work_Id)
            ->value('SDC_id');
            // dd($DBSDC);
            $PreviousSDC=DB::table('sdcmasters')
            ->where('SDC_id',$DBSDC)
            ->value('name');
// dd($PreviousSDC);
            $DBSDClist=DB::table('sdcmasters')
            ->select('name')
            ->where('div_id',$DSWorkmaster->Div_Id)
            ->where('subdiv_id',$DSWorkmaster->Sub_Div_Id)
            ->get();
            // DD($DBSDClist);
            $DBPO = DB::table('workmasters')
            ->select('PB_Id')
            ->where('Work_Id', $Work_Id)
            ->value('PB_Id');
            // dd($DBPO);

            $previousPOselected=DB::table('jemasters')
            ->where('jeid',$DBPO)
            ->value('name');
            // dd($previousPOselected);

            $DBPOlist=DB::table('jemasters')
            ->select('name')
            ->where('subdiv_id',$DivisionOffer)
            ->get();
            // dd($DBPOlist);

            $DBPAID = DB::table('workmasters')
            ->select('PA_Id')
            ->where('Work_Id', $Work_Id)
            ->value('PA_Id');
            // dd($DBPAID);

            $PApreviousselected=DB::table('dyemasters')
            ->where('dye_id',$DBPAID)
            ->value('name');
            // dd($PApreviousselected);
            $DBPAlist=DB::table('dyemasters')
            ->select('name')
            ->where('subdiv_id',$DivisionOffer)
            ->get();
        // dd($DBPAlist);



            // DD($DBPOlist);
            $DBDAO = DB::table('workmasters')
            ->select('DAO_Id')
            ->where('Work_Id', $Work_Id)
            ->value('DAO_Id');
// dd($DBDAO);
            $DAOprevious = DB::table('daomasters')
            ->where('DAO_id', $DBDAO)
            ->value('name');
        // dd($DAOprevious);

        $DBDAOlist = DB::table('daomasters')
            ->select('name')
            ->where('div_id',$DSWorkmaster->Div_Id)
            ->get();
        // dd($DBDAOlist);




        $DBAB = DB::table('workmasters')
        ->select('AB_Id')
        ->where('Work_Id', $Work_Id)
        ->value('AB_Id');
    // dd($DBAB);
    $Previous_AB = DB::table('abmasters')
        ->where('AB_Id',$DBAB)
        ->value('name');
    // dd($Previous_AB);


    $DBABlist = DB::table('abmasters')
        ->select('name')
        ->where('div_id',$DSWorkmaster->Div_Id)
        ->get();
    // dd($DBABlist);

    $DBMP = DB::table('workmasters')
    ->select('MP_Con_Id')
    ->where('Work_Id', $Work_Id)
    ->get();
// dd($DBMP);
$DBMPlist = DB::table('mpconsts')
    ->select('MP_Con')
    ->get();
// dd($DBMPlist);


$DBMLA = DB::table('workmasters')
->select('MLA_Con_Id')
->where('Work_Id', $Work_Id)
->get();
// dd($DBMLA);
$DBMLAlist = DB::table('mlaconsts')
->select('MLA_Con')
->get();
// dd($DBMLAlist);



           // Fetch items related to the workmaster

            $DStnditems=DB::table('tnditems')
            ->select('t_item_id','t_item_no','sub_no','item_desc','tnd_qty','item_unit','tnd_rt','t_item_amt')
            ->where('work_Id' ,$Work_Id)
            ->paginate(10);
            // ->get();
            // dd($DStnditems);
                        // Formatting monetary values
                        $convert=new CommonHelper;

            foreach($DStnditems as $item)
            {
                $item->t_item_amt=$convert->formatIndianRupees($item->t_item_amt);
            }

            
                    if ($request->ajax()) {
        return response()->json([
            'data' => $DStnditems->items(), // Return only the paginated items
            'links' => $DStnditems->links('pagination::bootstrap-4')->toHtml() // Return pagination links HTML
        ]);
        }

            
            
            $DBagencies = DB::table('workmasters')
            ->select('Agency_Nm')
            ->where('Work_Id', $Work_Id)
            ->where('Div_Id', $divisionId)
            ->get();
        

            // Pass data to the view
            return view('updateworkmaster', compact('DSWorkmaster', 'divisions' ,
            'DBagencies','Subdivisions','Subdivisionslist','DStnditems','DBagencies' ,'DBagengieslist',
            'DBTal','DBTalukalist','DBPS','DBpsconstlist','DBzp','DBZPconstlist',
            'PreviousselectedEE','DBEE','DBEElist',
            'preselectDYE','DBDYE','DBDYElist',
            'PreviousseleSO','DBSO','DBSOlist',
            'PreviousSDC','DBSDC','DBSDClist',
            'previousPOselected','DBPO','DBPOlist',
            'PApreviousselected','DBPAID','DBPAlist',
            'DAOprevious','DBDAO','DBDAOlist',
            'Previous_AB','DBAB','DBABlist',
            'DBMP','DBMPlist',
            'DBMLA','DBMLAlist',
            'dbvillagename','dbVillagelis',
            'DBtalidinVillageTable',
            'DBZPname',
            'psName',
            
            // 'DBPAname'

        ));
            //    $user=Workmaster::where('Work_Id' ,$Work_Id)->first();
    // //    dd($user);
    //    // return $user;
    //    return view('updateworkmaster',['user'=>$user]);
    }
    //update Record
    
        catch (\Exception $e) {
        // Log the error with detailed information
        Log::error('Error in Funfindiworkmaster: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
        $error_file = basename($e->getFile());
 // Return a response with a custom error view and session data
 return response()->view('errors.custom', [
    'error' => 'An error occurred while processing the' . $e->getMessage(),
    'error_file' => $error_file,
    'error_line' => $e->getLine(),
    'error_trace' => $e->getTraceAsString()
], 500); 

}
}

    
    
     //update the workmaster data
    public function funupdateworkmaster(Request $request)
    {
        try
        {
           // Retrieve required inputs from the request
           $Div = $request->Div;
        //    dd($Div);
           $Work_Id = $request->work_id;
        //    dd($Work_Id);

            $Sub_Div = $request->Sub_Div;
            // dd($Sub_Div);
            $subdivid=DB::table('subdivms')
            ->where('Sub_Div',$Sub_Div)
            ->value('Sub_Div_Id');
            // dd($subdivid);
           $F_H = $request->FH_code;
           $Work_Nm=$request->Work_Nm;
        // Retrieve additional work details from the request
           $Work_Nm_M = $request->Work_Nm_M;
           $worktype=$request->workType;
           $workarea=$request->workarea;
           $SSR_year = $request->SSR_year;
           $request->Tnd_Amt = str_replace(',', '', $request->Tnd_Amt);
           $Tnd_Amt = $request->Tnd_Amt;

           $Agency_Nm = $request->agency;
        //    DD($Agency_Nm);
           $DDAgencyId=DB::table('agencies')
           ->where('agency_nm',$Agency_Nm)
           ->value('id');
        //    DD($Agency_Nm,$DDAgencyId);

          
      // Retrieve other work-related information from the request
        $AA_No=$request->AA_No;
        $AA_Date=$request->AA_Date;
            // Clean up monetary values by removing commas
        $request->AA_Amount = str_replace(',', '', $request->AA_Amount);

        $AA_Amount=$request->AA_Amount;
        $AA_Authority=$request->AA_Authority;
        $TS_No=$request->TS_No;
        $TS_No = ($TS_No === null) ? '' : $TS_No;
        // dd($TS_No);
        $TS_Date=$request->TS_Date;
        // dd($TS_Date);
        if ($TS_Date !== null) {
            // $convertedTSDate = DateTime::createFromFormat('d/m/Y', $TS_Date)->format('Y-m-d');
            $convertedTSDate=$TS_Date;
        } else {
            $convertedTSDate = null;
        }
        // Clean up other monetary values

                $request->TS_Amount = str_replace(',', '', $request->TS_Amount);

        $TS_Amount=$request->TS_Amount;
        $TS_Authority=$request->TS_Authority;
        $TS_Authority = ($TS_Authority === null) ? '' : $TS_Authority;
    // Retrieve and clean up other work-related fields
        $WO_No = $request->WO_No;
        $Wo_Dt = $request->Wo_Dt;
        $WO_Amt = $request->WO_Amt;
        $Agree_No = $request->Agree_No;
        $Agree_Dt = $request->Agree_Dt;
        $Above_Below = $request->Above_Below;
        $A_B_Pc = $request->A_B_Pc;
        $Period = $request->Period;
        $Perd_Unit = $request->Perd_Unit;
        $Stip_Comp_Dt = $request->Stip_Comp_Dt;
        // dd($Stip_Comp_Dt);
                //    $user->tm_lm_extension = $request->input('tm_lm_extension');
                //    $user->rs_dt_comp = $request->input('rs_dt_comp');
        $Taluka=$request->Taluka;
      // Retrieve village and other related IDs
        $village=$request->village;
        // dd($village);
        $villgeIdd=DB::table('villagemasters')
        ->select('Village_Id')
        ->where('Village',$village)
        ->value('Village_Id');
        // dd($villgeIdd);
        $PS_Constituency=$request->PS_Constituency;
        // dd($PS_Constituency);
        $ps_constid=DB::table('psconsts')
        ->select('PS_Con_Id')
        ->where('PS_Con',$PS_Constituency)
        ->value('PS_Con_Id');
        // dd($ps_constid);

        $ZP_Constituency=$request->ZP_Constituency;
        // dd($ZP_Constituency);
        $ZP_constid=DB::table('zpconsts')
        ->select('ZP_Con_Id')
        ->where('ZP_Con',$ZP_Constituency)
        ->value('ZP_Con_Id');
// dd($ZP_constid);
        $EE_Name=$request->EE_Name;
        // dd($EE_Name);
        $EEid=DB::table('eemasters')
        ->select('eeid')
        ->where('name',$EE_Name)
        ->value('EEid');
        // dd($EEid);


        $DyE_Name=$request->DyE_Name;
        // dd($DyE_Name);
        $DYEid=DB::table('dyemasters')
        ->select('dye_id')
        ->where('name',$DyE_Name)
        ->value('dye_id');
        // dd($DYEid);

        $SO_Name=$request->SO_Name;
        // dd($SO_Name);
        $SOid=DB::table('jemasters')
        ->select('jeid')
        ->where('name',$SO_Name)
        ->value('jeid');
        // dd($SOid);

        $SDC_Name=$request->SDC_Name;
        // dd($SDC_Name);
        $SDCid=DB::table('sdcmasters')
        ->select('SDC_id')
        ->where('name',$SDC_Name)
        ->value('SDC_id');
        // dd($SDCid);

        $PO_Name=$request->PO_Name;
// dd($PO_Name);
$POid=DB::table('jemasters')
->select('jeid')
->where('name',$PO_Name)
->value('jeid');
// dd($POid);

$PA_Name=$request->panm;
// dd($PA_Name);
$PAid=DB::table('dyemasters')
->select('dye_id')
->where('name',$PA_Name)
->value('dye_id');
// dd($PAid);





        $DAO_Name=$request->DAO_Name;
        // dd($DAO_Name);
        $DAOid=DB::table('daomasters')
        ->select('DAO_id')
        ->where('name',$DAO_Name)
        ->value('DAO_id');
        // dd($DAOid);

        $AB_Name=$request->AB_Name;
        // dd($AB_Name);
        $ABid=DB::table('abmasters')
        ->select('AB_Id')
        ->where('name',$AB_Name)
        ->value('AB_Id');
        // dd($ABid);
        

        $mp=$request->mp;
        // dd($mp);
        $mpid=DB::table('mpconsts')
        ->select('MP_Con_Id')
        ->where('MP_Con',$mp)
        ->value('MP_Con_Id');
        // dd($mpid);

        $mla_Name=$request->mla_Name;
        // dd($mla_Name);
        $mlaid=DB::table('mlaconsts')
        ->select('MLA_Con_Id')
        ->where('MLA_Con',$mla_Name)
        ->value('MLA_Con_Id');
        // dd($mlaid);
        
         // Clean up total expenditure value
        $request->Tot_Exp = str_replace(',', '', $request->Tot_Exp);
        $Tot_Exp = $request->Tot_Exp;
        $work_comp=$request->work_comp;
        $actual_complete_date = $request->actual_complete_date;
        $abeffect = $request->a_b_effect;
        // dd($abeffect);
        $AA_Date=$request->AA_Date;
        // dd($AA_Date);
        $convertedAADate = $AA_Date !== null ? $AA_Date : null;
        // dd($AA_Date,$convertedAADate);

        // dd($AA_Date,$Wo_Dt,$TS_Date);
        // $convertedAADate = date("Y-m-d", strtotime($AA_Date));
        // $convertedAADate = DateTime::createFromFormat('d/m/Y', $AA_Date)->format('Y-m-d');
        // $convertedTSDate = date("Y-m-d", strtotime($TS_Date));
        // dd($convertedAADate,$Wo_Dt,$convertedTSDate);


     $tenderid=$request->tenderid;


// Update the workmasters table with the new values
    $user = DB::table('workmasters')
    ->where( 'Work_Id', '=',  $Work_Id)
    ->update( [ 
    'Div' => $Div,
    'Sub_Div_Id'=> $subdivid,
    'Sub_Div' => $Sub_Div,
    'F_H_Code' => $F_H,
    'Work_Nm' => $Work_Nm,
    'Work_Nm_M'=>$Work_Nm_M,
    'Work_Type'=>$worktype,
    'Work_Area'=>$workarea,
    'SSR_Year' => $SSR_year,
    'Tnd_Amt' => $Tnd_Amt,
    'Agency_Nm' => $Agency_Nm,
    'Agency_Id'=>$DDAgencyId,
    'AA_No'=> $AA_No,
    'AA_Dt'=>$convertedAADate,
    'AA_Amt'=>$AA_Amount,
    'AA_Authority'=>$AA_Authority,
    'TS_No'=>$TS_No,
    'TS_Dt'=>$convertedTSDate,
    'TS_Amt'=>$TS_Amount,
    'TS_Authority'=>$TS_Authority,
    'WO_No' => $WO_No,
    'Wo_Dt' => $Wo_Dt,
    'WO_Amt' => $WO_Amt,
    'Agree_No'=>$Agree_No,
    'Agree_Dt'=>$Agree_Dt,
    'A_B_Pc' => $Above_Below,
    'Above_Below' => $A_B_Pc,
    'abeffect'=>$abeffect,
    'Period' => $Period,
    'Perd_Unit' => $Perd_Unit,
    'Stip_Comp_Dt' => $Stip_Comp_Dt,
    'Tal'=>$Taluka,
    'Village_ID'=>$villgeIdd,
    'Ps_Consti'=>$ps_constid,
    'Zp_Consti'=>$ZP_constid,
    'EE_id'=>$EEid,
    'DYE_id'=>$DYEid,
    // 'SO_Id'=>$SOid,
    'jeid'=>$SOid,
    'SDC_id'=>$SDCid,
    'PB_Id'=>$POid,
    'PA_Id'=>$PAid,
    'DAO_Id'=>$DAOid,
    'AB_Id'=>$ABid,
    'MP_Con_Id'=>$mpid,
    'MLA_Con_Id'=>$mlaid,
    'Tot_Exp' => $Tot_Exp,
    'work_comp'=>$work_comp,
    'actual_complete_date' => $actual_complete_date,
    'Tender_Id'=>$tenderid

    ] );

    if($user){
        return redirect('listworkmasters')->with('success','Record Updated successfully.');
    }else{
        return redirect('updateworkroute/'.$request->Work_Id)->with('success','Error in update record.');
    }
}

catch (\Exception $e) {
    // Log the error with detailed information
    Log::error('Error in Funfindiworkmaster: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);

    $error_file=basename($e->getFile());

// Return a response with a custom error view and session data
return response()->view('errors.custom', [
'error' => 'An error occurred while Update WorkId.'.$e->getMessage(),
'error_file' => $error_file,
'error_line' => $e->getLine(),
'error_trace' => $e->getTraceAsString()
], 500); 

}
    }


//edit tender item 
public function FunEditTndItems(Request $request,$tItemId)
{
    // Fetch the record from 'tnditems' table where 't_item_id' matches the given $tItemId
    $Gettnddata=DB::table('tnditems')->where('t_item_id',$tItemId)->first();
     // Return the retrieved data as a JSON response
    return response()->json($Gettnddata);
}

//update tender item
public function FunUpdateTnd_item(Request $request)
{
    try
    {
    // Retrieve the t_item_id from the request
    $tItemId = $request->input('tItemIdToUpdate');
// dd($tItemId);
    // Retrieve other data from the request
    $itemNo = $request->input('itemNo');
        $subNo=$request->input('subNo');
    $itemDescription = $request->input('itemDescription');
    $tndqnt = round($request->input('tenderedQty'), 3); // Round tendered quantity to 3 decimal places
    $itemunit = $request->input('itemUnit');
    $tndrate = round($request->input('tenderedRate'), 2); // Round tendered rate to 2 decimal places
    $tndamt = round($request->input('itemAmount'), 2); // Round item amount to 2 decimal places

    // Update the fields in the tnditems table with the new values
    DB::table('tnditems')
        ->where('t_item_id', $tItemId) // Update the row with matching t_item_id
        ->update([
            't_item_no' => $itemNo, // If you also want to update t_item_no
                        'sub_no'=>$subNo,
            'item_desc' => $itemDescription,
            'tnd_qty' => $tndqnt,
            'item_unit' => $itemunit,
            'tnd_rt' => $tndrate,
            't_item_amt' => $tndamt,
        ]);
        
                // dd($tndamt);
        $convert=new CommonHelper;
        $formatIndianRupeestndamt=$convert->formatIndianRupees($tndamt);
        // dd($formatIndianRupeestndamt);


    // Return a response indicating success or failure
    return response()->json(['message' => 'Data updated successfully',
    'formatIndianRupeestndamt'=>$formatIndianRupeestndamt
    ]);
}

catch (\Exception $e) {
    // Log the error with detailed information
    Log::error('Error in Funfindiworkmaster: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
    $error_file = basename($e->getFile());
// Return a response with a custom error view and session data
return response()->json([
'error' => 'An error occurred while processing your request.',
'error_message'=> $e->getMessage(),
'error_file' => $error_file,
'error_line' => $e->getLine(),
'error_trace' => $e->getTraceAsString()
], 500); 

}
}



//delete the tender item
public function FundeleteTnd_item(Request $request)
{
    // Retrieve the item ID from the request
    $itemId = $request->input('itemId');

    // Delete the item
    DB::table('tnditems')->where('t_item_id', $itemId)->delete();

    // Fetch all items again after deletion
    $updatedData = DB::table('tnditems')->get();

    // Return the updated data as JSON response
    return response()->json(['message' => 'Item deleted successfully', 'data' => $updatedData]);
}

//delete the tender item
public function deleteTndItem($t_item_id)
{
    try
    {
          // Retrieve the item ID from the request
        $itemInBills = DB::table('bills')->where('t_bill_Id', $t_item_id)->first();
        // dd($itemInBills);
        if ($itemInBills) 
        {
            // Return a user-friendly message on the UI
            return response()->json(['message' => 'This item cannot be deleted because it is in use in bills.']);
        }
       $deleteddata= DB::table('tnditems')->where('t_item_id', $t_item_id)->delete();
        // dd($deleteddata);
        // Fetch all items again after deletion
        $updatedData = DB::table('tnditems')->get();

        // Return the updated data as JSON response
        return response()->json(['message' => 'Item deleted successfully', 'data' => $updatedData]);
}
// Method to fetch data from the server
// Method to fetch data from the server
catch (\Exception $e) {
    // Log the error with detailed information
    Log::error('Error in Funfindiworkmaster: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
    $error_file = basename($e->getFile());
// Return a response with a custom error view and session data
return response()->json([
'error' => 'An error occurred while processing your request.',
'error_message'=> $e->getMessage(),
'error_file' => $error_file,
'error_line' => $e->getLine(),
'error_trace' => $e->getTraceAsString()
], 500); 

}
}

//delete work id in whole project
public function FundeleteWorkmaster(Request $request, $Work_Id)
{
    try
    {
    // Access the Work_Id parameter using $Work_Id
    // dd($Work_Id);
    
    // Check if the Work_Id exists in the bills table
    $workidexist = DB::table('bills')->where('work_id', $Work_Id)->first();

    if (isset($workidexist))
    {
        // Work_Id exists in bills, so return a JSON response with an error message
        // return redirect()->back()->with('error', 'This item cannot be deleted because it is in use in bills');
        
        return response()->json(['status' => 'error', 'message' => 'This item cannot be deleted because it is in use in bills']);
    }
    else
    {
    // Work_Id is not in bills, proceed with deletion
    DB::table('workmasters')->where('Work_Id', $Work_Id)->delete();
    DB::table('tnditems')->where('work_Id', $Work_Id)->delete();
    DB::table('itemcons')->where('Work_Id', $Work_Id)->delete();
    
    MBIssueSO::where('MB_No' , $Work_Id)->delete();
    MBIssueDiv::where('MB_No' , $Work_Id)->delete();

    // Return a JSON response with a success message
    // return redirect('listworkmasters')->with('success', 'Item deleted successfully');

    return response()->json(['status' => 'success', 'message' => 'Work_Id  deleted successfully']);
    // return redirect('listworkmasters/');
    }
}

catch (\Exception $e) {
    // Log the error with detailed information
    Log::error('Error in Funfindiworkmaster: ' . $e->getMessage(), [
        'trace' => $e->getTraceAsString(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);
    $error_file = basename($e->getFile());
// Return a response with a custom error view and session data
return response()->json([
'error' => 'An error occurred while processing your request.',
'error_message'=> $e->getMessage(),
'error_file' => $error_file,
'error_line' => $e->getLine(),
'error_trace' => $e->getTraceAsString()
], 500); 

}
}



}


