"""Model card: the record that decides whether a trained ranker may be used.

Training writes ``train_meta.json`` next to the model. Evaluation writes ``model_card.json`` only when the
model passes the F1 threshold, with SHA-256 hashes of the model and scaler files, so a later failed retrain
can never reuse an earlier passing card. The service activates a model only when the card exists, passed,
matches the files on disk and was built without synthetic rows (synthetic labels are test data, not
coordinator evidence).
"""
import hashlib
import json
import os
from datetime import datetime, timezone

FEATURES = ["similarity_score", "distance_km", "capacity_at_time", "moa_active", "distance_km_missing"]
CARD_NAME = "model_card.json"
META_NAME = "train_meta.json"


def sha256(path: str) -> str:
    digest = hashlib.sha256()
    with open(path, "rb") as handle:
        for chunk in iter(lambda: handle.read(65536), b""):
            digest.update(chunk)
    return digest.hexdigest()


def synthetic_rows(df) -> int:
    return int(df["is_synthetic"].fillna(0).astype(int).sum()) if "is_synthetic" in df.columns else 0


def write_train_meta(model_path: str, df) -> dict:
    known = df.loc[df["distance_km_missing"] == 0, "distance_km"] if "distance_km_missing" in df.columns else df["distance_km"]
    fill = float(known.median()) if len(known) else 0.0
    meta = {
        "features": FEATURES,
        "distance_fill": 0.0 if fill != fill else fill,  # NaN guard
        "n_train": int(len(df)),
        "synthetic_rows_train": synthetic_rows(df),
        "trained_at": datetime.now(timezone.utc).isoformat(timespec="seconds"),
    }
    with open(os.path.join(os.path.dirname(model_path) or ".", META_NAME), "w") as handle:
        json.dump(meta, handle, indent=2)
    return meta


def write_card(model_path: str, scaler_path: str, metrics: dict, threshold: float, test_df) -> dict:
    folder = os.path.dirname(model_path) or "."
    with open(os.path.join(folder, META_NAME)) as handle:
        meta = json.load(handle)
    card = {
        **meta,
        "metrics": metrics,
        "threshold_f1": threshold,
        "passed": True,
        "n_test": int(len(test_df)),
        "synthetic_rows_test": synthetic_rows(test_df),
        "model_sha256": sha256(model_path),
        "scaler_sha256": sha256(scaler_path),
        "evaluated_at": datetime.now(timezone.utc).isoformat(timespec="seconds"),
    }
    with open(os.path.join(folder, CARD_NAME), "w") as handle:
        json.dump(card, handle, indent=2)
    return card


def remove_card(model_path: str) -> None:
    path = os.path.join(os.path.dirname(model_path) or ".", CARD_NAME)
    if os.path.exists(path):
        os.remove(path)


def check(folder: str, model_file: str, scaler_file: str, allow_synthetic: bool = False):
    """Return (card, None) when the model may be used, else (card_or_None, reason)."""
    model_path, scaler_path = os.path.join(folder, model_file), os.path.join(folder, scaler_file)
    card_path = os.path.join(folder, CARD_NAME)
    if not (os.path.exists(model_path) and os.path.exists(scaler_path)):
        return None, "No trained model is installed."
    if not os.path.exists(card_path):
        return None, "The installed model has no passing evaluation card."
    with open(card_path) as handle:
        card = json.load(handle)
    if not card.get("passed"):
        return card, "The installed model did not pass evaluation."
    if card.get("model_sha256") != sha256(model_path) or card.get("scaler_sha256") != sha256(scaler_path):
        return card, "The model files do not match their evaluation card."
    if not allow_synthetic and (card.get("synthetic_rows_train", 0) or card.get("synthetic_rows_test", 0)):
        return card, "The model was built with synthetic labels and cannot be used for real placements."
    return card, None
