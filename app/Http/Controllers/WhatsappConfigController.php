<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappConfig;

class WhatsappConfigController extends Controller
{
    public function index()
    {
        $config = WhatsappConfig::first();
        if (!$config) {
            $config = new WhatsappConfig();
            $config->reminder_days_before = 1;
            $config->reminder_time = '08:00:00';
            $config->message_template = "Hola {nombre}, te recordamos tu turno en Eleven Med para el día {fecha_turno} a las {hora_turno} con el profesional {medico}. Por favor, confirma asistencia.";
        }
        return view('whatsapp.index', compact('config'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'api_url' => 'nullable|url',
            'instance_id' => 'nullable|string|max:255',
            'token' => 'nullable|string|max:255',
            'reminder_days_before' => 'required|integer|min:1|max:30',
            'reminder_time' => 'required|date_format:H:i',
            'message_template' => 'nullable|string',
        ]);

        $config = WhatsappConfig::first() ?? new WhatsappConfig();
        $config->fill($request->all());
        $config->is_active = $request->has('is_active');
        
        // Formatear hora a H:i:s si viene como H:i
        if(strlen($config->reminder_time) == 5) {
            $config->reminder_time .= ':00';
        }
        
        $config->save();

        return redirect()->route('whatsapp.index')->with('success', 'Configuración de WhatsApp actualizada exitosamente.');
    }
}
