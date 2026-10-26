<?php 
    declare(strict_types= 1);

    //autoload classes
    spl_autoloader_register(function($class){
        require __DIR__ . "/src/$class.php";
    });

    //make req json
    header("Content-type: application/json; charset=UTF-8");

    $parts= exploed("/" , $_SERVER["REQUEST_URI"]);

    if($parts[1] != "chess"){
        //return 404 if any other URL
        http_response_code(404);
        exit;
    }

?>