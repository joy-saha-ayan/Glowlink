<?php
namespace Includes;

/**
 * Simple Gemini AI client using built‑in cURL.
 * It reads the API key from the .env file via utils/env.php (populates $_ENV).
 */
class GeminiClient
{
    private $lastError = null;
    private $apiKey;
    // Default model – you can change this to another Gemini model if desired
    private $model = 'gemini-2.5-flash';

    public function __construct()
    {
        // utils/env.php has already been required by the endpoint, so $_ENV is populated.
        // Fallback to the Grok config if for some reason .env was not loaded.
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        if (!$this->apiKey) {
            // As a safety net, try to read from the Grok config (which only contains GROK_API_KEY)
            $grokConfig = require __DIR__ . '/../config/grok.php';
            $this->apiKey = $grokConfig['api_key'] ?? null;
        }
    }

    /**
     * Send a prompt to Gemini and return the assistant reply.
     *
     * @param string $prompt The user's message
     * @return string|null   The assistant response or null on error
     */
    public function chat(string $prompt): ?string
    {
        if (!$this->apiKey) {
            $this->lastError = 'Gemini API key not set';
            error_log($this->lastError);
            return null;
        }

        // List of models to try – primary then fallbacks
        $modelsToTry = [$this->model, 'gemini-2.5-flash-lite', 'gemini-2.0-flash'];
        foreach ($modelsToTry as $model) {
            $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key=" . $this->apiKey;

            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
            ]);

            $raw = curl_exec($ch);
            $err = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err) {
                $this->lastError = 'Gemini cURL error: ' . $err;
                error_log($this->lastError);
                return null;
            }
            if ($httpCode === 200) {
                $data = json_decode($raw, true);
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }
                $this->lastError = 'Gemini unexpected response format.';
                error_log($this->lastError . ' Raw: ' . $raw);
                return null;
            }
            // If we get a 503 (unavailable) try next model
            if ($httpCode === 503) {
                // Log and continue to next model
                $this->lastError = "Gemini model $model unavailable (503). Response: $raw";
                error_log($this->lastError);
                continue;
            }
            // For other non‑200 errors, store and abort
            $this->lastError = "Gemini HTTP $httpCode response from model $model: $raw";
            error_log($this->lastError);
            return null;
        }
        // All models exhausted
        $this->lastError = $this->lastError ?? 'All Gemini models failed.';
        return null;
    }
}
?>