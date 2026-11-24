"""Model card: the record that decides whether an installed ranker may be used.

ml/learning.py writes the card together with the model files, and only for a model that passed the
documented rule (spec §9). The service uses a model only when the card exists, passed, matches the files on
disk (SHA-256), and was not built from synthetic test labels, unless ML_ALLOW_SYNTHETIC_MODEL=1 allows
it.
"""
import hashlib
import json
import os

CARD_NAME = "model_card.json"
MODEL_FILE = "judgment_model.joblib"
SCALER_FILE = "scaler.joblib"


def sha256(path: str) -> str:
    digest = hashlib.sha256()
    with open(path, "rb") as handle:
        for chunk in iter(lambda: handle.read(65536), b""):
            digest.update(chunk)
    return digest.hexdigest()


def check(folder: str, allow_synthetic: bool = False):
    """Return (card, None) when the model may be used, else (card_or_None, reason)."""
    model_path, scaler_path = os.path.join(folder, MODEL_FILE), os.path.join(folder, SCALER_FILE)
    card_path = os.path.join(folder, CARD_NAME)
    if not (os.path.exists(model_path) and os.path.exists(scaler_path)):
        return None, "No trained model is installed."
    if not os.path.exists(card_path):
        return None, "The installed model has no evaluation card."
    with open(card_path) as handle:
        card = json.load(handle)
    if not card.get("passed"):
        return card, "The installed model did not pass evaluation."
    if card.get("model_sha256") != sha256(model_path) or card.get("scaler_sha256") != sha256(scaler_path):
        return card, "The model files do not match their evaluation card."
    if card.get("synthetic") and not allow_synthetic:
        return card, "The model was built from test data and cannot be used for real placements."
    return card, None
