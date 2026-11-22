"""Supervised reranking (Logistic Regression) with a cosine fallback.

The model is used only when it has a passing, matching model card built from real coordinator judgments
(see ml/model_card.py). Otherwise every request falls back to cosine similarity and says so in
``ranking_method``. ``GET /recommendations/ml/status`` reports which method is active and why.
"""
import logging
import os

import joblib
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel, Field

from similarity import cosine

try:
    from ml.model_card import FEATURES, check
except ImportError:  # pragma: no cover - imported from inside ml/
    from model_card import FEATURES, check

logger = logging.getLogger(__name__)

router = APIRouter()

MODEL_DIR = os.getenv("ML_MODEL_DIR", os.path.join(os.path.dirname(__file__), "models"))
MODEL_FILE = "judgment_model.joblib"
SCALER_FILE = "scaler.joblib"
# Embedding model identity, echoed so Laravel can apply the same version check as /recommendations.
EMBEDDING_MODEL = "intfloat/multilingual-e5-base"
EMBEDDING_REVISION = os.getenv("MODEL_REVISION", "d128750597153bb5987e10b1c3493a34e5a4502a")

model = None
scaler = None
card: dict | None = None
inactive_reason: str | None = "Not loaded yet."


def load_ml_assets(folder: str | None = None, allow_synthetic: bool | None = None) -> None:
    """(Re)load the ranker. Only a model with a valid passing card is activated."""
    global model, scaler, card, inactive_reason
    folder = folder or MODEL_DIR
    if allow_synthetic is None:
        allow_synthetic = os.getenv("ML_ALLOW_SYNTHETIC_MODEL") == "1"
    model, scaler = None, None
    card, inactive_reason = check(folder, MODEL_FILE, SCALER_FILE, allow_synthetic=allow_synthetic)
    if inactive_reason:
        logger.info("Logistic regression ranker inactive: %s", inactive_reason)
        return
    try:
        model = joblib.load(os.path.join(folder, MODEL_FILE))
        scaler = joblib.load(os.path.join(folder, SCALER_FILE))
        logger.info("Logistic regression ranker active (trained %s).", card.get("trained_at"))
    except Exception as error:  # a corrupt file must not take the service down
        model, scaler = None, None
        inactive_reason = f"The model files could not be loaded: {error}"
        logger.error(inactive_reason)


load_ml_assets()


class MLCandidate(BaseModel):
    id: int = Field(gt=0)
    vector: list[float] = Field(min_length=768, max_length=768)
    distance_km: float | None = None
    capacity_at_time: int | None = None
    moa_status_at_time: str | None = None


class MLRankingInput(BaseModel):
    student_vector: list[float] = Field(min_length=768, max_length=768)
    candidates: list[MLCandidate] = Field(max_length=1000)


def _identity() -> dict:
    return {"model_name": EMBEDDING_MODEL, "model_version": EMBEDDING_REVISION}


def _cosine(ranked: list[dict], method: str) -> dict:
    data = [{"id": r["id"], "similarity_score": r["similarity_score"]} for r in ranked]
    return {"data": sorted(data, key=lambda c: (-c["similarity_score"], c["id"])), "ranking_method": method, **_identity()}


def features_for(r: dict, distance_fill: float) -> list[float]:
    """Same features, in the same order and with the same missing-distance fill, as training."""
    missing = r["distance_km"] is None
    return [
        r["similarity_score"],
        distance_fill if missing else r["distance_km"],
        r["capacity_at_time"] if r["capacity_at_time"] is not None else 0,
        1 if (r["moa_status_at_time"] or "").lower() == "active" else 0,
        1 if missing else 0,
    ]


@router.get("/recommendations/ml/status")
def ml_status():
    active = model is not None and scaler is not None
    return {
        "active": active,
        "ranking_method": "logistic_regression" if active else "cosine_fallback",
        "reason": None if active else inactive_reason,
        "features": FEATURES,
        "card": None if card is None else {k: card.get(k) for k in (
            "trained_at", "evaluated_at", "n_train", "n_test", "synthetic_rows_train", "synthetic_rows_test",
            "threshold_f1", "metrics", "passed")},
        **_identity(),
    }


@router.post("/recommendations/ml")
def recommendations_ml(data: MLRankingInput):
    if len({c.id for c in data.candidates}) != len(data.candidates):
        raise HTTPException(422, "Candidate IDs must be unique")
    try:
        ranked = [{
            "id": c.id,
            "similarity_score": cosine(data.student_vector, c.vector),
            "distance_km": c.distance_km,
            "capacity_at_time": c.capacity_at_time,
            "moa_status_at_time": c.moa_status_at_time,
        } for c in data.candidates]
    except ValueError as error:
        raise HTTPException(422, str(error)) from error

    if model is None or scaler is None or not ranked:
        return _cosine(ranked, "cosine_fallback")

    fill = float((card or {}).get("distance_fill", 0.0))
    try:
        probs = model.predict_proba(scaler.transform([features_for(r, fill) for r in ranked]))[:, 1]
    except Exception as error:
        logger.error("Prediction failed: %s", error)
        return _cosine(ranked, "cosine_fallback_error")

    results = [{"id": r["id"], "similarity_score": r["similarity_score"], "ml_score": float(p)} for r, p in zip(ranked, probs)]
    return {
        "data": sorted(results, key=lambda c: (-c["ml_score"], -c["similarity_score"], c["id"])),
        "ranking_method": "logistic_regression",
        "ranker": {"trained_at": card.get("trained_at"), "model_sha256": card.get("model_sha256"),
                   "synthetic_rows": int(card.get("synthetic_rows_train", 0)) + int(card.get("synthetic_rows_test", 0))},
        **_identity(),
    }
