# train_sentiment.py
"""
Train a TF-IDF + LogisticRegression sentiment classifier and save it.
Make sure DATA_PATH points to your real dataset file.
"""

import os, re, pickle
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.pipeline import Pipeline
from sklearn.linear_model import LogisticRegression
from sklearn.preprocessing import LabelEncoder
from sklearn.metrics import classification_report, accuracy_score, confusion_matrix

# -------------------
# CHANGE THIS PATH 👇 to your actual dataset file name
DATA_PATH = r"C:\xampp\htdocs\TalentUp\data\21dcc3fb-4817-4a64-9156-28fa16505f39.csv"
MODEL_OUT = r"C:\xampp\htdocs\TalentUp\sentiment_model.pkl"
RANDOM_STATE = 42
# -------------------

def clean_text(s: str) -> str:
    """Basic text cleaning"""
    s = str(s).lower()
    s = re.sub(r"http\S+", " ", s)      # remove links
    s = re.sub(r"@\w+", " ", s)         # remove mentions
    s = re.sub(r"[^a-z0-9\s]", " ", s)  # keep alphanumerics
    s = re.sub(r"\s+", " ", s).strip()
    return s

# 1) Load CSV safely
if not os.path.exists(DATA_PATH):
    raise FileNotFoundError(f"CSV not found at {DATA_PATH}. Please check the path.")

try:
    df = pd.read_csv(DATA_PATH, encoding="utf-8", on_bad_lines="skip")
except UnicodeDecodeError:
    df = pd.read_csv(DATA_PATH, encoding="latin1", on_bad_lines="skip")

# 2) Pick correct columns
if "text" in df.columns and "sentiment" in df.columns:
    text_col, label_col = "text", "sentiment"
else:
    # Auto-detect fallback
    text_col = next((c for c in df.columns if c.lower() in ["text","tweet","message","content"]), df.columns[0])
    label_col = next((c for c in df.columns if c.lower() in ["sentiment","label","target","class"]), df.columns[-1])
    print(f"Auto-detected columns: text={text_col}, label={label_col}")

df = df[[text_col, label_col]].dropna().reset_index(drop=True)

# 3) Clean
df["text_clean"] = df[text_col].apply(clean_text)
X = df["text_clean"].values
y_raw = df[label_col].astype(str).str.lower().str.strip().values

# 4) Encode labels
le = LabelEncoder()
y = le.fit_transform(y_raw)
print("Classes:", le.classes_)

# 5) Split data
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=RANDOM_STATE, stratify=y
)

# 6) Build pipeline
pipeline = Pipeline([
    ("tfidf", TfidfVectorizer(ngram_range=(1,2), max_features=15000, min_df=3)),
    ("clf", LogisticRegression(max_iter=2000, solver="saga", class_weight="balanced", random_state=RANDOM_STATE))
])

print("Training model...")
pipeline.fit(X_train, y_train)

# 7) Evaluate
y_pred = pipeline.predict(X_test)
acc = accuracy_score(y_test, y_pred)
print("Test Accuracy:", acc)
print(classification_report(y_test, y_pred, target_names=le.classes_))
print("Confusion Matrix:\n", confusion_matrix(y_test, y_pred))

# 8) Save model
with open(MODEL_OUT, "wb") as f:
    pickle.dump({"pipeline": pipeline, "label_encoder": le}, f)
print(f"✅ Model saved to {MODEL_OUT}")
