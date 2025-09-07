# predict_sentiment.py
"""
Load the trained sentiment model and make predictions on new text.
Make sure 'sentiment_model.pkl' exists in the same folder or update MODEL_PATH.
"""

import pickle

MODEL_PATH = r"C:\xampp\htdocs\TalentUp\sentiment_model.pkl"

# 1) Load the model + label encoder
with open(MODEL_PATH, "rb") as f:
    saved = pickle.load(f)

pipeline = saved["pipeline"]
label_encoder = saved["label_encoder"]

# 2) Example test inputs
sample_feedback = [
    "Amazing performance!", 
    "Not good at all", 
    "It was okay", 
    "The show was boring", 
    "I loved the energy!"
]

# 3) Predict
predictions = pipeline.predict(sample_feedback)
decoded_preds = label_encoder.inverse_transform(predictions)

# 4) Show results
for text, pred in zip(sample_feedback, decoded_preds):
    print(f"Feedback: {text} → Sentiment: {pred}")
