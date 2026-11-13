<?php
/**
 * api/chat.php  -  server-side bridge between the website and Gemini.
 * The API key stays here on the server and is NEVER sent to the browser.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// ---- SETTINGS -------------------------------------------------------------
$API_KEY = getenv('GEMINI_API_KEY') ?: 'PASTE_YOUR_GEMINI_API_KEY_HERE';
$MODEL   = 'gemini-2.5-flash';   // change to the model you use

$SYSTEM_PROMPT =
    "You are the AgriTrack Assistant, a friendly helper inside the AgriTrack website. " .
    "AgriTrack helps Filipino farmers monitor crops, check weather, manage harvests, find market locations, and get farming recommendations. " .
    "Answer questions about farming, crops, pests, planting and harvest timing, weather planning, and how to use AgriTrack. " .
    "Keep answers short, practical and easy to understand. Use simple English, and reply in Filipino/Tagalog if the user writes in Filipino. " .
    "If you are unsure, say so instead of guessing. Never ask for or repeat passwords or personal data.";
// ---------------------------------------------------------------------------

function fail($code, $message) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Method not allowed.');
}
if ($API_KEY === '' || $API_KEY === 'PASTE_YOUR_GEMINI_API_KEY_HERE') {
    fail(500, 'The Gemini API key is not set on the server yet.');
}

// Small rate limit: 12 messages per minute per visitor
session_start();
$now = time();
$hits = array_filter($_SESSION['chat_hits'] ?? [], function ($t) use ($now) { return $t > $now - 60; });
if (count($hits) >= 12) {
    session_write_close();
    fail(429, 'Too many messages. Please wait a moment and try again.');
}
$hits[] = $now;
$_SESSION['chat_hits'] = array_values($hits);
session_write_close();

// Read and clean the conversation
$input    = json_decode(file_get_contents('php://input'), true);
$messages = is_array($input['messages'] ?? null) ? $input['messages'] : [];
$messages = array_slice($messages, -12);   // last 12 messages only

$contents = [];
foreach ($messages as $m) {
    $role = (($m['role'] ?? '') === 'model') ? 'model' : 'user';
    $text = trim((string)($m['text'] ?? ''));
    if ($text === '') continue;
    $text = mb_substr($text, 0, 2000);
    if (!$contents && $role === 'model') continue;   // must start with a user message
    $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
}
if (!$contents) {
    fail(400, 'Please type a message.');
}

$payload = [
    'systemInstruction' => ['parts' => [['text' => $SYSTEM_PROMPT]]],
    'contents'          => $contents,
    'generationConfig'  => ['temperature' => 0.6, 'maxOutputTokens' => 800],
];

$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($MODEL) . ':generateContent';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $API_KEY,
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
]);
$response = curl_exec($ch);
$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    error_log('Gemini cURL error: ' . $curlErr);
    fail(502, 'Could not reach the AI service. Please try again later.');
}

$data = json_decode($response, true);

if ($status !== 200) {
    error_log('Gemini API error (' . $status . '): ' . $response);
    fail(502, 'The AI service returned an error. Please try again later.');
}

$reply = '';
foreach (($data['candidates'][0]['content']['parts'] ?? []) as $part) {
    $reply .= $part['text'] ?? '';
}
$reply = trim($reply);

if ($reply === '') {
    $reply = "Sorry, I couldn't come up with an answer for that. Could you rephrase it?";
}

echo json_encode(['reply' => $reply]);