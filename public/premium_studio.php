<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>GlowLink | Carousel Studio</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet" />
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

<style>
  :root {
    --bg-main: #0b0c10;
    --bg-panel: #1f2233;
    --border-color: #3a3b4a;
    --brand-accent: #ff2a5f;
    --text-light: #ffffff;
    --text-muted: #aaaabb;
    --premium-gradient: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Space Grotesk', sans-serif;
  }

  body {
    display: flex;
    height: 100vh;
    overflow: hidden;
    background-color: var(--bg-main);
    color: var(--text-light);
  }

  /* LEFT PANEL: Controls */
  .studio-controls {
    width: 450px;
    background: var(--bg-panel);
    border-right: 1px solid var(--border-color);
    padding: 30px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    overflow-y: auto;
    box-shadow: 0 0 20px rgba(0,0,0,0.7);
    border-radius: 8px;
  }

  .header h2 {
    font-size: 22px;
    font-weight: 700;
    color: var(--brand-accent);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .header p {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 5px;
  }

  .input-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .input-group label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
  }

  .input-field {
    background: transparent;
    border: 1px solid var(--border-color);
    padding: 12px 15px;
    border-radius: 8px;
    font-size: 14px;
    color: var(--text-light);
    transition: border-color 0.3s, box-shadow 0.3s;
  }

  .input-field:focus {
    border-color: var(--brand-accent);
    box-shadow: 0 0 8px rgba(255, 42, 95, 0.6);
    outline: none;
  }

  /* Upload Box */
  .upload-box {
    border: 2px dashed var(--border-color);
    padding: 20px;
    border-radius: 8px;
    background: rgba(255,255,255,0.05);
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    text-align: center;
  }

  .upload-box:hover {
    border-color: var(--brand-accent);
    background: rgba(255, 42, 95, 0.1);
    box-shadow: 0 0 15px rgba(255, 42, 95, 0.2);
  }

  .upload-box i {
    font-size: 20px;
    color: var(--text-muted);
  }

  .upload-text {
    font-size: 12px;
    font-weight: 500;
    margin-top: 8px;
  }

  .upload-preview-img {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: contain;
    display: none;
    background: rgba(0,0,0,0.8);
    border-radius: 8px;
  }

  /* Save Button */
  .save-btn {
    margin-top: 20px;
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #ff2a5f, #e01e4c);
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(224, 30, 76, 0.3);
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .save-btn:hover {
    box-shadow: 0 8px 20px rgba(224, 30, 76, 0.4);
    transform: translateY(-2px);
  }

  /* RIGHT PANEL: Preview */
  .preview-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 30px;
    background: radial-gradient(circle at center, #2d2f3c, var(--bg-main));
    border-radius: 8px;
    margin: 20px;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.4);
    overflow: hidden;
  }

  .preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
  }

  .preview-header span {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 2px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .p-logo {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
  }

  .p-links div {
    font-size: 10px;
    font-weight: 700;
    color: rgba(255,255,255,0.7);
    text-transform: uppercase;
    margin-left: 15px;
  }

  /* Mockup Frame & Slider Preview */
  .mockup-frame {
    flex: 1;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    border: 1px solid var(--border-color);
  }

  .slider-preview {
    width: 100%;
    height: 100%;
    background: var(--premium-gradient);
    position: relative;
    display: flex;
    align-items: center;
    font-family: 'Montserrat', sans-serif;
    overflow: hidden;
    padding: 20px;
  }

  /* Content Text Styles */
  .p-title {
    font-size: 48px;
    font-weight: 700;
    line-height: 1.2;
    color: #fff;
    text-shadow: 1px 1px 4px rgba(0,0,0,0.6);
  }

  .p-desc {
    font-size: 14px;
    color: #ddd;
    max-width: 60%;
    margin-top: 15px;
    line-height: 1.5;
    opacity: 0.9;
  }

  .p-btn {
    background: #fff;
    color: #111;
    padding: 12px 25px;
    border-radius: 4px;
    font-weight: 700;
    font-size: 11px;
    margin-top: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    cursor: pointer;
    transition: all 0.3s ease;
  }
  .p-btn:hover {
    background: #f0f0f0;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
  }

  /* Bottles Container */
  .p-bottles-container {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 45%;
    height: 80%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .p-bottle-active {
    height: 90%;
    filter: drop-shadow(0 15px 30px rgba(0,0,0,0.6));
    transition: all 0.3s ease;
  }

  /* Next Button & Bottles */
  .p-next-btn {
    position: absolute;
    top: 15%;
    left: 80%;
    padding: 10px 15px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    backdrop-filter: blur(5px);
    cursor: pointer;
    font-weight: 700;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    color: #fff;
  }
</style>
</head>
<body>

<div class="studio-controls">
  <div class="header">
    <h2><i class="fa-solid fa-wand-magic-sparkles"></i> GlowLink Studio</h2>
    <p>Design your carousel slide & see changes instantly</p>
  </div>

  <div class="input-group">
    <label>Flavor Headline</label>
    <textarea id="inTitle" class="input-field" rows="2" placeholder="e.g. Fresh&#10;Pomegranate">Fresh&#10;Pomegranate</textarea>
  </div>

  <div class="input-group">
    <label>Description</label>
    <textarea id="inDesc" class="input-field" rows="4">Puren is a captivating pomegranate juice that embodies the essence of this exquisite fruit...</textarea>
  </div>

  <div class="input-group">
    <label>Slide Background Color</label>
    <input type="color" id="inColor" class="input-field color-picker" value="#a71111" />
  </div>

  <div class="input-group">
    <label>Left Background Element (Fruit / Splash)</label>
    <div class="upload-box" onclick="document.getElementById('fruitFile').click()">
      <i class="fa-solid fa-apple-whole"></i>
      <div class="upload-text">Click to upload fruit/bg image (PNG)</div>
      <img id="thumbFruit" class="upload-preview-img" src="" />
    </div>
    <input type="file" id="fruitFile" accept="image/png" style="display:none" onchange="handleImage(event,'previewFruit','thumbFruit')"/>
  </div>

  <div class="input-group">
    <label>Main Product Bottle (Center)</label>
    <div class="upload-box" onclick="document.getElementById('bottleFile').click()">
      <i class="fa-solid fa-bottle-water"></i>
      <div class="upload-text">Click to upload main bottle (PNG)</div>
      <img id="thumbBottle" class="upload-preview-img" src="" />
    </div>
    <input type="file" id="bottleFile" accept="image/png" style="display:none" onchange="handleImage(event,'previewBottle','thumbBottle')"/>
  </div>

  <div class="input-group">
    <label>Next Product Bottle (Right Peek)</label>
    <div class="upload-box" onclick="document.getElementById('nextBottleFile').click()">
      <i class="fa-solid fa-arrow-right-to-bracket"></i>
      <div class="upload-text">Upload next bottle for preview (PNG)</div>
      <img id="thumbNextBottle" class="upload-preview-img" src="" />
    </div>
    <input type="file" id="nextBottleFile" accept="image/png" style="display:none" onchange="handleImage(event,'previewNextBottle','thumbNextBottle')"/>
  </div>

  <button class="save-btn"><i class="fa-solid fa-floppy-disk"></i> Save Variant to Database</button>
</div>

<div class="preview-area">
  <div class="preview-header">
    <span>
      <i class="fa-solid fa-desktop"></i> Live Customer View
    </span>
    <div style="font-size: 12px; color: var(--text-muted);">
      <i class="fa-solid fa-desktop"></i> Desktop Preview
    </div>
  </div>

  <div class="mockup-frame">
    <div class="slider-preview" id="sliderScreen" style="background-color: #a71111;">
      <!-- Navigation -->
      <div class="p-nav">
        <div class="p-logo">puren.</div>
        <div class="p-links">
          <div>Flavours</div>
          <div>Find Us</div>
          <div>About Us</div>
        </div>
      </div>

      <!-- Fruit Image -->
      <div class="p-fruit-container">
        <img id="previewFruit" src="https://png.pngtree.com/png-vector/20230906/ourmid/pngtree-pomegranate-fruit-png-image_9999121.png" alt="Fruit" />
      </div>

      <!-- Text Content -->
      <div class="p-text-content">
        <h1 class="p-title" id="previewTitle">Fresh<br/>Pomegranate</h1>
        <p class="p-desc" id="previewDesc">Puren is a captivating pomegranate juice that embodies the essence of this exquisite fruit...</p>
        <div class="p-btn">View Flavour</div>
      </div>

      <!-- Bottles -->
      <div class="p-bottles-container">
        <img id="previewBottle" class="p-bottle-active" src="https://png.pngtree.com/png-vector/20240129/ourmid/pngtree-bottle-of-pomegranate-juice-png-image_11565502.png" alt="Bottle" />
        <div class="p-next-btn">NEXT <i class="fa-solid fa-arrow-down" style="margin-top: 3px;"></i></div>
        <img id="previewNextBottle" class="p-bottle-next" src="https://png.pngtree.com/png-vector/20240203/ourmid/pngtree-apple-juice-bottle-png-image_11593351.png" alt="Next Bottle" />
      </div>
    </div>
  </div>
</div>

<script>
  // Sync Text
  document.getElementById('inTitle').addEventListener('input', function(e) {
    document.getElementById('previewTitle').innerHTML = e.target.value.replace(/\n/g, '<br>');
  });
  document.getElementById('inDesc').addEventListener('input', function(e) {
    document.getElementById('previewDesc').innerText = e.target.value;
  });

  // Sync Color
  document.getElementById('inColor').addEventListener('input', function(e) {
    document.getElementById('sliderScreen').style.backgroundColor = e.target.value;
  });

  // Image Upload Handler
  function handleImage(event, previewId, thumbId) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById(previewId).src = e.target.result;
        const thumbImg = document.getElementById(thumbId);
        thumbImg.src = e.target.result;
        thumbImg.style.display = 'block';
      }
      reader.readAsDataURL(file);
    }
  }
</script>

</body>
</html>