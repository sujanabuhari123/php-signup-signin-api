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
    //checking whether admin user
    $user_details = $auth->isAuth();
    $user_role=$user_details['user']['role'];
    $is_valid       = $auth->validatetoken();
    if($user_role!="admin"):

        $returnData = msg(0,400 ,'Unauthorized to list the tickets!');

    elseif($is_valid==0):

        $returnData = msg(0,400 ,'Unauthorized to list tickets!Invalid Token!');
    
    else:
        
        try{

            $fetch_user_by_id = "SELECT user_id,message FROM `support_tickets`";
            $query_stmt = $conn->prepare($fetch_user_by_id);
            $query_stmt->execute();
            $itemCount = $query_stmt->rowCount();
            echo json_encode($itemCount);

            //if($itemCount > 0):
                
                $userArr = array();
                $userArr["body"] = array();
                $userArr["itemCount"] = $itemCount;

                while ($row = $query_stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    
                    $e = array(
                            "user_id" => $user_id,
                            "message" => $message,
                            
                        );

                    array_push($userArr["body"], $e);
                }
               echo json_encode($userArr);
        }
        catch(PDOException $e){
            $returnData = msg(0,500,$e->getMessage());
            $this->pdo->rollBack();
        }
    
    
endif;

echo json_encode($returnData);