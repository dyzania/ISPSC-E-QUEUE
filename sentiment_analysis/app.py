from fastapi import FastAPI
from pydantic import BaseModel
from transformers import pipeline

app = FastAPI(title="Sentiment Analysis API")

# Firewall, ensure port 8000 is open for your PHP server's IP.

model_id = "tabularisai/multilingual-sentiment-analysis"

try:
    sentiment_pipeline = pipeline(
        "text-classification",
        model=model_id,
        tokenizer=model_id,
        truncation=True,
        top_k=1,          # return only top label
        return_all_scores=False
    )
except Exception as e:
    print(f"Warning: Model loading failed. API will run but sentiment analysis will fail. Error: {e}")
    sentiment_pipeline = None

class TextInput(BaseModel):
    text: str

@app.post("/analyze")
def analyze_sentiment(data: TextInput):
    if not sentiment_pipeline:
        return {"error": "Model not loaded service unavailable"}
        
    try:
        result = sentiment_pipeline(data.text)
        if isinstance(result, list) and len(result) > 0:
            if isinstance(result[0], list):
                result = result[0][0]
            else:
                result = result[0]
        return {
            "label": result["label"],
            "score": round(result["score"], 4)
        }
    except Exception as e:
        return {"error": str(e)}
