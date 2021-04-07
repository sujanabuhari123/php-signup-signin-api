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

require __DIR__.'/middlewares/Auth.php';
$allHeaders = getallheaders();
$db_connection = new Database();
$conn = $db_connection->dbConnection();
$auth = new Auth($conn,$allHeaders);

// getting data 
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// request method not POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// validating empty fields
elseif(!isset($data->user_id)
    || !isset($data->message) 
    || empty(trim($data->user_id))
    || empty(trim($data->message))
    ):

    $fields = ['fields' => ['user_id','message']];
    $returnData = msg(0,400 ,'Please Fill in all Required Fields!',$fields);

// if fields not empty-
else:
    
    $user_id        = trim($data->user_id);
    $message        = trim($data->message);
    $is_valid       = $auth->validatetoken();
           
    
    if($is_valid==0):

        $returnData = msg(0,400 ,'Unauthorized to create ticket!Invalid Token!');
    elseif($user_id==""):
        $returnData = msg(0,400 ,'user id is mandatory');
    
    elseif(strlen($message) < 20):
        $returnData = msg(0,400 ,'Your message must be at least 20 characters long!');

    else:
        try{

            $check_id = "SELECT `id` FROM `users` WHERE `id`=:user_id and `role`='user'";
            $check_id_stmt = $conn->prepare($check_id);
            $check_id_stmt->bindValue(':user_id', $user_id,PDO::PARAM_INT);
            $check_id_stmt->execute();

            if(!$check_id_stmt->rowCount()):
                $returnData = msg(0,400 , 'This user not existing!');
            else:

                $insert_query = "INSERT INTO `support_tickets`(`user_id`,`message`) VALUES(:user_id,:message)";

                $insert_stmt = $conn->prepare($insert_query);

                // binding data
                $insert_stmt->bindValue(':user_id', htmlspecialchars(strip_tags($user_id)),PDO::PARAM_INT);
                $insert_stmt->bindValue(':message', htmlspecialchars(strip_tags($message)),PDO::PARAM_STR);
                $insert_stmt->execute();

                $returnData = msg(1,200,'Your ticket has been submitted successfully');
             endif;

          

        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    endif;
    
endif;

echo json_encode($returnData);