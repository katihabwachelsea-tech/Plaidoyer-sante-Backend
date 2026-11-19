<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin · MOB-MEDHEALTH CARE SERVICES</title>
    <style>
        body { margin: 0; font-family: Segoe UI, sans-serif; background: #F4F8FB; color: #1F2933; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 420px; background: #fff; border: 1px solid #E1E8ED; border-radius: 16px; padding: 28px; }
        h1 { margin: 0 0 6px; font-size: 24px; }
        p { margin: 0 0 20px; color: #6B7280; }
        label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
        input { width: 100%; box-sizing: border-box; padding: 12px 14px; border: 1px solid #E1E8ED; border-radius: 10px; margin-bottom: 14px; }
        button { width: 100%; background: #107ACA; color: #fff; border: 0; border-radius: 10px; padding: 12px; font-weight: 700; cursor: pointer; }
        .error { background: #FDECEC; color: #E24B4A; padding: 10px 12px; border-radius: 10px; margin-bottom: 14px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="wrap">
        <form class="card" method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <h1>MOB-MEDHEALTH CARE SERVICES</h1>
            <p>Administration — validation des médecins et tarifs.</p>
            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" required>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
