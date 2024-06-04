<?php

    require_once ('vendor/autoload.php'); // if you use Composer
    require_once ('Webhook.php'); 

    $data = file_get_contents("php://input");
    $event = json_decode($data, true);

if ($_SERVER["REQUEST_METHOD"] == "GET") {
   $Mobile = htmlspecialchars($_REQUEST['mobile']);
   $Message = htmlspecialchars($_REQUEST['message']);
   SendMessage($Mobile,$Message);

   echo "SUCCESS";
}

    
