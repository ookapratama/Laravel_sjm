<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="icon" href="images/sairbeauty-logo.ico" type="image/x-icon" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        /* --- CSS untuk Validasi Form Login --- */
        /* Menyorot input yang memiliki error */
        .form-group .is-invalid {
            border-color: #e74c3c;
            box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
        }
        /* Menampilkan pesan error di bawah input */
        .invalid-feedback {
            color: #e74c3c; /* Warna teks merah */
            font-size: 0.8em;
            margin-top: 5px;
            display: block; /* Pastikan elemen ini selalu terlihat saat ada */
        }
        /* Opsi: Gaya untuk pesan error umum */
        .alert.error {
            background-color: #fdeaea;
            color: #c0392b;
            border: 1px solid #e74c3c;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        /* --- CSS untuk Toggle Password --- */
        .password-input-wrapper {
            position: relative;
        }
        .password-input-wrapper input {
            padding-right: 35px; /* Beri ruang di sisi kanan input untuk ikon */
        }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }
        .password-toggle:hover {
            color: #333;
        }
    </style>
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
                    <label for="username">username</label>
                    <input id="username" 
                           type="text" 
                           name="username" 
                           value="{{ old('username') }}" 
                           required autofocus 
                           class="@error('username') is-invalid @enderror">
                    @error('username')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input id="password"
                               type="password"
                               name="password"
                               required
                               class="@error('password') is-invalid @enderror">
                        <span class="password-toggle" id="togglePassword">
                            <i class="bi bi-eye-slash"></i>
                        </span>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                
                <div class="form-group checkbox">
                    <label><input type="checkbox" name="remember"> Ingat saya</label>
                </div>

                <button type="submit" class="btn-login">Login</button>

               
            </form>
        </div>
    </div>
    
    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>