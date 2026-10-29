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

    $path = trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");
    $parts= array_values(array_filter(explode("/", $path)));

    //echo json_encode(["path" => $path, "parts" => $parts, "id_selected" => $parts[1] ?? null]);


    if($parts[0] != "chess"){
        //return 404 if any other URL
        http_response_code(404);
        exit;
    }

    //echo json_encode(["id" => $id]);

    $database = new Database("localhost","chess_db","root","");

    $database->getConnection();

    $gateway = new Gateway($database);

    $controller = new Controller($gateway);

    //handle POST id in url
    $id = null;
    if (!empty($parts[1])) {
        $id = (string) $parts[1];
    } elseif (!empty($_GET["id"])) {
        $id = (string) $_GET["id"];
    }//this can be null wanna might remove later
    

    //test POST
    //$data= $_POST ?? null;

    $controller -> processRequest($_SERVER["REQUEST_METHOD"], $id);
    
    


?>