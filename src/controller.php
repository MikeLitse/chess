<?php 

    //echo json_encode returns
    //http_response_code returns
    class Controller{

        public function __construct(private Gateway $gateway){

        }

        public function processRequest(string $method, ?string $id) : void {
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
                    echo json_encode($user);
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
                    echo json_encode($this->gateway->getAllUsers());
                    http_response_code(200);
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

                    }elseif($data["action"] === "create"){
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

            if(empty($data["user"])){
                $errors[] = "username required";
            }
            if(empty($data["pass"])){
                $errors[] = "password required";
            }
        
            return $errors;

        }

    }

?>