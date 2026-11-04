<?php 

    spl_autoload_register(function($class){
        require __DIR__ . "/src/$class.php";
    });

    class Controller{

        private string $logFile;
        public function __construct(private Gateway $gateway){
            $this->logFile = __DIR__ . '/last_request.txt';
        }

        public function processRequest(string $method, ?string $resource, ?string $id) : void {
            
            //save last curl
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

            switch($resource){
                case "users":
                    $this->handleUsers($method, $id);
                    break;
                case "games":
                    $this->handleGames($method, $id);
                    break;
                case "auth":
                    $this->handleAuth($method, $id);
                    break;
                case "chess":
                    //$this->handleChess($method, $id);
                    break;
                case "matchHistory":
                    //$this->handleMatchHistory
                    break;
                case null:
                case "":
                    $this->handleRoot($method);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(["message" => "Resource $resource not found"]);
                    break;
            }
        }

        public function handleUsers(string $method, ?int $id) : void {
            if($id !== null){

                switch($method){
                    case "GET":
                        $user = $this->gateway->getUser($id);
                        if(!$user){
                            http_response_code(404);
                            echo json_encode(["message" => "User not found"]);
                            return;
                        }
                        unset($user["pass"]);
                        echo json_encode($user);
                        break;
                    case "PATCH":
                        $data = (array) json_decode(file_get_contents("php://input"), true);
                        $user = $this->gateway->getUser($id);
                            if(!$user){
                                http_response_code(404);
                                echo json_encode(["message" => "User not found"]);
                            return;
                            }

                        $rows = $this->gateway->updateUser($user, $data);
                        http_response_code(200);
                        echo json_encode(["message" => "User update", 
                                        "rows" => $rows,
                                        "user" => $user["username"]
                                        ]);
                        break;
                    case "DELETE":
                        $rows = $this->gateway->deleteUser((string)$id);
                        http_response_code(200);
                        echo json_encode(["message" => "User deleted", 
                                        "rows" => $rows,
                                        "id" => $id
                        ]);
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: GET, PATCH, DELETE");
                        break;
                }
            }else{

                switch($method){
                    case "GET":
                        $users = $this->gateway->getAllUsers();

                        $nopass = array_map(function ($u){
                            unset($u["pass"]);
                            return $u;
                        }, $users);
                        echo json_encode($nopass);
                        break;

                    case "POST":
                        $data = (array) json_decode(file_get_contents("php://input"), true);
                        $errors = $this->validateUserPayload($data);

                        if(!empty($errors)){
                        http_response_code(422);
                            echo json_encode(["errors" => $errors]);
                            return;
                        }

                        $newId = $this->gateway->createUser($data);
                        http_response_code(201);
                        header("Location: /chess/users/$newId");
                        echo json_encode([
                            "message" => "User Created",
                            "user" => [
                                "id" => (int) $newId,
                                "username" => $data["user"]
                            ]
                        ]);
                        break;

                    default:
                        http_response_code(405);
                        header("Allow: GET, POST");
                        break;
                }
            }   
        }

        private function handleGames(string $method, ?int $id) : void {
            if($id !== null){

                switch($method){
                    case "GET":
                        $game = $this->gateway->getGame($id);
                        if(!$game){
                            http_response_code(404);
                            echo json_encode(["message" => "Game Not Found"]);
                            return;
                        }
                        echo json_encode($game);
                        break;

                    case "PATCH":
                        $data = (array) json_decode(file_get_contents("php://input"), true);
                        $game = $this->gateway->getGame($id);
                        
                        if(!$game){
                            http_response_code(404);
                            echo json_encode(["message" => "Game Not Found"]);
                            return;
                        }

                        if($game["status"] === "playing"){
                            http_response_code(409);//conflict
                            echo json_encode(["message" => "Game is already in progress",
                                                "game"=> $game               
                            ]);
                            return;
                        }

                        if(empty($data["userid"])){
                            http_response_code(422);
                            echo json_encode(["errors" => "userid is required to update game"]);
                            return;
                        }

                        $rows = $this->gateway->joinGame($id, $data);

                        if($rows === 0){
                            http_response_code(409);
                            echo json_encode(["message" => "Game is not joinable"]);
                            return;
                        }

                        $updatedGame = $this->gateway->getGame($id);

                        http_response_code(200);
                        echo json_encode(["message" => "Game Updated", 
                                            "rows" => $rows,
                                            "game" => $updatedGame
                        ]);
                        break;

                    default:
                        http_response_code(405);
                        header("Allow: GET, PATCH");
                        break;
                }
            }else{
                switch($method){
                    case "POST":
                        $data = (array) json_decode(file_get_contents("php://input"), true);

                        if(empty($data["id"]) || empty($data["color"])){
                            http_response_code(422);
                            echo json_encode(["errors" => "userid and color are required"]);
                            return;
                        }

                        $game = $this->gateway->createGame($data);

                        if(empty($game)){
                            http_response_code(500);
                            echo json_encode(["message" => "Could not create game"]);
                            return;
                        }

                        http_response_code(201);
                        header("Location: /chess/games/" . $game["id"]);
                        echo json_encode(["message" => "Game Created", "game" => $game]);
                        break;

                    case "GET":
                        $games = $this->gateway->getAllGames();
                        http_response_code(200);
                        echo json_encode($games);
                        break;

                    default:
                        http_response_code(405);
                        header("Allow: GET, POST");
                        break;
                }
            }
        }

        private function handleAuth(string $method, ?string $subres) : void {
            if($subres !== "login"){
                http_response_code(404);
                echo json_encode(["message" => "Uknown auth action"]);
                return;
            }

            if($method !== "POST"){
                http_response_code(405);
                header("Allow: POST");
                return;
            }

            $data = (array) json_decode(file_get_contents("php://input"), true);
            $errors = $this->validateUserPayload($data);

            if(!empty($errors)){
                http_response_code(422);
                echo json_encode(["errors" => $errors]);
                return;
            }

            $user = $this->gateway->getUserLogin($data);

            if(!$user){
                http_response_code(401);
                echo json_encode(["message" => "Invalid credentials"]);
                return;
            }

            http_response_code(200);
            echo json_encode([  
                "message" => "Succesful Login",
                "user" => [
                    "id" => (int) $user["id"],
                    "username" => $user["username"]
                    ]
                
            ]);

        }

        private function handleRoot(string $method) : void {
            if($method === "GET"){
                $lastCurl = file_exists($this->logFile)
                        ? json_decode(file_get_contents($this->logFile), true)
                        : "No requests recorded yet";

                echo json_encode([
                    "status" => "API Online",
                    "last_curled" => $lastCurl
                ]);
            }else{
                http_response_code(405);
                header("Allow: GET");
            }
        }

        public function handleChess(string $method, ?string $subres, int $id ){
            $game= $this->gateway->getGame($id);

            if(!$game){
                http_response_code(404);
                echo json_encode(["message" => "Game doesnt exist"]);
            }

            switch($method){
                case "POST":
                    break;//TODO probably make this for move
                case "PATCH":
                    break;//TODO probably make this for forfeit?
            }

        }
        
        private function validateUserPayload(array $data) : array{
            $errors = [];
            
            if(empty($data["user"])){
                $errors[] = "Username is required"; 
            }
            if(empty($data["pass"])){
                $errors[] = "Password is required"; 
            }

            return $errors;
        }
    }
?>