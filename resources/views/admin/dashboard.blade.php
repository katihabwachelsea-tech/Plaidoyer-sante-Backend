<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration · Plaidoyer Santé</title>
    <style>
        body { margin: 0; font-family: Segoe UI, sans-serif; background: #F4F8FB; color: #1F2933; }
        header { background: #107ACA; color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; }
        main { max-width: 980px; margin: 0 auto; padding: 24px; }
        h1 { margin: 0; font-size: 20px; }
        h2 { margin: 28px 0 12px; font-size: 18px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .stat, .card, table { background: #fff; border: 1px solid #E1E8ED; border-radius: 14px; }
        .stat { padding: 14px; }
        .stat b { display: block; font-size: 24px; color: #107ACA; }
        .stat span { color: #6B7280; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; overflow: hidden; }
        th, td { text-align: left; padding: 12px 14px; border-bottom: 1px solid #E1E8ED; font-size: 14px; }
        th { color: #6B7280; font-size: 12px; }
        button, .btn { background: #107ACA; color: #fff; border: 0; border-radius: 8px; padding: 8px 12px; font-weight: 700; cursor: pointer; }
        input[type="number"] { width: 110px; padding: 8px; border: 1px solid #E1E8ED; border-radius: 8px; }
        .flash { padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; }
        .ok { background: #E8F8F1; color: #0E9F6E; }
        .err { background: #FDECEC; color: #E24B4A; }
        .muted { color: #6B7280; }
        form.inline { display: flex; gap: 8px; align-items: center; }
        @media (max-width: 700px) { .stats { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header>
        <h1>Plaidoyer Santé — Administration</h1>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit">Déconnexion</button>
        </form>
    </header>
    <main>
        @if (session('success'))
            <div class="flash ok">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="flash err">{{ session('error') }}</div>
        @endif

        <div class="stats">
            <div class="stat"><b>{{ $stats['medecins_valides'] }}</b><span>Médecins validés</span></div>
            <div class="stat"><b>{{ $stats['medecins_attente'] }}</b><span>En attente</span></div>
            <div class="stat"><b>{{ $stats['rdv_confirmes'] }}</b><span>RDV confirmés</span></div>
            <div class="stat"><b>{{ $stats['rdv'] }}</b><span>RDV au total</span></div>
            <div class="stat"><b>{{ $stats['paiements'] }}</b><span>Paiements reçus</span></div>
            <div class="stat"><b>{{ $stats['patients'] }}</b><span>Patients inscrits</span></div>
        </div>

        <h2>Médecins à valider</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Spécialité</th>
                    <th>Hôpital</th>
                    <th>Licence</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pending as $medecin)
                    <tr>
                        <td>{{ $medecin->user?->nom }}<div class="muted">{{ $medecin->user?->email }}</div></td>
                        <td>{{ $medecin->specialite }}</td>
                        <td>{{ $medecin->hopital }}</td>
                        <td>{{ $medecin->licence }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.medecins.validate', $medecin->user_id) }}">
                                @csrf
                                <button type="submit">Valider</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Aucun médecin en attente.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Grille tarifaire</h2>
        <table>
            <thead>
                <tr>
                    <th>Consultation</th>
                    <th>Prix (FBu)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($services as $service)
                    <tr>
                        <td>{{ $service->nom_service }}</td>
                        <td colspan="2">
                            <form class="inline" method="POST" action="{{ route('admin.services.update', $service->id) }}">
                                @csrf
                                <input type="number" name="prix" min="0" step="100" value="{{ (int) $service->prix }}" required>
                                <button type="submit">Enregistrer</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
</body>
</html>
