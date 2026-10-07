import os
import joblib
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel, Field
from similarity import cosine
import logging

logger = logging.getLogger(__name__)

router = APIRouter()

MODEL_PATH = os.path.join(os.path.dirname(__file__), "models", "judgment_model.joblib")
SCALER_PATH = os.path.join(os.path.dirname(__file__), "models", "scaler.joblib")

model = None
scaler = None

def load_ml_assets():
    global model, scaler
    if os.path.exists(MODEL_PATH) and os.path.exists(SCALER_PATH):
        try:
            model = joblib.load(MODEL_PATH)
            scaler = joblib.load(SCALER_PATH)
            logger.info("ML model and scaler loaded successfully.")
        except Exception as e:
            logger.error(f"Failed to load ML model: {e}")

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

@router.post('/recommendations/ml')
def recommendations_ml(data: MLRankingInput):
    if len({c.id for c in data.candidates}) != len(data.candidates):
        raise HTTPException(422, 'Candidate IDs must be unique')
        
    ranked = []
    try:
        for c in data.candidates:
            sim = cosine(data.student_vector, c.vector)
            ranked.append({
                'id': c.id,
                'similarity_score': sim,
                'distance_km': c.distance_km,
                'capacity_at_time': c.capacity_at_time,
                'moa_status_at_time': c.moa_status_at_time
            })
    except ValueError as error:
        raise HTTPException(422, str(error)) from error

    if model is None or scaler is None:
        # Fallback to cosine
        fallback = [{'id': r['id'], 'similarity_score': r['similarity_score']} for r in ranked]
        return {'data': sorted(fallback, key=lambda c: (-c['similarity_score'], c['id'])),
                'ranking_method': 'cosine_fallback'}
                
    # Re-rank using model
    features_list = []
    for r in ranked:
        sim = r['similarity_score']
        dist = r['distance_km'] if r['distance_km'] is not None else 0.0
        dist_missing = 1 if r['distance_km'] is None else 0
        cap = r['capacity_at_time'] if r['capacity_at_time'] is not None else 0
        moa = 1 if (r['moa_status_at_time'] or '').lower() == 'active' else 0
        features_list.append([sim, dist, cap, moa, dist_missing])
        
    try:
        X_scaled = scaler.transform(features_list)
        probs = model.predict_proba(X_scaled)[:, 1]
    except Exception as e:
        logger.error(f"Prediction failed: {e}")
        # Fallback
        fallback = [{'id': r['id'], 'similarity_score': r['similarity_score']} for r in ranked]
        return {'data': sorted(fallback, key=lambda c: (-c['similarity_score'], c['id'])),
                'ranking_method': 'cosine_fallback_error'}
                
    results = []
    for i, r in enumerate(ranked):
        results.append({
            'id': r['id'],
            'similarity_score': r['similarity_score'],
            'ml_score': float(probs[i])
        })
        
    return {'data': sorted(results, key=lambda c: (-c['ml_score'], -c['similarity_score'], c['id'])),
            'ranking_method': 'logistic_regression'}
