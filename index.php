<?php 
    declare(strict_types= 1);

    //autoload classes
    spl_autoload_register(function($class){
        require __DIR__ . "/src/$class.php";
    });

    //make req json
    header("Content-type: application/json; charset=UTF-8");

    set_error_handler("errorHandler::handleError");
    set_exception_handler("errorHandler::handleException");

    $rawPath = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?? '';
    $path = trim(str_replace('index.php', '', $rawPath), '/');
    $parts = array_values(array_filter(explode('/', $path)));

    if($parts[0] != "chess"){
        //return 404 if any other URL
        http_response_code(404);
        exit;
    }

    if (empty($parts) || $parts[0] !== 'chess') {
        http_response_code(404);
        echo json_encode(["message" => "Endpoint not found"]);
        exit;
    }

    $resource = $parts[1] ?? null;
    $id = $parts[2] ?? null;

    $config = file_exists(__DIR__ . "/confing.php") ? require __DIR__ . "/config.php" : [];
    $jwtSecret = $config["jwt_secret"] ?? "fallback_secret_for_dev";

    $database = new Database("localhost","chess_db","root","");

    $database->getConnection();

    $gateway = new Gateway($database);

    $controller = new Controller($gateway, $jwtSecret);

    $controller -> processRequest($_SERVER["REQUEST_METHOD"], $resource, $id);
    
?>