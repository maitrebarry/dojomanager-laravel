<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $page_title ?? 'Document PDF' }}</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
