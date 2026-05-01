<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = env('GROQ_API_KEY');

$rawArticles = [
    [
        'id' => 1,
        'title' => 'Transplant-associated bilateral fibrinous central serous chorioretinopathy',
        'description' => 'A case of bilateral fibrinous central serous chorioretinopathy in a transplant patient.'
    ]
];

$promptText = "Eres un experto oftalmólogo. A continuación hay una lista de noticias en JSON. TRADUCE el título al Español. Luego, escribe un `short_summary` de 2 oraciones máximo. Por último, escribe un `extended_text` de 3 a 4 párrafos donde analices detalladamente la noticia, aportando contexto médico para que sea interesante de leer.\n\nDEVUELVE ÚNICAMENTE UN OBJETO JSON con la siguiente estructura exacta:\n{\"articles\": [{\"id\": ID, \"title\": \"Título en Español\", \"short_summary\": \"Resumen de 2 líneas\", \"extended_text\": \"Análisis largo de 3 a 4 párrafos elaborando sobre el tema de la noticia con contexto médico oftalmológico.\"}]}\n\nNoticias:\n" . json_encode($rawArticles);

$groqRes = Http::withToken($apiKey)->timeout(60)->post("https://api.groq.com/openai/v1/chat/completions", [
    "model" => "llama-3.3-70b-versatile",
    "response_format" => ["type" => "json_object"],
    "messages" => [
        ["role" => "system", "content" => "Solo puedes responder con JSON puro, sin ningún otro texto o markdown."],
        ["role" => "user", "content" => $promptText]
    ],
    "temperature" => 0.3
]);

if ($groqRes->successful()) {
    $content = $groqRes->json()['choices'][0]['message']['content'] ?? '{}';
    echo "SUCCESS:\n";
    echo $content . "\n";
} else {
    echo "ERROR: " . $groqRes->status() . "\n" . $groqRes->body();
}
