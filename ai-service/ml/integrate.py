"""Supervised reranking with a similarity fallback, and the training endpoint (spec §9).

- POST /recommendations/ml ranks eligible candidates. With an installed model that passed the documented rule
  it orders by the model's suitability probability; otherwise by similarity. `ranking_method` says which.
- POST /ml/train checks the rule, evaluates with leave-one-student-out, and installs the model only if it
  passes (ml/learning.py). The current model stays otherwise.
- GET /recommendations/ml/status reports the active method and the installed model's card.

Eligibility (active opportunity, valid agreement for the program, remaining capacity) is decided by Laravel
before ranking; this service never receives ineligible opportunities.
"""
import logging
import os
import threading

import joblib
import numpy as np
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel, Field

from similarity import cosine

try:
    from ml import learning
    from ml.model_card import MODEL_FILE, SCALER_FILE, check
except ImportError:  # pragma: no cover - imported from inside ml/
    import learning
    from model_card import MODEL_FILE, SCALER_FILE, check

logger = logging.getLogger(__name__)

router = APIRouter()

MODEL_DIR = os.getenv("ML_MODEL_DIR", os.path.join(os.path.dirname(__file__), "models"))
# Embedding model identity, echoed so Laravel can apply the same version check as /recommendations.
EMBEDDING_MODEL = "intfloat/multilingual-e5-base"
EMBEDDING_REVISION = os.getenv("MODEL_REVISION", "d128750597153bb5987e10b1c3493a34e5a4502a")

model = None
scaler = None
card: dict | None = None
inactive_reason: str | None = "Not loaded yet."
_training = threading.Lock()


def allow_synthetic() -> bool:
    return os.getenv("ML_ALLOW_SYNTHETIC_MODEL") == "1"


def load_ml_assets(folder: str | None = None, allow_test_model: bool | None = None) -> None:
    """(Re)load the ranker. Only a model with a valid passing card is activated."""
    global model, scaler, card, inactive_reason
    folder = folder or MODEL_DIR
    allowed = allow_synthetic() if allow_test_model is None else allow_test_model
    model, scaler = None, None
    card, inactive_reason = check(folder, allow_synthetic=allowed)
    if inactive_reason:
        logger.info("Supervised ranker inactive: %s", inactive_reason)
        return
    try:
        model = joblib.load(os.path.join(folder, MODEL_FILE))
        scaler = joblib.load(os.path.join(folder, SCALER_FILE))
        logger.info("Supervised ranker active (trained %s).", card.get("trained_at"))
    except Exception as error:  # a corrupt file must not take the service down
        model, scaler = None, None
        inactive_reason = f"The model files could not be loaded: {error}"
        logger.error(inactive_reason)


load_ml_assets()


class MLCandidate(BaseModel):
    id: int = Field(gt=0)
    vector: list[float] = Field(min_length=768, max_length=768)
    distance_km: float | None = None


class MLRankingInput(BaseModel):
    student_vector: list[float] = Field(min_length=768, max_length=768)
    program: str | None = Field(default=None, max_length=50)
    candidates: list[MLCandidate] = Field(max_length=1000)


class TrainingRow(BaseModel):
    student: str = Field(min_length=1, max_length=100)
    program: str = Field(min_length=1, max_length=50)
    similarity: float = Field(ge=-1, le=1)
    distance_km: float | None = Field(default=None, ge=0)
    keyword_overlap: float | None = Field(default=None, ge=0, le=1)
    label: int = Field(ge=0, le=1)
    synthetic: bool = False


class TrainingInput(BaseModel):
    rows: list[TrainingRow] = Field(max_length=5000)
    min_per_class: int = Field(default=learning.MIN_PER_CLASS, ge=1)


def _identity() -> dict:
    return {"model_name": EMBEDDING_MODEL, "model_version": EMBEDDING_REVISION}


def _card_summary() -> dict | None:
    if card is None:
        return None
    keys = ("trained_at", "n_train", "counts", "programs", "synthetic", "metrics", "fallback", "passed")
    return {k: card.get(k) for k in keys}


@router.get("/recommendations/ml/status")
def ml_status():
    active = model is not None and scaler is not None
    return {"active": active, "ranking_method": "logistic_regression" if active else "cosine_fallback",
            "reason": None if active else inactive_reason, "card": _card_summary(), **_identity()}


@router.post("/ml/train")
def ml_train(data: TrainingInput):
    rows = [r.model_dump() for r in data.rows]
    if any(r["synthetic"] for r in rows) and not allow_synthetic():
        raise HTTPException(422, "Test data can be used for training only when ML_ALLOW_SYNTHETIC_MODEL=1.")
    if not _training.acquire(blocking=False):
        raise HTTPException(409, "An update is already running.")
    try:
        report = learning.run(rows, MODEL_DIR, data.min_per_class)
        if report["result"] == "updated":
            load_ml_assets()
        return report
    finally:
        _training.release()


@router.post("/recommendations/ml")
def recommendations_ml(data: MLRankingInput):
    if len({c.id for c in data.candidates}) != len(data.candidates):
        raise HTTPException(422, "Candidate IDs must be unique")
    try:
        ranked = [{"id": c.id, "similarity": cosine(data.student_vector, c.vector), "distance_km": c.distance_km,
                   "program": data.program} for c in data.candidates]
    except ValueError as error:
        raise HTTPException(422, str(error)) from error

    def cosine_order(method: str) -> dict:
        rows = [{"id": r["id"], "similarity_score": r["similarity"]} for r in ranked]
        return {"data": sorted(rows, key=lambda c: (-c["similarity_score"], c["id"])), "ranking_method": method, **_identity()}

    if model is None or scaler is None or not ranked:
        return cosine_order("cosine_fallback")
    try:
        x = learning.features(ranked, card.get("programs", []), float(card.get("distance_fill", 0.0)))
        probs = model.predict_proba(scaler.transform(x))[:, 1]
    except Exception as error:
        logger.error("Prediction failed: %s", error)
        return cosine_order("cosine_fallback_error")
    results = [{"id": r["id"], "similarity_score": r["similarity"], "ml_score": float(p)} for r, p in zip(ranked, np.asarray(probs))]
    return {
        "data": sorted(results, key=lambda c: (-c["ml_score"], -c["similarity_score"], c["id"])),
        "ranking_method": "logistic_regression",
        "ranker": {"trained_at": card.get("trained_at"), "model_sha256": card.get("model_sha256"),
                   "synthetic": bool(card.get("synthetic"))},
        **_identity(),
    }
