<?php

    require_once ('vendor/autoload.php'); // if you use Composer
    require_once ('Webhook.php'); 

    $data = file_get_contents("php://input");
    $event = json_decode($data, true);

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $Mobile = htmlspecialchars($_REQUEST['mobile']);
    $Type = htmlspecialchars($_REQUEST['type']);
    $Language = GetLanguage($Mobile);
    if($Type == 0){
        UpdateSession($Mobile,0);
        if($Language == "AR"){
            $Message = "شكرا لإختيارك شركة الغزال .. ونتمنى لك رحلة آمنة وممتعة 🙏🏻";
            $Message = $Message."\n"."برجاء تقييم مستوى الخدمة :";
            $Message = $Message."\n *1.* سيئة جدا 😠\n *2.* سيئة ☹️ \n *3.* متوسطة 🙂\n *4.* جيدة 😃\n *5.* جيدة جدا 🤩";
        }
        if($Language == "EN"){
            $Message = "Thank you for choosing Al Ghazal Company.. We wish you a safe and enjoyable trip 🙏🏻";
            $Message = $Message."\n"."Please rate the level of service:";
            $Message = $Message."\n *1.* Very Bad 😠\n *2.* Bad ☹️ \n *3.* Medium 🙂\n *4.* Good 😃\n *5.* Very Good 🤩";
        }
        SendMessage($Mobile,$Message);
    }
    if($Type == 1){
        UpdateSession($Mobile,0);
        if($Language == "AR"){
            $Message = "تشرفنا بخدمتك .. ونتشرف بتكرار تجربتك مع الغزال 🙏🏻";
            $Message = $Message."\n"."برجاء تقييم مستوى الخدمة :";
            $Message = $Message."\n *1.* سيئة جدا 😠\n *2.* سيئة ☹️ \n *3.* متوسطة 🙂\n *4.* جيدة 😃\n *5.* جيدة جدا 🤩";
        }
        if($Language == "EN"){
            $Message = "We were honored to serve you...and we are honored to repeat your experience with Al Ghazal 🙏🏻";
            $Message = $Message."\n"."Please rate the level of service:";
            $Message = $Message."\n *1.* Very Bad 😠\n *2.* Bad ☹️ \n *3.* Medium 🙂\n *4.* Good 😃\n *5.* Very Good 🤩";
        }
        SendMessage($Mobile,$Message);
    }

   echo "SUCCESS";
}

    
