<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PatientAssignment;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Poll for patients that have been dilating for > 20 mins
     */
    public function poll(Request $request)
    {
        $excludeIds = $request->query('exclude', '');
        $excludeArray = array_filter(explode(',', $excludeIds));

        // Find active assignments in "espera" or "dilatacion" depending on how it's named in DB.
        // Assuming the DB uses "espera" as the status for patients waiting in reception/dilating area.
        $alerts = [];
        
        $activeAssignments = PatientAssignment::with('patient')
            ->where('status', 'in_progress')
            ->whereRaw('LOWER(event_type) LIKE ?', ['%dilata%'])
            ->whereNotIn('id', $excludeArray)
            ->get();

        foreach ($activeAssignments as $assignment) {
            $minutes = Carbon::parse($assignment->started_at ?? $assignment->created_at)->diffInMinutes(Carbon::now());
            
            // Only alert if they have been waiting for exactly or more than 20 minutes
            if ($minutes >= 20) {
                $alerts[] = [
                    'id' => $assignment->id,
                    'patient_name' => collect([$assignment->patient?->first_name, $assignment->patient?->last_name])->filter()->join(' ') ?: 'Paciente Desconocido',
                    'minutes' => $minutes
                ];
            }
        }

        return response()->json($alerts);
    }
}
