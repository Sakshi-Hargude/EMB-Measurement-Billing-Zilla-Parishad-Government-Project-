<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
   public function showLogs(Request $request)
    {
        $query = ActivityLog::query();
    
        // Get all users for the dropdown
        $users = \App\Models\User::all();
    
          // Initialize date variables
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Check if both from_date and to_date are provided and valid
            if (!empty($fromDate) && !empty($toDate)) {
                // Validate date format
                if ($this->isValidDate($fromDate) && $this->isValidDate($toDate)) {
                    // Ensure from_date is before to_date
                    if ($fromDate <= $toDate) {
                        $query->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
                    }
                }
            }
    
        // Check if user_id is provided
        if ($request->has('user_id') && $request->user_id !== '' && $request->user_id !== null) {
            $query->where('user_id', $request->user_id);
        }
    
        // Get the logs based on the query
        $logs = $query->orderBy('created_at', 'desc')->get();
    
        return view('Logs.index', compact('logs', 'users'));
    }

     // Helper function to validate date format
     private function isValidDate($date, $format = 'Y-m-d')
     {
         $d = \DateTime::createFromFormat($format, $date);
         return $d && $d->format($format) === $date;
     }
    
}
