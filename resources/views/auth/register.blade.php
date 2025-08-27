<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link
  rel="icon"
  href="images/sairbeauty-logo.ico"
  type="image/x-icon"
  />
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <img src="{{ asset('images/logo1.png') }}" alt="Sair Beauty">
            </div>
            <h2>PT. SAIR JAYA MANDIRI</h2>

            @if(session('status'))
                <div class="alert success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">username</label>
                    <input id="username" type="text" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                </div>

                <div class="form-group checkbox">
                    <label><input type="checkbox" name="remember"> Ingat saya</label>
                </div>

                <button type="submit" class="btn-login">Login</button>

                <p class="register-link">
                    Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
                </p>
            </form>
        </div>
    </div>
</body>
</html>
