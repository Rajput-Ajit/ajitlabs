<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reading Hall Management – Loading</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    background: #0a0a0f;
    font-family: 'Segoe UI', system-ui, sans-serif;
    overflow: hidden;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  #loader {
    position: fixed;
    inset: 0;
    background: #0a0a0f;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    transition: opacity 0.8s ease, visibility 0.8s ease;
  }

  #loader.hide {
    opacity: 0;
    visibility: hidden;
  }

  /* ── Particle canvas ── */
  #particles { position: absolute; inset: 0; }

  /* ── Central scene ── */
  .scene {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 40px;
  }

  /* ── Book icon ── */
  .book-wrap {
    position: relative;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .ring {
    position: absolute;
    border-radius: 50%;
    border: 1.5px solid transparent;
    animation: spin linear infinite;
  }
  .ring-1 {
    width: 120px; height: 120px;
    border-top-color: #6c63ff;
    border-right-color: #6c63ff;
    animation-duration: 2.4s;
    box-shadow: 0 0 18px #6c63ff44;
  }
  .ring-2 {
    width: 96px; height: 96px;
    border-bottom-color: #00d4ff;
    border-left-color: #00d4ff;
    animation-duration: 1.8s;
    animation-direction: reverse;
    box-shadow: 0 0 14px #00d4ff33;
  }
  .ring-3 {
    width: 72px; height: 72px;
    border-top-color: #ff6b9d;
    border-right-color: #ff6b9d;
    animation-duration: 1.2s;
    box-shadow: 0 0 10px #ff6b9d33;
  }

  @keyframes spin { to { transform: rotate(360deg); } }

  .book-svg {
    position: relative;
    z-index: 2;
    animation: bookFloat 3s ease-in-out infinite;
    filter: drop-shadow(0 0 12px #6c63ff88);
  }

  @keyframes bookFloat {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-6px) scale(1.04); }
  }

  /* ── Title ── */
  .title-wrap {
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .title {
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    background: linear-gradient(90deg, #6c63ff, #00d4ff, #ff6b9d, #6c63ff);
    background-size: 300% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gradientFlow 3s linear infinite;
  }

  @keyframes gradientFlow {
    0% { background-position: 0% 50%; }
    100% { background-position: 300% 50%; }
  }

  .subtitle {
    font-size: 12px;
    letter-spacing: 5px;
    text-transform: uppercase;
    color: #4a4a6a;
    animation: subtitlePulse 2s ease-in-out infinite;
  }

  @keyframes subtitlePulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; color: #7a7aaa; }
  }

  /* ── Progress bar ── */
  .progress-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    width: 320px;
  }

  .bar-track {
    width: 100%;
    height: 3px;
    background: #1a1a2e;
    border-radius: 99px;
    overflow: hidden;
    position: relative;
  }

  .bar-fill {
    height: 100%;
    width: 0%;
    border-radius: 99px;
    background: linear-gradient(90deg, #6c63ff, #00d4ff, #ff6b9d);
    background-size: 200% 100%;
    animation: gradientFlow 1.5s linear infinite;
    transition: width 0.3s ease;
    box-shadow: 0 0 10px #6c63ff88;
    position: relative;
  }

  .bar-fill::after {
    content: '';
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 6px; height: 6px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 0 8px #fff, 0 0 16px #6c63ff;
  }

  .bar-shimmer {
    position: absolute;
    top: 0; left: -60px;
    width: 60px; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 1.5s ease-in-out infinite;
  }

  @keyframes shimmer {
    0% { left: -60px; }
    100% { left: 110%; }
  }

  .progress-info {
    display: flex;
    justify-content: space-between;
    width: 100%;
  }

  .status-text {
    font-size: 11px;
    letter-spacing: 1px;
    color: #5a5a8a;
    font-family: 'Courier New', monospace;
  }

  .percent-text {
    font-size: 11px;
    letter-spacing: 1px;
    color: #6c63ff;
    font-family: 'Courier New', monospace;
    font-weight: 700;
  }

  /* ── Dots ── */
  .dots-row {
    display: flex;
    gap: 8px;
  }

  .dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #2a2a4a;
    animation: dotPop 1.2s ease-in-out infinite;
  }

  .dot:nth-child(1) { animation-delay: 0s; }
  .dot:nth-child(2) { animation-delay: 0.15s; }
  .dot:nth-child(3) { animation-delay: 0.3s; }
  .dot:nth-child(4) { animation-delay: 0.45s; }
  .dot:nth-child(5) { animation-delay: 0.6s; }

  @keyframes dotPop {
    0%, 100% { transform: scale(1); background: #2a2a4a; }
    50% { transform: scale(1.6); background: #6c63ff; box-shadow: 0 0 8px #6c63ff; }
  }

  /* ── Corner hexagons ── */
  .hexagons {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
  }

  .hex {
    position: absolute;
    opacity: 0.06;
    animation: hexDrift linear infinite;
  }

  @keyframes hexDrift {
    0% { transform: translateY(110vh) rotate(0deg); opacity: 0; }
    10% { opacity: 0.06; }
    90% { opacity: 0.06; }
    100% { transform: translateY(-10vh) rotate(360deg); opacity: 0; }
  }

  /* ── Grid overlay ── */
  .grid-overlay {
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(rgba(108,99,255,0.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(108,99,255,0.04) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
  }

  /* ── Corner brackets ── */
  .corner {
    position: absolute;
    width: 30px; height: 30px;
    border-color: #6c63ff;
    border-style: solid;
    opacity: 0.6;
    animation: cornerPulse 2s ease-in-out infinite;
  }
  .corner.tl { top: 20px; left: 20px; border-width: 2px 0 0 2px; border-radius: 4px 0 0 0; }
  .corner.tr { top: 20px; right: 20px; border-width: 2px 2px 0 0; border-radius: 0 4px 0 0; }
  .corner.bl { bottom: 20px; left: 20px; border-width: 0 0 2px 2px; border-radius: 0 0 0 4px; }
  .corner.br { bottom: 20px; right: 20px; border-width: 0 2px 2px 0; border-radius: 0 0 4px 0; }

  @keyframes cornerPulse {
    0%, 100% { opacity: 0.3; border-color: #6c63ff; }
    50% { opacity: 0.8; border-color: #00d4ff; }
  }

  /* ── Scanline ── */
  .scanline {
    position: absolute;
    left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #6c63ff44, #00d4ff88, #6c63ff44, transparent);
    animation: scanMove 4s linear infinite;
    pointer-events: none;
  }

  @keyframes scanMove {
    0% { top: 0; opacity: 0; }
    5% { opacity: 1; }
    95% { opacity: 1; }
    100% { top: 100%; opacity: 0; }
  }

  /* ── Content behind loader ── */
  #app {
    position: fixed;
    inset: 0;
    background: #0d0d1a;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #e0e0ff;
    font-size: 24px;
    font-weight: 600;
    letter-spacing: 3px;
    text-transform: uppercase;
    opacity: 0;
    transition: opacity 1s ease 0.4s;
  }

  #app.show { opacity: 1; }

  .app-inner {
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: center;
  }

  .app-inner span {
    font-size: 13px;
    letter-spacing: 4px;
    color: #5a5a8a;
  }
</style>
</head>
<body>

<!-- Main app content (behind loader) -->
<div id="app">
  <div class="app-inner">
    <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
      <rect x="4" y="8" width="40" height="32" rx="3" fill="#6c63ff22" stroke="#6c63ff" stroke-width="1.5"/>
      <line x1="24" y1="8" x2="24" y2="40" stroke="#6c63ff" stroke-width="1.5"/>
      <line x1="4" y1="20" x2="24" y2="20" stroke="#6c63ff66" stroke-width="1"/>
      <line x1="4" y1="28" x2="24" y2="28" stroke="#6c63ff66" stroke-width="1"/>
    </svg>
    Reading Hall Management System
    <span>Welcome. System ready.</span>
  </div>
</div>

<!-- Loader -->
<div id="loader">
  <canvas id="particles"></canvas>
  <div class="grid-overlay"></div>
  <div class="hexagons" id="hexagons"></div>
  <div class="scanline"></div>

  <div class="corner tl"></div>
  <div class="corner tr"></div>
  <div class="corner bl"></div>
  <div class="corner br"></div>

  <div class="scene">

    <!-- Book with rings -->
    <div class="book-wrap">
      <div class="ring ring-1"></div>
      <div class="ring ring-2"></div>
      <div class="ring ring-3"></div>
      <svg class="book-svg" width="44" height="44" viewBox="0 0 44 44" fill="none">
        <rect x="2" y="6" width="20" height="32" rx="2" fill="#6c63ff33" stroke="#6c63ff" stroke-width="1.5"/>
        <rect x="22" y="6" width="20" height="32" rx="2" fill="#00d4ff22" stroke="#00d4ff" stroke-width="1.5"/>
        <line x1="22" y1="6" x2="22" y2="38" stroke="#ffffff33" stroke-width="1"/>
        <line x1="6" y1="14" x2="18" y2="14" stroke="#6c63ff88" stroke-width="1"/>
        <line x1="6" y1="18" x2="18" y2="18" stroke="#6c63ff88" stroke-width="1"/>
        <line x1="6" y1="22" x2="18" y2="22" stroke="#6c63ff88" stroke-width="1"/>
        <line x1="6" y1="26" x2="14" y2="26" stroke="#6c63ff88" stroke-width="1"/>
        <line x1="26" y1="14" x2="38" y2="14" stroke="#00d4ff88" stroke-width="1"/>
        <line x1="26" y1="18" x2="38" y2="18" stroke="#00d4ff88" stroke-width="1"/>
        <line x1="26" y1="22" x2="38" y2="22" stroke="#00d4ff88" stroke-width="1"/>
        <line x1="26" y1="26" x2="34" y2="26" stroke="#00d4ff88" stroke-width="1"/>
        <circle cx="22" cy="4" r="3" fill="#ff6b9d" opacity="0.9"/>
      </svg>
    </div>

    <!-- Title -->
    <div class="title-wrap">
      <div class="title">Reading Hall</div>
      <div class="subtitle">Management System</div>
    </div>

    <!-- Progress -->
    <div class="progress-wrap">
      <div class="bar-track">
        <div class="bar-fill" id="barFill">
          <div class="bar-shimmer"></div>
        </div>
      </div>
      <div class="progress-info">
        <span class="status-text" id="statusText">Initializing core modules...</span>
        <span class="percent-text" id="pct">0%</span>
      </div>
    </div>

    <!-- Dots -->
    <div class="dots-row">
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
      <div class="dot"></div>
    </div>

  </div>
</div>

<script>
(function() {
  // ── Particles ──
  const canvas = document.getElementById('particles');
  const ctx = canvas.getContext('2d');
  let W, H, pts = [];

  function resize() {
    W = canvas.width = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }

  resize();
  window.addEventListener('resize', resize);

  const colors = ['#6c63ff', '#00d4ff', '#ff6b9d', '#a78bfa', '#34d399'];

  class Particle {
    constructor() { this.reset(); }
    reset() {
      this.x = Math.random() * W;
      this.y = Math.random() * H;
      this.r = Math.random() * 1.8 + 0.3;
      this.color = colors[Math.floor(Math.random() * colors.length)];
      this.vx = (Math.random() - 0.5) * 0.4;
      this.vy = (Math.random() - 0.5) * 0.4;
      this.alpha = Math.random() * 0.6 + 0.1;
      this.life = 0;
      this.maxLife = Math.random() * 400 + 200;
    }
    update() {
      this.x += this.vx;
      this.y += this.vy;
      this.life++;
      const t = this.life / this.maxLife;
      const fade = t < 0.1 ? t / 0.1 : t > 0.9 ? (1 - t) / 0.1 : 1;
      this.currentAlpha = this.alpha * fade;
      if (this.life >= this.maxLife) this.reset();
    }
    draw() {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
      ctx.fillStyle = this.color + Math.round(this.currentAlpha * 255).toString(16).padStart(2, '0');
      ctx.fill();
    }
  }

  for (let i = 0; i < 120; i++) pts.push(new Particle());

  function drawConnections() {
    const nearby = pts.filter(p => p.currentAlpha > 0.2);
    for (let i = 0; i < nearby.length; i++) {
      for (let j = i + 1; j < nearby.length; j++) {
        const dx = nearby[i].x - nearby[j].x;
        const dy = nearby[i].y - nearby[j].y;
        const dist = Math.sqrt(dx*dx + dy*dy);
        if (dist < 80) {
          const alpha = (1 - dist / 80) * 0.08;
          ctx.beginPath();
          ctx.moveTo(nearby[i].x, nearby[i].y);
          ctx.lineTo(nearby[j].x, nearby[j].y);
          ctx.strokeStyle = `rgba(108,99,255,${alpha})`;
          ctx.lineWidth = 0.5;
          ctx.stroke();
        }
      }
    }
  }

  function animParticles() {
    ctx.clearRect(0, 0, W, H);
    pts.forEach(p => { p.update(); p.draw(); });
    drawConnections();
    requestAnimationFrame(animParticles);
  }
  animParticles();

  // ── Floating hexagons ──
  const hexCont = document.getElementById('hexagons');
  for (let i = 0; i < 8; i++) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    const size = Math.random() * 80 + 30;
    svg.setAttribute('width', size);
    svg.setAttribute('height', size);
    svg.setAttribute('viewBox', '0 0 100 100');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
    path.setAttribute('points', '50,2 95,25 95,75 50,98 5,75 5,25');
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke', colors[i % colors.length]);
    path.setAttribute('stroke-width', '3');
    svg.appendChild(path);
    svg.classList.add('hex');
    svg.style.left = (Math.random() * 90 + 5) + '%';
    svg.style.animationDuration = (Math.random() * 12 + 10) + 's';
    svg.style.animationDelay = -(Math.random() * 20) + 's';
    hexCont.appendChild(svg);
  }

  // ── Progress simulation ──
  const bar = document.getElementById('barFill');
  const pctEl = document.getElementById('pct');
  const statusEl = document.getElementById('statusText');

  const steps = [
    { at: 5,  msg: 'Initializing core modules...' },
    { at: 18, msg: 'Loading seat configurations...' },
    { at: 32, msg: 'Syncing member database...' },
    { at: 47, msg: 'Fetching reservation data...' },
    { at: 61, msg: 'Loading hall floor plans...' },
    { at: 74, msg: 'Establishing connections...' },
    { at: 87, msg: 'Compiling preferences...' },
    { at: 95, msg: 'Finalizing system check...' },
    { at: 100, msg: 'System ready. Launching...' }
  ];

  let pct = 0;
  let stepIdx = 0;
  let raf;

  function tick() {
    const speed = pct < 40 ? 0.6 : pct < 80 ? 0.4 : 0.2;
    pct = Math.min(100, pct + speed);

    bar.style.width = pct + '%';
    pctEl.textContent = Math.floor(pct) + '%';

    if (stepIdx < steps.length && pct >= steps[stepIdx].at) {
      statusEl.textContent = steps[stepIdx].msg;
      stepIdx++;
    }

    if (pct < 100) {
      raf = requestAnimationFrame(tick);
    } else {
      setTimeout(done, 600);
    }
  }

  function done() {
    document.getElementById('loader').classList.add('hide');
    document.getElementById('app').classList.add('show');
  }

  setTimeout(() => requestAnimationFrame(tick), 400);

})();
</script>
</body>
</html>