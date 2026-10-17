<?php

namespace App\Services\Gemini;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClient
{
    public function generateJson(string $prompt): array
    {
        $key = (string) config('services.gemini.key');

        if ($key === '') {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $model = (string) config('services.gemini.model', 'gemini-2.0-flash');
        $timeout = (int) config('services.gemini.timeout', 20);
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            $model,
        );

        try {
            $response = Http::timeout($timeout)
                ->asJson()
                ->post($url.'?key='.urlencode($key), [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Gemini request failed: '.$exception->getMessage(), 0, $exception);
        }

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON.');
        }

        return $decoded;
    }

    public function isConfigured(): bool
    {
        return (string) config('services.gemini.key') !== '';
    }
}
