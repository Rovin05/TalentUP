from flask import Flask, request, jsonify
import pickle

app = Flask(__name__)
MODEL_PATH = r"C:\xampp\htdocs\TalentUp\sentiment_model.pkl"

with open(MODEL_PATH, "rb") as f:
    saved = pickle.load(f)

pipeline = saved["pipeline"]
label_encoder = saved["label_encoder"]

@app.route("/", methods=["POST"])
def predict():
    text = request.form.get("text", "")
    if not text:
        return jsonify(success=False, message="No text provided")
    pred = pipeline.predict([text])
    sentiment = label_encoder.inverse_transform(pred)[0]
    return jsonify(success=True, sentiment=sentiment)

if __name__ == "__main__":
    app.run(port=5001, debug=True)
