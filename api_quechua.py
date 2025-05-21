from flask import Flask, request, jsonify
from flask_cors import CORS
import requests
import base64
import string

app = Flask(__name__)
CORS(app)  # Habilita CORS para todas las rutas y orígenes

# API externa (AIMLAPI)
api_key = "4b3b890b3d2e4c06a892674f5660f7a6"
base_url = "https://api.aimlapi.com/v1/chat/completions"
model = "meta-llama/Llama-Vision-Free"

# Diccionario de traducción al quechua huanuqueño
translation_dict = {
    # 🐾 Animales (Uywakuna)
    "dog": "Allqu", "cat": "Michi", "fox": "Atuq", "pig": "Kuchi", "cow": "Waka",
    "hen": "Wallpa", "rooster": "K’anka", "chick": "Chiwchi", "dove": "Urpi", "condor": "Kuntur",
    "bear": "Ukuku", "monkey": "K’usillu", "rabbit": "Quwi", "turtle": "Charapa", "fly": "Ch’uspi",
    "butterfly": "Pilpintu", "snail": "Ch’uru", "fish": "Challwa", "llama": "Llama", "ostrich": "Suri",

    # 🏠 Objetos (Imakuna)
    "computer": "Kuyuchiq raphra", "laptop": "Millkana raphra", "cellphone": "Simi ch'iriska",
    "phone": "T'ikuq simi", "notebook": "Qillqana p'anqa", "tablet": "Lliklla simi", "keyboard": "T’inki",
    "mouse": "Ch’ipu apachiq", "usb": "Ñit’isqa llaqta", "book": "P’anqa", "screen": "Qhawana machina",
    "television": "Wachana simi", "printer": "Ranti simi", "scanner": "Chani machina", "router": "Saywasapa simi",
    "internet": "Llika simi", "stylus": "Q'illqa simi", "desk": "Wasi qillqana", "office accessories": "Llapa ima qillqanapaq",

    # 🎨 Colores (Llinphikuna)
    "red": "Puka", "white": "Yuraq", "yellow": "Q’illu", "green": "Q’omer", "blue": "Anqas",
    "purple": "Kulli", "brown": "Ch’umpi", "black": "Yana", "pink": "Puka yuraq", "turquoise": "Siwar",
    "mustard": "K'illu yana", "burgundy": "Yana puka", "beige": "Yuraq ch’umpi", "gray": "Yuraq yana",
    "dark green": "Q’omer yana", "cream": "Panti", "violet": "Puka anqas", "silver": "Yuraq qullqi",
    "gold": "Quri",

    # 🔢 Números (Yupaykuna)
    "one": "Huk", "two": "Iskay", "three": "Kinsa", "four": "Tawa", "five": "Pisqa",
    "six": "Soqta", "seven": "Qanchis", "eight": "Pusaq", "nine": "Isqon", "ten": "Chunka",
    "eleven": "Chunka hukniyuq", "twelve": "Chunka iskayniyuq", "thirteen": "Chunka kinsayuq",
    "fourteen": "Chunka tawayuq", "fifteen": "Chunka písqayuq", "sixteen": "Chunka soqtañiyuq",
    "seventeen": "Chunka qanchisniyuq", "eighteen": "Chunka pusaqniyuq", "nineteen": "Chunka isqonniyuq",
    "twenty": "Iskay chunka",

    # 🍎 Frutas y Verduras (Mikhuykuna)
    "potato": "Papa", "sweet potato": "Kamuti", "cassava": "Yuka", "oca": "Uqa", "green bean": "Chaqallu",
    "bean": "Jawas", "pea": "Alwirja", "corn": "Sara", "corn cob": "Chuqllu", "quinoa": "Kinuwa",
    "tomato": "Tumati", "onion": "Siwulla", "chili": "Uchu", "chuño": "Ch’uñu", "egg": "Runtu",
    "avocado": "Palta", "apple": "Manzana", "banana": "Bananu", "lemon": "Limu", "orange": "Naranxa"
}

def detect_object_from_image_bytes(image_bytes):
    image_base64 = base64.b64encode(image_bytes).decode("utf-8")
    headers = {
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json"
    }

    payload = {
        "model": model,
        "messages": [
            {
                "role": "user",
                "content": [
                    {
                        "type": "text",
                        "text": "What object do you see in this image? Answer only with the object name in English."
                    },
                    {
                        "type": "image_url",
                        "image_url": {
                            "url": f"data:image/jpeg;base64,{image_base64}"
                        }
                    }
                ]
            }
        ],
        "max_tokens": 20
    }

    response = requests.post(base_url, headers=headers, json=payload)
    if response.status_code in [200, 201]:
        result = response.json()
        return result["choices"][0]["message"]["content"].strip().lower()
    else:
        print(f"Error from API: {response.status_code} - {response.text}")
        return None

@app.route("/detect", methods=["POST"])
def detect():
    if 'image' not in request.files:
        return jsonify({"error": "No image file provided"}), 400

    image_file = request.files['image']
    image_bytes = image_file.read()
    object_name_en = detect_object_from_image_bytes(image_bytes)

    print(f"Detectado (inglés): {object_name_en}")  # Para debug

    if object_name_en:
        clean_name = object_name_en.strip().lower()
        # Eliminar signos de puntuación comunes, incluyendo el punto
        clean_name = clean_name.translate(str.maketrans('', '', string.punctuation))
        print(f"Nombre limpio: {clean_name}")  # Debug para ver el nombre sin signos
        object_name_quechua = translation_dict.get(clean_name, "Manan atinqachu rikuyta")
        return jsonify({"objeto": object_name_quechua})
    else:
        return jsonify({"error": "Object detection failed"}), 500

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
