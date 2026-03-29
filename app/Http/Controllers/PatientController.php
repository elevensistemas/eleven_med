<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $patients = Patient::with('latestVisit.doctor')->when($search, function ($query, $search) {
            return $query->where('last_name', 'like', "%{$search}%")
                         ->orWhere('first_name', 'like', "%{$search}%")
                         ->orWhere('dni', 'like', "%{$search}%");
        })->latest()->paginate(15);
        
        return view('patients.index', compact('patients', 'search'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $doctors = User::role('médico')->get();
        return view('patients.create', compact('doctors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'dni' => 'required|string|unique:patients|max:50',
            'date_of_birth' => 'required|date',
            'obra_social' => 'nullable|string|max:255',
            'plan' => 'nullable|string|max:255',
            'affiliate_number' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'iva_condition' => 'nullable|string|max:255',
            'nro_siniestro' => 'nullable|string|max:255',
            'director_id' => 'nullable|exists:users,id',
            'medical_notes' => 'nullable|string',
        ]);

        $patient = Patient::create($validated);

        return redirect()->route('patients.show', $patient)->with('success', 'Paciente registrado correctamente.');
    }

    /**
     * Display the specific patient card and clinical tabs.
     */
    public function show(Patient $patient)
    {
        // Phase 20: Track in Session for "Últimos Pacientes Buscados"
        $this->pushToRecent($patient);

        // Load relationships like director, appointments, studies later
        $patient->load(['director', 'studies.uploader', 'assignments.doctor', 'surgeries.doctor', 'visits.doctor', 'appointments.doctor', 'comments.user']);
        $doctors = \App\Models\User::role('médico')->get();
        return view('patients.show', compact('patient', 'doctors'));
    }

    /**
     * Phase 20: Global Omnibar Search
     */
    public function globalSearch(Request $request)
    {
        $search = $request->get('q');
        if (!$search) return redirect()->back();

        $query = Patient::where('dni', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%");
                        
        // Si encontramos uno único, vamos directo a su ficha
        if ($query->count() === 1) {
            return redirect()->route('patients.show', $query->first());
        }

        // Si hay varios o cero, lo mandamos al index con el filtro
        return redirect()->route('patients.index', ['search' => $search]);
    }

    /**
     * Phase 21: Live API Search for Modals
     */
    public function apiSearch(Request $request)
    {
        $search = $request->get('q');
        if (!$search || strlen($search) < 2) {
            return response()->json([]);
        }

        $patients = Patient::where('dni', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->limit(10)
                        ->get(['id', 'first_name', 'last_name', 'dni']);

        return response()->json($patients);
    }

    /**
     * Helper para Phase 20 Omnibar
     */
    private function pushToRecent(Patient $patient)
    {
        $recent = session()->get('recent_patients', []);

        // Filter out if already exists
        $recent = array_filter($recent, function($p) use ($patient) {
            return $p['id'] !== $patient->id;
        });

        // Prepend current
        array_unshift($recent, [
            'id' => $patient->id,
            'name' => $patient->first_name . ' ' . $patient->last_name,
            'dni' => $patient->dni,
        ]);

        // Keep only top 5
        $recent = array_slice($recent, 0, 5);

        session()->put('recent_patients', $recent);
    }

    public function edit(Patient $patient)
    {
        $doctors = User::role('médico')->get();
        return view('patients.edit', compact('patient', 'doctors'));
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'dni' => 'required|string|max:50|unique:patients,dni,'.$patient->id,
            'date_of_birth' => 'required|date',
            'obra_social' => 'nullable|string|max:255',
            'plan' => 'nullable|string|max:255',
            'affiliate_number' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'profession' => 'nullable|string|max:255',
            'iva_condition' => 'nullable|string|max:255',
            'nro_siniestro' => 'nullable|string|max:255',
            'director_id' => 'nullable|exists:users,id',
            'medical_notes' => 'nullable|string',
        ]);

        $patient->update($validated);

        return redirect()->route('patients.show', $patient)->with('success', 'Datos actualizados.');
    }

    public function destroy(Patient $patient)
    {
        // Here we could soft-delete to preserve medical records
        $patient->delete();
        return redirect()->route('patients.index')->with('success', 'Paciente eliminado.');
    }

    /**
     * Phase 17: Motor analítico para determinar los hábitos de asistencia de un paciente
     */
    public function getHabits($id)
    {
        $patient = Patient::with('appointments')->find($id);
        
        if (!$patient || $patient->appointments->isEmpty()) {
            return response()->json(['message' => '']); // Sin historial
        }

        $daysCount = [];
        $shiftsCount = ['Mañana' => 0, 'Tarde' => 0];
        
        $dayNames = [
            0 => 'Domingos',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábados'
        ];

        foreach ($patient->appointments as $appt) {
            $date = \Carbon\Carbon::parse($appt->date);
            $dayOfWeek = $date->dayOfWeek;
            
            if (!isset($daysCount[$dayOfWeek])) {
                $daysCount[$dayOfWeek] = 0;
            }
            $daysCount[$dayOfWeek]++;

            $hour = (int) date('H', strtotime($appt->time));
            if ($hour < 14) {
                $shiftsCount['Mañana']++;
            } else {
                $shiftsCount['Tarde']++;
            }
        }

        // Find most frequent day
        arsort($daysCount);
        $topDayIndex = array_key_first($daysCount);
        $topDayName = $dayNames[$topDayIndex];

        // Find most frequent shift
        $topShift = $shiftsCount['Mañana'] >= $shiftsCount['Tarde'] ? 'Mañana' : 'Tarde';

        return response()->json([
            'message' => "El paciente suele atenderse los {$topDayName} por la {$topShift}."
        ]);
    }
}
