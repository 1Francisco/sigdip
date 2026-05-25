<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGDIP - Iniciar Sesión</title>
    <link rel="icon" type="image/png" href="{{ asset('icon_png.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icon_png.png') }}">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c6e49; /* Deep Green for Agriculture/Vet theme */
            --secondary-color: #4c956c;
            --accent-color: #d68c45;
            --text-color: #2b2d42;
            --bg-color: #f0f3f5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        html, body {
            width: 100%;
            height: 100%;
            height: 100dvh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            overflow-y: auto; /* Permite scroll vertical únicamente si es necesario (ej: cuando sale el teclado) */
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #eef2f3 0%, #8e9eab 100%);
            position: relative;
            padding: 20px 10px;
        }

        /* Contenedor de figuras de fondo para evitar que desborden o creen barras de desplazamiento */
        .bg-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        /* Dynamic Background Shapes */
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            animation: float 10s infinite ease-in-out alternate;
        }

        .shape-1 {
            width: 400px;
            height: 400px;
            background: rgba(44, 110, 73, 0.4);
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 500px;
            height: 500px;
            background: rgba(214, 140, 69, 0.3);
            bottom: -150px;
            right: -100px;
            animation-delay: 2s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, 50px) scale(1.1); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 90%;
            max-width: 400px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                width: 92%;
            }
            .logo-area {
                margin-bottom: 20px;
            }
        }

        .logo-area {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-area h1 {
            color: var(--primary-color);
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 5px;
        }

        .logo-area p {
            color: #555;
            font-size: 14px;
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-size: 14px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            font-size: 15px;
            color: var(--text-color);
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }

        .form-control:focus {
            outline: none;
            background: #fff;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(76, 149, 108, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(44, 110, 73, 0.2);
            margin-top: 10px;
        }

        .btn-login:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(44, 110, 73, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
        }
    </style>
</head>
<body>

    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
    </div>

    <div class="login-container">
        <div class="logo-area">
            <img src="{{ asset('icon_png.png') }}" alt="SIGDIP" style="width: 72px; height: 72px; object-fit: contain; margin-bottom: 12px;">
            <h1>SIGDIP</h1>
            <p>Sistema Integral de Gestión y Dictamen</p>
        </div>

        @if ($errors->any())
            <div class="error-message">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="admin@sigdip.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required style="padding-right: 50px;">
                    <button type="button" id="togglePassword" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--secondary-color); cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; z-index: 10;">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Ingresar al Panel</button>
        </form>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const toggleIcon = document.querySelector('#toggleIcon');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            if (type === 'password') {
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            } else {
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            }
        });
    </script>
</body>
</html>
