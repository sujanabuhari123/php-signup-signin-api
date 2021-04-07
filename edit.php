<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function msg($success,$status,$message,$extra = []){
    return array_merge([
        'success'   => $success,
        'status'    => $status,
        'message'   => $message
    ],$extra);
}

// including db connevtion and object creation
require __DIR__.'/config/Database.php';
require __DIR__.'/classes/JwtHandler.php';
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// getting data 
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// request method not POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// validating empty fields
elseif(!isset($data->id)
    || !isset($data->firstname)
    || !isset($data->lastname) 
    || empty(trim($data->id)) 
    || empty(trim($data->firstname))
    || empty(trim($data->lastname))
    ):

    $fields = ['fields' => ['id','firstname','lastname']];
    $returnData = msg(0,400 ,'Please Fill in all Required Fields!',$fields);

// IF THERE ARE NO EMPTY FIELDS THEN-
else:
    $id             = trim($data->id);
    $firstname      = trim($data->firstname);
    $lastname       = trim($data->lastname);
   

    if($id=="")
        $returnData = msg(0,400 ,'id is mandatory');
    else if(strlen($firstname) < 3):
        $returnData = msg(0,400 ,'Your firstname must be at least 3 characters long!');

    elseif(strlen($lastname) < 3):
        $returnData = msg(0,400 ,'Your lastname must be at least 3 characters long!');

    else:
        try{
 
                $update_query = "UPDATE `users` SET firstname = :firstname, lastname = :lastname WHERE id = :id";
                $update_stmt = $conn->prepare($update_query);

                // binding data
                $update_stmt->bindValue(':id', htmlspecialchars(strip_tags($id)),PDO::PARAM_INT);
                $update_stmt->bindValue(':firstname', htmlspecialchars(strip_tags($firstname)),PDO::PARAM_STR);
                $update_stmt->bindValue(':lastname', htmlspecialchars(strip_tags($lastname)),PDO::PARAM_STR);
                

                $update_stmt->execute();

                //updating the jwt token too
                $jwt = new JwtHandler();
                $token = $jwt->_jwt_encode_data(
                    'http://localhost/php_rest_api/',
                    array("user_id"=> $id)
                );
                $returnData = [
                    'success' => 1,
                    'message' => 'Your details has been successfully updated.',
                    'token' => $token
                ];

           

        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    endif;
    
endif;

echo json_encode($returnData);