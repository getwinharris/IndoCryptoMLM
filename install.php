<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install App | Indo Global Services</title>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#00e5ff">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="IGS">
<link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0c10;--card:#141820;--border:rgba(255,255,255,.08);--primary:#00e5ff;--secondary:#7000ff;--text:#fff;--muted:#8892a4}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Outfit',sans-serif}
body{background:var(--bg);color:var(--text);min-height:100vh}
.glow1{position:fixed;top:-10%;left:-5%;width:50vw;height:50vw;background:radial-gradient(circle,rgba(112,0,255,.3),transparent 70%);pointer-events:none;border-radius:50%;z-index:0}
.glow2{position:fixed;bottom:-10%;right:-5%;width:45vw;height:45vw;background:radial-gradient(circle,rgba(0,229,255,.18),transparent 70%);pointer-events:none;border-radius:50%;z-index:0}
header{display:flex;justify-content:space-between;align-items:center;padding:1.5rem 2rem;border-bottom:1px solid var(--border);position:relative;z-index:1}
.logo{font-size:1.3rem;font-weight:800;text-decoration:none;color:var(--text);display:flex;align-items:center;gap:.6rem;}.logo img{height:32px;border-radius:50%;box-shadow:0 0 10px rgba(0,229,255,0.3);}.logo span{color:var(--primary)}
.nav-links{display:flex;gap:1.2rem;align-items:center}
.nav-links a{color:var(--muted);text-decoration:none;font-size:.9rem;transition:color .2s}.nav-links a:hover{color:var(--text)}
.btn-primary{background:linear-gradient(45deg,var(--primary),var(--secondary));color:#fff;padding:.6rem 1.4rem;border-radius:50px;text-decoration:none;font-weight:700;font-size:.9rem}
main{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:80vh;padding:3rem 1.5rem;text-align:center;position:relative;z-index:1}
.icon-wrap{width:130px;height:130px;border-radius:28px;overflow:hidden;box-shadow:0 0 60px rgba(0,229,255,.3);margin-bottom:2rem;border:2px solid rgba(0,229,255,.3)}
.icon-wrap img{width:100%;height:100%;object-fit:cover}
h1{font-size:2.5rem;font-weight:800;margin-bottom:.8rem}
h1 span{background:linear-gradient(45deg,var(--primary),var(--secondary));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
p.sub{color:var(--muted);font-size:1.05rem;max-width:500px;line-height:1.6;margin-bottom:2.5rem}
.install-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;width:100%;max-width:560px;margin-bottom:2.5rem}
.install-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:1.5rem;text-align:left}
.install-card .step-num{background:linear-gradient(45deg,var(--primary),var(--secondary));-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-size:2rem;font-weight:800;margin-bottom:.5rem}
.install-card h3{font-size:.95rem;margin-bottom:.4rem}
.install-card p{color:var(--muted);font-size:.82rem;line-height:1.5}
#install-btn{display:inline-block;padding:1rem 2.5rem;font-size:1.1rem;font-weight:800;border:none;border-radius:50px;background:linear-gradient(45deg,var(--primary),var(--secondary));color:#fff;cursor:pointer;box-shadow:0 0 40px rgba(0,229,255,.4);transition:transform .2s,box-shadow .2s;margin-bottom:1rem}
#install-btn:hover{transform:translateY(-2px);box-shadow:0 0 60px rgba(0,229,255,.5)}
.already-installed{display:none;background:rgba(0,200,83,.1);border:1px solid rgba(0,200,83,.3);color:#00c853;padding:.8rem 1.5rem;border-radius:50px;font-weight:600;margin-bottom:1rem}
.open-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.9rem 2rem;border-radius:50px;background:rgba(255,255,255,.05);border:1px solid var(--border);color:var(--text);text-decoration:none;font-weight:600;font-size:.95rem;transition:background .2s}
.open-btn:hover{background:rgba(255,255,255,.1)}
.features{display:flex;gap:1.5rem;flex-wrap:wrap;justify-content:center;margin-bottom:2rem;max-width:560px}
.feature{display:flex;align-items:center;gap:.5rem;color:var(--muted);font-size:.88rem}
.feature::before{content:'✓';color:var(--primary);font-weight:700}
footer{border-top:1px solid var(--border);padding:1.5rem 2rem;display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;position:relative;z-index:1}
footer a{color:var(--muted);text-decoration:none;font-size:.85rem;transition:color .2s}footer a:hover{color:var(--primary)}
</style>
</head>
<body>
<div class="glow1"></div><div class="glow2"></div>
<header>
  <a class="logo" href="/"><img src="/assets/logo.jpg" alt="IGS"> <span>Indo</span> Global Services</a>
  <div class="nav-links">
    <a href="/">Home</a>
    <a href="/app/auth/login.php">Sign In</a>
    <a href="/app/auth/register.php" class="btn-primary">Get Started</a>
  </div>
</header>

<main>
  <div class="icon-wrap">
    <img src="/assets/icons/icon-512.png" alt="IGS App Icon">
  </div>
  <h1>Install <span>IGS App</span></h1>
  <p class="sub">Get the full Indo Global Services experience on your phone or desktop. Install as a native-like app — no App Store required.</p>

  <button id="install-btn">📲 Install App Now</button>
  <div class="already-installed" id="installed-msg">✓ App already installed on your device!</div>
  <a href="/app/auth/login.php" class="open-btn" id="open-app" style="display:none">🚀 Open Dashboard</a>

  <br><br>
  <div class="features">
    <span class="feature">Works offline</span>
    <span class="feature">Home screen icon</span>
    <span class="feature">Full-screen mode</span>
    <span class="feature">Fast &amp; secure</span>
    <span class="feature">No App Store needed</span>
    <span class="feature">Auto-updates</span>
  </div>

  <div class="install-grid" id="manual-steps" style="display:none">
    <div class="install-card"><div class="step-num">1</div><h3>iPhone / iPad (Safari)</h3><p>Tap the <strong>Share</strong> icon (📤) at the bottom of Safari, then tap <strong>"Add to Home Screen"</strong></p></div>
    <div class="install-card"><div class="step-num">2</div><h3>Android (Chrome)</h3><p>Tap the <strong>⋮ menu</strong> in Chrome, then tap <strong>"Add to Home Screen"</strong> or <strong>"Install App"</strong></p></div>
    <div class="install-card"><div class="step-num">3</div><h3>Desktop (Chrome)</h3><p>Click the <strong>install icon</strong> (⊕) in the address bar, or use Chrome menu → <strong>"Install Indo Global Services"</strong></p></div>
    <div class="install-card"><div class="step-num">4</div><h3>Desktop (Edge)</h3><p>Click the <strong>app icon</strong> in the address bar, then click <strong>"Install"</strong></p></div>
  </div>
</main>

<footer>
  <a href="/">Home</a>
  <a href="/app/auth/login.php">Sign In</a>
  <a href="/legal/terms.html">Terms</a>
  <a href="/legal/privacy.html">Privacy</a>
  <a href="/legal/refund.html">Refund Policy</a>
</footer>

<script>
if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
let deferredPrompt = null;
window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredPrompt = e;
});

document.getElementById('install-btn').addEventListener('click', async () => {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    deferredPrompt = null;
    if (outcome === 'accepted') {
      document.getElementById('install-btn').style.display = 'none';
      document.getElementById('installed-msg').style.display = 'inline-block';
      document.getElementById('open-app').style.display = 'inline-flex';
    }
  } else {
    // Show manual steps fallback
    document.getElementById('manual-steps').style.display = 'grid';
  }
});

window.addEventListener('appinstalled', () => {
  document.getElementById('install-btn').style.display = 'none';
  document.getElementById('installed-msg').style.display = 'inline-block';
  document.getElementById('manual-steps').style.display = 'none';
  document.getElementById('open-app').style.display = 'inline-flex';
});

if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
  document.getElementById('install-btn').style.display = 'none';
  document.getElementById('installed-msg').style.display = 'inline-block';
  document.getElementById('open-app').style.display = 'inline-flex';
}
</script>
</body></html>
