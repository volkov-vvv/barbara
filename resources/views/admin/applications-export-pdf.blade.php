<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Заявки на обучение</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 6px;
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
    </style>
</head>
<body>
    <h1>Заявки на обучение</h1>
    <div class="meta">
        Всего: {{ $applications->count() }} · Экспорт: {{ now()->format('d.m.Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Регион</th>
                <th>Курс</th>
                <th>Уровень</th>
                <th>Комментарий</th>
                <th>Создана</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applications as $application)
                <tr>
                    <td>{{ $application->id }}</td>
                    <td>{{ $application->full_name }}</td>
                    <td>{{ $application->email }}</td>
                    <td>{{ $application->phone }}</td>
                    <td>{{ $application->region }}</td>
                    <td>{{ $application->course }}</td>
                    <td>{{ $application->level }}</td>
                    <td>{{ $application->comment ?: '—' }}</td>
                    <td>{{ $application->created_at?->format('d.m.Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Заявки не найдены.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
