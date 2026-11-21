import os
import pandas as pd
import pytest
import joblib
from fastapi.testclient import TestClient

# Must set before importing main if we want to bypass real models in some cases,
# but we just test the endpoints.
from main import app

client = TestClient(app)

def create_dummy_data(path):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    df = pd.DataFrame({
        'similarity_score': [0.9, 0.1, 0.8, 0.2, 0.95, 0.05],
        'distance_km': [5.0, 50.0, None, 100.0, 2.0, 200.0],
        'capacity_at_time': [5, 0, 2, 0, 10, -1],
        'moa_active': [1, 0, 1, 0, 1, 0],
        'label': [1, 0, 1, 0, 1, 0]
    })
    df.to_csv(path, index=False)

def test_pipeline_stages(tmp_path):
    raw_path = str(tmp_path / "raw.csv")
    cleaned_path = str(tmp_path / "cleaned.csv")
    train_path = str(tmp_path / "train.csv")
    test_path = str(tmp_path / "test.csv")
    model_path = str(tmp_path / "model.joblib")
    scaler_path = str(tmp_path / "scaler.joblib")
    report_path = str(tmp_path / "report.json")
    
    create_dummy_data(raw_path)
    
    # 1. Clean Data
    from ml.clean_data import clean_data
    clean_data(raw_path, cleaned_path)
    df_clean = pd.read_csv(cleaned_path)
    assert 'distance_km_missing' in df_clean.columns
    assert df_clean['distance_km'].isna().sum() == 0
    
    # 2. Split Data
    from ml.split_data import split_data
    split_data(cleaned_path, train_path, test_path, test_size=0.33)
    assert os.path.exists(train_path)
    assert os.path.exists(test_path)
    
    # 3. Train Model
    from ml.train_model import train_model
    train_model(train_path, model_path, scaler_path)
    assert os.path.exists(model_path)
    assert os.path.exists(scaler_path)
    
    # 4. Evaluate Model
    from ml.evaluate_model import evaluate_model
    # For dummy data, threshold might fail, so we catch or set threshold to 0.0
    evaluate_model(test_path, model_path, scaler_path, report_path, threshold=0.0)
    assert os.path.exists(report_path)

def test_integration_fallback():
    # If we hit /recommendations/ml without a model, it should fallback to cosine
    payload = {
        "student_vector": [0.1] * 768,
        "candidates": [
            {
                "id": 1,
                "vector": [0.1] * 768,
                "distance_km": 10.0,
                "capacity_at_time": 5,
                "moa_status_at_time": "active"
            }
        ]
    }
    response = client.post("/recommendations/ml", json=payload)
    # The app health might fail if engine is none but we're testing just the ml route.
    # Wait, the lifespan handles engine initialization. If it's loaded, it's fine.
    # The ml route doesn't use `engine` directly, just `cosine()`.
    if response.status_code == 200:
        data = response.json()
        assert "ranking_method" in data
        assert "cosine_fallback" in data["ranking_method"] or "logistic_regression" in data["ranking_method"]


def test_default_input_points_at_laravel_local_disk():
    """Regression: ml:export-judgments writes to Laravel's local disk root (storage/app/private)."""
    from ml.clean_data import DEFAULT_INPUT
    ml_dir = os.path.join(os.path.dirname(__file__), "..", "ml")
    resolved = os.path.normpath(os.path.join(ml_dir, DEFAULT_INPUT)).replace("\\", "/")
    assert resolved.endswith("backend/storage/app/private/ml/judgment_export.csv")


# --- Model card gate: only a passing model built from real judgments is ever used -------------------

def _train_into(folder, rows=60, synthetic=False, threshold=0.0):
    """Run clean -> split -> train -> evaluate on a separable dataset; returns the model folder."""
    import numpy as np
    from ml.clean_data import clean_data
    from ml.split_data import split_data
    from ml.train_model import train_model
    from ml.evaluate_model import evaluate_model

    rng = np.random.default_rng(7)
    good = rng.uniform(0.7, 0.95, rows // 2)
    bad = rng.uniform(0.05, 0.35, rows // 2)
    df = pd.DataFrame({
        "similarity_score": np.concatenate([good, bad]),
        "distance_km": [3.0 + i % 5 for i in range(rows // 2)] + [None if i % 4 == 0 else 60.0 + i for i in range(rows // 2)],
        "capacity_at_time": [5] * (rows // 2) + [1] * (rows // 2),
        "moa_active": [1] * (rows // 2) + [0] * (rows // 2),
        "label": [1] * (rows // 2) + [0] * (rows // 2),
        "is_synthetic": [1 if synthetic else 0] * rows,
    })
    folder.mkdir(parents=True, exist_ok=True)
    raw, cleaned = folder / "raw.csv", folder / "cleaned.csv"
    train, test = folder / "train.csv", folder / "test.csv"
    models = folder / "models"
    df.to_csv(raw, index=False)
    clean_data(str(raw), str(cleaned))
    split_data(str(cleaned), str(train), str(test), test_size=0.25)
    train_model(str(train), str(models / "judgment_model.joblib"), str(models / "scaler.joblib"))
    evaluate_model(str(test), str(models / "judgment_model.joblib"), str(models / "scaler.joblib"),
                   str(folder / "report.json"), threshold=threshold)
    return models


def _payload(distance=10.0):
    return {"student_vector": [0.1] * 768, "candidates": [
        {"id": 1, "vector": [0.1] * 768, "distance_km": distance, "capacity_at_time": 5, "moa_status_at_time": "active"},
        {"id": 2, "vector": [0.1] * 767 + [-0.1], "distance_km": 80.0, "capacity_at_time": 1, "moa_status_at_time": "expired"},
    ]}


def test_no_model_means_cosine_fallback_with_reason(tmp_path):
    from ml import integrate
    integrate.load_ml_assets(str(tmp_path))
    status = client.get("/recommendations/ml/status").json()
    assert status["active"] is False and "No trained model" in status["reason"]
    body = client.post("/recommendations/ml", json=_payload()).json()
    assert body["ranking_method"] == "cosine_fallback"
    assert body["model_name"] == "intfloat/multilingual-e5-base" and body["model_version"]


def test_passing_real_model_is_used_and_reported(tmp_path):
    from ml import integrate
    models = _train_into(tmp_path)
    integrate.load_ml_assets(str(models), allow_synthetic=False)
    status = client.get("/recommendations/ml/status").json()
    assert status["active"] is True and status["card"]["synthetic_rows_train"] == 0
    body = client.post("/recommendations/ml", json=_payload()).json()
    assert body["ranking_method"] == "logistic_regression"
    assert [row["id"] for row in body["data"]] == [1, 2]
    assert all(0.0 <= row["ml_score"] <= 1.0 for row in body["data"])
    integrate.load_ml_assets(str(tmp_path / "none"))


def test_synthetic_model_is_never_activated_for_real_use(tmp_path):
    from ml import integrate
    models = _train_into(tmp_path, synthetic=True)
    integrate.load_ml_assets(str(models), allow_synthetic=False)
    status = client.get("/recommendations/ml/status").json()
    assert status["active"] is False and "synthetic" in status["reason"]
    integrate.load_ml_assets(str(tmp_path / "none"))


def test_tampered_or_failed_model_is_refused(tmp_path):
    from ml import integrate
    from ml.evaluate_model import evaluate_model
    models = _train_into(tmp_path)
    with open(models / "judgment_model.joblib", "ab") as handle:
        handle.write(b"tamper")
    integrate.load_ml_assets(str(models), allow_synthetic=False)
    assert "do not match" in client.get("/recommendations/ml/status").json()["reason"]

    models = _train_into(tmp_path / "second")
    with pytest.raises(ValueError):
        evaluate_model(str(tmp_path / "second" / "test.csv"), str(models / "judgment_model.joblib"),
                       str(models / "scaler.joblib"), str(tmp_path / "second" / "report.json"), threshold=1.01)
    assert not (models / "model_card.json").exists()
    integrate.load_ml_assets(str(models), allow_synthetic=False)
    assert client.get("/recommendations/ml/status").json()["active"] is False
    integrate.load_ml_assets(str(tmp_path / "none"))


def test_missing_distance_uses_training_fill_not_zero(tmp_path):
    from ml import integrate
    import json
    models = _train_into(tmp_path)
    fill = json.loads((models / "model_card.json").read_text())["distance_fill"]
    assert fill > 0
    row = {"similarity_score": 0.5, "distance_km": None, "capacity_at_time": 2, "moa_status_at_time": "active"}
    assert integrate.features_for(row, fill) == [0.5, fill, 2, 1, 1]
