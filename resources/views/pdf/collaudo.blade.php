<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin-top: 24px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .test-id { font-weight: bold; color: #17a180; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        td, th { padding: 4px 6px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
    </style>
</head>
<body>
    @include('pdf.partials.collaudo-copertina', ['manifest' => $manifest])

    @foreach ($manifest['topics'] as $topic)
        <h2>{{ $topic['titolo'] }}</h2>
        <table>
            <thead>
                <tr><th style="width: 60px;">ID</th><th>Test</th><th>Test automatico</th></tr>
            </thead>
            <tbody>
                @foreach ($topic['test'] as $test)
                    <tr>
                        <td class="test-id">{{ $test['id'] }}</td>
                        <td>{{ $test['descrizione'] }}</td>
                        <td>{{ $test['test_automatico'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
