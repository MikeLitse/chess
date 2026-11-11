<?php 

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class Controller{

        private string $logFile;
        public function __construct(private Gateway $gateway, private string $jwtSecret){
            $this->logFile = __DIR__ . '/last_request.txt';
        }

        public function authUser() : ?array {
            $token = null;

            if(!empty($_COOKIE["token"])){
                $token = $_COOKIE["token"];
            }

            if(!$token){
                $headers = getallheaders();
                $authHeader = $headers["Authorization"] ?? $headers["authorization"] ?? null;

                if($authHeader && preg_match("/Bearer\s(\S+)/", $authHeader, $matches)){
                    $token = $matches[1];
                }
            }

            if(!$token){
                http_response_code(401);
                echo json_encode(["message" => "Authentication required: missing token"]);
                return null;
            }

            try{
                $decoded = JWT::decode($token, new Key($this->jwtSecret, "HS256"));
                return (array) $decoded->data;
            }catch (Exception $e){
                http_response_code(401);
                echo json_encode([
                                    "message" => "Invalid or expired token",
                                    "error" => $e->getMessage()
                                ]);
                return null;
            }

        }

        public function processRequest(string $method, ?string $resource, ?string $id) : void {
            //$id can be a string in auth/login $id==login
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
                    $this->handleUsers($method, $id !== null ? (int)$id : null);
                    break;
                case "games":
                    $this->handleGames($method, $id !== null ? (int)$id : null);
                    break;
                case "auth":
                    $this->handleAuth($method, $id);
                    break;
                case "play":
                    $this->handlePlay($method,$id !== null ? (int)$id : null);
                    break;
                case "matchHistory":
                    $this->handleMatchHistory($method);//TODO
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

                        if(empty($data["user"]) || empty($data["pass"])){
                            http_response_code(400);
                            echo json_encode(["message" => "Didnt include username or password"]);
                        }

                        $rows = $this->gateway->updateUser($user, $data);
                        http_response_code(200);
                        echo json_encode(["message" => "User update", 
                                        "rows" => $rows,
                                        "user" => $user["username"]
                                        ]);
                        break;
                    case "DELETE":
                        $rows = $this->gateway->deleteUser($id);
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

                        $now = new DateTime();
                        if(new DateTime($game["expires_at"]) < $now){
                            http_response_code(400);
                            echo json_encode(["message" => "This games has expired"]);
                            return;
                        }

                        $rows = $this->gateway->joinGame($id, $data);

                        if(!$rows){
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
                        //TODO changes this so a user cannot be in multiple games at once
                        //if userid exists in ongoing game cannot create new game
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

            $token = JWT::encode(
                [
                    "iat" => time(),
                    "nbf" => time(),
                    "exp" => time() + 3600,
                    "data" => [
                                "userid" => $user["id"],
                                "username" => $user["username"]
                            ]
                ],
                $this->jwtSecret,
                'HS256'
            );

            $isHttps = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || $_SERVER["SERVER_PORT"] === 443;

            setcookie("token", $token, [
                "expires" => time() + 3600,
                "path" => "/",
                "domain" => "",
                "secure" => $isHttps,
                "httponly" => true,
                "samesite" => "Lax"
            ]);

            http_response_code(200);
            echo json_encode([  
                "message" => "Succesful Login",
                "token" => $token,
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

        public function handlePlay(string $method, ?int $id){

            $auth = $this->authUser();
            if(!$auth){
                return;
            }

            $userId = $auth["userid"];

            $data = (array) json_decode(file_get_contents("php://input"), true);

            if(empty($userId)){
                http_response_code(400);
                echo json_encode(["message" => "UserID required"]);
                return;
            }
            
            $game= $this->gateway->getGameByUser($userId);

            if(!$game){
                http_response_code(404);
                echo json_encode(["message" => "User isnt participating in any game"]);
                return;
            }

            if((int)$game["player_turn"] !== $userId){
                http_response_code(403);
                echo json_encode(["message" => "Not this user's turn"]);
                return;
            }

            $now = new DateTime();
            if(new DateTime($game["expires_at"]) < $now){
                http_response_code(400);
                echo json_encode(["message" => "This games has expired"]);
                return;
            }

            switch($method){
                case "POST":

                    if(
                        !is_array($data) || $data["from"] === null || $data["to"] === null ||
                        !preg_match('/^[a-h][1-8]$/', $data["from"]) ||
                        !preg_match('/^[a-h][1-8]$/', $data["to"])
                    ){
                        http_response_code(422);
                        echo json_encode([
                            "message" => "Move is required"
                        ]);
                        return;
                    }

                    $chess = new Chess($game["fen"]);
                    
                    $move = $chess->move($data["from"], $data["to"]);

                    if(!$move){
                        http_response_code(422);
                        echo json_encode([
                            "message" => "Not valid move"
                        ]);
                        return;
                    }

                    $newFen = $chess->toFen();
                    $nextColor = $chess->getActiveColor();
                    $nextPlayerTurn = ($nextColor === "w" ? $game["white"] : $game["black"]);

                    $updated = $this->gateway->updateGameState($game["id"], $newFen ,$nextPlayerTurn);

                    if($updated === 0){
                        http_response_code(500);
                        echo json_encode(["message" => "Failed to save move"]);
                        return;
                    }

                    
                    http_response_code(200);
                    echo json_encode([
                        "message" => "Moved Played",
                        "fen" => $newFen,
                        "player_turn" => $nextPlayerTurn
                    ]);
                        
                    break;
                case "PATCH":
                    $data = (array) json_decode(file_get_contents("php://input"), true);

                    if($data["action"] === null){
                        http_response_code(422);
                        echo json_encode(["message" => "action is required"]);
                        return;
                    }

                    if($data["action"] === "resign"){
                        $game = $this->gateway->getGameByUser($id);

                        $rows = $this->gateway->resignGame($game,$id);

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Resigned, Game Terminated",
                            "Rows Affected" => $rows 
                        ]);

                    }else{
                        http_response_code(422);
                        echo json_encode(["message" => "Not valid action"]);
                        return;
                    }
                    break;
                default:
                    http_response_code(405);
                    header("Allow: POST, PATCH");
            }

        }
        
        public function handleMatchHistory(string $method){
            switch($method){
                case "GET":
                    $game = $this->gateway->getMatchesHistory();
                    if(!$game){
                        http_response_code(404);
                        echo json_encode(["message" => "Game Not Found"]);
                        return;
                        }
                    echo json_encode($game);
                    break;

                default:
                    http_response_code(405);
                    header("Allow: GET");
                    break;
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