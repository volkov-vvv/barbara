<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>{{ $wordSet->title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }
        h2 {
            font-size: 14px;
            margin: 0 0 10px;
        }
        .meta {
            margin-bottom: 14px;
            color: #555;
        }
        .description {
            margin-bottom: 14px;
        }
        .section {
            page-break-after: always;
        }
        .section:last-child {
            page-break-after: auto;
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
        .empty {
            min-height: 1em;
        }
    </style>
</head>
<body>
    @foreach ($sections as $section)
        @php
            $fillText = $section['fillText'];
            $fillTranslation = $section['fillTranslation'];
            $showExample = $section['showExample'];
            $columnCount = 3 + ($showExample ? 1 : 0);
        @endphp

        <div class="section">
            <h1>{{ $wordSet->title }}</h1>
            <div class="meta">
                {{ $wordSet->language?->name ?? '—' }}
                · Слов: {{ $words->count() }}
                @if ($random)
                    · Порядок: случайный
                @endif
                · Экспорт: {{ now()->format('d.m.Y H:i') }}
            </div>

            @if ($wordSet->description !== '')
                <div class="description">{{ $wordSet->description }}</div>
            @endif

            <h2>{{ $section['heading'] }}</h2>

            <table>
                <thead>
                    <tr>
                        <th class="num">№</th>
                        <th>Слово</th>
                        <th>Перевод</th>
                        @if ($showExample)
                            <th>Пример</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($words as $index => $word)
                        <tr>
                            <td class="num">{{ $index + 1 }}</td>
                            <td class="{{ $fillText ? '' : 'empty' }}">
                                {{ $fillText ? $word->text : '' }}
                            </td>
                            <td class="{{ $fillTranslation ? '' : 'empty' }}">
                                {{ $fillTranslation ? $word->translation : '' }}
                            </td>
                            @if ($showExample)
                                <td>{{ $word->example_sentence !== '' ? $word->example_sentence : '—' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}">В словаре пока нет слов.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
