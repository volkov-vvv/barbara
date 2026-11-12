<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>{{ $wordSet->title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }
        .meta {
            margin-bottom: 14px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f3f4f6;
            font-weight: bold;
        }
        tr:nth-child(even) td {
            background: #fafafa;
        }
        .num {
            width: 36px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>{{ $wordSet->title }}</h1>
    <div class="meta">
        {{ $wordSet->language?->name ?? '—' }}
        · Слов: {{ $words->count() }}
        · Экспорт: {{ now()->format('d.m.Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">№</th>
                <th>ID</th>
                <th>Слово</th>
                <th>Перевод</th>
                <th>Пример</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($words as $index => $word)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $word->id }}</td>
                    <td>{{ $word->text }}</td>
                    <td>{{ $word->translation }}</td>
                    <td>{{ $word->example_sentence !== '' ? $word->example_sentence : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Слова не найдены.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
