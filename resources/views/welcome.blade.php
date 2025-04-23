<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Gestione DDT - B&C Prodotti Chimici</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <!-- Styles -->
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Nunito', sans-serif;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f7fafc;
        }
        .container {
            text-align: center;
            padding: 20px;
        }
        .logo {
            max-width: 200px;
            margin-bottom: 20px;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            background-color: #3490dc;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2779bd;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="/img/logo.png" alt="B&C Prodotti Chimici" class="logo">
        <h1>Gestione DDT</h1>
        <a href="/app" class="btn">Accedi all'applicazione</a>
    </div>
</body>
</html>