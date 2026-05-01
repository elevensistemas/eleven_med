<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\NewsController;

$ctrl = new NewsController();
$apiKey = env('GROQ_API_KEY');

$rawArticles = [
    ['id' => 1, 'title' => 'Bridge to vision', 'description' => 'A test description about colobomas in English']
];

$promptText = "Eres un asistente médico experto. A continuación te doy una lista de artículos médicos oftalmológicos en formato JSON. Para cada artículo, TRADUCE el título al Español y redacta un resumen corto en Español (2 a 3 oraciones como máximo) enfocado en lo más relevante para un médico oftalmólogo.\n\nDEVUELVE ÚNICAMENTE UN ARRAY JSON VÁLIDO con la estructura: [{\"id\": ID, \"title\": \"Título en Español\", \"summary\": \"Resumen en Español\"}]. NO incluyas markdown, texto introductorio, ni comillas invertidas. Solo el JSON puro.\n\nArtículos:\n" . json_encode($rawArticles);

$groqRes = Http::withToken($apiKey)->timeout(40)->post("https://api.groq.com/openai/v1/chat/completions", [
    "model" => "llama-3.3-70b-versatile",
    "messages" => [
        ["role" => "system", "content" => "Solo puedes responder con código JSON puro, sin ningún otro texto."],
        ["role" => "user", "content" => $promptText]
    ],
    "temperature" => 0.3
]);

if ($groqRes->successful()) {
    $content = $groqRes->json()['choices'][0]['message']['content'] ?? '[]';
    echo "RAW:\n$content\n\n";
    $content = preg_replace('/```json/i', '', $content);
    $content = preg_replace('/```/i', '', $content);
    var_dump(json_decode(trim($content), true));
} else {
    echo "ERROR: " . $groqRes->status() . " " . $groqRes->body();
}
