<?php 

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
            }
        }

        private function processControllerRequest(string $method) : void {
            switch ($method){
                case "GET":
                    echo json_encode($this->gateway->getAllUsers());
                    break;
                case "POST":
                    //cast into array so it doesnt return null
                    $data= (array) json_decode(file_get_contents("php://input"), true);//true returns assoc

                    $errors= $this->getValidationErrors($data);
                    
                    if(!empty($errors)){
                        
                        http_response_code(422);
                        echo json_encode(["errors" => $errors]);
                        break;
                        
                    }

                    $id = $this->gateway->createUser($data);

                    http_response_code(201);

                    echo json_encode([
                        "message" => "User Created",
                        "id" => $id
                    ]);

                    break;

                default :
                    //check allowed methods
                    http_response_code(405);
                    header("Allow: GET, POST");
            }
        }

        private function getValidationErrors(array $data, bool $is_new= true): array{
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