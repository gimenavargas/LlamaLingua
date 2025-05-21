<?php
// Permitir acceso desde cualquier origen
header("Access-Control-Allow-Origin: *");

// Responder rápido a preflight OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    exit(0);
}

// Indicar que el contenido es JSON
header("Content-Type: application/json");

// API Key y configuración para el modelo
$apiKey = "4b3b890b3d2e4c06a892674f5660f7a6";
$baseUrl = "https://api.aimlapi.com/v1/chat/completions";
$model = "meta-llama/Llama-Vision-Free";

// Diccionario de traducción al quechua huanuqueño
$translationDict = [
    // 🐾 Animales
    "dog" => "Allqu", "cat" => "Michi", "fox" => "Atuq", "pig" => "Kuchi", "cow" => "Waka",
    "hen" => "Wallpa", "rooster" => "K’anka", "chick" => "Chiwchi", "dove" => "Urpi", "condor" => "Kuntur",
    "bear" => "Ukuku", "monkey" => "K’usillu", "rabbit" => "Quwi", "turtle" => "Charapa", "fly" => "Ch’uspi",
    "butterfly" => "Pilpintu", "snail" => "Ch’uru", "fish" => "Challwa", "llama" => "Llama", "ostrich" => "Suri",

    // 🏠 Objetos
    "computer" => "Kuyuchiq raphra", "laptop" => "Millkana raphra", "cellphone" => "Simi ch'iriska",
    "phone" => "T'ikuq simi", "notebook" => "Qillqana p'anqa", "tablet" => "Lliklla simi", "keyboard" => "T’inki",
    "mouse" => "Ch’ipu apachiq", "usb" => "Ñit’isqa llaqta", "book" => "P’anqa", "screen" => "Qhawana machina",
    "television" => "Wachana simi", "printer" => "Ranti simi", "scanner" => "Chani machina", "router" => "Saywasapa simi",
    "internet" => "Llika simi", "stylus" => "Q'illqa simi", "desk" => "Wasi qillqana", "office accessories" => "Llapa ima qillqanapaq",

    // 🎨 Colores
    "red" => "Puka", "white" => "Yuraq", "yellow" => "Q’illu", "green" => "Q’omer", "blue" => "Anqas",
    "purple" => "Kulli", "brown" => "Ch’umpi", "black" => "Yana", "pink" => "Puka yuraq", "turquoise" => "Siwar",
    "mustard" => "K'illu yana", "burgundy" => "Yana puka", "beige" => "Yuraq ch’umpi", "gray" => "Yuraq yana",
    "dark green" => "Q’omer yana", "cream" => "Panti", "violet" => "Puka anqas", "silver" => "Yuraq qullqi",
    "gold" => "Quri",

    // 🔢 Números
    "one" => "Huk", "two" => "Iskay", "three" => "Kinsa", "four" => "Tawa", "five" => "Pisqa",
    "six" => "Soqta", "seven" => "Qanchis", "eight" => "Pusaq", "nine" => "Isqon", "ten" => "Chunka",
    "eleven" => "Chunka hukniyuq", "twelve" => "Chunka iskayniyuq", "thirteen" => "Chunka kinsayuq",
    "fourteen" => "Chunka tawayuq", "fifteen" => "Chunka písqayuq", "sixteen" => "Chunka soqtañiyuq",
    "seventeen" => "Chunka qanchisniyuq", "eighteen" => "Chunka pusaqniyuq", "nineteen" => "Chunka isqonniyuq",
    "twenty" => "Iskay chunka",

    // 🍎 Frutas y Verduras
    "potato" => "Papa", "sweet potato" => "Kamuti", "cassava" => "Yuka", "oca" => "Uqa", "green bean" => "Chaqallu",
    "bean" => "Jawas", "pea" => "Alwirja", "corn" => "Sara", "corn cob" => "Chuqllu", "quinoa" => "Kinuwa",
    "tomato" => "Tumati", "onion" => "Siwulla", "chili" => "Uchu", "chuño" => "Ch’uñu", "egg" => "Runtu",
    "avocado" => "Palta", "apple" => "Manzana", "banana" => "Bananu", "lemon" => "Limu", "orange" => "Naranxa"
];

// Función para detectar objeto o color en la imagen usando la API externa
function detectObjectFromImageBytes($imageBytes, $apiKey, $baseUrl, $model) {
    $imageBase64 = base64_encode($imageBytes);

    $payload = json_encode([
        "model" => $model,
        "messages" => [
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "text",
                        "text" => "What object or color do you see in this image? Answer ONLY with one single English word (e.g., 'cat', 'red'). Do not use full sentences."
                    ],
                    [
                        "type" => "image_url",
                        "image_url" => [
                            "url" => "data:image/jpeg;base64," . $imageBase64
                        ]
                    ]
                ]
            ]
        ],
        "max_tokens" => 20
    ]);

    $ch = curl_init($baseUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($status === 200 || $status === 201) {
        $data = json_decode($response, true);
        $rawResponse = $data['choices'][0]['message']['content'];
        error_log("🧠 Respuesta del modelo: " . $rawResponse);

        return strtolower(trim($rawResponse));
    }

    error_log("❌ Error HTTP $status: $response");
    return null;
}

// Solo aceptar método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["error" => "No se proporcionó una imagen válida."]);
        exit;
    }

    $imageBytes = file_get_contents($_FILES['image']['tmp_name']);
    $objectNameEn = detectObjectFromImageBytes($imageBytes, $apiKey, $baseUrl, $model);

    if ($objectNameEn) {
        // Limpiar respuesta
        $cleanName = strtolower(trim($objectNameEn));
        $cleanName = preg_replace('/[^\w\s]/', '', $cleanName); // Elimina signos
        $cleanName = explode(" ", $cleanName)[0]; // Toma solo la primera palabra

        // Traducir si está en el diccionario
        $translation = $translationDict[$cleanName] ?? "Manan atinqachu rikuyta";
        echo json_encode(["objeto" => $translation]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "No se pudo detectar el objeto en la imagen."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido. Usa POST."]);
}
?>
