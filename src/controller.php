<?php 

    //echo json_encode returns
    //http_response_code returns
    class Controller{

        private string $logFile;
        public function __construct(private Gateway $gateway){
            $this->logFile = __DIR__ . '/last_request.txt';
        }

        public function processRequest(string $method, ?string $id) : void {

            $fullUrl = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http")
            . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

            if($method !== "GET"){
                file_put_contents($this->logFile, json_encode([
                    "url" => $fullUrl,
                    "method" => $method,
                    "time" => date("Y-m-d H:i:s")
                ])
                );
            }

            if($id){
               $this->processResourceRequest($method,$id);
            }else{
                $this->processControllerRequest($method);
            }
        }

        private function processResourceRequest(string $method, string $id) : void {
            $user = $this->gateway->getUser($id);

            //if there are no users return 404
            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "User Not Found"]);//array that turns into json

                return;
            }

            switch ($method){
                case "GET":
                    $lastCurl = file_exists($this->logFile) 
                        ? json_decode(file_get_contents($this->logFile), true) 
                        : "No curl requests recorded yet";

                    http_response_code(200);
                    echo json_encode([
                        "HTTP" => 200,
                        "message" => "OK",
                        "last_curled" => $lastCurl
                    ]);
                    break;
                case "DELETE":
                    $rows= $this->gateway->deleteUser($id);

                    echo json_encode([
                        "message" => "User $id deleted",
                        "rows" => $rows
                    ]);
                    break;
                case "PATCH":
                    //cast into array so it doesnt return null
		            $data= (array) json_decode(file_get_contents("php://input"),true); //true returns a assoc array

                    $errors= $this->getValidationErrors($data);

                    if(!empty($errors)){
                        http_response_code(422);
                        echo json_encode([
                            "errors" => $errors
                        ]);
                        break;
                    }

                    $rows= $this->gateway->updateUser($user, $data);

                    http_response_code(201);

                    echo json_encode([
                        "message" => "User $id updated",
                        "rows" => $rows
                    ]);
                    break;
            }
        }

        private function processControllerRequest(string $method) : void {
            switch ($method){
                case "GET":
                    $lastCurl = file_exists($this->logFile) 
                        ? json_decode(file_get_contents($this->logFile), true) 
                        : "No curl requests recorded yet";

                    http_response_code(200);
                    echo json_encode([
                        "HTTP" => 200,
                        "message" => "OK",
                        "last_curled" => $lastCurl
                    ]);
                    break;
                case "POST":
                    //cast into array so it doesnt return null
                    $data= (array) json_decode(file_get_contents("php://input"), true);//true returns assoc

                    $errors= $this->getValidationErrors($data); //this is for debugging
                    
                    if(!empty($errors)){
                        
                        http_response_code(422);
                        echo json_encode(["errors" => $errors]);
                        return;
                        
                    }

                    if($data["action"] === "login"){
                        
                        $user = $this->gateway->getUserLogin($data);

                        if(!$user){
                            http_response_code(401);
                            echo json_encode(["message" => "Invalid Username or Password"]);//array that turns into json

                            return;
                        }

                        http_response_code(200);//OK this is a return
                        //this is a return too
                        echo json_encode(["message" => "Successful Login", 
                                          "user" => [
                                                "id" => $user["id"],
                                                "username" => $user["username"],
                                                "password" => $user["pass"]
                                                ]
                        ]);
                        return;

                    }
                    if($data["action"] === "create"){
                        $this->gateway->createUser($data);

                        $user = $this->gateway->getUserLogin($data);

                        http_response_code(201);

                        echo json_encode([
                            "message" => "User Created",
                            "user" => [
                                        "id" => $user["id"],
                                        "username" => $user["username"],
                                        "password" => $user["pass"]
                                    ]
                            ]
                        );
                        return;
                    }

                    if($data["action"] === "creategame"){
                        $game = $this->gateway->createGame($data);

                        http_response_code(201);
                        echo json_encode([
                            "message" => "Game Created Successfully",
                            "game" => [
                                        "id" => $game
                                        ]
                        ]);
                    }

                    break;

                default :
                    //check allowed methods
                    http_response_code(405);
                    header("Allow: GET, POST");
            }
        }

        private function getValidationErrors(array $data): array{
            $errors = [];
            $action = $data["action"] ?? null;

            if($action === "login" || $action === "create"){

                $action = $data["action"] ?? null;

                if (empty($data["user"])) {
                    $errors[] = "username required";
                }
                if (empty($data["pass"])) {
                    $errors[] = "password required";
                }
            }
            if ($action === "creategame") {
                if (empty($data["id"])) {
                    $errors[] = "username required";
                }
            }

            return $errors;

        }

    }

?>