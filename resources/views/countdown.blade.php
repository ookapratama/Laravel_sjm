<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Countdown Pembukaan Aplikasi</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #FFD700;
      height: 100vh;
      background: #000;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      position: relative;
      text-align: center;
    }

    .background-glow {
      position: absolute;
      top: -20%;
      left: -20%;
      width: 150%;
      height: 150%;
      background: radial-gradient(circle, rgba(255,215,0,0.2) 0%, transparent 70%),
                  radial-gradient(circle, rgba(255,255,0,0.05) 0%, transparent 80%),
                  repeating-conic-gradient(from 0deg, rgba(255,215,0,0.06) 0deg, rgba(0,0,0,0.0) 30deg);
      animation: rotateBg 60s linear infinite;
      z-index: 0;
    }

    @keyframes rotateBg {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    .logo {
      z-index: 2;
      margin-bottom: 20px;
    }

    .logo img {
      height: 100px;
    }

    h1 {
      font-size: 2.2rem;
      margin-bottom: 20px;
      z-index: 2;
    }

    .countdown {
      font-size: 64px;
      font-weight: bold;
      letter-spacing: 4px;
      background: linear-gradient(90deg, #FFD700, #ffcc00, #b8860b);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: shine 3s infinite linear;
      z-index: 2;
    }

    @keyframes shine {
      0% { background-position: -500%; }
      100% { background-position: 500%; }
    }

    .footer {
      margin-top: 40px;
      font-size: 0.9rem;
      color: #888;
      z-index: 2;
    }

    #audio {
      display: none;
    }
  </style>
</head>
<body>

  <div class="background-glow"></div>

  <!-- LOGO -->
  <div class="logo">
    <img src="{{ asset('images/logo.png') }}" alt="Logo Perusahaan">
  </div>

  <h1>Aplikasi akan dibuka dalam</h1>
  <div class="countdown" id="countdown">00:00:00</div>
  <div class="footer">PT. SAIR JAYA MANDIRI</div>

  <!-- AUDIO -->
  <audio id="audio" autoplay loop>
    <source src="{{ asset('audio/buka.mp3') }}" type="audio/mpeg">
    Browser Anda tidak mendukung audio.
  </audio>

  <script>
    const targetDate = new Date("2025-08-08T09:00:00+08:00").getTime(); // 🎯 Jam 9:00 WITA
    const countdownEl = document.getElementById("countdown");
    const audio = document.getElementById("audio");

    // Mainkan audio jika di-click (antisipasi blokir autoplay)
    document.addEventListener('click', () => {
      audio.play().catch(() => {});
    });

    const timer = setInterval(() => {
      const now = new Date().getTime();
      const distance = targetDate - now;

      if (distance <= 0) {
        clearInterval(timer);
        countdownEl.innerHTML = "00:00:00";
        window.location.href = "{{ route('home') }}"; // ⏩ Redirect
        return;
      }

      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      countdownEl.innerHTML =
        String(hours).padStart(2, "0") + ":" +
        String(minutes).padStart(2, "0") + ":" +
        String(seconds).padStart(2, "0");
    }, 1000);
  </script>

</body>
</html>
