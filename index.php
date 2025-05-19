<?php
// ==== Si la requête est AJAX (JavaScript fetch) ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ville'])) {
    $apiKey = '5e611d4c7e6779351247eed784e77ac4'; // Ta clé API OpenWeatherMap
    $ville = urlencode($_POST['ville']);

    // Appel à l'API pour la météo actuelle
    $weatherUrl = "https://api.openweathermap.org/data/2.5/weather?q=$ville&units=metric&lang=fr&appid=$apiKey";
    $forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q=$ville&units=metric&lang=fr&appid=$apiKey";

    $weatherData = @file_get_contents($weatherUrl);
    $forecastData = @file_get_contents($forecastUrl);

    if (!$weatherData || !$forecastData) {
        echo json_encode(['error' => 'Impossible de récupérer les données météo.']);
        exit;
    }

    $weather = json_decode($weatherData, true);
    $forecast = json_decode($forecastData, true);

    if ($weather['cod'] != 200 || $forecast['cod'] != "200") {
        echo json_encode(['error' => 'Ville non trouvée ou erreur API.']);
        exit;
    }

    // Extraire les données utiles
    $result = [
        'ville' => $weather['name'],
        'temperature' => round($weather['main']['temp']),
        'humidite' => $weather['main']['humidity'],
        'description' => $weather['weather'][0]['description'],
        'icon' => $weather['weather'][0]['icon'],
        'previsions' => []
    ];

    // Extraire une prévision par jour (midi) sur les 5 jours
    foreach ($forecast['list'] as $item) {
        if (strpos($item['dt_txt'], '12:00:00') !== false) {
            $result['previsions'][] = [
                'date' => date('D d/m', strtotime($item['dt_txt'])),
                'temp' => round($item['main']['temp']),
                'desc' => $item['weather'][0]['description'],
                'icon' => $item['weather'][0]['icon']
            ];
        }
    }

    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Météo en Temps Réel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: linear-gradient(to right, #1e3c72, #2a5298);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            color: #fff;
        }

        .container {
            max-width: 700px;
            margin: 50px auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        form {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }

        input[type="text"] {
            width: 70%;
            padding: 12px;
            border: none;
            border-radius: 8px 0 0 8px;
            font-size: 16px;
        }

        button {
            padding: 12px 20px;
            border: none;
            background-color: #ffb347;
            color: #333;
            font-weight: bold;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
        }

        .weather-result {
            text-align: center;
        }

        .weather-result img {
            width: 100px;
        }

        .forecast {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .forecast-day {
            flex: 1 1 30%;
            background-color: rgba(255,255,255,0.2);
            margin: 5px;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }

        .error {
            text-align: center;
            color: #ff4d4d;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🌤️ Météo en Temps Réel</h1>
    <form onsubmit="getWeather(); return false;">
        <input type="text" id="ville" placeholder="Entrez une ville...">
        <button type="submit">Voir</button>
    </form>

    <div id="result" class="weather-result"></div>
</div>

<script>
    function getWeather() {
        const ville = document.getElementById('ville').value.trim();
        if (!ville) return;

        const formData = new FormData();
        formData.append('ville', ville);

        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            const result = document.getElementById('result');
            if (data.error) {
                result.innerHTML = `<p class="error">${data.error}</p>`;
                return;
            }

            let html = `
                <h2>${data.ville}</h2>
                <img src="https://openweathermap.org/img/wn/${data.icon}@2x.png" alt="${data.description}">
                <p><strong>${data.description}</strong></p>
                <p>🌡️ Température : ${data.temperature}°C</p>
                <p>💧 Humidité : ${data.humidite}%</p>
                <div class="forecast">
            `;

            data.previsions.forEach(jour => {
                html += `
                    <div class="forecast-day">
                        <h4>${jour.date}</h4>
                        <img src="https://openweathermap.org/img/wn/${jour.icon}.png" alt="${jour.desc}">
                        <p>${jour.temp}°C</p>
                        <p>${jour.desc}</p>
                    </div>
                `;
            });

            html += '</div>';
            result.innerHTML = html;
        })
        .catch(err => {
            document.getElementById('result').innerHTML = `<p class="error">Erreur de communication.</p>`;
        });
    }
</script>
</body>
</html>
