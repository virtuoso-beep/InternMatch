"""Recommendation learning (spec §9): the 30/30 rule, leave-one-student-out evaluation, the install rule,
the fallback evaluation and the model card gate."""
import json
import os
import random

import pytest
from fastapi.testclient import TestClient

from main import app
from ml import integrate, learning

client = TestClient(app)


def make_rows(students=16, programs=("BSA", "BSIT"), rule=None, synthetic=False, seed=7):
    """Five judged opportunities per student. Default rule: suitable needs a close fit AND a short distance,
    so distance carries information that similarity alone does not."""
    rng = random.Random(seed)
    rule = rule or (lambda s, d: s > 0.5 and d < 15)
    rows = []
    for n in range(students):
        program = programs[n % len(programs)]
        for _ in range(5):
            s, d = rng.uniform(0.1, 0.95), rng.uniform(1, 30)
            rows.append({"student": f"S{n}", "program": program, "similarity": round(s, 3), "distance_km": round(d, 2),
                         "keyword_overlap": round(min(1.0, max(0.0, s + rng.uniform(-0.2, 0.2))), 3),
                         "label": int(rule(s, d)), "synthetic": synthetic})
    return rows


@pytest.fixture
def model_dir(tmp_path, monkeypatch):
    folder = tmp_path / "ranker"
    monkeypatch.setattr(integrate, "MODEL_DIR", str(folder))
    integrate.load_ml_assets(str(folder), allow_test_model=False)
    yield folder
    integrate.load_ml_assets(str(tmp_path / "none"), allow_test_model=False)


def payload(program="BSA"):
    return {"student_vector": [0.1] * 768, "program": program, "candidates": [
        {"id": 1, "vector": [0.1] * 768, "distance_km": 3.0},
        {"id": 2, "vector": [0.1] * 767 + [-0.1], "distance_km": 28.0},
    ]}


def test_no_model_ranks_by_similarity_and_says_why(model_dir):
    status = client.get("/recommendations/ml/status").json()
    assert status["active"] is False and "No trained model" in status["reason"]
    body = client.post("/recommendations/ml", json=payload()).json()
    assert body["ranking_method"] == "cosine_fallback"
    assert body["model_name"] == "intfloat/multilingual-e5-base" and body["model_version"]


def test_not_enough_labels_keeps_the_current_model(model_dir):
    rows = make_rows(students=40)
    suitable = [r for r in rows if r["label"] == 1][:29]
    unsuitable = [r for r in rows if r["label"] == 0][:30]
    report = client.post("/ml/train", json={"rows": suitable + unsuitable}).json()
    assert report["result"] == "not_enough_data" and report["metrics"] is None
    assert report["counts"]["suitable"] == 29
    assert not model_dir.exists()


def test_a_model_better_than_similarity_alone_is_installed_and_used(model_dir):
    rows = make_rows(students=24)
    report = client.post("/ml/train", json={"rows": rows}).json()
    assert report["result"] == "updated", report
    model, baseline = report["metrics"]["model"], report["metrics"]["similarity_only"]
    assert model["overall"]["f1"] > baseline["overall"]["f1"]
    assert set(model["by_program"]) == {"BSA", "BSIT"}
    assert {"accuracy", "precision", "recall", "f1"} <= set(model["overall"])
    assert model["students"] == 24
    card = json.loads((model_dir / "model_card.json").read_text())
    assert card["synthetic"] is False and card["features"][:2] == ["similarity_score", "distance_km"]
    assert not (model_dir.parent / ".ranker-staging").exists()

    status = client.get("/recommendations/ml/status").json()
    assert status["active"] is True
    body = client.post("/recommendations/ml", json=payload()).json()
    assert body["ranking_method"] == "logistic_regression"
    assert [row["id"] for row in body["data"]] == [1, 2]  # same fit, the nearer one ranks first


def test_a_model_no_better_than_similarity_alone_is_refused(model_dir):
    rows = make_rows(students=24, rule=lambda s, d: s > 0.5)  # distance adds nothing
    report = client.post("/ml/train", json={"rows": rows}).json()
    assert report["result"] == "not_reliable" and "not better than similarity alone" in report["reason"]
    assert not model_dir.exists()


def test_every_program_needs_both_labels(model_dir):
    rows = make_rows(students=24)
    rows += [{"student": f"C{n}", "program": "BSCRIM", "similarity": 0.9, "distance_km": 2.0, "keyword_overlap": 0.9,
              "label": 1, "synthetic": False} for n in range(3)]
    report = client.post("/ml/train", json={"rows": rows}).json()
    assert report["result"] == "not_reliable" and "BSCRIM" in report["reason"]
    assert not model_dir.exists()


def test_test_data_needs_local_test_mode(model_dir, monkeypatch):
    rows = make_rows(students=24, synthetic=True)
    monkeypatch.delenv("ML_ALLOW_SYNTHETIC_MODEL", raising=False)
    assert client.post("/ml/train", json={"rows": rows}).status_code == 422

    monkeypatch.setenv("ML_ALLOW_SYNTHETIC_MODEL", "1")
    assert client.post("/ml/train", json={"rows": rows}).json()["result"] == "updated"
    assert client.get("/recommendations/ml/status").json()["card"]["synthetic"] is True

    integrate.load_ml_assets(str(model_dir), allow_test_model=False)
    status = client.get("/recommendations/ml/status").json()
    assert status["active"] is False and "test data" in status["reason"]


def test_tampered_model_files_are_refused(model_dir):
    assert client.post("/ml/train", json={"rows": make_rows(students=24)}).json()["result"] == "updated"
    with open(model_dir / "judgment_model.joblib", "ab") as handle:
        handle.write(b"tamper")
    integrate.load_ml_assets(str(model_dir), allow_test_model=False)
    assert "do not match" in client.get("/recommendations/ml/status").json()["reason"]


def test_fallback_evaluation_by_hand():
    rows = [  # one student: similarity order puts the two suitable pairs 1st and 3rd
        {"student": "A", "program": "BSA", "similarity": 0.9, "distance_km": 9, "keyword_overlap": 0.2, "label": 1},
        {"student": "A", "program": "BSA", "similarity": 0.8, "distance_km": 1, "keyword_overlap": 0.9, "label": 0},
        {"student": "A", "program": "BSA", "similarity": 0.7, "distance_km": 2, "keyword_overlap": 0.8, "label": 1},
        {"student": "A", "program": "BSA", "similarity": 0.2, "distance_km": 3, "keyword_overlap": 0.1, "label": 0},
        {"student": "B", "program": "BSA", "similarity": 0.5, "distance_km": 3, "keyword_overlap": 0.1, "label": 1},
    ]
    result = learning.fallback_evaluation(rows)
    assert result["students"] == 1  # B has no not-suitable label
    sim = result["similarity"]
    assert sim["precision_at_3"] == pytest.approx(2 / 3)
    assert sim["recall_at_3"] == pytest.approx(1.0)
    ideal = 1 + 1 / 1.5849625007211562
    assert sim["ndcg_at_3"] == pytest.approx((1 + 1 / 2) / ideal)
    assert result["distance_only"]["precision_at_3"] == pytest.approx(1 / 3)  # 1 km, 2 km, 3 km: one suitable
    assert learning.fallback_evaluation(rows[4:]) is None


def test_missing_distance_uses_the_training_median():
    rows = [{"similarity": 0.5, "distance_km": None, "program": "BSA"}]
    assert learning.features(rows, ["BSA", "BSIT"], 7.5).tolist() == [[0.5, 7.5, 1.0, 0.0]]


def test_export_default_location_note():
    """The research export lives in Laravel's local disk; the AI service no longer reads it."""
    assert "ml:export-judgments" in open(os.path.join(os.path.dirname(__file__), "..", "ml", "README.md")).read()
