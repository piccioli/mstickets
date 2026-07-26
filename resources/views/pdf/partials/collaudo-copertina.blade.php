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

<h3>Come accedere a Mailpit</h3>
<p>Le email inviate dall'ambiente UAT non escono realmente: sono intercettate da Mailpit, raggiungibile
all'indirizzo sopra con autenticazione HTTP (utente/password forniti separatamente dal team, non stampati
in questo documento per non esporli insieme all'URL pubblico).</p>

<h3>Come segnalare un problema</h3>
<p>Per ogni test fallito, annotare l'ID del test (es. F1-03), una descrizione di cosa è successo invece del
comportamento atteso, e se possibile uno screenshot.</p>

<h3>Indice</h3>
<ol>
    @foreach ($manifest['topics'] as $topic)
        <li>{{ $topic['titolo'] }} ({{ count($topic['test']) }} test)</li>
    @endforeach
</ol>
