<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success' => $success,
        'status' => $status,
        'message' => $message
    ],$extra);
}

require __DIR__.'/config/Database.php';
require __DIR__.'/classes/JwtHandler.php';

$db_connection = new Database();
$conn = $db_connection->dbConnection();

$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// if request method not POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// validating empty fields
elseif(!isset($data->email) 
    || !isset($data->password)
    || empty(trim($data->email))
    || empty(trim($data->password))
    ):

    $fields = ['fields' => ['email','password']];
    $returnData = msg(0,400 ,'Please fill up the required fields!',$fields);

// if empty fields validated 
else:
    $email = trim($data->email);
    $password = trim($data->password);

    // validating email format
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = msg(0,400 ,'Invalid Email Address!');
    
    // checking password
    elseif(strlen($password) < 8):
        $returnData = msg(0,400 ,'Your password must be at least 8 characters long!');

    // validation success then
    else:
        try{
            
            $fetch_user_by_email = "SELECT * FROM `users` WHERE `email`=:email";
            $query_stmt = $conn->prepare($fetch_user_by_email);
            $query_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $query_stmt->execute();

            // got records by email
            if($query_stmt->rowCount()):
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                $check_password = password_verify($password, $row['password']);

                // checking the password correct ,if yes sending the  login token
                if($check_password):

                    $jwt = new JwtHandler();
                    $token = $jwt->_jwt_encode_data(
                        'http://localhost/php_rest_api/',
                        array("user_id"=> $row['id'])
                    );
                    
                    $returnData = [
                        'success' => 1,
                        'message' => 'You have successfully logged in.',
                        'token' => $token
                    ];

                // if invalid password
                else:
                    $returnData = msg(0,400 ,'Invalid Password!');
                endif;

            // if coudnt find user by email
            else:
                $returnData = msg(0,400 ,'Invalid Email Address!');
            endif;
        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }

    endif;

endif;

echo json_encode($returnData);