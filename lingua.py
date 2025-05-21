import requests
import base64
import pyttsx3

# Datos de la API
api_key = "4b3b890b3d2e4c06a892674f5660f7a6"
base_url = "https://api.aimlapi.com/v1/chat/completions"
model = "meta-llama/Llama-Vision-Free"

# Convierte imagen a base64
def encode_image_to_base64(image_path):
    with open(image_path, "rb") as image_file:
        return base64.b64encode(image_file.read()).decode("utf-8")

# Texto a voz
def speak(text):
    engine = pyttsx3.init()
    engine.say(text)
    engine.runAndWait()

# Enviar imagen a la API y obtener la respuesta
def detect_object_in_image(image_path):
    image_base64 = encode_image_to_base64(image_path)

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
                        "text": "What object do you see in this image? Answer with only the object name in English."
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

    if response.status_code in [200, 201]:  # <-- Cambiado aquí
        result = response.json()
        object_name = result["choices"][0]["message"]["content"].strip()
        return object_name
    else:
        print("Error:", response.status_code, response.text)
        return None

# Programa principal
def main():
    image_path = "imagen.jpg"  # Cambia por la ruta de tu imagen
    print("Analizando imagen...")
    object_name = detect_object_in_image(image_path)

    if object_name:
        print(object_name)
        speak(object_name)

    else:
        speak("There was an error detecting the object.")

if __name__ == "__main__":
    main()
