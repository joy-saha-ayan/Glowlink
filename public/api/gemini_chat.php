<?php
require_once __DIR__ . '/../../utils/env.php'; // load .env
require_once __DIR__ . '/../../includes/GeminiClient.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? '';
if (trim($message) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing message']);
    exit;
}

$client = new \Includes\GeminiClient();
$response = $client->chat($message);

if ($response === null) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to get response from Gemini API']);
    exit;
}

// Return OpenAI‑like JSON shape for the front‑end code
echo json_encode([
    'choices' => [
        [
            'message' => [
                'role' => 'assistant',
                'content' => $response
            ]
        ]
    ]
]);
?>