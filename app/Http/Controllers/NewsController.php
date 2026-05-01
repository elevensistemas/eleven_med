<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\FavoriteNews;
use Carbon\Carbon;

class NewsController extends Controller
{
    public function index()
    {
        $news = Cache::remember('ophthalmology_news_ai_summary_v6', 3600 * 12, function () {
            $feeds = [
                ['url' => 'https://news.google.com/rss/search?q=oftalmología+argentina+cataratas+OR+miopía+OR+cirugía+ojos+OR+tecnologías&hl=es-419&gl=AR&ceid=AR:es-419', 'source' => 'Google News AR', 'limit' => 3],
                ['url' => 'https://www.nature.com/eye.rss', 'source' => 'Nature Eye', 'limit' => 1],
                ['url' => 'https://www.who.int/rss-feeds/news-english.xml', 'source' => 'WHO News', 'limit' => 1],
            ];

            $rawArticles = [];
            $idCounter = 1;
            
            $fallbacks = [
                'https://images.unsplash.com/photo-1579684385127-1ef15d508118?q=80&w=600&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1516549655169-df83a0774514?q=80&w=600&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1551076805-e1869033e561?q=80&w=600&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1584362917165-526a968579e8?q=80&w=600&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1530497610245-94d3c16cda28?q=80&w=600&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?q=80&w=600&auto=format&fit=crop',
                'https://images.unsplash.com/photo-1581093458791-9f3c3900df4b?q=80&w=600&auto=format&fit=crop',
            ];

            foreach ($feeds as $feed) {
                try {
                    $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ])->timeout(15)->get($feed['url']);
                    
                    if ($response->successful()) {
                        $xml = simplexml_load_string($response->body());
                        if ($xml) {
                            $items = isset($xml->channel->item) ? $xml->channel->item : (isset($xml->item) ? $xml->item : []);
                            $count = 0;
                            foreach ($items as $item) {
                                if ($count >= $feed['limit']) break;
                                
                                $dateStr = (string)$item->pubDate;
                                if (empty($dateStr)) {
                                    $dc = $item->children('http://purl.org/dc/elements/1.1/');
                                    $dateStr = (string)$dc->date;
                                }
                                if (empty($dateStr)) $dateStr = 'now';

                                $imageUrl = null;
                                if (isset($item->enclosure) && isset($item->enclosure['url'])) {
                                    $imageUrl = (string)$item->enclosure['url'];
                                } elseif (isset($item->children('media', true)->content)) {
                                    $imageUrl = (string)$item->children('media', true)->content->attributes()->url;
                                }
                                
                                if (empty($imageUrl)) {
                                    $imageUrl = $fallbacks[$idCounter % count($fallbacks)];
                                }

                                $rawArticles[] = [
                                    'id' => $idCounter++,
                                    'source' => $feed['source'],
                                    'title' => strip_tags((string)$item->title),
                                    'link' => (string)$item->link,
                                    'image_url' => $imageUrl,
                                    'description' => strip_tags(html_entity_decode((string)$item->description)),
                                    'extended_text' => '',
                                    'pub_date' => Carbon::parse($dateStr)->format('d/m/Y H:i'),
                                ];
                                $count++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error fetching RSS: " . $e->getMessage());
                }
            }

            if (empty($rawArticles)) return [];

            // AI Summarization with Groq
            $apiKey = env('GROQ_API_KEY');
            if (!empty($apiKey) && $apiKey !== 'tu_clave_aqui') {
                $promptText = "Eres un experto oftalmólogo. A continuación hay una lista de noticias en JSON. TRADUCE el título al Español. Luego, escribe un `short_summary` de 2 oraciones máximo. Por último, escribe un `extended_text` de 3 a 4 párrafos donde analices detalladamente la noticia, aportando contexto médico para que sea interesante de leer.\n\nDEVUELVE ÚNICAMENTE UN OBJETO JSON con la siguiente estructura exacta:\n{\"articles\": [{\"id\": ID, \"title\": \"Título en Español\", \"short_summary\": \"Resumen de 2 líneas\", \"extended_text\": \"Análisis largo de 3 a 4 párrafos elaborando sobre el tema de la noticia con contexto médico oftalmológico.\"}]}\n\nNoticias:\n" . json_encode($rawArticles);

                try {
                    $groqRes = Http::withToken($apiKey)->timeout(60)->post("https://api.groq.com/openai/v1/chat/completions", [
                        "model" => "llama-3.1-8b-instant",
                        "response_format" => ["type" => "json_object"],
                        "messages" => [
                            ["role" => "system", "content" => "Solo puedes responder con JSON puro, sin ningún otro texto o markdown."],
                            ["role" => "user", "content" => $promptText]
                        ],
                        "temperature" => 0.3
                    ]);

                    if ($groqRes->successful()) {
                        $content = $groqRes->json()['choices'][0]['message']['content'] ?? '{}';
                        $data = json_decode(trim($content), true);

                        if (isset($data['articles']) && is_array($data['articles'])) {
                            $summaryMap = collect($data['articles'])->keyBy('id');
                            foreach ($rawArticles as &$article) {
                                if (isset($summaryMap[$article['id']])) {
                                    $article['title'] = $summaryMap[$article['id']]['title'] ?? $article['title'];
                                    $article['description'] = $summaryMap[$article['id']]['short_summary'] ?? $article['description'];
                                    $article['extended_text'] = $summaryMap[$article['id']]['extended_text'] ?? $article['description'];
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Groq summarization failed: " . $e->getMessage());
                }
            }

            return $rawArticles;
        });

        $userId = Auth::id();
        $favoritesLinks = FavoriteNews::where('user_id', $userId)->pluck('link')->toArray();

        return view('news.index', compact('news', 'favoritesLinks'));
    }

    public function favorites()
    {
        $favorites = FavoriteNews::where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
        // Extract links so the view can use the exact same toggle logic
        $favoritesLinks = $favorites->pluck('link')->toArray();
        return view('news.favorites', compact('favorites', 'favoritesLinks'));
    }

    public function refresh()
    {
        Cache::forget('ophthalmology_news_ai_summary_v6');
        return redirect()->route('news.index')->with('success', 'Buscando nuevas noticias...');
    }

    public function saveFavorite(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'link' => 'required|url',
            'image_url' => 'nullable|url',
            'description' => 'nullable|string',
            'extended_text' => 'nullable|string',
            'source' => 'nullable|string',
            'pub_date' => 'nullable|string'
        ]);

        $favorite = FavoriteNews::firstOrCreate(
            ['user_id' => Auth::id(), 'link' => $request->link],
            [
                'title' => substr($request->title, 0, 500),
                'image_url' => $request->image_url,
                'description' => $request->description,
                'extended_text' => $request->extended_text,
                'source' => $request->source,
                'pub_date' => $request->pub_date
            ]
        );

        return response()->json(['success' => true]);
    }

    public function removeFavorite(Request $request)
    {
        FavoriteNews::where('user_id', Auth::id())
            ->where('link', $request->link)
            ->delete();

        return response()->json(['success' => true]);
    }
}
