<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#F7F4EE">
    <title>BP Explorer — Comprendre le business plan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=fraunces:400,500,600|hanken-grotesk:400,500,600,700"
        rel="stylesheet"
    >
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="antialiased">
    <div id="app"></div>
</body>
</html>
