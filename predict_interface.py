from flask import Flask, render_template_string, request
import pickle

MODEL_PATH = r"C:\xampp\htdocs\TalentUp\sentiment_model.pkl"

with open(MODEL_PATH, "rb") as f:
    saved = pickle.load(f)

pipeline = saved["pipeline"]
label_encoder = saved["label_encoder"]

app = Flask(__name__)

HTML_PAGE = """
<!DOCTYPE html>
<html>
<head>
    <title>Sentiment Predictor</title>
</head>
<body style="font-family: Arial; margin: 40px;">
    <h2>TalentUp Sentiment Predictor</h2>
    <form method="POST">
        <textarea name="text" rows="4" cols="60" placeholder="Enter feedback..."></textarea><br><br>
        <button type="submit">Predict Sentiment</button>
    </form>
    {% if result %}
        <h3>Prediction: {{ result }}</h3>
    {% endif %}
</body>
</html>
"""

@app.route("/", methods=["GET", "POST"])
def home():
    result = None
    if request.method == "POST":
        text = request.form["text"]
        pred = pipeline.predict([text])
        result = label_encoder.inverse_transform(pred)[0]
    return render_template_string(HTML_PAGE, result=result)

if __name__ == "__main__":
    app.run(port=5001, debug=True)
