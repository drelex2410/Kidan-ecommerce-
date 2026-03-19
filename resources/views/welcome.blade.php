<!-- Legacy alternate blade entry. The active storefront route returns resources/views/frontend/app.blade.php. -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kidan – Luxury African Heritage Fashion</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link 
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@300;400;500;600&family=Lato:wght@300;400;600;700&display=swap" 
        rel="stylesheet"
    >

    <!-- Vite Assets -->
    @vite(['resources/css/global.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Lato', sans-serif;
            background-color: #fff;
            color: #222;
            margin: 0;
            padding: 0;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
        }
    </style>
</head>
<body>
    <div id="app"></div>
</body>
</html>
