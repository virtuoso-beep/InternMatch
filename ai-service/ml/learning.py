"""Recommendation learning: train, evaluate and install the supervised ranker (spec §9).

Rules (fixed before label collection, spec §9 and Development Guide Phase 9):
- Features: similarity score, straight-line distance and program (one-hot). One shared model.
- Train only with at least 30 suitable and 30 not-suitable labels (uncertain labels are excluded upstream).
- Evaluate with leave-one-student-out cross-validation; report accuracy, precision, recall and F1, overall
  and per program.
- Install only if the model's leave-one-student-out F1 is better than a model using similarity alone, and
  every program in the data has both suitable and not-suitable labels.
- The fallback ranking (similarity, then distance) is evaluated with Precision@3, Recall@3 and NDCG@3 and
  compared with keyword overlap and distance-only baselines.

Synthetic (test) labels are marked on the model card; the service refuses such a model unless explicitly
allowed (ML_ALLOW_SYNTHETIC_MODEL=1).
"""
from __future__ import annotations

import json
import math
import os
import shutil
from datetime import datetime, timezone

import joblib
import numpy as np
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, f1_score, precision_score, recall_score
from sklearn.preprocessing import StandardScaler

try:
    from ml.model_card import CARD_NAME, MODEL_FILE, SCALER_FILE, sha256
except ImportError:  # pragma: no cover
    from model_card import CARD_NAME, MODEL_FILE, SCALER_FILE, sha256

MIN_PER_CLASS = 30


def _distance_fill(rows: list[dict]) -> float:
    known = [r["distance_km"] for r in rows if r.get("distance_km") is not None]
    return float(np.median(known)) if known else 0.0


def features(rows: list[dict], programs: list[str], distance_fill: float, similarity_only: bool = False) -> np.ndarray:
    """[similarity, distance, program one-hot...]; a missing distance takes the training median."""
    out = []
    for r in rows:
        if similarity_only:
            out.append([float(r["similarity"])])
            continue
        distance = r.get("distance_km")
        row = [float(r["similarity"]), float(distance_fill if distance is None else distance)]
        row += [1.0 if r.get("program") == p else 0.0 for p in programs]
        out.append(row)
    return np.asarray(out, dtype=float)


def _fit(x: np.ndarray, y: np.ndarray):
    scaler = StandardScaler().fit(x)
    model = LogisticRegression(class_weight="balanced", random_state=42, max_iter=1000).fit(scaler.transform(x), y)
    return model, scaler


def _scores(y_true: list[int], y_pred: list[int]) -> dict:
    return {
        "accuracy": float(accuracy_score(y_true, y_pred)),
        "precision": float(precision_score(y_true, y_pred, zero_division=0)),
        "recall": float(recall_score(y_true, y_pred, zero_division=0)),
        "f1": float(f1_score(y_true, y_pred, zero_division=0)),
        "n": len(y_true),
    }


def leave_one_student_out(rows: list[dict], programs: list[str], similarity_only: bool = False) -> dict:
    """Each student's pairs are predicted by a model trained on everyone else's."""
    students = sorted({r["student"] for r in rows})
    y_true, y_pred, by_program = [], [], {}
    for student in students:
        train = [r for r in rows if r["student"] != student]
        test = [r for r in rows if r["student"] == student]
        y_train = np.asarray([r["label"] for r in train])
        fill = _distance_fill(train)
        if len(set(y_train)) < 2:  # a fold with one class can only predict that class
            predicted = [int(y_train[0])] * len(test)
        else:
            model, scaler = _fit(features(train, programs, fill, similarity_only), y_train)
            predicted = [int(v) for v in model.predict(scaler.transform(features(test, programs, fill, similarity_only)))]
        for r, p in zip(test, predicted):
            y_true.append(int(r["label"]))
            y_pred.append(p)
            by_program.setdefault(r["program"], ([], []))
            by_program[r["program"]][0].append(int(r["label"]))
            by_program[r["program"]][1].append(p)
    return {
        "overall": _scores(y_true, y_pred),
        "by_program": {p: _scores(t, q) for p, (t, q) in sorted(by_program.items())},
        "students": len(students),
    }


def _ranking_metrics(ordered_labels: list[int], k: int = 3) -> tuple[float, float, float]:
    top = ordered_labels[:k]
    relevant = sum(ordered_labels)
    precision = sum(top) / min(k, len(ordered_labels))
    recall = sum(top) / relevant if relevant else 0.0
    dcg = sum(rel / math.log2(i + 2) for i, rel in enumerate(top))
    ideal = sorted(ordered_labels, reverse=True)[:k]
    idcg = sum(rel / math.log2(i + 2) for i, rel in enumerate(ideal))
    return precision, recall, (dcg / idcg if idcg else 0.0)


def fallback_evaluation(rows: list[dict]) -> dict | None:
    """Precision@3, Recall@3 and NDCG@3 per student (students with both labels), averaged.

    Methods: the current fallback ranking (similarity, ties by distance), keyword overlap, distance only.
    Returns None when no student has both a suitable and a not-suitable label.
    """
    groups: dict[str, list[dict]] = {}
    for r in rows:
        groups.setdefault(r["student"], []).append(r)
    eligible = [g for g in groups.values() if any(r["label"] == 1 for r in g) and any(r["label"] == 0 for r in g)]
    if not eligible:
        return None
    far = float("inf")
    orders = {
        "similarity": lambda g: sorted(g, key=lambda r: (-r["similarity"], r["distance_km"] if r.get("distance_km") is not None else far)),
        "keyword_overlap": lambda g: sorted(g, key=lambda r: -(r.get("keyword_overlap") or 0.0)),
        "distance_only": lambda g: sorted(g, key=lambda r: r["distance_km"] if r.get("distance_km") is not None else far),
    }
    result = {"students": len(eligible)}
    for name, order in orders.items():
        values = [_ranking_metrics([r["label"] for r in order(g)]) for g in eligible]
        result[name] = {
            "precision_at_3": float(np.mean([v[0] for v in values])),
            "recall_at_3": float(np.mean([v[1] for v in values])),
            "ndcg_at_3": float(np.mean([v[2] for v in values])),
        }
    return result


def counts(rows: list[dict]) -> dict:
    return {"suitable": sum(1 for r in rows if r["label"] == 1), "not_suitable": sum(1 for r in rows if r["label"] == 0),
            "students": len({r["student"] for r in rows}), "programs": sorted({r["program"] for r in rows})}


def run(rows: list[dict], model_dir: str, min_per_class: int = MIN_PER_CLASS) -> dict:
    """Check the rule, evaluate, and install only if the model passes. Never replaces a model otherwise."""
    synthetic = any(bool(r.get("synthetic")) for r in rows)
    summary = counts(rows)
    report = {"counts": summary, "synthetic": synthetic, "fallback": fallback_evaluation(rows),
              "min_per_class": min_per_class, "metrics": None}
    if summary["suitable"] < min_per_class or summary["not_suitable"] < min_per_class:
        return report | {"result": "not_enough_data",
                         "reason": f"Needs at least {min_per_class} suitable and {min_per_class} not-suitable judgments."}

    missing = [p for p in summary["programs"]
               if not (any(r["program"] == p and r["label"] == 1 for r in rows) and any(r["program"] == p and r["label"] == 0 for r in rows))]
    programs = summary["programs"]
    full = leave_one_student_out(rows, programs)
    baseline = leave_one_student_out(rows, programs, similarity_only=True)
    report["metrics"] = {"model": full, "similarity_only": baseline}
    if missing:
        return report | {"result": "not_reliable",
                         "reason": "Every program needs both suitable and not-suitable judgments. Missing in: " + ", ".join(missing)}
    if not full["overall"]["f1"] > baseline["overall"]["f1"]:
        return report | {"result": "not_reliable",
                         "reason": f"Leave-one-student-out F1 {full['overall']['f1']:.3f} is not better than similarity alone ({baseline['overall']['f1']:.3f})."}

    fill = _distance_fill(rows)
    model, scaler = _fit(features(rows, programs, fill), np.asarray([r["label"] for r in rows]))
    card = install(model, scaler, model_dir, {
        "features": ["similarity_score", "distance_km"] + [f"program={p}" for p in programs],
        "programs": programs, "distance_fill": fill, "synthetic": synthetic,
        "n_train": len(rows), "counts": summary, "metrics": report["metrics"], "fallback": report["fallback"],
    })
    return report | {"result": "updated", "reason": None, "model": {k: card[k] for k in ("trained_at", "model_sha256", "synthetic")}}


def install(model, scaler, model_dir: str, meta: dict) -> dict:
    """Write to a staging folder, then swap it in, so a reader never sees a half-written model."""
    parent = os.path.dirname(os.path.abspath(model_dir)) or "."
    staging = os.path.join(parent, ".ranker-staging")
    previous = os.path.join(parent, ".ranker-previous")
    shutil.rmtree(staging, ignore_errors=True)
    os.makedirs(staging)
    joblib.dump(model, os.path.join(staging, MODEL_FILE))
    joblib.dump(scaler, os.path.join(staging, SCALER_FILE))
    card = meta | {
        "passed": True,
        "trained_at": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "model_sha256": sha256(os.path.join(staging, MODEL_FILE)),
        "scaler_sha256": sha256(os.path.join(staging, SCALER_FILE)),
    }
    with open(os.path.join(staging, CARD_NAME), "w") as handle:
        json.dump(card, handle, indent=2)
    shutil.rmtree(previous, ignore_errors=True)
    if os.path.exists(model_dir):
        os.replace(model_dir, previous)
    os.replace(staging, model_dir)
    shutil.rmtree(previous, ignore_errors=True)
    return card
