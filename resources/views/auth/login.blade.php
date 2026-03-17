<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
</head>

<body>

    <h2>Login</h2>

    @if ($errors->any())
        <div style="color:red">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="/login">

        @csrf

        <label>Email</label>
        <br>
        <input type="email" name="email">
        <br><br>

        <label>Senha</label>
        <br>
        <input type="password" name="password">
        <br><br>

        <button type="submit">Entrar</button>

    </form>

</body>

</html>
