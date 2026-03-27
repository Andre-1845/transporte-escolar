<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Login - BusWay</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* BASE */
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;

            display: flex;
            justify-content: center;
            align-items: center;

            background: linear-gradient(135deg, #1a1a2e, #16213e);
        }

        /* CARD */
        .login-card {
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            width: 100%;
            max-width: 380px;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        /* LOGO */
        .logo {
            width: 70px;
            margin-bottom: 1rem;
        }

        /* TITLE */
        .login-title {
            margin-bottom: 1.5rem;
        }

        .login-title h2 {
            margin: 0;
        }

        .login-title p {
            font-size: 0.85rem;
            color: #666;
        }

        /* INPUT */
        .form-group {
            text-align: left;
            margin-bottom: 1rem;
        }

        .form-group label {
            font-size: 0.8rem;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-top: 5px;
        }

        .form-control:focus {
            outline: none;
            border-color: #2ecc71;
        }

        /* BUTTON */
        .btn-login {
            width: 100%;
            padding: 10px;
            background: #2ecc71;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: bold;
        }

        .btn-login:hover {
            background: #27ae60;
        }

        /* ERROR */
        .alert {
            background: #f8d7da;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #721c24;
        }
    </style>
</head>

<body>

    <div class="login-card">

        <!-- LOGO -->
        <img src="{{ asset('images/logo.png') }}" class="logo">

        <!-- TITLE -->
        <div class="login-title">
            <h2>BusWay</h2>
            <p>Painel Administrativo</p>
        </div>

        <!-- ERRO -->
        @if ($errors->any())
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <!-- FORM -->
        <form method="POST" action="/login">
            @csrf

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>

        </form>

    </div>

</body>

</html>
