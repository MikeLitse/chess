<?php

    class userController{

        public function __construct(private Gateway $gateway){
            
        }
        private function handleUsers(string $method, ?int $id) : void {
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
                        echo json_encode(["message" => "User update", "rows" => $rows]);
                        break;
                    case "DELETE":
                        $rows = $this->gateway->deleteUser((string)$id);
                        http_response_code(200);
                        echo json_encode(["message" => "User deleted", "rows" => $rows]);
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

                        $sanitized = array_map(function ($u){
                            unset($u["pass"]);
                            return $u;
                        }, $users);
                        echo json_encode($sanitized);
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
    }    
?>