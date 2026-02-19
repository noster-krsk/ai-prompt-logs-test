<?php

// Загрузим переменные окружения из .env файла
if (file_exists(__DIR__ . '/../../.env')) {
    $env = parse_ini_file(__DIR__ . '/../../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// Проверка подключения к MySQL
try {
    $mysqlHost = $_ENV['MYSQL_HOST'] ?? 'mysql';
    $mysqlDb = $_ENV['MYSQL_DATABASE'] ?? 'app_database';
    $mysqlUser = $_ENV['MYSQL_USER'] ?? 'app_user';
    $mysqlPass = $_ENV['MYSQL_PASSWORD'] ?? 'app_password';
    
    $pdo = new PDO(
        "mysql:host=$mysqlHost;dbname=$mysqlDb",
        $mysqlUser,
        $mysqlPass
    );
    $mysqlStatus = '<span style="color: green;">✓ MySQL подключена</span>';
} catch (PDOException $e) {
    $mysqlStatus = '<span style="color: red;">✗ Ошибка MySQL: ' . $e->getMessage() . '</span>';
}

// Проверка Redis
try {
    $redisHost = $_ENV['REDIS_HOST'] ?? 'redis';
    $redisPort = $_ENV['REDIS_PORT'] ?? 6379;
    
    $redis = new Redis();
    $redis->connect($redisHost, (int)$redisPort);
    $redisStatus = '<span style="color: green;">✓ Redis подключена</span>';
    $redis->close();
} catch (Exception $e) {
    $redisStatus = '<span style="color: red;">✗ Ошибка Redis: ' . $e->getMessage() . '</span>';
}

// Проверка расширений
$extensions = get_loaded_extensions();
$redisExtension = in_array('redis', $extensions) ? '<span style="color: green;">✓ Redis расширение установлено</span>' : '<span style="color: red;">✗ Redis расширение не установлено</span>';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Docker Stack Status</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .status {
            background: white;
            margin: 20px 0;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .status:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }
        
        .status h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.3em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .status p {
            color: #555;
            font-size: 1em;
            line-height: 1.6;
        }
        
        .status span {
            font-weight: bold;
        }
        
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.85em;
            color: #333;
            border-left: 4px solid #667eea;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🐳 Docker Stack Status</h1>
        
        <div class="info-grid">
            <div class="status">
                <h3>📦 PHP версия</h3>
                <p><?php echo phpversion(); ?></p>
            </div>
            
            <div class="status">
                <h3>🔌 Redis расширение</h3>
                <p><?php echo $redisExtension; ?></p>
            </div>
        </div>
        
        <div class="status">
            <h3>🗄️ MySQL</h3>
            <p><?php echo $mysqlStatus; ?></p>
        </div>
        
        <div class="status">
            <h3>⚡ Redis</h3>
            <p><?php echo $redisStatus; ?></p>
        </div>
        
        <div class="status">
            <h3>📚 Загруженные расширения PHP</h3>
            <pre><?php echo implode(', ', $extensions); ?></pre>
        </div>
        
        <div class="status">
            <h3>🔧 Переменные окружения</h3>
            <pre><?php 
                echo "MySQL HOST: " . ($_ENV['MYSQL_HOST'] ?? 'mysql') . "\n";
                echo "MySQL DB: " . ($_ENV['MYSQL_DATABASE'] ?? 'app_database') . "\n";
                echo "MySQL USER: " . ($_ENV['MYSQL_USER'] ?? 'app_user') . "\n";
                echo "Redis HOST: " . ($_ENV['REDIS_HOST'] ?? 'redis') . "\n";
                echo "Redis PORT: " . ($_ENV['REDIS_PORT'] ?? '6379') . "\n";
            ?></pre>
        </div>
    </div>
</body>
</html>