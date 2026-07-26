<h1>Montagna Servizi S.C.p.A.</h1>
<p><strong>Documento di collaudo — {{ $manifest['titolo'] }}</strong></p>

<h2>Parte 1 — Come eseguire il collaudo</h2>
<p>Applicazione: <a href="{{ $manifest['parte_1']['app_url'] }}">{{ $manifest['parte_1']['app_url'] }}</a></p>
<p>Mailpit (email di test): <a href="{{ $manifest['parte_1']['mailpit_url'] }}">{{ $manifest['parte_1']['mailpit_url'] }}</a></p>
<table>
    <thead><tr><th>Ruolo</th><th>Email</th><th>Password</th></tr></thead>
    <tbody>
        @foreach ($manifest['parte_1']['credenziali'] as $cred)
            <tr><td>{{ $cred['ruolo'] }}</td><td>{{ $cred['email'] }}</td><td>{{ $cred['password'] }}</td></tr>
        @endforeach
    </tbody>
</table>
