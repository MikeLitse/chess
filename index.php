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

    $parts= explode("/" , $_SERVER["REQUEST_URI"]);

    if($parts[1] != "chess"){
        //return 404 if any other URL
        http_response_code(404);
        exit;
    }

    $id = $parts[2] ?? null;//this can be null wanna might remove later

    $database = new Database("localhost","chess_db","root","");

    $database->getConnection();

    $gateway = new Gateway($database);

    $data = $gateway->getAllUsers();

    /*foreach($data as $user){
        echo json_encode($user);
    }*/

    $controller = new Controller($gateway);

    $controller -> processRequest($_SERVER["REQUEST_METHOD"], $id)

    /*
    $ch = require "src/initcurl.php";

    curl_setopt($ch, CURLOPT_URL, "http://localhost/chess/");

    $response = curl_exec($ch);

    if ($response === false) {
        http_response_code(500);
        echo json_encode([
            "error" => "cURL request failed",
            "details" => curl_error($ch),
            "code" => curl_errno($ch)
        ]);
        exit;
    }

    echo $response;
    */
?>