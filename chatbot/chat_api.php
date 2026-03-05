<?php
header('Content-Type: application/json');

$GROQ_API_KEY = "YOUR_API_KEY";
$GROQ_URL = "https://api.groq.com/openai/v1/chat/completions";

/* -----------------------------
CHECK REQUEST METHOD
------------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error"=>"POST request required"]);
    exit;
}

/* -----------------------------
READ INPUT
------------------------------*/
$inputRaw = file_get_contents("php://input");
$input = json_decode($inputRaw, true);

if (!$input || !isset($input["messages"])) {
    echo json_encode([
        "error"=>"Invalid request format",
        "raw_input"=>$inputRaw
    ]);
    exit;
}

/* -----------------------------
GET LAST MESSAGE
------------------------------*/
$last = end($input["messages"]);
$userMessage = $last["content"] ?? "";

if (!$userMessage) {
    echo json_encode(["error"=>"Message empty"]);
    exit;
}

/* -----------------------------
BUILD GROQ REQUEST
------------------------------*/
$data = [
    "model" => "llama-3.1-8b-instant",
    "messages" => [
        [
            "role" => "system",
            "content" => "You are an AI assistant for a chemical laboratory inventory system."
        ],
        [
            "role" => "user",
            "content" => $userMessage
        ]
    ],
    "temperature" => 0.7
];

/* -----------------------------
CALL GROQ API
------------------------------*/
$ch = curl_init($GROQ_URL);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer ".$GROQ_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode([
        "error"=>"cURL error",
        "details"=>curl_error($ch)
    ]);
    exit;
}

$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

/* -----------------------------
CHECK STATUS
------------------------------*/
if ($status !== 200) {
    echo json_encode([
        "error"=>"Groq API HTTP error",
        "status"=>$status,
        "response"=>$response
    ]);
    exit;
}

/* -----------------------------
PARSE RESPONSE
------------------------------*/
$result = json_decode($response,true);

if (!$result) {
    echo json_encode([
        "error"=>"Invalid JSON from Groq",
        "raw"=>$response
    ]);
    exit;
}

$reply = $result["choices"][0]["message"]["content"] ?? null;

if (!$reply) {
    echo json_encode([
        "error"=>"AI returned empty message",
        "debug"=>$result
    ]);
    exit;
}

/* -----------------------------
RETURN SUCCESS
------------------------------*/
echo json_encode([
    "reply"=>$reply
]);