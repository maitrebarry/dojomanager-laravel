<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Licence Régionale TKD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(21, 38, 69, 0.94), rgba(64, 96, 160, 0.82));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 24px;
        }

        .auth-card {
            width: 100%;
            max-width: 500px;
            border: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.26);
        }

        .auth-header {
            background: #152645;
            color: #fff;
            padding: 28px 32px;
        }

        .auth-body {
            padding: 32px;
            background: #fff;
        }

        .form-control,
        .btn {
            border-radius: 8px;
            min-height: 46px;
        }
    </style>
</head>
<body>
    <main class="card auth-card">
        <div class="auth-header">
            <h1 class="h4 mb-1"><i class="fas fa-key"></i> Mot de passe oublié</h1>
            <p class="mb-0 opacity-75">Entrez votre email pour recevoir un lien de réinitialisation.</p>
        </div>
        <div class="auth-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-4">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane"></i> Envoyer le lien
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="text-decoration-none">Retour à la connexion</a>
            </div>
        </div>
    </main>
</body>
</html>
