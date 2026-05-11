#!/usr/bin/env python3
"""
Génération d'image à partir de texte via Hugging Face Inference Providers.

Modèle par défaut : Tongyi-MAI/Z-Image-Turbo (fiche HF, provider fal-ai)
Provider par défaut : fal-ai

Installation : pip install huggingface_hub
Usage :
    echo "Prompt" | python3 hf_image.py
    python3 hf_image.py "Astronaut riding a horse"

Variables d'environnement :
  HF_TOKEN          (obligatoire) Token HF avec permission "Inference Providers"
  HF_IMAGE_PROVIDER (optionnel)  "fal-ai" (défaut), "replicate", "hf-inference", etc.
  HF_IMAGE_MODEL    (optionnel)  Modèle, défaut: Tongyi-MAI/Z-Image-Turbo
  HF_IMAGE_STRICT_PROVIDER (optionnel) "1" pour ne pas remplacer auto/hf-inference sur Z-Image

Sortie : une ligne base64 de l'image PNG sur stdout.
"""

import base64
import os
import sys


def main() -> None:
    token = os.environ.get("HF_TOKEN", "").strip()
    if not token:
        print("HF_TOKEN manquant", file=sys.stderr)
        sys.exit(2)

    if len(sys.argv) > 1:
        prompt = " ".join(sys.argv[1:]).strip()
    else:
        prompt = sys.stdin.read().strip()

    if not prompt:
        print("Prompt vide", file=sys.stderr)
        sys.exit(2)

    try:
        from huggingface_hub import InferenceClient
    except ImportError:
        print("huggingface_hub manquant. Exécuter: pip install huggingface_hub", file=sys.stderr)
        sys.exit(2)

    provider = os.environ.get("HF_IMAGE_PROVIDER", "fal-ai").strip() or "fal-ai"
    model = os.environ.get("HF_IMAGE_MODEL", "Tongyi-MAI/Z-Image-Turbo").strip() or "Tongyi-MAI/Z-Image-Turbo"

    # Avec provider=auto, huggingface_hub choisit le 1er provider du compte HF
    # (souvent hf-inference) — incompatible avec Z-Image-Turbo sur ce routeur.
    strict = os.environ.get("HF_IMAGE_STRICT_PROVIDER", "").strip() in ("1", "true", "yes")
    if not strict and "z-image" in model.lower() and provider in ("auto", "hf-inference"):
        provider = "fal-ai"

    try:
        client = InferenceClient(provider=provider, api_key=token)
        image = client.text_to_image(prompt, model=model)
    except Exception as e:
        print(str(e), file=sys.stderr)
        sys.exit(1)

    # image est un PIL.Image
    import io
    buf = io.BytesIO()
    image.save(buf, format="PNG")
    b64 = base64.b64encode(buf.getvalue()).decode("ascii")
    print(b64)


if __name__ == "__main__":
    main()
