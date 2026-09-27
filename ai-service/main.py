"""Internal E5 service. Laravel owns authorization, eligibility and persisted provenance."""
import hashlib
import os
from contextlib import asynccontextmanager
from typing import Literal
from threading import Lock

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from similarity import cosine

MODEL = "intfloat/multilingual-e5-base"
REVISION = os.getenv("MODEL_REVISION", "d128750597153bb5987e10b1c3493a34e5a4502a")
engine = None
inference_lock = Lock()

class E5:
    def __init__(self):
        import torch
        from transformers import AutoModel, AutoTokenizer
        self.torch = torch
        torch.set_num_threads(int(os.getenv("TORCH_THREADS", "2")))
        self.tokenizer = AutoTokenizer.from_pretrained(MODEL, revision=REVISION, trust_remote_code=False)
        self.model = AutoModel.from_pretrained(MODEL, revision=REVISION, trust_remote_code=False, use_safetensors=True).eval()

    def encode(self, text):
        batch = self.tokenizer([text], max_length=512, padding=True, truncation=True, return_tensors="pt")
        with inference_lock, self.torch.inference_mode():
            hidden = self.model(**batch).last_hidden_state
            masked = hidden.masked_fill(~batch['attention_mask'][..., None].bool(), 0.0)
            pooled = masked.sum(dim=1) / batch['attention_mask'].sum(dim=1)[..., None]
            return self.torch.nn.functional.normalize(pooled, p=2, dim=1)[0].tolist()

@asynccontextmanager
async def lifespan(app):
    global engine
    # Startup fails visibly if real model weights cannot load; never substitute fake vectors.
    engine = E5()
    yield

app = FastAPI(title="InternMatch semantic matching", lifespan=lifespan)

class TextInput(BaseModel):
    text: str = Field(min_length=1, max_length=12000)
    kind: Literal['student', 'opportunity']

class Candidate(BaseModel):
    id: int = Field(gt=0)
    vector: list[float] = Field(min_length=768, max_length=768)

class RankingInput(BaseModel):
    student_vector: list[float] = Field(min_length=768, max_length=768)
    candidates: list[Candidate] = Field(max_length=1000)

@app.get('/health')
def health():
    if engine is None:
        raise HTTPException(503, 'Model is not loaded')
    return {'status': 'ready', 'model_name': MODEL, 'model_version': REVISION, 'dimensions': 768}

@app.post('/embeddings')
def embeddings(data: TextInput):
    if engine is None:
        raise HTTPException(503, 'Model is not loaded')
    text = data.text.strip()
    if not text:
        raise HTTPException(422, 'Competency or opportunity text is required')
    prefix = 'query: ' if data.kind == 'student' else 'passage: '
    source = prefix + text
    return {'vector': engine.encode(source), 'source_text_hash': hashlib.sha256(source.encode()).hexdigest(),
            'model_name': MODEL, 'model_version': REVISION, 'dimensions': 768}

@app.post('/recommendations')
def recommendations(data: RankingInput):
    if len({c.id for c in data.candidates}) != len(data.candidates):
        raise HTTPException(422, 'Candidate IDs must be unique')
    try:
        ranked = [{'id': c.id, 'similarity_score': cosine(data.student_vector, c.vector)} for c in data.candidates]
    except ValueError as error:
        raise HTTPException(422, str(error)) from error
    return {'data': sorted(ranked, key=lambda c: (-c['similarity_score'], c['id'])),
            'ranking_method': 'cosine', 'model_name': MODEL, 'model_version': REVISION}
