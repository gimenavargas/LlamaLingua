<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$api_key = "4b3b890b3d2e4c06a892674f5660f7a6";
$base_url = "https://api.aimlapi.com/v1/chat/completions";
$model = "meta-llama/Llama-Vision-Free";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(["error" => "No image file provided"]);
        exit;
    }

    $image_tmp = $_FILES['image']['tmp_name'];
    $image_data = file_get_contents($image_tmp);
    $image_base64 = base64_encode($image_data);

    $payload = [
        "model" => $model,
        "messages" => [
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "text",
                        "text" => "What is the main object or color in this image? Respond ONLY with the object name or color in English. Do not describe or explain. If no object or color is visible, respond with \"None\"."
                    ],
                    [
                        "type" => "image_url",
                        "image_url" => [
                            "url" => "data:image/jpeg;base64," . $image_base64
                        ]
                    ]
                ]
            ]
        ],
        "max_tokens" => 20,
        "temperature" => 0.0
    ];

    $headers = [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
    ];

    $ch = curl_init($base_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 || $http_code === 201) {
        $result = json_decode($response, true);
        $raw_response = trim($result["choices"][0]["message"]["content"]);

        // Limpia prefijos como "**Answer:**", "Answer:", etc.
        $cleaned = preg_replace('/^\*{0,2}Answer:\*{0,2}\s*/i', '', $raw_response);
        $cleaned = strip_tags($cleaned); // por si acaso contiene HTML

        echo json_encode(["object" => $cleaned]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Object detection failed", "details" => $response]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Invalid request method"]);
}
?>
