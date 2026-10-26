<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhatsappConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappConfigController extends Controller
{
    /**
     * Get the microservice URL and API key from DB or .env
     */
    private function getServiceConfig()
    {
        $config = WhatsappConfig::first();
        $url = rtrim($config->api_url ?? config('services.whatsapp.url') ?? env('WHATSAPP_SERVICE_URL', 'http://127.0.0.1:3001'), '/');
        $key = $config->token ?? config('services.whatsapp.key') ?? env('WHATSAPP_SERVICE_KEY', 'eleven_med_secret_wa_key_2026');
        return [$url, $key];
    }

    public function index()
    {
        $config = WhatsappConfig::first();
        if (!$config) {
            $config = new WhatsappConfig();
            $config->reminder_days_before = 1;
            $config->reminder_time = '08:00:00';
            $config->message_template = "Hola {nombre}, te recordamos tu turno en Eleven Med para el día {fecha_turno} a las {hora_turno} con el profesional {medico}. Por favor, confirma asistencia.";
        }

        list($url, $key) = $this->getServiceConfig();

        $serviceStatus = 'disconnected';
        $connectedPhone = null;

        try {
            $response = Http::withHeaders(['x-api-key' => $key])
                ->timeout(2)
                ->get("{$url}/status");

            if ($response->successful()) {
                $data = $response->json();
                $serviceStatus = $data['status'] ?? 'disconnected';
                $connectedPhone = $data['phone'] ?? null;
            } else {
                $serviceStatus = 'offline';
            }
        } catch (\Exception $e) {
            $serviceStatus = 'offline';
        }

        return view('whatsapp.index', compact('config', 'serviceStatus', 'connectedPhone'));
    }

    public function status()
    {
        list($url, $key) = $this->getServiceConfig();

        try {
            $response = Http::withHeaders(['x-api-key' => $key])
                ->timeout(3)
                ->get("{$url}/status");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'offline',
                'message' => 'El servicio de WhatsApp respondió con error (HTTP ' . $response->status() . ').'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'offline',
                'message' => 'Servicio de WhatsApp desconectado o iniciando.'
            ]);
        }
    }

    public function qr()
    {
        list($url, $key) = $this->getServiceConfig();

        try {
            $response = Http::withHeaders(['x-api-key' => $key])
                ->timeout(6)
                ->get("{$url}/qr");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json([
                'status' => 'offline',
                'error' => 'No se pudo obtener el código QR del servicio.'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'offline',
                'error' => 'El servicio de WhatsApp no se encuentra en línea.'
            ], 503);
        }
    }

    public function disconnect()
    {
        list($url, $key) = $this->getServiceConfig();

        try {
            $response = Http::withHeaders(['x-api-key' => $key])
                ->timeout(5)
                ->post("{$url}/disconnect");

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'offline',
                'error' => 'No se pudo contactar el servicio de WhatsApp para desconectar.'
            ], 500);
        }
    }

    public function testSend(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        list($url, $key) = $this->getServiceConfig();

        try {
            $response = Http::withHeaders(['x-api-key' => $key])
                ->timeout(10)
                ->post("{$url}/send", [
                    'phone' => $request->phone,
                    'message' => $request->message
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json(),
                    'message' => '¡Mensaje de prueba enviado con éxito!'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $response->json('error') ?? 'Error al enviar mensaje'
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'No se pudo conectar con el servidor de WhatsApp: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'reminder_days_before' => 'required|integer|min:1|max:30',
            'reminder_time' => 'required|date_format:H:i',
            'message_template' => 'nullable|string',
            'api_url' => 'nullable|url',
        ]);

        $config = WhatsappConfig::first() ?? new WhatsappConfig();
        $config->reminder_days_before = $request->reminder_days_before;
        $config->reminder_time = $request->reminder_time;
        $config->message_template = $request->message_template;
        $config->is_active = $request->has('is_active');

        if ($request->filled('api_url')) {
            $config->api_url = $request->api_url;
        }

        // Formatear hora a H:i:s si viene como H:i
        if (strlen($config->reminder_time) == 5) {
            $config->reminder_time .= ':00';
        }

        $config->save();

        return redirect()->route('whatsapp.index')->with('success', 'Configuración de WhatsApp guardada exitosamente.');
    }
}
