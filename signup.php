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
$db_connection = new Database();
$conn = $db_connection->dbConnection();

// getting data 
$data = json_decode(file_get_contents("php://input"));
$returnData = [];

// request method not POST
if($_SERVER["REQUEST_METHOD"] != "POST"):
    $returnData = msg(0,404,'Page Not Found!');

// validating empty fields
elseif(!isset($data->firstname)
    || !isset($data->lastname) 
    || !isset($data->email) 
    || !isset($data->password)
    || !isset($data->role)
    || empty(trim($data->firstname))
    || empty(trim($data->lastname))
    || empty(trim($data->email))
    || empty(trim($data->password))
    || empty(trim($data->role))
    ):

    $fields = ['fields' => ['firstname','lastname','email','password','role']];
    $returnData = msg(0,400 ,'Please Fill in all Required Fields!',$fields);

// if fields not empty
else:
    
    $firstname      = trim($data->firstname);
    $lastname       = trim($data->lastname);
    $email          = trim($data->email);
    $password       = trim($data->password);
    $role           = trim($data->role);

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)):
        $returnData = msg(0,400 ,'Invalid Email Address!');
    
    elseif(strlen($password) < 8):
        $returnData = msg(0,400 ,'Your password must be at least 8 characters long!');

    elseif(strlen($firstname) < 3):
        $returnData = msg(0,400 ,'Your firstname must be at least 3 characters long!');

    elseif(strlen($lastname) < 3):
        $returnData = msg(0,400 ,'Your lastname must be at least 3 characters long!');

    else:
        try{

            $check_email = "SELECT `email` FROM `users` WHERE `email`=:email";
            $check_email_stmt = $conn->prepare($check_email);
            $check_email_stmt->bindValue(':email', $email,PDO::PARAM_STR);
            $check_email_stmt->execute();

            if($check_email_stmt->rowCount()):
                $returnData = msg(0,400 , 'This E-mail existing already!');
            
            else:
                $insert_query = "INSERT INTO `users`(`firstname`,`lastname`,`email`,`password`,`role`) VALUES(:firstname,:lastname,:email,:password,:role)";

                $insert_stmt = $conn->prepare($insert_query);

                // binding data
                $insert_stmt->bindValue(':firstname', htmlspecialchars(strip_tags($firstname)),PDO::PARAM_STR);
                $insert_stmt->bindValue(':lastname', htmlspecialchars(strip_tags($lastname)),PDO::PARAM_STR);
                $insert_stmt->bindValue(':email', $email,PDO::PARAM_STR);
                $insert_stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT),PDO::PARAM_STR);
                $insert_stmt->bindValue(':role', htmlspecialchars(strip_tags($role)),PDO::PARAM_STR);

                $insert_stmt->execute();

                $returnData = msg(1,200,'Your sign up has been successful.');

            endif;

        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
        }
    endif;
    
endif;

echo json_encode($returnData);