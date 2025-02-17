<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\ChecklistController;
use Illuminate\Support\Facades\Log;
use Exception; // Import the Exception class
use Illuminate\Support\Facades\Mail;
use App\Mail\MBStatusUpdatedMail;



//  Checklist class all officers
class ChecklistController extends Controller
{
    //Checked measurement by junior engineer function
    public function FunChecklistJE(Request $request)
    {

        try{

          // Retrieve the work ID and bill ID from the request
        $workid=$request->workid;
        $tbill_id=$request->t_bill_Id;
        // dd($workid,$tbill_id);
        // $tbill_id='123456';

        // Fetch the stipulated completion date from the workmasters table based on the work ID
        $stupulatedDate=DB::table('workmasters')
        ->where('Work_Id',$workid)
        ->value('Stip_Comp_Dt');
        // dd($stupulatedDate);

         // Check if a checklist record for the junior engineer exists in the chklst_je table for the given bill ID
        $DBchklst_jeExist=DB::table('chklst_je')
        ->where('t_bill_Id',$tbill_id)
        ->first();
        // dd($workid,$tbill_id,$DBbillsExist);

         // If the checklist record exists
        if ($DBchklst_jeExist !== null) 
        {
            // If the record exists, update it
            // dd('ifok');

            $DBChklst=DB::table('chklst_je')
            // ->select('chklst_Id','t_bill_Id','t_bill_No','Work_Nm')
            ->where('t_bill_Id',$tbill_id)
            ->first();
            // dd($DBChklst);
            
             // If the checklist details are not found, throw an exception
            if(!$DBChklst)
            {
                throw new Exception('Here In Correct TbillId get');
            }

              // Extract details from the checklist record
            $CTbillid=$DBChklst->t_bill_Id;
            // dd($CTbillid);
            $CTbillno=$DBChklst->t_bill_No;
            $workNM=$DBChklst->Work_Nm;
            $DBAgencyId=$DBChklst->Agency_id;
            $DBjeId=$DBChklst->jeid;

            $DBAgencyName=$DBChklst->agency_nm;
            $DBagency_pl=$DBChklst->Agency_Pl;
            $DBJeName=$DBChklst->je_Nm;

            $DBagency_pl = $DBagency_pl === null ? '' : $DBagency_pl;
            // dd($DBAgencyId,$DBagency_pl);            
            $concateresult=$DBChklst->t_bill_No;

            // dd($concateresult);
            $DBAgreeNO=$DBChklst->Agree_No;
            $DBAgreeDT=$DBChklst->Agree_Dt === null ? '' : $DBChklst->Agree_Dt;
            $A_B_Pc=$DBChklst->A_B_Pc;
            $Above_Below=$DBChklst->Above_Below === null ? '' : $DBChklst->Above_Below;
            $Stip_Comp_Dt=$DBChklst->Stip_Comp_Dt === null ? '' : $DBChklst->Stip_Comp_Dt;
            $Act_Comp_Dt=$DBChklst->Act_Comp_Dt === null ? '' : $DBChklst->Act_Comp_Dt;
            $DBMESUrementDate=$DBChklst->M_B_Dt;
            $Agency_MB_Accept=$DBChklst->Agency_MB_Accept;
            $Part_Red_per= $DBChklst->Part_Red_per;
            $Excess_Qty= $DBChklst->Excess_Qty;
            // dd($Excess_Qty);
            $Ex_qty_det= $DBChklst->Ex_qty_det;
            $Qc_Result= $DBChklst->Qc_Result;
            $Roy_Challen= $DBChklst->Roy_Challen;
            $Bitu_Challen= $DBChklst->Bitu_Challen;
            $Qc_Reports= $DBChklst->Qc_Reports;
            $Board= $DBChklst->Board;
            $CFinalbillhandover =$DBChklst->Handover;
            $CFinalbillForm65=$DBChklst->Form_65;
            $CFinalbill='';

       // Retrieve additional details
            $Rec_Drg= $DBChklst->Rec_Drg;
            $Je_Chk= $DBChklst->Je_Chk;
            $Je_chk_Dt= $DBChklst->Je_chk_Dt;
            $Dye_chk= $DBChklst->Dye_chk;
            $Dye_chk_Dt= $DBChklst->Dye_chk_Dt;




         // Retrieve photo and document counts
            $partrtAnalysis=$DBChklst->part_Red_Rt;
            $materialconsu=$DBChklst->Mc_Stat;
            $Recoverystatement=$DBChklst->Rec_Stat;
            $Excesstatement=$DBChklst->Es_Stat;
            $Royaltystatement=$DBChklst->Roy_Stat;
            // dd($Royaltystatement);

            // $Jephoto=$DBChklst->Photo_Docs;
            // dd($Jephoto);
            $photo=DB::table('bills')
            ->where('t_bill_id',$CTbillid)
            ->select('photo1','photo2','photo3','photo4','photo5')
            ->first();

            // Determine if photos exist
         $Jephoto = ($photo->photo1 || $photo->photo2 || $photo->photo3 || $photo->photo4 || $photo->photo5) ? 'Yes' : 'Not Applicable';


           // Count the number of non-null photos
            $countphoto = 0; // Initialize count to zero
            if ($photo !== null) {
                // Convert the object to an array and remove null values
                $photoArray = array_filter((array)$photo);
                // Count the non-null values
                $countphoto = count($photoArray);
            }

            // dd($Jephoto, $countphoto);


            $document = DB::table('bills')
            ->where('t_bill_id', $CTbillid)
            ->select('doc1', 'doc2', 'doc3', 'doc4', 'doc5', 'doc6', 'doc7', 'doc8', 'doc9', 'doc10')
            ->first();
        
         // Count the number of non-null documents
        $countdoc = 0; // Initialize count to zero
        if ($document !== null) 
            {
                        // Convert the object to an array and remove null values
                        $documentArray = array_filter((array)$document);
                        // Count the non-null values
                        $countdoc = count($documentArray);
            }
        
        // dd($document, $countdoc);
        // Retrieve video
        $vedio = DB::table('bills')
        ->where('t_bill_id', $CTbillid)
        ->value('vdo');
    
        // Determine if video exists
    $countvideo = $vedio ? 1 : 0; // If video exists, count it as 1, else 0
    
    // dd($vedio, $countvideo);

    // Retrieve the agency check date
    $Agencychedate=DB::table('bills')
    ->where('t_bill_id', $CTbillid)
    ->value('Agency_Check_Date');
    // dd($Agencychedate);


        } 
        else 
        {
          // If the record does not exist, insert a new record

            // Retrieve bill data from the bills table
            $DBbillData=DB::table('bills')
            ->select('work_id','t_bill_Id','t_bill_No','final_bill')
            ->where('t_bill_Id',$tbill_id)
            ->first();

           // If the bill data is not found, throw an exception
                        if(!$DBbillData)
            {
                throw new Exception('Here In Correct TbillId get cant open checklist Page');
            }

            $CTbillid=$DBbillData->t_bill_Id;
            $CTbillno=$DBbillData->t_bill_No;
            $CFinalbill=$DBbillData->final_bill;

            $CFinalbillForm65=$DBbillData->final_bill;
            $CFinalbillhandover =$DBbillData->final_bill;
            $Board =$DBbillData->final_bill;


            $DBbillWorkid=$DBbillData->work_id;
            // dd($DBbillData,$DBbillWorkid, $CTbillid,$CFinalbill);

             // Determine the final bill status
            $CFinalbillForm65 = $CFinalbillForm65 === 1 ? 'Yes' : 'Not Applicable';
            $CFinalbillhandover = 'Not Applicable';
            $Board = $Board === 1 ? 'Yes' : 'Not Applicable';


            // Retrieve workmaster data from the workmasters table
            $DBWorkmaterDate=DB::table('workmasters')
            ->select('Work_Nm','Agency_Nm','Agency_Id','jeid','Agree_No','Agree_Dt','A_B_Pc','Above_Below','Stip_Comp_Dt'
            ,'actual_complete_date')
            ->where('Work_Id',$DBbillWorkid)
            ->first();
            $workNM=$DBWorkmaterDate->Work_Nm;
            $DBAgencyId=$DBWorkmaterDate->Agency_Id;
            // dd($DBAgencyId);
            $DBjeId=$DBWorkmaterDate->jeid;

            $DBAgencyName=$DBWorkmaterDate->Agency_Nm;

             // Retrieve agency place from the agencies table
            $DBagency_pl=DB::table('agencies')
            ->where('id',$DBAgencyId)
            ->value('Agency_Pl');
            $DBagency_pl = $DBagency_pl === null ? '' : $DBagency_pl;
            // dd($DBAgencyId,$DBagency_pl);

              // Retrieve junior engineer name from the jemasters table
            $DBJeName=DB::table('jemasters')
            ->where('jeid',$DBAgencyId=$DBWorkmaterDate->jeid)
            ->value('name');
            // dd($DBAgencyId=$DBWorkmaterDate->jeid,$DBJeName);
            $tbillnoFUN=CommonHelper::formatTItemNo($CTbillno);
            $finalbillFun=CommonHelper:: getBillType($CFinalbill);

            // Add space to $tbillnoFUN
            $tbillnoFUN = str_pad($tbillnoFUN, strlen($tbillnoFUN) + 2, ' ', STR_PAD_RIGHT);
            // Add space to $finalbillFun
            $finalbillFun = str_pad($finalbillFun, strlen($finalbillFun) + 2, ' ', STR_PAD_RIGHT);

             // Concatenate the formatted bill number and final bill
            $concateresult=$tbillnoFUN.$finalbillFun;
            // dd($concateresult);

             // Extract agreement details
            $DBAgreeNO=$DBWorkmaterDate->Agree_No;
            $DBAgreeDT=$DBWorkmaterDate->Agree_Dt === null ? '' : $DBWorkmaterDate->Agree_Dt;
            $A_B_Pc=$DBWorkmaterDate->A_B_Pc;
            $Above_Below=$DBWorkmaterDate->Above_Below === null ? '' : $DBWorkmaterDate->Above_Below;
            $Stip_Comp_Dt=$DBWorkmaterDate->Stip_Comp_Dt === null ? '' : $DBWorkmaterDate->Stip_Comp_Dt;
            $Act_Comp_Dt=$DBWorkmaterDate->actual_complete_date === null ? '' : $DBWorkmaterDate->actual_complete_date;
            $DBMESUrementDate=DB::table('embs')
            ->where('t_bill_id',$CTbillid)
            ->where('Work_Id',$DBbillWorkid)
            ->max('measurment_dt');
            // dd($DBMESUrementDate);
            // dd($DBAgreeNO,$DBAgreeDT,$A_B_Pc,$Above_Below,$Stip_Comp_Dt,$Act_Comp_Dt);

            $DBMESUrementDate=$DBMESUrementDate === null ? '' : $DBMESUrementDate;
            $partrtAnalysis=DB::table('part_rt_ms')
            ->where('t_bill_id',$CTbillid)->where('work_id',$DBbillWorkid)->value('t_bill_id');
            // dd($partrtAnalysis);
            // $partrtAnalysis=$partrtAnalysis === null ? '' : $partrtAnalysis;
            $partrtAnalysis = $partrtAnalysis !== null ? 'Yes' : 'Not Applicable';



            $materialconsu=DB::table('mat_cons_m')
            ->where('t_bill_id',$CTbillid)
            ->value('t_bill_id');
            // dd($materialconsu);
            // $materialconsu=$materialconsu === null ? '' : $materialconsu;
            $materialconsu = $materialconsu !== null ? 'Yes' : 'Not Applicable';


            $Recoverystatement=DB::table('recoveries')
            ->where('t_bill_id',$CTbillid)
            ->value('t_bill_id');
            // $Recoverystatement=$Recoverystatement === null ? '' : $Recoverystatement;
            $Recoverystatement = $Recoverystatement !== null ? 'Yes' : 'Not Applicable';

            // dd($Recoverystatement);

            $Excesstatement=DB::table('bil_item')
            ->where('t_bill_id',$CTbillid)
            ->value('t_bill_id');
            // $Excesstatement=$Excesstatement === null ? '' : $Excesstatement;
            $Excesstatement = $Excesstatement !== null ? 'Yes' : 'Not Applicable';

            // dd($Excesstatement);

            $Royaltystatement=DB::table('royal_m')
            ->where('t_bill_id',$CTbillid)
            ->value('t_bill_id');
            // $Royaltystatement=$Royaltystatement === null ? '' : $Royaltystatement;
            $Royaltystatement = $Royaltystatement !== null ? 'Yes' : 'Not Applicable';

            // dd($Royaltystatement);

              // Fetch photo details
            $photo=DB::table('bills')
            ->where('t_bill_id',$CTbillid)
            ->select('photo1','photo2','photo3','photo4','photo5')
            ->first();
            // dd($photo);

         $Jephoto = ($photo->photo1 || $photo->photo2 || $photo->photo3 || $photo->photo4 || $photo->photo5) ? 'Yes' : 'Not Applicable';
        // dd($Jephoto);
            $countphoto = 0; // Initialize count to zero
            if ($photo !== null) {
                // Convert the object to an array and remove null values
                $photoArray = array_filter((array)$photo);
                // Count the non-null values
                $countphoto = count($photoArray);
            }

            // dd($photo, $countphoto);

           // Fetch document details
            $document = DB::table('bills')
            ->where('t_bill_id', $CTbillid)
            ->select('doc1', 'doc2', 'doc3', 'doc4', 'doc5', 'doc6', 'doc7', 'doc8', 'doc9', 'doc10')
            ->first();
        
        $countdoc = 0; // Initialize count to zero
        if ($document !== null) {
            // Convert the object to an array and remove null values
            $documentArray = array_filter((array)$document);
            // Count the non-null values
            $countdoc = count($documentArray);
        }
        
        // dd($document, $countdoc);
        
        $vedio = DB::table('bills')
        ->where('t_bill_id', $CTbillid)
        ->value('vdo');
    
    $countvideo = $vedio ? 1 : 0; // If video exists, count it as 1, else 0
    
     // Fetch the agency check date
    $Agency_MB_Accept='Yes';               
    $Part_Red_per= 'Not Required';     
    $Excess_Qty = 'No';               
    $Ex_qty_det= 'Not Required';          
    $Qc_Result= 'Yes';           
    $Roy_Challen = 'No';            
    $Bitu_Challen = 'No';           
    $Qc_Reports= 'Yes';
    $Rec_Drg= 'No';
    $Je_Chk= '';                                 
    $Je_chk_Dt= '';
    $Dye_chk= '';
    $Dye_chk_Dt= '';
    $Agencychedate=DB::table('bills')
    ->where('t_bill_id', $CTbillid)
    ->value('Agency_Check_Date');
    // dd($Agencychedate);

        }
        // $DBworkmaster::table('workmasters')
        // ->select('Work_Id',)
        return view('Checklist.Checklistje',compact('workid','stupulatedDate','workNM','CTbillid','DBAgencyName','DBagency_pl','DBJeName',
        'concateresult','DBAgreeNO','DBAgreeDT',
        'A_B_Pc','Above_Below','Stip_Comp_Dt','Act_Comp_Dt','DBMESUrementDate','partrtAnalysis','materialconsu',
        'Recoverystatement','Excesstatement','Royaltystatement','Jephoto','countphoto','document','countdoc','vedio',
        'countvideo','CFinalbill','CTbillno','DBchklst_jeExist','DBAgencyId','DBjeId','CFinalbillForm65','CFinalbillhandover',
        'Agency_MB_Accept','Part_Red_per','Excess_Qty','Ex_qty_det','Qc_Result','Roy_Challen','Bitu_Challen',
        'Qc_Reports','Board','Rec_Drg','Je_Chk','Je_chk_Dt','Dye_chk','Dye_chk_Dt','Agencychedate'));
        }
        catch (Exception $e) {
            // Log the exception message
            Log::error('Error in FunChecklistJE: ' . $e->getMessage());

            // Return response with error message
            return back()->with('error', 'An error occurred: ' . $e->getMessage());
        }

}


   //Check measurement by junior engineer saved
    public function FunSaveChecklistJE(Request $request)
    {
         // Retrieve the bill ID from the request
        $tbillid=$request->input('tbill_id');

         // Retrieve the work ID associated with the bill ID
        $Work_Id=DB::table('bills')
        ->where('t_bill_Id', $tbillid)
        ->value('work_id');

           // Determine the action (save or update)
        $action = $request->input('action');

          // Set default values for some fields
        $Excess_Qty = 'No';
        $Ex_qty_det= 'Not Required';
        $Bitu_Challen = 'No';
        $CFinalbillhandover = 'Not Applicable';
        $Rec_Drg= 'No';

          // If the action is 'save'
            if ($action === 'save') 
            {
                $tbillid=$request->input('tbill_id');
                $Stip_Comp_Dt=$request->input('Stip_Comp_Dt');
                $MBDT=$request->input('MBDT');
                // dd($MBDT,$Stip_Comp_Dt);

                $Work_Id=DB::table('bills')
                ->where('t_bill_Id', $tbillid)
                ->value('work_id');
                // dd($tbillid,$workId);
        
                        // dd('ok',$request);
                        $radio_excessquantity=$request->input('ExcessQty');
                        // dd('ok',$request,$radio_excessquantity);

                // dd($action);
        $workNM=$request->work_nm;

          // Insert the checklist data into the chklst_je table
        $Savechklist = DB::table('chklst_je')->insert
        ([
            
        't_bill_Id' => $request->input('tbill_id'),
        'Work_Nm'=> $workNM,
        'Agency_id'=> $request->input('AgencyId'),
        'agency_nm'=> $request->input('AgencyNM'),
        'Agency_Pl'=> $request->input('Agency_PL'),
        'jeid'=> $request->input('JEId'),
        'je_Nm'=> $request->input('JeName'),
        't_bill_No'=> $request->input('concateresultbillno'),
        'Agree_No'=> $request->input('AgreeNO'),
        'Agree_Dt'=> $request->input('AgreeDT'),
        'A_B_Pc'=> $request->input('A_B_Pc'),
        'Above_Below'=> $request->input('Above_Below'),
        'Stip_Comp_Dt'=> $request->input('Stip_Comp_Dt'),
        'Act_Comp_Dt'=> $request->input('Act_Comp_Dt'),
        'M_B_No'=> $request->input('MBNO'),
        'M_B_Dt'=> $request->input('MBDT'),

        'Agency_MB_Accept'=> $request->input('radio_Contractorsigned'),
        'part_Red_Rt'=> $request->input('radio_Analysis'),
        'Part_Red_per'=> $request->input('radio_authority'),
        'Excess_Qty'=> $Excess_Qty,
        'Ex_qty_det'=> $Ex_qty_det,
        'Qc_Result'=> $request->input('radio_Q_C_Results'),
        'Mc_Stat'=> $request->input('radio_Material'),
        'Rec_Stat'=> $request->input('radio_Recovery'),
        'Es_Stat'=> $request->input('radio_Excess'),
        'Roy_Stat'=> $request->input('radio_Royalty'),
        'Photo_Docs'=> $request->input('radio_photo'),
        'Roy_Challen'=> $request->input('radio_RoyaltyChallen'),
        'Bitu_Challen'=> $Bitu_Challen,
        'Qc_Reports'=> $request->input('radio_Q_C'),
        'Board'=> $request->input('radio_Board'),
        'Form_65'=> $request->input('radio_Form_65'),
        'Handover'=> $CFinalbillhandover,
        'Rec_Drg'=> $Rec_Drg,

        'Je_Chk'=> 1,
        'Je_chk_Dt'=> $request->input('JEdate'),
        // 'Dye_chk'=> $request->input('tbill_id'),
        // 'Dye_chk_Dt'=> $request->input('tbill_id'),
            ]);
            }
           // If the action is 'update'
            elseif ($action === 'update') 
            {
                $tbillid=$request->input('tbill_id');
                $Work_Id=DB::table('bills')
                ->where('t_bill_Id', $tbillid)
                ->value('work_id');
                // dd($tbillid,$Work_Id);
        
                // dd($action);
                // dd('ok',$request);

     $Update = DB::table('chklst_je')
    ->where('t_bill_Id', $request->input('tbill_id'))
    ->update([
        'Work_Nm' =>  $request->input('work_nm'),
        'Agency_id' => $request->input('AgencyId'),
        'agency_nm' => $request->input('AgencyNM'),
        'Agency_Pl' => $request->input('Agency_PL'),
        'jeid' => $request->input('JEId'),
        'je_Nm' => $request->input('JeName'),
        't_bill_No' => $request->input('concateresultbillno'),
        'Agree_No' => $request->input('AgreeNO'),
        'Agree_Dt' => $request->input('AgreeDT'),
        'A_B_Pc' => $request->input('A_B_Pc'),
        'Above_Below' => $request->input('Above_Below'),
        'Stip_Comp_Dt' => $request->input('Stip_Comp_Dt'),
        'Act_Comp_Dt' => $request->input('Act_Comp_Dt'),
        'M_B_No' => $request->input('MBNO'),
        'M_B_Dt' => $request->input('MBDT'),
        'Agency_MB_Accept' => $request->input('radio_Contractorsigned'),
        'part_Red_Rt' => $request->input('radio_Analysis'),
        'Part_Red_per' => $request->input('radio_authority'),
        'Excess_Qty' => $Excess_Qty,
        'Ex_qty_det' => $Ex_qty_det,
        'Qc_Result' => $request->input('radio_Q_C_Results'),
        'Mc_Stat' => $request->input('radio_Material'),
        'Rec_Stat' => $request->input('radio_Recovery'),
        'Es_Stat' => $request->input('radio_Excess'),
        'Roy_Stat' => $request->input('radio_Royalty'),
        'Photo_Docs' => $request->input('radio_photo'),
        'Roy_Challen' => $request->input('radio_RoyaltyChallen'),
        'Bitu_Challen' => $Bitu_Challen,
        'Qc_Reports' => $request->input('radio_Q_C'),
        'Board' => $request->input('radio_Board'),
        'Form_65' => $request->input('radio_Form_65'),
        'Handover' => $CFinalbillhandover,
        'Rec_Drg' => $Rec_Drg,
        'Je_Chk' => 1,
        'Je_chk_Dt' => $request->input('JEdate'),
        // 'Dye_chk' => $request->input('tbill_id'),
        // 'Dye_chk_Dt' => $request->input('tbill_id'),
        ]);

        // return redirect()->route('listemb', ['Work_Id' => $Work_Id]);


            }    

                // Update the measurement book status in the bills table
            $updateMbstatus = DB::table('bills')
            ->where('t_bill_id', $tbillid)
            ->update(['mb_status' => 7]);
            
            // Check if the update was successful
        if ($updateMbstatus) {

            
            //Email notification for MB status

          // Define the new status
          $newStatus = 7;


          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $Work_Id)->first();

          // Fetch the JE  details related to the given work_id
          $SDCDetails = DB::table('sdcmasters')->where('SDC_id', $workdata->SDC_id)->first();
          //dd($eeDetails);
          
            // Fetch the EE  details related to the given work_id
            $from = DB::table('jemasters')->where('jeid', $workdata->jeid)->first();

          if ($SDCDetails) {
 
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);
              // Send the notification email to the JE
              Mail::to($SDCDetails->email)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $from , $SDCDetails));
          } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }

     }

             // Redirect to the bill list page with the work ID
            return redirect()->route('billlist', ['workid' => $Work_Id]);
    
        
        }


        //Check the data by SDC(sub divisional clerk)
        public function FunChecklistSDC(Request $request)
        {
            try
            {

            // Retrieve the work ID and bill ID from the request
            $workid=$request->workid;
            $tbill_id=$request->t_bill_Id;
            // dd($workid,$tbill_id);
            // $tbill_id='12345678';

             // Retrieve the stipulated completion date for the work
            $stupulatedDate=DB::table('workmasters')
            ->where('Work_Id',$workid)
            ->value('Stip_Comp_Dt');
            // dd($stupulatedDate);
    
             // Check if a checklist for the given bill ID already exists in the chklst_sdc table
            $DBchklst_sdcExist=DB::table('chklst_sdc')
            ->where('t_bill_Id',$tbill_id)
            ->first();
            // dd($workid,$tbill_id,$DBchklst_sdcExist);
    
            if ($DBchklst_sdcExist !== null) 
            {
                // If the record exists, update it
                // dd('ifok');
                $DBChklstSDC=DB::table('chklst_sdc')
                // ->select('chklst_Id','t_bill_Id','t_bill_No','Work_Nm')
                ->where('t_bill_Id',$tbill_id)
                ->first();
                // dd($DBChklstSDC);
                
                // Throw an exception if the retrieved record is null or empty
            if(!$DBChklstSDC)
                {
                    // dd('DBChklst is null or empty'); 
                    throw new Exception('Incorrect TBill ID received, so cannot open Checklist SDC. Please verify and correct the TBill ID.');
                }

           // Retrieve the date the junior engineer checked the bill
                $Jechecklast=DB::table('chklst_je')
                ->where('t_bill_Id',$tbill_id)
                ->value('Je_chk_Dt');
                // dd($Jechecklast);

                 // Retrieve various details from the retrieved checklist record
                $CTbillid=$DBChklstSDC->t_bill_Id;
                $workNM=$DBChklstSDC->Work_Nm;
                $F_H_Codeid=$DBChklstSDC->F_H_id;

               // Retrieve the fund head associated with the fund head code
                $selectedfundhead=DB::table('fundhdms')
                ->where('F_H_id',$F_H_Codeid)
                ->value('Fund_Hd_M');

               // Retrieve a list of all fund heads
                $fundheadList=DB::table('fundhdms')
                ->select('Fund_Hd_M' , 'F_H_id')
                ->get();
                // dd($fundheadList);

                  // Retrieve various details from the retrieved checklist record
                $Arith_chk=$DBChklstSDC->Arith_chk;
                $Sdc_chk=$DBChklstSDC->Sdc_chk;
                $Sdc_chk_dt=$DBChklstSDC->Sdc_chk_dt;


            } 
            else 
            {
                // If the record does not exist, insert a new record save Record
                // dd('elseok');
                $DBbillData=DB::table('bills')
                ->select('work_id','t_bill_Id','t_bill_No','final_bill')
                ->where('t_bill_Id',$tbill_id)
                ->first();
                
                // Throw an exception if the retrieved bill data is null or empty
                if(!$DBbillData)
                {
                    // dd('DBbillData is null or empty'); 
                    throw new Exception('Incorrect TBill ID received, so cannot open Checklist SDC. Please verify and correct the TBill ID.');
                }

                
                $CTbillid=$DBbillData->t_bill_Id;
                $CTbillno=$DBbillData->t_bill_No;
                $CFinalbill=$DBbillData->final_bill;
                $DBbillWorkid=$DBbillData->work_id;

                 // Set the final bill status to 'Yes' or 'Not Applicable'
                $CFinalbill = $CFinalbill === 1 ? 'Yes' : 'Not Applicable';

                // Retrieve work master data for the given work ID
                $DBWorkmaterDate=DB::table('workmasters')
                ->select('Work_Nm','Agency_Nm','F_H_Code','F_H_id','Agency_Id','jeid','Agree_No','Agree_Dt','A_B_Pc','Above_Below','Stip_Comp_Dt'
                ,'Act_Comp_Dt')
                ->where('Work_Id',$DBbillWorkid)
                ->first();

                 // Retrieve the date the junior engineer checked the bill
                $Jechecklast=DB::table('chklst_je')
                ->where('t_bill_Id',$tbill_id)
                ->value('Je_chk_Dt');
                // dd($Jechecklast);
                 // Retrieve various details from the retrieved work master data
                $F_H_Codeid=$DBWorkmaterDate->F_H_id;
                //dd($F_H_Codeid);

                $selectedfundhead=DB::table('fundhdms')
                ->where('F_H_id',$F_H_Codeid)
                ->value('Fund_Hd_M');
                // dd($selectedfundhead);
                // Retrieve a list of all fund heads
                $fundheadList=DB::table('fundhdms')
                ->select('Fund_Hd_M' , 'F_H_id')
                ->get();
                // dd($fundheadList);

                 // Retrieve the work name and set default values for checklist fields
                $workNM=$DBWorkmaterDate->Work_Nm;
                $Arith_chk='Yes';
                $Sdc_chk='';
                $Sdc_chk_dt='';

        
            }

            // Return the checklist view with the necessary data
            return view('Checklist.ChecklistSDC',compact('DBchklst_sdcExist','workNM','CTbillid','Arith_chk',
        'Sdc_chk','Sdc_chk_dt','workid','Jechecklast','stupulatedDate','fundheadList','selectedfundhead' , 'F_H_Codeid'));
        }
        catch (Exception $e) {
            // Log the error message
            
            Log::error('Error in FunAbstractcalculation: ' . $e->getMessage());

            // You can also return an error view or a JSON response depending on your requirement
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
                }
            }


        //SAVE the data of checklist of sdc
        public function FunSaveChecklistSDC(Request $request)
        {

             // Retrieve the action and bill ID from the request
            $action=$request->action;
            $tbillid=$request->tbill_id;
            // dd($request,$action,$tbillid);


            // If the action is to save a new checklist
            if ($action === 'save') 
            {
                // Retrieve the bill ID and work ID
                $tbillid=$request->input('tbill_id');
                $Work_Id=DB::table('bills')
                ->where('t_bill_Id', $tbillid)
                ->value('work_id');
                // dd($tbillid,$workId);
        
                        // dd('ok',$request);
       // Retrieve the work name and selected fund head ID from the request
        $workNM=$request->work_nm;
        // dd($workNM);
        $selectedFHid=$request->input('F_H_Code');

          // Retrieve the fund head code based on the selected fund head ID
        $F_H_Code=DB::table('fundhdms')
        ->where('F_H_id',$selectedFHid)
        ->value('F_H_CODE');

        // Insert a new record into the chklst_sdc table
        $SavechklistSDC = DB::table('chklst_sdc')->insert
        ([
        't_bill_Id' => $request->input('tbill_id'),
        'Work_Nm'=> $workNM,
        'F_H_Code'=> $F_H_Code,
        'F_H_id'=> $selectedFHid,
        'Arith_chk'=> $request->input('Arith_chk'),
        'Sdc_chk'=> 1,
        'Sdc_chk_dt'=> $request->input('SDCdate'),
        // 'Dye_chk'=> $request->input('tbill_id'),
        // 'Dye_chk_Dt'=> $request->input('tbill_id'),
            ]);

       // Update the fund head code and ID in the workmasters table
            $saveFHcodeINWorkmaster=DB::table('workmasters')
            ->where('Work_Id',$Work_Id)
            ->update(['F_H_Code'=>$F_H_Code,
                      'F_H_id' => $selectedFHid
                           ]);
            // dd($saveFHcodeINWorkmaster);
            }

            // If the action is to update an existing checklist
            if ($action === 'update') 
            {
                // dd($action);
                 // Retrieve the bill ID and work ID
                $tbillid=$request->input('tbill_id');
                $Work_Id=DB::table('bills')
                ->where('t_bill_Id', $tbillid)
                ->value('work_id');
                // dd($tbillid,$workId);
        
                        // dd('ok',$request);
            // Retrieve the work name and selected fund head ID from the request
        $workNM=$request->work_nm;
        // dd($workNM);

        $selectedFHid=$request->input('F_H_Code');
        // dd($selectedFHName);
        $F_H_Code=DB::table('fundhdms')
        ->where('F_H_id',$selectedFHid)
        ->value('F_H_CODE');
        // dd($F_H_Code);

         // Update the existing record in the chklst_sdc table
        $UpdatechklistSDC = DB::table('chklst_sdc')
        ->where('t_bill_Id', $request->input('tbill_id'))
        ->update([
        't_bill_Id' => $request->input('tbill_id'),
        'Work_Nm'=> $workNM,
        'F_H_Code'=> $F_H_Code,
        'F_H_id'=> $selectedFHid,
        'Arith_chk'=> $request->input('Arith_chk'),
        'Sdc_chk'=> 1,
        'Sdc_chk_dt'=> $request->input('SDCdate'),
        // 'Dye_chk'=> $request->input('tbill_id'),
        // 'Dye_chk_Dt'=> $request->input('tbill_id'),
            ]);


             // Update the fund head code and ID in the workmasters table
            $saveFHcodeINWorkmaster=DB::table('workmasters')
            ->where('Work_Id',$Work_Id)
            ->update(['F_H_Code'=>$F_H_Code,
                     'F_H_id'=> $selectedFHid,
            ]);
            // dd($saveFHcodeINWorkmaster);

        }
         // Update the mb_status in the bills table to 8
            $updateMbstatus = DB::table('bills')
            ->where('t_bill_id', $tbillid)
            ->update(['mb_status' => 8]);
            
            
            // Check if the update was successful
        if ($updateMbstatus) {
            
             //Email notification for MB status

          // Define the new status
          $newStatus = 8;


          

          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $Work_Id)->first();

           // Fetch the DYE details related to the given work_id
           $DyeDetails = DB::table('dyemasters')->where('dye_id', $workdata->DYE_id)->first();
          //dd($eeDetails);
          
            // Fetch the EE  details related to the given work_id
            $from = DB::table('sdcmasters')->where('SDC_id', $workdata->SDC_id)->first();

          if ($DyeDetails) {
 
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);
              // Send the notification email to the JE
              Mail::to($DyeDetails->email)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $from , $DyeDetails));
          } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }

        }
             // Redirect to the bill list route with the work ID
            return redirect()->route('billlist', ['workid' => $Work_Id]);

        }


        //  checked data by DYE (deputy engineer)
        public function FunChecklistDYE(Request $request)
        {
                 // Retrieve the bill ID from the request
                $t_bill_Id=$request->t_bill_Id;

                // Retrieve the work ID associated with the bill ID
                $workID=DB::table('bills')
                ->where('t_bill_id',$t_bill_Id)
                ->value('work_id');

                 // Retrieve the stipulated completion date from the workmasters table
                $stupulatedDate=DB::table('workmasters')
                ->where('Work_Id',$workID)
                ->value('Stip_Comp_Dt');
                // dd($stupulatedDate);
    
                  // Retrieve the checklist details from chklst_je table related to the bill ID
                $DBchklst_jeRelatedTbillid=DB::table('chklst_je')
                ->where('t_bill_id',$t_bill_Id)
                ->first();

               // Extract necessary details from the retrieved checklist
                $CTbillid=$t_bill_Id;
                $workNM=$DBchklst_jeRelatedTbillid->Work_Nm;
                // dd( $DBchklst_jeRelatedTbillid,$workNM);
                $DBAgencyId=$DBchklst_jeRelatedTbillid->Agency_id;
                $DBAgencyName=$DBchklst_jeRelatedTbillid->agency_nm;
                $DBagency_pl=$DBchklst_jeRelatedTbillid->Agency_Pl;
                $DBjeId=$DBchklst_jeRelatedTbillid->jeid;
                $DBJeName=$DBchklst_jeRelatedTbillid->je_Nm;
                $concateresult=$DBchklst_jeRelatedTbillid->t_bill_No;
                $DBAgreeNO=$DBchklst_jeRelatedTbillid->Agree_No;
                $DBAgreeDT=$DBchklst_jeRelatedTbillid->Agree_Dt;
                $A_B_Pc=$DBchklst_jeRelatedTbillid->A_B_Pc;
                $Above_Below=$DBchklst_jeRelatedTbillid->Above_Below;
                $Stip_Comp_Dt=$DBchklst_jeRelatedTbillid->Stip_Comp_Dt;
                $Act_Comp_Dt=$DBchklst_jeRelatedTbillid->Act_Comp_Dt;
                $CTbillid=$DBchklst_jeRelatedTbillid->M_B_No;
                $DBMESUrementDate=$DBchklst_jeRelatedTbillid->M_B_Dt;
                $Agency_MB_Accept=$DBchklst_jeRelatedTbillid->Agency_MB_Accept;
                $partrtAnalysis=$DBchklst_jeRelatedTbillid->part_Red_Rt;
                $Part_Red_per=$DBchklst_jeRelatedTbillid->Part_Red_per;
                $Excess_Qty=$DBchklst_jeRelatedTbillid->Excess_Qty;
                $Ex_qty_det=$DBchklst_jeRelatedTbillid->Ex_qty_det;
                $Qc_Result=$DBchklst_jeRelatedTbillid->Qc_Result;
                $materialconsu=$DBchklst_jeRelatedTbillid->Mc_Stat;
                $Recoverystatement=$DBchklst_jeRelatedTbillid->Rec_Stat;
                $Excesstatement=$DBchklst_jeRelatedTbillid->Es_Stat;
                $Royaltystatement=$DBchklst_jeRelatedTbillid->Roy_Stat;
                $photo=$DBchklst_jeRelatedTbillid->Photo_Docs;
                // dd($photo);

                $photo1=DB::table('bills')
                ->where('t_bill_id',$CTbillid)
                ->select('photo1','photo2','photo3','photo4','photo5')
                ->first();

                 // Count the number of non-null photo entries
                $countphoto = 0; // Initialize count to zero
                if ($photo1 !== null) {
                    // Convert the object to an array and remove null values
                    $photoArray = array_filter((array)$photo1);
                    // Count the non-null values
                    $countphoto = count($photoArray);
                }
    
                // dd($photo, $countphoto);
    
              // Retrieve document details from the bills table
                $document = DB::table('bills')
                ->where('t_bill_id', $CTbillid)
                ->select('doc1', 'doc2', 'doc3', 'doc4', 'doc5', 'doc6', 'doc7', 'doc8', 'doc9', 'doc10')
                ->first();
            
                 // Count the number of non-null document entries
            $countdoc = 0; // Initialize count to zero
            if ($document !== null) {
                // Convert the object to an array and remove null values
                $documentArray = array_filter((array)$document);
                // Count the non-null values
                $countdoc = count($documentArray);
            }
            
            // dd($document, $countdoc ,$photo1, $countphoto);
            
            // Retrieve video details from the bills table
            $vedio = DB::table('bills')
            ->where('t_bill_id', $CTbillid)
            ->value('vdo');
        
        $countvideo = $vedio ? 1 : 0; // If video exists, count it as 1, else 0
    

        // Extract additional details from the checklist
                $Roy_Challen=$DBchklst_jeRelatedTbillid->Roy_Challen;
                $Bitu_Challen=$DBchklst_jeRelatedTbillid->Bitu_Challen;
                $Qc_Reports=$DBchklst_jeRelatedTbillid->Qc_Reports;
                $Board=$DBchklst_jeRelatedTbillid->Board;
                $CFinalbill=$DBchklst_jeRelatedTbillid->Form_65;
                $Handover=$DBchklst_jeRelatedTbillid->Handover;

                $Rec_Drg=$DBchklst_jeRelatedTbillid->Rec_Drg;
                $Je_Chk=$DBchklst_jeRelatedTbillid->Je_Chk;
                $Je_chk_Dt=$DBchklst_jeRelatedTbillid->Je_chk_Dt;
                $SODYEchk=$DBchklst_jeRelatedTbillid->Dye_chk;
                $SODYEchk_Dt=$DBchklst_jeRelatedTbillid->Dye_chk_Dt;

//UI SDC Form Nessasary data get

                $DBSDCgetdata=DB::table('chklst_sdc')
                ->where('t_bill_id',$t_bill_Id)
                ->first();
                // dd($DBSDCgetdata);
                $SDCTbillId=$DBSDCgetdata->t_bill_Id;
                $SDCWork_Nm=$DBSDCgetdata->Work_Nm;
                // $SDCFHCODE=$DBSDCgetdata->F_H_Code;
                $SDCFHCODENO=$DBSDCgetdata->F_H_Code;
                                
               $SDCFHCODEID=$DBSDCgetdata->F_H_id;
                 $FHOCDEName=DB::table('fundhdms')->where('F_H_id',$SDCFHCODEID)->value('Fund_Hd_M');
                //  dd($FHOCDEName);

                                
                $SDCFHCODE=DB::table('fundhdms')->where('F_H_CODE',$SDCFHCODENO)->value('Fund_Hd_M');

                $SDCArith_chk=$DBSDCgetdata->Arith_chk;
                // dd($SDCArith_chk);
                $SDCSdc_chk=$DBSDCgetdata->Sdc_chk;
                $SDCSdc_chk_Dt=$DBSDCgetdata->Sdc_chk_dt;
                $SDCDye_chk=$DBSDCgetdata->Dye_chk;
                $SDCDye_chk_Dt=$DBSDCgetdata->Dye_chk_Dt;



         // Return the view with the necessary data
                return view ('Checklist.ChecklistDYE',compact('workID','CTbillid','workNM','DBAgencyId','DBAgencyName','DBagency_pl','DBjeId',
            'DBJeName','concateresult','DBAgreeNO','DBAgreeDT','A_B_Pc','Above_Below','Stip_Comp_Dt','Act_Comp_Dt','CTbillid',
        'DBMESUrementDate','Agency_MB_Accept','partrtAnalysis','Part_Red_per','Excess_Qty','Ex_qty_det','Qc_Result','materialconsu',
    'Recoverystatement','Excesstatement','Royaltystatement','photo','Roy_Challen','Bitu_Challen','Qc_Reports','Board','CFinalbill','Handover','Rec_Drg',
'Je_Chk','Je_chk_Dt','countphoto','countdoc','countvideo','SODYEchk','SODYEchk_Dt',
'SDCTbillId','SDCWork_Nm','SDCFHCODE','FHOCDEName','SDCArith_chk','SDCSdc_chk','SDCSdc_chk_Dt','SDCDye_chk',
'SDCDye_chk_Dt','stupulatedDate'));
        }

          //Check date of dye update
        public function FunDyeChkAndDate(Request $request)
              {
                    // dd('ok',$request);
                    $tbill_id=$request->tbill_id;

                    //bill data given tbillid
                    $workID=DB::table('bills')
                    ->where('t_bill_id', $tbill_id)
                    ->value('work_id');
                    // dd($workID);
    
                     //Update the checklist date for the  je checklist
                    $UpdatejechklstTable = DB::table('chklst_je')
                    ->where('t_bill_id', $tbill_id)
                    ->update([
                        'Dye_chk' => 1,
                        'Dye_chk_Dt' => $request->SODYEdate
                    ]);

                     //Update the checklist date for the  SDC checklist
                    $UpdateSDCchklstTable = DB::table('chklst_sdc')
                    ->where('t_bill_Id', $tbill_id)
                    ->update([
                        'Dye_chk' => 1,
                        'Dye_chk_Dt' => $request->SDCDYEdate
                    ]);

                    //update the bills table 
                    $updateMbstatus = DB::table('bills')
                    ->where('t_bill_id', $tbill_id)
                    ->update(['mb_status' => 9]);
                    
                    
                 // Check if the update was successful
          if ($updateMbstatus) {
            
                    //Email notification for MB status

          // Define the new status
          $newStatus = 9;


          

          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $workID)->first();

           // Fetch the PO details related to the given work_id
           $PODetails =DB::table('jemasters')->where('jeid', $workdata->PB_Id)->first();
          //dd($eeDetails);
          
            // Fetch the DYE  details related to the given work_id
            $from = DB::table('dyemasters')->where('dye_id', $workdata->DYE_id)->first();

          if ($PODetails) {
 
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbill_id)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);
              // Send the notification email to the JE
              Mail::to($PODetails->email)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $from , $PODetails));
          } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }
          
          
          }
        
                    //return to bill page
                    return redirect()->route('billlist', ['workid' => $workID]);
                }


                //Checked data by the  PO(project officer)
                public function FunChecklistPO(Request $request)
                {
                    // Get the t_bill_Id from the request
                    $t_bill_Id=$request->t_bill_Id;
                    // dd($request,$t_bill_Id);

                    // Check if a record exists in the chklst_pb table for the given t_bill_Id
                    $DBchklst_POExist=DB::table('chklst_pb')
                    ->where('t_bill_Id',$t_bill_Id)
                    ->first();

                    // Fetch the work_id associated with the given t_bill_Id
                    $workid=DB::table('bills')
                    ->where('t_bill_id',$t_bill_Id)
                    ->value('work_id');
                    // dd($workid);

                 // Fetch the stipulated completion date from the workmasters table
                    $stupulatedDate=DB::table('workmasters')
                    ->where('Work_Id',$workid)
                    ->value('Stip_Comp_Dt');
                    // dd($stupulatedDate);
        


                    if ($DBchklst_POExist !== null) 
                    {
                        // If the record exists, update it
                        // dd('ifok');
                        $DBChklstpo=DB::table('chklst_pb')
                        // ->select('chklst_Id','t_bill_Id','t_bill_No','Work_Nm')
                        ->where('t_bill_Id',$t_bill_Id)
                        ->first();
                        // dd($DBChklstpo);
                        $workid=DB::table('bills')
                        ->where('t_bill_id',$t_bill_Id)
                        ->value('work_id');
                        // dd($workid);

                         // Fetch details from the existing record
                        $workNM=$DBChklstpo->Work_Nm;
                        $SD_chklst=$DBChklstpo->SD_chklst;
                        $QC_T_Done=$DBChklstpo->QC_T_Done;
                        $QC_T_No=$DBChklstpo->QC_T_No;
                        $QC_Result=$DBChklstpo->QC_Result;
                        $SQM_Chk = $DBChklstpo->SQM_Chk;
                        $Part_Red_Rt_Proper=$DBChklstpo->Part_Red_Rt_Proper;
                        $Excess_qty_125=$DBChklstpo->Excess_qty_125;
                        $CL_38_Prop=$DBChklstpo->CL_38_Prop;
                        $CFinalbillBoard=$DBChklstpo->Board;
                        $Rec_Drg=$DBChklstpo->Rec_Drg;
                        $TotRoy=$DBChklstpo->Tot_Roy;
                        $PreTotRoy=$DBChklstpo->Pre_Bill_Roy;
                        $Cur_Bill_Roy_Paid=$DBChklstpo->Cur_Bill_Roy_Paid;
                        $Roy_Rec=$DBChklstpo->Roy_Rec;
                        $Tnd_Amt=$DBChklstpo->Tnd_Amt;
                        $netAmt=$DBChklstpo->Net_Amt;
                        $c_netamt=$DBChklstpo->C_NetAmt;
                        $Act_Comp_Dt=$DBChklstpo->Act_Comp_Dt;
                        $MB_NO=$DBChklstpo->MB_NO;
                        $DBMB_Dt=$DBChklstpo->MB_Dt;
                        $Mess_Mode=$DBChklstpo->Mess_Mode;
                        $Mat_cons=$DBChklstpo->Mat_Cons;
                        $CFinalbillForm65=$DBChklstpo->Form_65;
                        $CFinalbillhandover=$DBChklstpo->Handover;
                        $Red_Est=$DBChklstpo->Red_Est;
                        $PO_Chk=$DBChklstpo->PO_Chk;
                        $PO_Chk_Dt=$DBChklstpo->PO_Chk_Dt;

                         // Fetch the last DYE check date from chklst_sdc table
                        $lstDYEcheckdate=DB::table('chklst_sdc')
                        ->where('t_bill_Id',$t_bill_Id)
                        ->value('Dye_chk_Dt');
                        // dd($lstDYEcheckdate);
                    } 
                    else 
                    {
                        // If the record does not exist, insert a new record save Record
                        // dd('elseok');
                        $workid=DB::table('bills')
                        ->where('t_bill_id',$t_bill_Id)
                        ->value('work_id');

                        // If the record does not exist, fetch data and set default values for the new record
                        $workNM=DB::table('workmasters')
                        ->where('Work_Id',$workid)
                        ->value('Work_Nm');
                        // dd($workNM);

                         // Fetch data from bills table
                        $DBbillData=DB::table('bills')
                        ->select('work_id','t_bill_Id','t_bill_No','final_bill','net_amt','c_netamt')
                        ->where('t_bill_Id',$t_bill_Id)
                        ->first();
                        $CFinalbill=$DBbillData->final_bill;
                        // dd($CFinalbill);
                        $CFinalbill = $CFinalbill === 1 ? 'Yes' : 'Not Applicable';
                        $netAmt=$DBbillData->net_amt;
                        $c_netamt=$DBbillData->c_netamt;
                        $CFinalbillhandover=$DBbillData->final_bill;
                        $CFinalbillhandover = $CFinalbillhandover === 1 ? 'Yes' : 'Not Applicable';
                        $CFinalbillForm65 =$DBbillData->final_bill;
                        $CFinalbillForm65 = $CFinalbillForm65 === 1 ? 'Yes' : 'Not Applicable';
                        $CFinalbillBoard=$DBbillData->final_bill;
                        $CFinalbillBoard = $CFinalbillBoard === 1 ? 'Yes' : 'Not Applicable';


                         // Calculate total royalty amount
                        $TotRoy=DB::table('royal_m')
                        ->where('t_bill_id',$t_bill_Id)
                        ->sum('royal_amt');
                        // dd($TotRoy);
                        
                         // Custom rounding for total royalty amount
                        $commonHelper = new CommonHelper();
                        // Call the customRound method on the instance
                        $TotRoy = $commonHelper->customRound($TotRoy); 
                        // dd($TotRoy);


                        
                        
                        if ($TotRoy == 0) 
                        {
                            $TotRoy = "0.00";
                        }
                        // dd($TotRoy);


                         // Fetch previous bill royalty amount
                        $previous_t_bill_id = DB::table('royal_m')
                        ->where('t_bill_id', '<', $t_bill_Id)
                        ->where('work_id',$workid)
                        ->max('t_bill_id');
                        // dd($t_bill_Id,$previous_t_bill_id);

                        if ($previous_t_bill_id !== null) 
                        {
                            $PreTotRoy = DB::table('royal_m')
                                ->where('t_bill_id', $previous_t_bill_id)
                                ->sum('royal_amt');
                                // dd($PreTotRoy);
                                
                                $commonHelper = new CommonHelper();
                                // Call the customRound method on the instance
                                $PreTotRoy = $commonHelper->customRound($PreTotRoy); 
                                // dd($PreTotRoy);

                        }
                        else
                        {
                            $PreTotRoy="0.00";
                            // dd($PreTotRoy);
                        }
                                                   
                        // dd($TotRoy,$PreTotRoy);


                         // Fetch tender amount and actual completion date
                        $WorkmasterData=DB::table('workmasters')
                        ->where('Work_Id', $workid)
                        ->select('Tnd_Amt','actual_complete_date')
                        ->first();
                        $Tnd_Amt=$WorkmasterData->Tnd_Amt;
                        $Act_Comp_Dt=$WorkmasterData->actual_complete_date;

                         // Fetch MB date
                        $MB_NO=$workid;
                        $DBMB_Dt=DB::table('embs')
                        ->where('t_bill_id',$t_bill_Id)
                        ->where('Work_Id',$workid)
                        ->max('measurment_dt');
                        // dd($DBMB_Dt);

                        // Check if material consumption is applicable
                        $Mat_cons=DB::table('mat_cons_m')
                        ->where('t_bill_id',$t_bill_Id)
                        ->value('t_bill_id');
                        // dd($Mat_cons);
                        $Mat_cons = $Mat_cons !== null ? 'Yes' : 'Not Applicable';


                        // Set default values for other fields
                        $SD_chklst='Yes';
                        $QC_T_Done='Yes';
                        $QC_T_No='Yes';
                        $QC_Result='Yes';
                        $SQM_Chk = 'Not Applicable';
                        $Part_Red_Rt_Proper='Not Applicable';
                        $Excess_qty_125='No';
                        $CL_38_Prop='Not Required';
                        $Rec_Drg='Yes';
                        $Cur_Bill_Roy_Paid='0.00';
                        $Roy_Rec='0.00';
                        $Mess_Mode='Yes';
                        $PO_Chk='';
                        $PO_Chk_Dt='';
                        $Red_Est = 'Not Required';

                       // Fetch the last DYE check date from chklst_sdc table
                        $lstDYEcheckdate=DB::table('chklst_sdc')
                        ->where('t_bill_Id',$t_bill_Id)
                        ->value('Dye_chk_Dt');
                        // dd($lstDYEcheckdate);
                
                    }

                     // Return the view with the compacted data
                    return view('Checklist.checklistPO',compact('workid','stupulatedDate','workNM','t_bill_Id','TotRoy','PreTotRoy',
                'Tnd_Amt','Act_Comp_Dt','netAmt','c_netamt','DBMB_Dt','Mat_cons','DBchklst_POExist','CFinalbillhandover',
            'CFinalbillForm65','CFinalbillBoard','Red_Est','SQM_Chk','MB_NO',
        'SD_chklst','QC_T_Done','QC_T_No','QC_Result','Part_Red_Rt_Proper','Excess_qty_125','CL_38_Prop',
    'Rec_Drg','Cur_Bill_Roy_Paid','Roy_Rec','Mess_Mode','PO_Chk','PO_Chk_Dt','lstDYEcheckdate'));
                }



                 //save the data checked by PO
                public function FunSaveChecklistPO(Request $request)
                {
                    // Get the action type and t_bill_id from the request
                    $action=$request->action;
                    $tbillid=$request->tbill_id;
                    // dd($request,$action,$tbillid);
                    $Work_Id=$request->Work_Id;
                    // dd($Work_Id);
                    // dd($tbillid);

        
        
                    if ($action === 'save') 
                    {
                         // Insert a new record into the chklst_pb table
                        $tbillid=$request->input('tbill_id');
                        // $Work_Id=DB::table('bills')
                        // ->where('t_bill_Id', $tbillid)
                        // ->value('work_id');
                        // dd($tbillid,$Work_Id);
                
                        // dd('ok',$request);
                        // dd($action);
                $workNM=$request->work_nm;
                // dd($workNM);
                $SavechklistPB = DB::table('chklst_pb')->insert
                ([
                't_bill_Id' => $request->input('tbill_id'),
                'Work_Nm'=> $workNM,
                'SD_chklst'=> $request->input('SD_chklst'),
                'QC_T_Done'=> $request->input('QC_T_Done'),
                'QC_T_No'=> $request->input('QC_T_No'),
                'QC_Result'=> $request->input('QC_Result'),
                'SQM_Chk' => $request->input('SQM_Chk'),
                'Part_Red_Rt_Proper'=> $request->input('Part_Red_Rt_Proper'),
                'Excess_qty_125'=> $request->input('Excess_qty_125'),


                'CL_38_Prop' => $request->input('CL_38_Prop'),
                'Board'=> $request->input('Board'),
                'Rec_Drg'=> $request->input('Rec_Drg'),
                'Tot_Roy'=> $request->input('Tot_Roy'),
                'Pre_Bill_Roy'=> $request->input('Pre_Bill_Roy'),
                'Cur_Bill_Roy_Paid'=> $request->input('Cur_Bill_Roy_Paid'),
                'Roy_Rec'=> $request->input('Roy_Rec'),
                'Tnd_Amt'=> $request->input('Tnd_Amt'),

                'Net_Amt' => $request->input('Net_Amt'),
                'C_NetAmt'=> $request->input('C_NetAmt'),
                'Act_Comp_Dt'=> $request->input('Act_Comp_Dt'),
                'MB_NO'=> $request->input('MB_NO'),
                'MB_Dt'=> $request->input('MB_DT'),
                'Mess_Mode'=> $request->input('Mess_ModeMat_Cons'),
                'Mat_Cons'=> $request->input('Mat_Cons'),
                'Form_65'=> $request->input('Form_65'),

                'Handover' => $request->input('Handover'),
                'Red_Est' =>$request->input('Red_Est'),
                'PO_Chk'=> 1,
                'PO_Chk_Dt'=> $request->input('POdate'),
                // 'PA_Chk'=> $request->input('Arith_chk'),
                // 'PA_Chk_Dt'=> 1,
                // 'EE_Chk'=> $request->input('SDCdate'),
                // 'EE_Chk_Dt'=> $request->input('tbill_id'),



                    ]);
        
                    }
        // Update a new record into the chklst_pb table
                    if ($action === 'update') 
                    {
                        // dd($action);
                        $tbillid=$request->input('tbill_id');
                        // dd($tbillid)
                        // $Work_Id=DB::table('bills')
                        // ->where('t_bill_Id', $tbillid)
                        // ->value('work_id');
                        // dd($tbillid,$workId);
                
                        // dd('ok',$request);
                        // dd($action);
                $workNM=$request->work_nm;
                // dd($workNM);
                $UpdatechklistPB = DB::table('chklst_pb')
                ->where('t_bill_Id', $request->input('tbill_id'))
                ->update([
                    
                'Work_Nm'=>$request->input('work_nm'),
                'SD_chklst'=> $request->input('SD_chklst'),
                'QC_T_Done'=> $request->input('QC_T_Done'),
                'QC_T_No'=> $request->input('QC_T_No'),
                'QC_Result'=> $request->input('QC_Result'),
                'SQM_Chk' => $request->input('SQM_Chk'),

                'Part_Red_Rt_Proper'=> $request->input('Part_Red_Rt_Proper'),
                'Excess_qty_125'=> $request->input('Excess_qty_125'),
                'CL_38_Prop' => $request->input('CL_38_Prop'),
                'Board'=> $request->input('Board'),
                'Rec_Drg'=> $request->input('Rec_Drg'),
                'Tot_Roy'=> $request->input('Tot_Roy'),
                'Pre_Bill_Roy'=> $request->input('Pre_Bill_Roy'),
                'Cur_Bill_Roy_Paid'=> $request->input('Cur_Bill_Roy_Paid'),
                'Roy_Rec'=> $request->input('Roy_Rec'),
                'Tnd_Amt'=> $request->input('Tnd_Amt'),
                'Net_Amt' => $request->input('Net_Amt'),
                'C_NetAmt'=> $request->input('C_NetAmt'),
                'Act_Comp_Dt'=> $request->input('Act_Comp_Dt'),
                'MB_NO'=> $request->input('MB_NO'),
                'MB_Dt'=> $request->input('MB_DT'),
                'Mess_Mode'=> $request->input('Mess_ModeMat_Cons'),
                'Mat_Cons'=> $request->input('Mat_Cons'),
                'Form_65'=> $request->input('Form_65'),
                'Handover' => $request->input('Handover'),
                'PO_Chk'=> 1,
                'PO_Chk_Dt'=> $request->input('POdate'),
                // 'PA_Chk'=> $request->input('Arith_chk'),
                // 'PA_Chk_Dt'=> 1,
                // 'EE_Chk'=> $request->input('SDCdate'),
                // 'EE_Chk_Dt'=> $request->input('tbill_id'),
                    ]);
                    }

                    // dd($Work_Id);

                   //MB status update the given bill
                    $updateMbstatus = DB::table('bills')
                    ->where('t_bill_id', $tbillid)
                    ->update(['mb_status' => 10]);
                    // DD($updateMbstatus);
                    // dd($Work_Id);
                    
                     // Check if the update was successful
             if ($updateMbstatus) {
                    
                      //Email notification for MB status

          // Define the new status
          $newStatus = 10;


          

          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $Work_Id)->first();

           // Fetch the Auditor details related to the given work_id
           $AuditorDetails =DB::table('abmasters')->where('AB_Id', $workdata->AB_Id)->first();
          //dd($eeDetails);
          
            // Fetch the PO  details related to the given work_id
            $from = DB::table('jemasters')->where('jeid', $workdata->PB_Id)->first();

          if ($AuditorDetails) {
 
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $tbillid)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);
              // Send the notification email to the JE
              Mail::to($AuditorDetails->email)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $from , $AuditorDetails));
          } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }

          }
                    //return to bill view page
                    return redirect()->route('billlist', ['workid' => $Work_Id]);
                        }


                        //checked data by AB(auditor)
                public function FunChecklistAB(Request $request)
                {
                              // Get the t_bill_id and workid from the request
                            $t_bill_id = $request->input('t_bill_Id');
                            $workid = $request->input('workid');

                             // Check if the checklist for the auditor exists in the database
                            $DBchklst_AudiExist=DB::table('chcklst_aud')
                            ->where('t_bill_Id',$t_bill_id)
                            ->first();
                            // dd($DBchklst_AudiExist);

                            // Get the stipulated completion date from the workmasters table
                            $stupulatedDate=DB::table('workmasters')
                            ->where('Work_Id',$workid)
                            ->value('Stip_Comp_Dt');
                            // dd($stupulatedDate);
        
        
        
                            if ($DBchklst_AudiExist !== null) 
                            {
                                 // If data is available in the auditor table, update the record
                                $DBAudiExist=DB::table('chcklst_aud')
                                ->where('t_bill_Id',$t_bill_id)
                                ->first();
                                 // Get work details from the bills table
                                $BillsData = DB::table('bills')
                                ->where('t_bill_id', $t_bill_id)
                                ->select('work_id')
                                ->first();
                                // dd($BillsData);
    
                                $work_id = $BillsData->work_id;
                                $workNM=$DBAudiExist->Work_Nm;

                                 // Get workmaster details
                                $workmaster=DB::table('workmasters')            
                                ->where('Work_Id',$work_id) 
                                ->select('F_H_id','Work_Nm')
                                ->first();

                                 // Get the Fund Head Code from the fundhdms table
                                $FH_code=DB::table('fundhdms')
                                ->where('F_H_id',$workmaster->F_H_id)->value('Fund_Hd_M');
                                // dd($FH_code);

    
                                 // Set variables from the existing auditor checklist record
                                $Arith_chk = $DBAudiExist->Arith_chk;
                                $Ins_Policy_Agency = $DBAudiExist->Ins_Policy_Agency ;
                                $Ins_Prem_Amt_Agency = $DBAudiExist->Ins_Prem_Amt_Agency;
                                $Bl_Rec_Ded = $DBAudiExist->Bl_Rec_Ded ;
                                $C_netAmt = $DBAudiExist->C_netAmt;
                                $tot_ded = $DBAudiExist->Tot_Ded;
                                $chq_amt = $DBAudiExist->Chq_Amt ;
                                $Aud_chck=$DBAudiExist->Aud_chck;
                                $Aud_Chk_Dt=$DBAudiExist->Aud_Chk_Dt;
                             
                                  // Get the last PO check date from the checklist PB table
                                $lastPOdate=DB::table('chklst_pb')
                                ->where('t_bill_Id',$t_bill_id)
                                ->value('PO_Chk_Dt');
        

                            }
                            else
                            {
                                 // If data is not available in the auditor table, insert a new record

                                    // Get bills data

                            $BillsData = DB::table('bills')
                                            ->where('t_bill_id', $t_bill_id)
                                            ->select('work_id','c_netamt','tot_ded','tot_recovery','chq_amt')
                                            ->first();

                            $work_id = $BillsData->work_id;

                             // Get workmaster details
                            $workmaster=DB::table('workmasters')            
                            ->where('Work_Id',$work_id) 
                            ->select('F_H_id','Work_Nm')
                            ->first();

                             // Get the Fund Head Code from the fundhdms table
                            $FH_code=DB::table('fundhdms')
                            ->where('F_H_id',$workmaster->F_H_id)->value('Fund_Hd_M');
                            // dd($FH_code);

                             // Set default values for the new auditor checklist record
                            $workNM=$workmaster->Work_Nm;
                            // $FH_code=$workmaster->F_H_Code;
                            $Arith_chk='Yes';
                            $Ins_Policy_Agency='No';
                            $Ins_Prem_Amt_Agency=0.00;
                            $Bl_Rec_Ded='Yes';
                            $C_netAmt=$BillsData->c_netamt;
                            $tot_ded=$BillsData->tot_ded;
                            $chq_amt=$BillsData->chq_amt;
                            $Aud_chck='';
                            $Aud_Chk_Dt='';

                            // Get the last PO check date from the checklist PB table
                            $lastPOdate=DB::table('chklst_pb')
                            ->where('t_bill_Id',$t_bill_id)
                            ->value('PO_Chk_Dt');
                            // dd($lastPOdate);
                            }
                        

                 // Return the view with the compacted variables           
                return view('Checklist.ChecklistAB', compact('t_bill_id','work_id','stupulatedDate','workNM','FH_code',
            'Arith_chk','Ins_Policy_Agency','Ins_Prem_Amt_Agency','Bl_Rec_Ded','C_netAmt',
            'tot_ded','chq_amt','Aud_chck','Aud_Chk_Dt','DBchklst_AudiExist','lastPOdate'));
        }



        //save the data of checked by AB(auditor)
        public function FunSaveChecklistAB(Request $request)
        {
             // Get action, bill ID, and work ID from the request
            $action=$request->action;
            $t_bill_id=$request->t_bill_id;
            $work_id=$request->work_id;

             // Get the checkbox value from the request
            $ABChckbox=$request->ABcheckbox;
            // dd($ABChckbox);
            
            
            // Remove commas from the input values for numerical fields
            $request->chq_amt = str_replace(',', '', $request->chq_amt);
            $request->tot_ded = str_replace(',', '', $request->tot_ded);
            $request->C_netAmt = str_replace(',', '', $request->C_netAmt);

            
             // Convert the checkbox value to a boolean
            if($ABChckbox === 'on')
            {
                $ABChckbox=1;
            }
            else
            {
                $ABChckbox=0;
            }
            // dd($ABChckbox);


            if ($action === 'save') 
            {
                 // If action is 'save', insert a new record into the checklist auditor table
                 // Get the Fund Head Code from the fundhdms table
                $FHCODE=DB::table('fundhdms')
                ->where('Fund_Hd_M',$request->F_H_Code)->value('F_H_CODE');
                // dd($FHCODE);

                 // Prepare the data for insertion
                $insertData = [
                    't_bill_Id' => $t_bill_id,
                    'Work_Nm'=>$request->work_nm,
                    'Arith_chk' =>$request->Arith_chk,
                    'Ins_Policy_Agency' =>$request->Ins_Policy_Agency,
                    'Ins_Prem_Amt_Agency' =>$request->Ins_Prem_Amt_Agency,
                    'Bl_Rec_Ded' =>$request->Bl_Rec_Ded,
                    'C_netAmt' =>$request->C_netAmt,
                    'Tot_Ded' =>$request->tot_ded,
                    'Chq_Amt' =>$request->chq_amt,
                    'Aud_chck' =>$ABChckbox ,
                    'Aud_Chk_Dt' =>$request->ABdate
                ];
                // Perform the insertion into the database
                DB::table('chcklst_aud')->insert($insertData);
            }

            else
            {
                // dd($action);
                $FHCODE=DB::table('fundhdms')
                ->where('Fund_Hd_M',$request->F_H_Code)->value('F_H_CODE');
                // dd($FHCODE);

                 // Prepare the data for updating
                $UpdateData = [
                    't_bill_Id' => $t_bill_id,
                    'Work_Nm'=>$request->work_nm,
                    'Arith_chk' =>$request->Arith_chk,
                    'Ins_Policy_Agency' =>$request->Ins_Policy_Agency,
                    'Ins_Prem_Amt_Agency' =>$request->Ins_Prem_Amt_Agency,
                    'Bl_Rec_Ded' =>$request->Bl_Rec_Ded,
                    'C_netAmt' =>$request->C_netAmt,
                    'Tot_Ded' =>$request->tot_ded,
                    'Chq_Amt' =>$request->chq_amt,
                    'Aud_chck' =>$ABChckbox ,
                    'Aud_Chk_Dt' =>$request->ABdate
                ];

                 // Perform the update in the database
                DB::table('chcklst_aud')
                ->where('t_bill_Id',$t_bill_id)
                ->update($UpdateData);
            }

               // Update the mb_status field in the bills table to 11
            $updateMbstatus = DB::table('bills')
            ->where('t_bill_id', $t_bill_id)
            ->update(['mb_status' => 11]);
            
            
            // Check if the update was successful
             if ($updateMbstatus) {
                 
                 
             //Email notification for MB status

          // Define the new status
          $newStatus = 11;


          

          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $work_id)->first();

           // Fetch the AAO details related to the given work_id
           $AAODetails =DB::table('daomasters')->where('DAO_id', $workdata->DAO_Id)->first();
          //dd($eeDetails);
          
            // Fetch the PO  details related to the given work_id
            $from = DB::table('abmasters')->where('AB_Id', $workdata->AB_Id)->first();

          if ($AAODetails) {
 
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $t_bill_id)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);
              // Send the notification email to the JE
              Mail::to($AAODetails->email)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $from , $AAODetails));
          } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }

             }
             // Redirect to the bill list route with the work ID
            return redirect()->route('billlist', ['workid' => $work_id]);



        }


        // CHecked data by AAO (divisional accountant)
        public function FunChecklistAAO(Request $request)
        {

              // Retrieve t_bill_id from the request
            $t_bill_id = $request->input('t_bill_Id');

              // Check if data exists in chcklst_aud table for the given t_bill_id
            $DBchklst_AudiExist=DB::table('chcklst_aud')
            ->where('t_bill_Id',$t_bill_id)
            ->first();
            // dd($DBchklst_AudiExist);

                // Fetch details from chcklst_aud table if data exists
                $DBAudiExist=DB::table('chcklst_aud')
                ->where('t_bill_Id',$t_bill_id)
                ->first();

                 // Retrieve work_id associated with the t_bill_id from bills table
                $BillsData = DB::table('bills')
                ->where('t_bill_id', $t_bill_id)
                ->select('work_id')
                ->first();
                // dd($BillsData);

                 // Fetch stipulated completion date from workmasters table using work_id
                $work_id = $BillsData->work_id;
                $stupulatedDate=DB::table('workmasters')
                ->where('Work_Id',$work_id)
                ->value('Stip_Comp_Dt');


                  // Fetch other details from chcklst_aud, workmasters, and fundhdms tables
                $workNM=$DBAudiExist->Work_Nm;

                $workmaster=DB::table('workmasters')            
                ->where('Work_Id',$work_id) 
                ->select('F_H_id','Work_Nm')
                ->first();
                $FH_code=DB::table('fundhdms')
                ->where('F_H_id',$workmaster->F_H_id)->value('Fund_Hd_M');
                // dd($FH_code);

                 // Assign values from chcklst_aud to variables
                $Arith_chk = $DBAudiExist->Arith_chk;
                $Ins_Policy_Agency = $DBAudiExist->Ins_Policy_Agency ;
                $Ins_Prem_Amt_Agency = $DBAudiExist->Ins_Prem_Amt_Agency;
                $Bl_Rec_Ded = $DBAudiExist->Bl_Rec_Ded ;
                $C_netAmt = $DBAudiExist->C_netAmt;
                $tot_ded = $DBAudiExist->Tot_Ded;
                $chq_amt = $DBAudiExist->Chq_Amt ;
                $Aud_chck=$DBAudiExist->Aud_chck;
                $Aud_Chk_Dt=$DBAudiExist->Aud_Chk_Dt;
                $AAO_Chk=$DBAudiExist->AAO_Chk;
                $AAO_Chk_Dt=$DBAudiExist->AAO_Chk_Dt;


              // Fetch last PO check date from chklst_pb table
                $lastPOdate=DB::table('chklst_pb')
                ->where('t_bill_Id',$t_bill_id)
                ->value('PO_Chk_Dt');


                // Load the ChecklistAAO view with compacted variables
            return view('Checklist.ChecklistAAO', compact('t_bill_id','work_id','workNM','FH_code','stupulatedDate',
                            'Arith_chk','Ins_Policy_Agency','Ins_Prem_Amt_Agency','Bl_Rec_Ded','C_netAmt',
                            'tot_ded','chq_amt','Aud_chck','Aud_Chk_Dt','DBchklst_AudiExist','lastPOdate',
                            'AAO_Chk','AAO_Chk_Dt'));


        }


        //update the  checked date and data of AAO
        public function FunAAOChkAndDateUpdate(Request $request)
        {
            //request form view page
            $work_id=$request->work_id;
            $t_bill_id=$request->t_bill_id;
            $chckAAO=$request->AAOcheckbox;
            $AAOdate=$request->AAOdate;


           //if checklist divisional auditor is on then update data
            if($chckAAO === 'on')
            {
                $chckAAO = 1;
            }
            else
            {
                $chckAAO = 0;
            }

            // dd($chckAAO,$AAOdate);

            $AAOCnetAmt = $request->AAOCnetAmt;
            
                        $AAOCnetAmt = str_replace(',', '', $AAOCnetAmt);

            // dd($AAOCnetAmt);
            $Updatechcklst_aud = DB::table('chcklst_aud')
            ->where('t_bill_Id', $t_bill_id)
            ->update([
                'C_netAmt' => $AAOCnetAmt,
                'AAO_Chk' => $chckAAO,
                'AAO_Chk_Dt' => $AAOdate
            ]);

            $updateMbstatus = DB::table('bills')
            ->where('t_bill_id', $t_bill_id)
            ->update(['mb_status' => 12]);
            
            
            // Check if the update was successful
             if ($updateMbstatus) {
                 
             //Email notification for MB status

          // Define the new status
          $newStatus = 12;


          

          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $work_id)->first();

           // Fetch the EE details related to the given work_id
           $eeDetails = DB::table('eemasters')->where('eeid', $workdata->EE_id)->first();
          //dd($eeDetails);
          
            // Fetch the AAO  details related to the given work_id
            $from = DB::table('daomasters')->where('DAO_id', $workdata->DAO_Id)->first();

          if ($eeDetails) {
 
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $t_bill_id)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);
              // Send the notification email to the JE
              Mail::to($eeDetails->email)->queue(new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType , $workdata , $tbilldata , $from , $eeDetails));
          } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }

             }
            //redirect to the view page
            return redirect()->route('billlist', ['workid' => $work_id]);

        }

        //checked data for EE(executive engineer)
        public function FunChecklistEE(Request $request)
        {
             // Retrieve parameters from the request
            $workid=$request->workid;
            $t_bill_Id=$request->t_bill_Id;
            // dd($t_bill_Id,$workid);

            //PO Detail
              // Retrieve stipulated completion date from workmasters table
            $stupulatedDate=DB::table('workmasters')
            ->where('Work_Id',$workid)
            ->value('Stip_Comp_Dt');
            // dd($stupulatedDate);


             // Retrieve PO details from chklst_pb table
                $DBChklstpo=DB::table('chklst_pb')
                ->where('t_bill_Id',$t_bill_Id)
                ->first();
                // dd($DBChklstpo);

                 // Assign PO details to variables
                $workNM=$DBChklstpo->Work_Nm;
                $SD_chklst=$DBChklstpo->SD_chklst;
                $QC_T_Done=$DBChklstpo->QC_T_Done;
                $QC_T_No=$DBChklstpo->QC_T_No;
                $QC_Result=$DBChklstpo->QC_Result;
                $SQM_Chk = $DBChklstpo->SQM_Chk;
                $Part_Red_Rt_Proper=$DBChklstpo->Part_Red_Rt_Proper;
                $Excess_qty_125=$DBChklstpo->Excess_qty_125;
                $CL_38_Prop=$DBChklstpo->CL_38_Prop;
                $CFinalbillBoard=$DBChklstpo->Board;
                $Rec_Drg=$DBChklstpo->Rec_Drg;
                $TotRoy=$DBChklstpo->Tot_Roy;
                $PreTotRoy=$DBChklstpo->Pre_Bill_Roy;
                $Cur_Bill_Roy_Paid=$DBChklstpo->Cur_Bill_Roy_Paid;
                $Roy_Rec=$DBChklstpo->Roy_Rec;
                $Tnd_Amt=$DBChklstpo->Tnd_Amt;
                $netAmt=$DBChklstpo->Net_Amt;
                $c_netamt=$DBChklstpo->C_NetAmt;
                $Act_Comp_Dt=$DBChklstpo->Act_Comp_Dt;
                $MBNO=$DBChklstpo->MB_NO;
                $DBMB_Dt=$DBChklstpo->MB_Dt;
                $Mess_Mode=$DBChklstpo->Mess_Mode;
                $Mat_cons=$DBChklstpo->Mat_Cons;
                $CFinalbillForm65=$DBChklstpo->Form_65;
                $CFinalbillhandover=$DBChklstpo->Handover;
                $Red_Est = $DBChklstpo->Red_Est;
                $PO_Chk=$DBChklstpo->PO_Chk;
                $PO_Chk_Dt=$DBChklstpo->PO_Chk_Dt;
                $EE_Chk=$DBChklstpo->EE_Chk;
                $EE_Chk_Dt=$DBChklstpo->EE_Chk_Dt;

               // Retrieve last DYE check date from chklst_sdc table
                $lstDYEcheckdate=DB::table('chklst_sdc')
                ->where('t_bill_Id',$t_bill_Id)
                ->value('Dye_chk_Dt');
                // dd($lstDYEcheckdate);



 ///////////////////// //Auditor Detail//////////////////////////
         // Retrieve auditor details from chcklst_aud table
                $DBAudiExist=DB::table('chcklst_aud')
                ->where('t_bill_Id',$t_bill_Id)
                ->first();
                // dd($DBAudiExist,$t_bill_Id);

                 // Assign auditor details to variables
                $workNM=$DBAudiExist->Work_Nm;
                // dd($workNM);

                // Fetch workmaster and fundhdms details for FH_code
                $workmaster=DB::table('workmasters')            
                ->where('Work_Id',$workid) 
                ->select('F_H_id','Work_Nm')
                ->first();
                $FH_code=DB::table('fundhdms')
                ->where('F_H_id',$workmaster->F_H_id)->value('Fund_Hd_M');
                // dd($FH_code);

                // Assign auditor details to variables
                $Arith_chk = $DBAudiExist->Arith_chk;
                $Ins_Policy_Agency = $DBAudiExist->Ins_Policy_Agency ;
                $Ins_Prem_Amt_Agency = $DBAudiExist->Ins_Prem_Amt_Agency;
                $Bl_Rec_Ded = $DBAudiExist->Bl_Rec_Ded ;
                $C_netAmt = $DBAudiExist->C_netAmt;
                $tot_ded = $DBAudiExist->Tot_Ded;
                $chq_amt = $DBAudiExist->Chq_Amt ;
                $Aud_chck=$DBAudiExist->Aud_chck;
                $Aud_Chk_Dt=$DBAudiExist->Aud_Chk_Dt;
                $AAO_Chk=$DBAudiExist->AAO_Chk;
                $AAO_Chk_Dt=$DBAudiExist->AAO_Chk_Dt;
                $AAOEE_Chk=$DBAudiExist->EE_Chck;
                $AAOEE_Chk_Dt=$DBAudiExist->EE_Chck_Dt;


                // Retrieve last PO check date from chklst_pb table
                $lastPOdate=DB::table('chklst_pb')
                ->where('t_bill_Id',$t_bill_Id)
                ->value('PO_Chk_Dt');

            // Return the ChecklistEE view with compacted variables
            return view('Checklist.ChecklistEE',compact('workid','stupulatedDate','workNM','t_bill_Id','TotRoy','PreTotRoy',
                        'Tnd_Amt','Act_Comp_Dt','netAmt','c_netamt','DBMB_Dt','Mat_cons','CFinalbillhandover',
                        'CFinalbillForm65','CFinalbillBoard','SQM_Chk','Red_Est','MBNO',
                        'SD_chklst','QC_T_Done','QC_T_No','QC_Result','Part_Red_Rt_Proper','Excess_qty_125','CL_38_Prop',
                        'Rec_Drg','Cur_Bill_Roy_Paid','Roy_Rec','Mess_Mode','PO_Chk','PO_Chk_Dt','lstDYEcheckdate','EE_Chk','EE_Chk_Dt',
                        //Auditor Detail return 
                        'workNM','FH_code', 'Arith_chk','Ins_Policy_Agency','Ins_Prem_Amt_Agency','Bl_Rec_Ded','C_netAmt',
                        'tot_ded','chq_amt','Aud_chck','Aud_Chk_Dt','lastPOdate','AAO_Chk','AAO_Chk_Dt','AAOEE_Chk',
                        'AAOEE_Chk_Dt'
                ));

        }

        //Update the executive engineer 
        public function FunEEChkAndDateUpdate(Request $request)
        {
            // Retrieve values from the request
            $EEcheckbox=$request->EEcheckbox;
            $t_bill_Id=$request->tbill_id;
            $Work_Id=$request ->Work_Id;
            // dd($t_bill_Id);
           // Determine the value of EE checkbox
            if($EEcheckbox === 'on')
            {
                $EEcheckbox = 1 ;
            }
            else
            {
                $EEcheckbox = 0;
            }

            // Retrieve EE date from the request
            $EEdate=$request->EEdate;
            // dd($EEcheckbox,$EEdate);

            $EEcheckboxAuditor= $request->EEcheckboxAuditor;
            if($EEcheckboxAuditor === 'on')
            {
                $EEcheckboxAuditor = 1;
            }
             else
             {
                $EEcheckboxAuditor = 0;
             }

             
             $EEdateAuditor=$request->EEdateAuditor;
            //  dd($EEcheckboxAuditor,$EEdateAuditor);

              // Update chklst_pb table with EE checkbox and date
        $Updatechklst_pb=DB::table('chklst_pb')
        ->where('t_bill_Id',$t_bill_Id)
        ->update(['EE_Chk' => $EEcheckbox,
        'EE_Chk_Dt' => $EEdate]);

           // Update chcklst_aud table with EE checkbox and date for Auditor
        $Updatechcklst_aud=DB::table('chcklst_aud')
        ->where('t_bill_Id',$t_bill_Id)
        ->update(['EE_Chck' => $EEcheckboxAuditor,
        'EE_Chck_Dt' => $EEdateAuditor]);

         // Update mb_status in bills table to 13
        $updateMbstatus = DB::table('bills')
        ->where('t_bill_id', $t_bill_Id)
        ->update(['mb_status' => 13]);
        
        
        // Check if the update was successful
       if ($updateMbstatus) {

         //Email notification for MB status

          // Define the new status
          $newStatus = 13;


          //Work information
          $workdata=DB::table('workmasters')->where('Work_Id', $Work_Id)->first();

           // Fetch the EE details related to the given work_id
           $eeDetails = DB::table('eemasters')->where('eeid', $workdata->EE_id)->first();
            // Fetch the DYE details related to the given work_id
            $DyeDetails = DB::table('dyemasters')->where('dye_id', $workdata->DYE_id)->first();
             // Fetch the JE  details related to the given work_id
          $jeDetails = DB::table('jemasters')->where('jeid', $workdata->jeid)->first();
            // Fetch the Agency  details related to the given work_id
            $agencyDetails = DB::table('agencies')->where('id', $workdata->Agency_Id)->first();
          
            // Fetch the AAO  details related to the given work_id
            $from =  DB::table('eemasters')->where('eeid', $workdata->EE_id)->first();

          if ($eeDetails) {
 
              $tbilldata=DB::table('bills')->where('t_bill_Id' , $t_bill_Id)->first();
               //change format of item no  and bill type
              $formattedTItemNo = CommonHelper::formatTItemNo($tbilldata->t_bill_No);
              $billType = CommonHelper::getBillType($tbilldata->final_bill);
              //dd($jeDetails);

                              // Prepare the email
        $email = new MBStatusUpdatedMail($newStatus, $formattedTItemNo, $billType, $workdata, $tbilldata, $from, $DyeDetails);

        // Set CC recipients if they exist
        if ($jeDetails) {
            $email->cc($jeDetails->email);
        }
        if ($agencyDetails) {
            $email->cc($agencyDetails->Agency_Mail);
        }

        // Send the email
        Mail::to($DyeDetails->email)->queue($email);
         
            } else {
              // Handle the case where no JE details are found
              // You can log the error or throw an exception
          }


        }

        // Redirect to the billlist route with workid parameter
        return redirect()->route('billlist', ['workid' => $Work_Id]);

        }
        
        

    }
