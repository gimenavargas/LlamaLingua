from flask import Flask, request, jsonify
from flask_cors import CORS
import requests
import base64

app = Flask(__name__)
CORS(app)  # Habilita CORS para todas las rutas y orígenes

# API externa (AIMLAPI)
api_key = "4b3b890b3d2e4c06a892674f5660f7a6"
base_url = "https://api.aimlapi.com/v1/chat/completions"
model = "meta-llama/Llama-Vision-Free"

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
        return result["choices"][0]["message"]["content"].strip()
    else:
        print(f"Error from API: {response.status_code} - {response.text}")
        return None

@app.route("/detect", methods=["POST"])
def detect():
    if 'image' not in request.files:
        return jsonify({"error": "No image file provided"}), 400

    image_file = request.files['image']
    image_bytes = image_file.read()
    object_name = detect_object_from_image_bytes(image_bytes)

    if object_name:
        return jsonify({"object": object_name})
    else:
        return jsonify({"error": "Object detection failed"}), 500

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
