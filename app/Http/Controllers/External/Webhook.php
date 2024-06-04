<?php

    require_once ('vendor/autoload.php'); // if you use Composer

    $data = file_get_contents("php://input");
    $event = json_decode($data, true);

    // $GetMessages = GetMessages("201092282391@c.us");
    // $GetMessages = $GetMessages['messages'];

    // $Counter = 0;
    // $Flow = "";
    // $Request = "";
    // $Flag = False;
    // $To = "201092282397";
    // $IDChat = "201092282391@c.us";
    
    if(isset($event)){
        $To = $event['data']['from'];
        $IDChat = $To;
        $Body = $event['data']['body'];
        $GetMessages = GetMessages($IDChat);
        $GetMessages = $GetMessages['messages'];

        $Counter = 0;
        $Flow = "";
        $Request = "";
        $Flag = False;
        $To = substr($To, 0, -5);
        $RateComplainFlag = False;

        foreach($GetMessages as $Message){
            $Text = $Message['body'];
            if (str_contains($Text, 'ولكننا بحاجة لتحسين مستوى الخدمة لدينا ..  لذلك برجاء كتابة ملاحظاتك') || str_contains($Text, 'But we need to improve our service level..so please write your feedback')) {
                $RateComplainFlag = True;
                continue;
            }
            if (str_contains($Text, 'سنكون سعداء بخدمتك') || str_contains($Text, 'We will be happy to serve you')) { 
                $Flow = "START";
                break;
            }
            if (str_contains($Text, 'اللغة العربية') || str_contains($Text, 'For English')) { 
                $Flow = "MAIN";
                break;
            }
            if (str_contains($Text, 'شكرا لإختيارك شركة الغزال .. ونتمنى لك رحلة آمنة وممتعة 🙏🏻') || str_contains($Text, 'Thank you for choosing Al Ghazal Company.. We wish you a safe and enjoyable trip 🙏🏻')) { 
                $Flow = "RATING_0";
                break;
            }
            if (str_contains($Text, 'تشرفنا بخدمتك .. ونتشرف بتكرار تجربتك مع الغزال 🙏🏻') || str_contains($Text, 'We were honored to serve you...and we are honored to repeat your experience with Al Ghazal 🙏🏻')) { 
                $Flow = "RATING_1";
                break;
            }
            if (str_contains($Text, 'برجاء تقييم السيارة  :') || str_contains($Text, 'Please rate the car:')) { 
                $Flow = "RATING_2";
                break;
            }
            if (str_contains($Text, 'برجاء تقييم مستوى الموظف  :') || str_contains($Text, "Please rate the employee's level:")) { 
                $Flow = "RATING_3";
                break;
            }
            if (str_contains($Text, ' وسوف يتم التواصل معك من قبل افضل مسئولي خدمة عملاء لدينا في اقرب وقت 😊') || str_contains($Text, 'نسعى جاهدين لتقديم افضل مستوى من الخدمة .. ونعدك بالأفضل دائما 🤝') || str_contains($Text, 'ونعدك بتحسين مستوى الخدمة في المرات القادمة') || str_contains($Text, "Our best customer service representatives will contact you as soon as possible") || str_contains($Text, "We strive to provide the best level of service...and we always promise you the best") || str_contains($Text, "We promise to improve the level of service in the coming times") || str_contains($Text, "We are sorry to hear this complaint from you") || str_contains($Text, "We will contact you as soon as possible") ) { 
                $Flow = "";
                break;
            }
            if (str_contains($Text, 'يمكنك حجز سيارة من السيارات السابقة') || str_contains($Text, 'You can reserve a car from the previous cars')) { 
                $Flow = "RETURN";
                break;
            }
            if (str_contains($Text, 'برجاء ارسال اسم سيادتكم') || str_contains($Text, 'Please write your name') ) { 
                $Flow = "FROM_LEMO";
                break;
            }
            if (str_contains($Text, 'برجاء ارسال من أين تبدأ الرحلة') || str_contains($Text, 'Please write where you start the journey')) { 
                $Flow = "TO_LEMO";
                break;
            }
            if (str_contains($Text, 'برجاء ارسال الى أين تنتهي الرحلة') || str_contains($Text, 'Please write where the journey ends')) { 
                $Flow = "DATE_LEMO";
                break;
            }
            if (str_contains($Text, 'برجاء ارسال تاريخ الرحلة 📆') || str_contains($Text, 'Please write the journey date')) { 
                $Flow = "AFTER_LEMO";
                break;
            }
            // if (str_contains($Text, 'العنوان') || str_contains($Text, 'Address')) { 
            //     $Flow = "AFTER_BRANCH";
            //     break;
            // }
            if (str_contains($Text, 'سوف يتم التواصل معك من قبل افضل مسئولي خدمة عملاء لدينا في اقرب وقت') || str_contains($Text, 'You will be contacted by our best customer service representatives as soon as possible')) { 
                $Flow = "STOP";
                break;
            }
            if (str_contains($Text, 'نسعى لتقديم') || str_contains($Text, 'We strive to provide the best level of service')) { 
                $Flow = "CS";
                break;
            }
            if (str_contains($Text, 'كتابة الشكوي') || str_contains($Text, 'Please write your complaint')) { 
                $Flow = "CS_COMPLAIN";
                break;
            }
            if (str_contains($Text, 'برجاء ارسال مرفقات وصور الحادث') || str_contains($Text, 'Please send attachments and photos of the accident')) { 
                $Flow = "AFTER_ACCIDENT";
                break;
            }
            if (str_contains($Text, 'ارسال مرفقات وصور الحادث') || str_contains($Text, 'Send attachments and photos of the accident')) { 
                $Flow = "ACCIDENT";
                break;
            }
            if (str_contains($Text, 'التواصل') || str_contains($Text, "سوف نكون سعداء بالرد عليكم") || str_contains($Text, 'لم يصلنا اي رد منك منذ فترة') || str_contains($Text, 'contacted') || str_contains($Text, "We will be happy to respond to you, please call this number") || str_contains($Text, "We haven't received any response from you for a while") ) { 
                $Flow = "";
                break;
            }
            if (str_contains($Text, 'ماركة ') || str_contains($Text, 'Please select the desired car brand number')) { 
                $Flow = "BRAND";
                break;
            }
            if (str_contains($Text, 'فئة') || str_contains($Text, 'Please select the desired car class number')) { 
                $Flow = "SUBBRAND";
                break;
            }
            if (str_contains($Text, 'برجاء اختيار السيارة التي تريد تمديد عقدها') || str_contains($Text, 'Please select the car whose contract you want to extend')) { 
                $Flow = "CONTRACT";
                break;
            }
            if (str_contains($Text, 'تمديدها') || str_contains($Text, 'Please write the number of days to be extended')) { 
                $Flow = "EXTEND_REQUEST";
                break;
            }
            $Counter++;
        }

        if($Flow != ""){
            if($Body == "0"){
                $Flow = "RETURN";
            }
            if($Body == "*"){
                $Flow = "AGENT";
            }
        }

        $Language = "AR";
        if($Flow == "MAIN"){
            if($Body == 2){
                $Language = "EN";
            }
            SaveLanguage($To,$Language);
        }

        if($Flow == "START"){
            $Body = EnglishConverter($Body);
            if($Body == 1 && !$Flag){
                $Request = "CS";
                $Flag = True;
            }
            if($Body == 2 && !$Flag){
                $Flow = "RENT";
                $Flag = True;
            }
            if($Body == 3 && !$Flag){
                $Request = "ACCIDENT";
                $Flag = True;
            }
            if($Body == 4 && !$Flag){
                $Flow = "EXTEND";
                $Flag = True;
            }
            if($Body == 5 && !$Flag){
                $Flow = "LEMO";
                $Flag = True;
            }
            if($Body == 6 && !$Flag){
                $Flow = "OFFER";
                $Flag = True;
            }
            if($Body == 7 && !$Flag){
                $Flow = "BRANCH";
                $Flag = True;
            }
        }

        if($Flow == "RETURN"){
            if($Body == 0){
                $Request = "MAIN";
            }
            if($Body == 1){
                $Flow = "RENT";
            }
        }

        if($Flow == "ACCIDENT" || $Flow == "CS"){
            $Body = EnglishConverter($Body);
            if($Body == 1 && !$Flag){
                $Request = "MSG";
                $Flag = True;
            }
            if($Body == 2 && !$Flag){
                $Request = "CALL";
                $Flag = True;
            }
            if($Flow == "CS" && !$Flag){
                $Request = "COMPLAIN";
                $Flag = True;
            }
            if($Flow == "ACCIDENT" && !$Flag){
                $Request = "IMAGE";
                $Flag = True;
            }
        }

        if($Flow == "RATING_0" || $Flow == "RATING_1" || $Flow == "RATING_2" || $Flow == "RATING_3"){
            if(!$RateComplainFlag){
                if($Body <= 3){
                    $Flow = "RATING_COMPLAIN";
                }
            }
        }

        if($Flow != "" && $Flow != "MAIN"){
            $Language = GetLanguage($To);
        }

        if($Body == "#"){
            if($Flow == "SUBBRAND"){
                $Flow = "RENT";
            }
            if($Flow == "RETURN"){
                $Body = GetLastSentMessage($IDChat);
                $Flow = "BRAND";
            }
        }

        if($Language == "AR"){
            if($Flow != "STOP"){
                if(!count($GetMessages) || $Flow == ""){
                    $Offer = UpdateSession($To,0);
                    $Message = "مرحبا بك في شركة الغزال لتأجير السيارات 😊";
                    SendMessage($To,$Message);
                    if($Offer['offer']){
                        $Image = $Offer['image'];
                        $Caption = $Offer['arabic_title'];
                        // $Caption = $Caption."\n\n".$Offer['arabic_desc'];
                        $Caption = $Caption."\n\n"."https://alghazal.sa/od/".$Offer['offer_id'];
                        SendImage($To,$Image,$Caption);
                    }
                    $Message = "1.اللغة العربية";
                    $Message = $Message."\n"."2.For English";
                    SendMessage($To,$Message);
                }
                if($Request == "MAIN" || $Flow == "MAIN"){
                    $Message = "سنكون سعداء بخدمتك 🤩";
                    $Message = $Message."\n"." برجاء اختيار رقم الخدمة المطلوبة :";
                    $Message = $Message."\n *1.* خدمة العملاء\n *2.* تأجير سيارة \n *3.* ابلاغ عن حادث \n *4.* تمديد مدة عقد الايجار";
                    $Message = $Message."\n *5.*  حجز ليموزين\n *6.*  العروض \n *7.*   الفروع";
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . للرجوع للقائمة الرئيسية 📌";
                    $Message = $Message."\n"."* "." . للتحدث مع احد ممثلي خدمة العملاء 💬";
                    SendMessage($To,$Message);
                }
                if($Request == "CS"){
                    $Message = "نسعى لتقديم افضل مستوي من الخدمة 🙏";
                    $Message = $Message."\n"." برجاء اختيار رقم الخدمة المطلوبة :";
                    $Message = $Message."\n *1.* التحدث كتابة مع مسئول خدمة عملاء  💬\n *2.* مكالمة مع مسئول خدمة عملاء 📱 \n *3.* لديك شكوي 📨";
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . للرجوع للقائمة الرئيسية 📌";
                    SendMessage($To,$Message);
                }
                if($Request == "ACCIDENT"){
                    $Message = "الحمد لله على سلامتك 🙏🏻";
                    $Message = $Message."\n"."برجاء اختيار رقم الخدمة المطلوبة :";
                    $Message = $Message."\n *1.* التحدث كتابة مع مسئول خدمة عملاء  💬\n *2.* مكالمة مع مسئول خدمة عملاء 📱 \n *3.* ارسال مرفقات وصور الحادث";
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . للرجوع للقائمة الرئيسية 📌";
                    SendMessage($To,$Message);
                }
                if($Request == "CALL" && $Flow == "CS"){
                    $Message = "سوف نكون سعداء بالرد عليكم رجاء الاتصال على هذا الرقم  😊";
                    SendMessage($To,$Message);
                    $Message = "0920006435";
                    SendMessage($To,$Message);
                    UpdateSession($To,2);
                }
                if($Request == "MSG" && $Flow == "CS"){
                    $Message = "سوف يتم التواصل معك من قبل افضل مسئولي خدمة عملاء لدينا في اقرب وقت  🤝";
                    SendMessage($To,$Message);
                }
                if($Flow == "AGENT"){
                    $Message = "سوف يتم التواصل معك من قبل افضل مسئولي خدمة عملاء لدينا في اقرب وقت  🤝";
                    SendMessage($To,$Message);
                }
                if($Request == "COMPLAIN" && $Flow == "CS"){
                    $Message = "برجاء كتابة الشكوي 🙏🏻";
                    $Message = $Message."\n"."وبعد الانتهاء برجاء إرسال الرمز  #";
                    SendMessage($To,$Message);
                }
                if($Request == "CALL" && $Flow == "ACCIDENT"){
                    $Message = "سوف نكون سعداء بالرد عليكم رجاء الاتصال على هذا الرقم  😊";
                    SendMessage($To,$Message);
                    $Message = "0920006435";
                    SendMessage($To,$Message);
                    UpdateSession($To,2);
                }
                if($Request == "MSG" && $Flow == "ACCIDENT"){
                    $Message = "سوف يتم التواصل معك من قبل افضل مسئولي خدمة عملاء لدينا في اقرب وقت  🤝";
                    SendMessage($To,$Message);
                }
                if($Request == "IMAGE" && $Flow == "ACCIDENT"){
                    UpdateSession($To,1);
                    $Message = "برجاء ارسال مرفقات وصور الحادث";
                    $Message = $Message."\n"."وبعد الانتهاء برجاء إرسال الرمز  #";
                    SendMessage($To,$Message);
                }
                if($Flow == "CS_COMPLAIN"){
                    if($Body == "#"){
                        $Name = GetName($IDChat);
                        $Body = GetComplain($IDChat);
                        Ticket($To,$Name,0,$Body,0,Null);
                        $Message = "نأسف لسماع هذه الشكوي من سيادتكم  🙏 وسوف يتم النظر فيها واتخاذ الإجراءات اللازمة  والتواصل مع سيادتكم في اقرب وقت";
                        SendMessage($To,$Message);
                        UpdateSession($To,2);
                    }
                }
                if($Flow == "AFTER_ACCIDENT"){
                    if($Body == "#"){
                        $Name = GetName($IDChat);
                        Ticket($To,$Name,1,"",0,Null);
                        $Message = "الحمد لله على سلامتك 🙏🏻 ";
                        $Message = $Message."\n"."وسوف يتم التواصل معك في اقرب وقت .";
                        SendMessage($To,$Message);
                        UpdateSession($To,2);
                    }
                }
                if($Flow == "RENT"){
                    $ClientExist = CheckClientExist($To);
                    $Brands = GetBrands("SHOW",$Language);
                    if($ClientExist){
                        $Message = "نشكرك دوما على انك من ضمن افضل عملاء الشركة الكرام 🤝";
                    }else{
                        $Message = "يسعدنا ان نقوم بالترحيب بك كعميل جديد ونسعى بتقديم افضل الخدمات اليك  🤝";
                    }
                    $Message = $Message."\n"."برجاء اختيار رقم ماركة السيارة المطلوبة : ".$Brands;
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . للرجوع للقائمة الرئيسية 📌";
                    $Message = $Message."\n"."* "." . للتحدث مع احد ممثلي خدمة العملاء 💬";
                    $Message = $Message."\n\n"."يمكنك تصفح المزيد من السيارات من خلال زيارة الموقع الالكتروني";
                    $Message = $Message."\n"."https://alghazal.sa/";
                    SendMessage($To,$Message);
                }
                if($Flow == "BRAND"){
                    $Body = EnglishConverter($Body);
                    $IDBrand = GetIDBrand($Body,$Language);
                    $SubBrands = GetSubBrands($IDBrand,"SHOW",$Language);
                    $Message = "برجاء اختيار رقم فئة السيارة المطلوبة : ".$SubBrands;
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . للرجوع للقائمة الرئيسية 📌";
                    $Message = $Message."\n"."# . للرجوع للقائمة السابقة ⏪";
                    $Message = $Message."\n"."* "." . للتحدث مع احد ممثلي خدمة العملاء 💬";
                    $Message = $Message."\n\n"."يمكنك تصفح المزيد من السيارات من خلال زيارة الموقع الالكتروني";
                    $Message = $Message."\n"."https://alghazal.sa/";
                    SendMessage($To,$Message);
                }
                if($Flow == "SUBBRAND"){
                    $Body = EnglishConverter($Body);
                    $IDSubBrand = GetIDSubBrand($Body,$IDChat,$Language);
                    $Cars = GetCars($IDSubBrand);
                    if(count($Cars)){
                        foreach($Cars as $Car){
                            $Image = $Car['image'];
                            // $Caption = $Car['name']."\n\n"." النوع " . $Car['type_arabic'] ."\n\n" . " السعر في اليوم الواحد " . $Car['price'];
                            $Offer = $Car['offer'];
                            if($Offer){
                                $Caption = "*".$Car['name']."*"."\n\n". "*النوع*" ." ". $Car['type_arabic']  ."\n\n" . "السعر في اليوم الواحد :" . "~".$Car['price']. "~". " " . " *".$Car['offer_price']."* " . "ريال شامل الضريبة ";
                            }else{
                                $Caption = "*".$Car['name']."*"."\n\n". "*النوع*" ." ". $Car['type_arabic']  ."\n\n" . "السعر في اليوم الواحد : " . " *".$Car['price']."* " . "ريال شامل الضريبة ";
                            }
                            $Caption = $Caption."\n\n"."https://alghazal.sa/cd/".$Car['id'];
                            SendImage($To,$Image,$Caption);
                        }
                        $Message = "يمكنك حجز سيارة من السيارات السابقة بالضغط على الرابط اسفل السيارة";
                        $Message = $Message."\n"."‐--------------------------------";
                        $Message = $Message."\n"."0 . للرجوع للقائمة الرئيسية 📌";
                        $Message = $Message."\n"."# . للرجوع للقائمة السابقة ⏪";
                        $Message = $Message."\n"."* "." . للتحدث مع احد ممثلي خدمة العملاء 💬";
                        SendMessage($To,$Message);
    
                        // $Message = "تشرفنا بخدمتكم .. ونسعي لتقديم افضل مستوي من الخدمة 🤝";
                        // SendMessage($To,$Message);
                    }else{
                        $Brands = GetBrands("SHOW",$Language);
                        $Message = "نعتذر لسيادتكم هذه السيارة غير متوفرة في الوقت الحالي 🙏🏻";
                        $Message = $Message."\n"."برجاء اختيار سيارة اخري";
                        SendMessage($To,$Message);
                        $Message = "برجاء اختيار رقم ماركة السيارة المطلوبة : ".$Brands;
                        $Message = $Message."\n"."‐--------------------------------";
                        $Message = $Message."\n"."0 . للرجوع للقائمة الرئيسية 📌";
                        $Message = $Message."\n"."* "." . للتحدث مع احد ممثلي خدمة العملاء 💬";
                        $Message = $Message."\n\n"."يمكنك تصفح المزيد من السيارات من خلال زيارة الموقع الالكتروني";
                        $Message = $Message."\n"."https://alghazal.sa/";
                        SendMessage($To,$Message);
                    }
                }
                if($Flow == "EXTEND"){
                    $Contracts = GetContracts($To,"SHOW");
                    if(!$Contracts){
                        $Message = "نعتذر لسيادتكم ليس لديك سيارات مؤجرة حاليا 🙏🏻";
                        $Message = $Message."\n"."‐--------------------------------";
                        $Message = $Message."\n"."0 . للرجوع للقائمة الرئيسية 📌";
                        $Message = $Message."\n"."* "." . للتحدث مع احد ممثلي خدمة العملاء 💬";
                        SendMessage($To,$Message); 
                    }else{
                        $Message = "تشرفنا بخدمتكم وسعداء باستمرارك معنا لفترة اطول 🤩 ";
                        $Message = $Message."\n"."برجاء اختيار السيارة التي تريد تمديد عقدها".$Contracts;
                        SendMessage($To,$Message);
                    }
                }
                if($Flow == "CONTRACT"){
                    $Message = "برجاء كتابة عدد الايام المطلوب تمديدها 🙏🏻";
                    SendMessage($To,$Message);
                }
                if($Flow == "EXTEND_REQUEST"){
                    $Body = EnglishConverter($Body);
                    $Name = GetName($IDChat);
                    $IDReservation = GetIDReservation($To,$IDChat);
                    Ticket($To,$Name,2,Null,$Body,$IDReservation);
                    $Message = "يمكنك إجراء التحويل البنكي على الحسابات البنكية التاليه :";
                    $Message = $Message."\n"."مصرف الراجحي :";
                    $Message = $Message."\n"."443000010006080165814";
                    $Message = $Message."\n"."IBAN : SA1580000443608010165814";
                    $Message = $Message."\n\n"."البنك الاهلي السعودي : ";
                    $Message = $Message."\n"."00577255000104";
                    $Message = $Message."\n"."IBAN : SA2910000000577255000104";
                    SendMessage($To,$Message);
                    $Message = "سوف يتم التواصل معك من قبل افضل مسئولي خدمة العملاء لدينا في اقرب وقت  🤝";
                    SendMessage($To,$Message);
                    UpdateSession($To,2);
                }
                if($Flow == "BRANCH"){
                    $Branches = GetBranches();
                    foreach($Branches as $Branch){
                        $Message = $Branch['arabic_name'];
                        $Message = $Message."\n\n"."*العنوان:* ".$Branch['arabic_address'];
                        if($Branch['mobile']){
                            $Message = $Message."\n\n"."*الهاتف:* ".$Branch['mobile'];
                        }
                        $Message = $Message."\n\n".$Branch['link'];
                        SendMessage($To,$Message);
                    }
                    $Message = "0 . للرجوع للقائمة الرئيسية 📌";
                    $Message = $Message."\n"."* "." . للتحدث مع احد ممثلي خدمة العملاء 💬";
                    SendMessage($To,$Message);
                }
                if($Flow == "OFFER"){
                    $Offers = GetOffers();
                    if(count($Offers)){
                        foreach($Offers as $Offer){
                            $Image = $Offer['image'];
                            $Caption = $Offer['title_arabic'];
                            // $Caption = $Caption."\n\n".$Offer['desc_arabic'];
                            $Caption = $Caption."\n\n"."https://alghazal.sa/od/".$Offer['id'];
                            SendImage($To,$Image,$Caption);
                        }
                        $Message = "0 . للرجوع للقائمة الرئيسية 📌";
                        $Message = $Message."\n"."* "." . للتحدث مع احد ممثلي خدمة العملاء 💬";
                        SendMessage($To,$Message);
                    }else{
                        $Message = "لا يوجد عروض في الفترة الحالية ";
                        $Message = $Message."\n"."برجاء المتابعة وسوف يتم الاعلان عن عروض جديدة في اقرب وقت 🤩";
                        $Message = $Message."\n"."‐--------------------------------";
                        $Message = $Message."\n"."0 . للرجوع للقائمة الرئيسية 📌";
                        $Message = $Message."\n"."* "." . للتحدث مع احد ممثلي خدمة العملاء 💬";
                        SendMessage($To,$Message);
                    }
                }
                if($Flow == "LEMO"){
                    $Message = "شكرا لإختيارك خدمة ليموزين الغزال .. ونتمنى لك رحلة آمنة وممتعة 🙏🏻";
                    SendMessage($To,$Message);
                    $Message = "برجاء ارسال اسم سيادتكم";
                    SendMessage($To,$Message);
                }
                if($Flow == "FROM_LEMO"){
                    $Message = "برجاء ارسال من أين تبدأ الرحلة";
                    SendMessage($To,$Message);
                }
                if($Flow == "TO_LEMO"){
                    $Message = "برجاء ارسال الى أين تنتهي الرحلة";
                    SendMessage($To,$Message);
                }
                if($Flow == "DATE_LEMO"){
                    $Message = "برجاء ارسال تاريخ الرحلة 📆";
                    SendMessage($To,$Message);
                }
                if($Flow == "AFTER_LEMO"){
                    $LemoData = GetLemoData($IDChat);
                    LemoTicket($LemoData['Name'],$To,$LemoData['From'],$LemoData['To'],$LemoData['Date']);
                    $Message = "شكرا علي معلوماتك .. ونتشرف بخدمتك 🙏🏻";
                    $Message = $Message."\n"." وسوف يتم التواصل معك من قبل افضل مسئولي خدمة عملاء لدينا في اقرب وقت 😊";
                    SendMessage($To,$Message);
                    UpdateSession($To,2);
                }
                if($Flow == "RATING_0"){
                    if($RateComplainFlag){
                        if($Body == "#"){
                            $GetRateComplain = GetRateComplain($IDChat);
                            $Complain = $GetRateComplain['Complain'];
                            $Rate = $GetRateComplain['Rate'];
                            SendRate($To,0,$Rate,$Complain);
                            $Message = "شكرا لوقتك .. ";
                            $Message = $Message."\n"."ونعدك بتحسين مستوى الخدمة في المرات القادمة 🤝";
                            SendMessage($To,$Message);
                            UpdateSession($To,2);
                        }
                    }else{
                        if(is_numeric($Body)){
                            SendRate($To,0,$Body,"");
                            $Message = "شكرا لوقتك 🙏🏻";
                            $Message = $Message."\n"."نسعى جاهدين لتقديم افضل مستوى من الخدمة .. ونعدك بالأفضل دائما 🤝";
                            SendMessage($To,$Message);
                            UpdateSession($To,2);
                        }else{
                            $Message = "برجاء الإختيار من 1 الى 5 🙏🏻";
                            SendMessage($To,$Message);
                        }
                    }
                }
                if($Flow == "RATING_1"){
                    if($RateComplainFlag){
                        if($Body == "#"){
                            $GetRateComplain = GetRateComplain($IDChat);
                            $Complain = $GetRateComplain['Complain'];
                            $Rate = $GetRateComplain['Rate'];
                            SendRate($To,1,$Rate,$Complain);
                            $Message = "برجاء تقييم السيارة  :";
                            $Message = $Message."\n *1.* سيئة جدا 😠\n *2.* سيئة ☹️ \n *3.* متوسطة 🙂\n *4.* جيدة 😃\n *5.* جيدة جدا 🤩";
                            SendMessage($To,$Message);
                        }
                    }else{
                        if(is_numeric($Body)){
                            SendRate($To,1,$Body,"");
                            $Message = "برجاء تقييم السيارة  :";
                            $Message = $Message."\n *1.* سيئة جدا 😠\n *2.* سيئة ☹️ \n *3.* متوسطة 🙂\n *4.* جيدة 😃\n *5.* جيدة جدا 🤩";
                            SendMessage($To,$Message);
                        }else{
                            $Message = "برجاء الإختيار من 1 الى 5 🙏🏻";
                            SendMessage($To,$Message);
                        }
                    }
                }
                if($Flow == "RATING_2"){
                    if($RateComplainFlag){
                        if($Body == "#"){
                            $GetRateComplain = GetRateComplain($IDChat);
                            $Complain = $GetRateComplain['Complain'];
                            $Rate = $GetRateComplain['Rate'];
                            SendRate($To,2,$Rate,$Complain);
                            $Message = "برجاء تقييم مستوى الموظف  :";
                            $Message = $Message."\n *1.* سيئ جدا 😠\n *2.* سيئ ☹️ \n *3.* متوسط 🙂\n *4.* جيد 😃\n *5.* جيد جدا 🤩";
                            SendMessage($To,$Message);
                        }
                    }else{
                        if(is_numeric($Body)){
                            SendRate($To,2,$Body,"");
                            $Message = "برجاء تقييم مستوى الموظف  :";
                            $Message = $Message."\n *1.* سيئ جدا 😠\n *2.* سيئ ☹️ \n *3.* متوسط 🙂\n *4.* جيد 😃\n *5.* جيد جدا 🤩";
                            SendMessage($To,$Message);
                        }else{
                            $Message = "برجاء الإختيار من 1 الى 5 🙏🏻";
                            SendMessage($To,$Message);
                        }
                    }
                }
                if($Flow == "RATING_3"){
                    if($RateComplainFlag){
                        if($Body == "#"){
                            $GetRateComplain = GetRateComplain($IDChat);
                            $Complain = $GetRateComplain['Complain'];
                            $Rate = $GetRateComplain['Rate'];
                            SendRate($To,3,$Rate,$Complain);
                            $Message = "شكرا لوقتك .. ";
                            $Message = $Message."\n"."ونعدك بتحسين مستوى الخدمة في المرات القادمة 🤝";
                            SendMessage($To,$Message);
                            UpdateSession($To,2);
                        }
                    }else{
                        if(is_numeric($Body)){
                            SendRate($To,3,$Body,"");
                            $CheckForComplain = CheckForComplain($IDChat);
                            if($CheckForComplain){
                                $Message = "شكرا لوقتك .. ";
                                $Message = $Message."\n"."ونعدك بتحسين مستوى الخدمة في المرات القادمة 🤝";
                                SendMessage($To,$Message);
                                UpdateSession($To,2);
                            }else{
                                $Message = "شكرا لوقتك 🙏🏻";
                                $Message = $Message."\n"."نسعى جاهدين لتقديم افضل مستوى من الخدمة .. ونعدك بالأفضل دائما 🤝";
                                SendMessage($To,$Message);
                                UpdateSession($To,2);
                            }
                        }else{
                            $Message = "برجاء الإختيار من 1 الى 5 🙏🏻";
                            SendMessage($To,$Message);
                        }
                    }
                }
                if($Flow == "RATING_COMPLAIN"){
                    $Message = "شكرا لتقييمك 😊";
                    $Message = $Message."\n"."ولكننا بحاجة لتحسين مستوى الخدمة لدينا ..  لذلك برجاء كتابة ملاحظاتك 🙏🏻";
                    $Message = $Message."\n"."وبعد الانتهاء برجاء الضغط على #";
                    SendMessage($To,$Message);
                }
            }
        }

        if($Language == "EN"){
            if($Flow != "STOP"){
                if(!count($GetMessages) || $Flow == ""){
                    $Offer = UpdateSession($To,0);
                    $Message = "مرحبا بك في شركة الغزال لتأجير السيارات 😊";
                    SendMessage($To,$Message);
                    if($Offer['offer']){
                        $Image = $Offer['image'];
                        $Caption = $Offer['arabic_title'];
                        // $Caption = $Caption."\n\n".$Offer['arabic_desc'];
                        $Caption = $Caption."\n\n"."https://alghazal.sa/od/".$Offer['offer_id'];
                        SendImage($To,$Image,$Caption);
                    }
                    $Message = "1.عربي";
                    $Message = $Message."\n"."2.English";
                    SendMessage($To,$Message);
                }
                if($Request == "MAIN" || $Flow == "MAIN"){
                    $Message = "We will be happy to serve you 🤩";
                    $Message = $Message."\n"."Please choose the required service number:";
                    $Message = $Message."\n *1.* Customers Service\n *2.* Car Rental \n *3.* Report an Accident \n *4.* Extending the Rental Period";
                    $Message = $Message."\n *5.* Book a Limousine\n *6.* Offers \n *7.* Branches";
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . To return to the main menu📌";
                    $Message = $Message."\n"."* "." . To speak with a customer service representative 💬";
                    SendMessage($To,$Message);
                }
                if($Request == "CS"){
                    $Message = "We strive to provide the best level of service 🙏";
                    $Message = $Message."\n"." Please choose the required service number :";
                    $Message = $Message."\n *1.* Chat with a customer service representative  💬\n *2.* Call with a customer service representative 📱 \n *3.* You have a complaint 📨";
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . To return to the main menu📌";
                    SendMessage($To,$Message);
                }
                if($Request == "ACCIDENT"){
                    $Message = "We're Glad You're Okay 🙏🏻";
                    $Message = $Message."\n"." Please choose the required service number :";
                    $Message = $Message."\n *1.* Chat with a customer service representative  💬\n *2.* Call with a customer service representative 📱 \n *3.* Send attachments and photos of the accident";
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . To return to the main menu📌";
                    SendMessage($To,$Message);
                }
                if($Request == "CALL" && $Flow == "CS"){
                    $Message = "We will be happy to respond to you, please call this number 😊";
                    SendMessage($To,$Message);
                    $Message = "0920006435";
                    SendMessage($To,$Message);
                    UpdateSession($To,2);
                }
                if($Request == "MSG" && $Flow == "CS"){
                    $Message = "You will be contacted by our best customer service representatives as soon as possible 🤝";
                    SendMessage($To,$Message);
                }
                if($Flow == "AGENT"){
                    $Message = "You will be contacted by our best customer service representatives as soon as possible 🤝";
                    SendMessage($To,$Message);
                }
                if($Request == "COMPLAIN" && $Flow == "CS"){
                    $Message = "Please write your complaint 🙏🏻";
                    $Message = $Message."\n"."After finishing your complaint please press  #";
                    SendMessage($To,$Message);
                }
                if($Request == "CALL" && $Flow == "ACCIDENT"){
                    $Message = "We will be happy to respond to you, please call this number 😊";
                    SendMessage($To,$Message);
                    $Message = "0920006435";
                    SendMessage($To,$Message);
                    UpdateSession($To,2);
                }
                if($Request == "MSG" && $Flow == "ACCIDENT"){
                    $Message = "You will be contacted by our best customer service representatives as soon as possible 🤝";
                    SendMessage($To,$Message);
                }
                if($Request == "IMAGE" && $Flow == "ACCIDENT"){
                    UpdateSession($To,1);
                    $Message = "Please send attachments and photos of the accident";
                    $Message = $Message."\n"."After finishing please press  #";
                    SendMessage($To,$Message);
                }
                if($Flow == "CS_COMPLAIN"){
                    if($Body == "#"){
                        $Name = GetName($IDChat);
                        $Body = GetComplain($IDChat);
                        Ticket($To,$Name,0,$Body,0,Null);
                        $Message = "We are sorry to hear this complaint from you 🙏. It will be looked into, the necessary measures will be taken, and we will contact you as soon as possible.";
                        SendMessage($To,$Message);
                        UpdateSession($To,2);
                    }
                }
                if($Flow == "AFTER_ACCIDENT"){
                    if($Body == "#"){
                        $Name = GetName($IDChat);
                        Ticket($To,$Name,1,"",0,Null);
                        $Message = "We're Glad You're Okay 🙏🏻";
                        $Message = $Message."\n"."We will contact you as soon as possible.";
                        SendMessage($To,$Message);
                        UpdateSession($To,2);
                    }
                }
                if($Flow == "RENT"){
                    $ClientExist = CheckClientExist($To);
                    $Brands = GetBrands("SHOW",$Language);
                    if($ClientExist){
                        $Message = "We always thank you for being among the company’s best valued clients 🤝";
                    }else{
                        $Message = "We are pleased to welcome you as a new customer and we strive to provide you with the best services  🤝";
                    }
                    $Message = $Message."\n"."Please select the desired car brand number: ".$Brands;
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . To return to the main menu📌";
                    $Message = $Message."\n"."* "." . To speak with a customer service representative 💬";
                    $Message = $Message."\n\n"."You can browse more cars by visiting the website";
                    $Message = $Message."\n"."https://alghazal.sa/";
                    SendMessage($To,$Message);
                }
                if($Flow == "BRAND"){
                    $Body = EnglishConverter($Body);
                    $IDBrand = GetIDBrand($Body,$Language);
                    $SubBrands = GetSubBrands($IDBrand,"SHOW",$Language);
                    $Message = "Please select the desired car class number: ".$SubBrands;
                    $Message = $Message."\n"."‐--------------------------------";
                    $Message = $Message."\n"."0 . To return to the main menu📌";
                    $Message = $Message."\n"."# . To return to the previous menu⏪";
                    $Message = $Message."\n"."* "." . To speak with a customer service representative 💬";
                    $Message = $Message."\n\n"."You can browse more cars by visiting the website";
                    $Message = $Message."\n"."https://alghazal.sa/";
                    SendMessage($To,$Message);
                }
                if($Flow == "SUBBRAND"){
                    $Body = EnglishConverter($Body);
                    $IDSubBrand = GetIDSubBrand($Body,$IDChat,$Language);
                    $Cars = GetCars($IDSubBrand);
                    if(count($Cars)){
                        foreach($Cars as $Car){
                            $Image = $Car['image'];
                            $Offer = $Car['offer'];
                            if($Offer){
                                $Caption = "*".$Car['name_en']."*"."\n\n". "*Type*" ." ". $Car['type']  ."\n\n" . "Price per day:" . "~".$Car['price']. "~". " " . " *".$Car['offer_price']."* " . "RS including tax ";
                            }else{
                                $Caption = "*".$Car['name_en']."*"."\n\n". "*Type*" ." ". $Car['type']  ."\n\n" . "Price per day: " . " *".$Car['price']."* " . "RS including tax ";
                            }
                            $Caption = $Caption."\n\n"."https://alghazal.sa/cd/".$Car['id'];
                            SendImage($To,$Image,$Caption);
                        }
                        $Message = "You can reserve a car from the previous cars by clicking on the link below the car";
                        $Message = $Message."\n"."‐--------------------------------";
                        $Message = $Message."\n"."0 . To return to the main menu📌";
                        $Message = $Message."\n"."# . To return to the previous menu⏪";
                        $Message = $Message."\n"."* "." . To speak with a customer service representative 💬";
                        SendMessage($To,$Message);
                    }else{
                        $Brands = GetBrands("SHOW",$Language);
                        $Message = "We apologize, this car is not available at the moment 🙏🏻";
                        $Message = $Message."\n"."Please choose another car";
                        SendMessage($To,$Message);
                        $Message = "Please select the desired car brand number: ".$Brands;
                        $Message = $Message."\n"."‐--------------------------------";
                        $Message = $Message."\n"."0 . To return to the main menu📌";
                        $Message = $Message."\n"."* "." . To speak with a customer service representative 💬";
                        $Message = $Message."\n\n"."You can browse more cars by visiting the website";
                        $Message = $Message."\n"."https://alghazal.sa/";
                        SendMessage($To,$Message);
                    }
                }
                if($Flow == "EXTEND"){
                    $Contracts = GetContracts($To,"SHOW");
                    if(!$Contracts){
                        $Message = "We apologize, you do not currently have rental cars 🙏🏻";
                        $Message = $Message."\n"."‐--------------------------------";
                        $Message = $Message."\n"."0 . To return to the main menu📌";
                        $Message = $Message."\n"."* "." . To speak with a customer service representative 💬";
                        SendMessage($To,$Message); 
                    }else{
                        $Message = "We are honored to serve you and are happy to have you continue with us for a longer period 🤩 ";
                        $Message = $Message."\n"."Please select the car whose contract you want to extend".$Contracts;
                        SendMessage($To,$Message);
                    }
                }
                if($Flow == "CONTRACT"){
                    $Message = "Please write the number of days to be extended 🙏🏻";
                    SendMessage($To,$Message);
                }
                if($Flow == "EXTEND_REQUEST"){
                    $Body = EnglishConverter($Body);
                    $Name = GetName($IDChat);
                    $IDReservation = GetIDReservation($To,$IDChat);
                    Ticket($To,$Name,2,Null,$Body,$IDReservation);
                    $Message = "You can make a bank transfer to the following bank accounts :";
                    $Message = $Message."\n"."Al Rajhi bank :";
                    $Message = $Message."\n"."443000010006080165814";
                    $Message = $Message."\n"."IBAN : SA1580000443608010165814";
                    $Message = $Message."\n\n"."Saudi National Bank :";
                    $Message = $Message."\n"."00577255000104";
                    $Message = $Message."\n"."IBAN : SA2910000000577255000104";
                    SendMessage($To,$Message);
                    $Message = "You will be contacted by our best customer service representatives as soon as possible 🤝";
                    SendMessage($To,$Message);
                    UpdateSession($To,2);
                }
                if($Flow == "BRANCH"){
                    $Branches = GetBranches();
                    foreach($Branches as $Branch){
                        $Message = $Branch['name'];
                        $Message = $Message."\n\n"."*Address:* ".$Branch['address'];
                        if($Branch['mobile']){
                            $Message = $Message."\n\n"."*Phone:* ".$Branch['mobile'];
                        }
                        $Message = $Message."\n\n".$Branch['link'];
                        SendMessage($To,$Message);
                    }
                    $Message = "0 . To return to the main menu📌";
                    $Message = $Message."\n"."* "." . To speak with a customer service representative 💬";
                    SendMessage($To,$Message);
                }
                if($Flow == "OFFER"){
                    $Offers = GetOffers();
                    if(count($Offers)){
                        foreach($Offers as $Offer){
                            $Image = $Offer['image'];
                            $Caption = $Offer['title'];
                            // $Caption = $Caption."\n\n".$Offer['desc'];
                            $Caption = $Caption."\n\n"."https://alghazal.sa/od/".$Offer['id'];
                            SendImage($To,$Image,$Caption);
                        }
                        $Message = "0 . To return to the main menu📌";
                        $Message = $Message."\n"."* "." . To speak with a customer service representative 💬";
                        SendMessage($To,$Message);
                    }else{
                        $Message = "There are no offers at the current moment ";
                        $Message = $Message."\n"."Please follow us and new offers will be announced soon 🤩";
                        $Message = $Message."\n"."‐--------------------------------";
                        $Message = $Message."\n"."0 . To return to the main menu📌";
                        $Message = $Message."\n"."* "." . To speak with a customer service representative 💬";
                        SendMessage($To,$Message);
                    }
                }
                if($Flow == "LEMO"){
                    $Message = "Thank you for choosing Al Ghazal Limousine Service.. We wish you a safe and enjoyable trip 🙏🏻";
                    SendMessage($To,$Message);
                    $Message = "Please write your name";
                    SendMessage($To,$Message);
                }
                if($Flow == "FROM_LEMO"){
                    $Message = "Please write where you start the journey";
                    SendMessage($To,$Message);
                }
                if($Flow == "TO_LEMO"){
                    $Message = "Please write where the journey ends";
                    SendMessage($To,$Message);
                }
                if($Flow == "DATE_LEMO"){
                    $Message = "Please write the journey date 📆";
                    SendMessage($To,$Message);
                }
                if($Flow == "AFTER_LEMO"){
                    $LemoData = GetLemoData($IDChat);
                    LemoTicket($LemoData['Name'],$To,$LemoData['From'],$LemoData['To'],$LemoData['Date']);
                    $Message = "Thank you for your information.. We are honored to serve you 🙏🏻";
                    $Message = $Message."\n"." Our best customer service representatives will contact you as soon as possible 😊";
                    SendMessage($To,$Message);
                    UpdateSession($To,2);
                }
                if($Flow == "RATING_0"){
                    if($RateComplainFlag){
                        if($Body == "#"){
                            $GetRateComplain = GetRateComplain($IDChat);
                            $Complain = $GetRateComplain['Complain'];
                            $Rate = $GetRateComplain['Rate'];
                            SendRate($To,0,$Rate,$Complain);
                            $Message = "Thanks for your time .. ";
                            $Message = $Message."\n"."We promise to improve the level of service in the coming times 🤝";
                            SendMessage($To,$Message);
                            UpdateSession($To,2);
                        }
                    }else{
                        if(is_numeric($Body)){
                            SendRate($To,0,$Body,"");
                            $Message = "Thanks for your time 🙏🏻";
                            $Message = $Message."\n"."We strive to provide the best level of service...and we always promise you the best 🤝";
                            SendMessage($To,$Message);
                            UpdateSession($To,2);
                        }else{
                            $Message = "Please choose a number from 1 to 5 🙏🏻";
                            SendMessage($To,$Message);
                        }
                    }
                }
                if($Flow == "RATING_1"){
                    if($RateComplainFlag){
                        if($Body == "#"){
                            $GetRateComplain = GetRateComplain($IDChat);
                            $Complain = $GetRateComplain['Complain'];
                            $Rate = $GetRateComplain['Rate'];
                            SendRate($To,1,$Rate,$Complain);
                            $Message = "Please rate the car:";
                            $Message = $Message."\n *1.* Very Bad 😠\n *2.* Bad ☹️ \n *3.* Medium 🙂\n *4.* Good 😃\n *5.* Very Good 🤩";
                            SendMessage($To,$Message);
                        }
                    }else{
                        if(is_numeric($Body)){
                            SendRate($To,1,$Body,"");
                            $Message = "Please rate the car:";
                            $Message = $Message."\n *1.* Very Bad 😠\n *2.* Bad ☹️ \n *3.* Medium 🙂\n *4.* Good 😃\n *5.* Very Good 🤩";
                            SendMessage($To,$Message);
                        }else{
                            $Message = "Please choose a number from 1 to 5 🙏🏻";
                            SendMessage($To,$Message);
                        }
                    }
                }
                if($Flow == "RATING_2"){
                    if($RateComplainFlag){
                        if($Body == "#"){
                            $GetRateComplain = GetRateComplain($IDChat);
                            $Complain = $GetRateComplain['Complain'];
                            $Rate = $GetRateComplain['Rate'];
                            SendRate($To,2,$Rate,$Complain);
                            $Message = "Please rate the employee's level:";
                            $Message = $Message."\n *1.* Very Bad 😠\n *2.* Bad ☹️ \n *3.* Medium 🙂\n *4.* Good 😃\n *5.* Very Good 🤩";
                            SendMessage($To,$Message);
                        }
                    }else{
                        if(is_numeric($Body)){
                            SendRate($To,2,$Body,"");
                            $Message = "Please rate the employee's level:";
                            $Message = $Message."\n *1.* Very Bad 😠\n *2.* Bad ☹️ \n *3.* Medium 🙂\n *4.* Good 😃\n *5.* Very Good 🤩";
                            SendMessage($To,$Message);
                        }else{
                            $Message = "Please choose a number from 1 to 5 🙏🏻";
                            SendMessage($To,$Message);
                        }
                    }
                }
                if($Flow == "RATING_3"){
                    if($RateComplainFlag){
                        if($Body == "#"){
                            $GetRateComplain = GetRateComplain($IDChat);
                            $Complain = $GetRateComplain['Complain'];
                            $Rate = $GetRateComplain['Rate'];
                            SendRate($To,3,$Rate,$Complain);
                            $Message = "Thanks for your time .. ";
                            $Message = $Message."\n"."We promise to improve the level of service in the coming times 🤝";
                            SendMessage($To,$Message);
                            UpdateSession($To,2);
                        }
                    }else{
                        if(is_numeric($Body)){
                            SendRate($To,3,$Body,"");
                            $CheckForComplain = CheckForComplain($IDChat);
                            if($CheckForComplain){
                                $Message = "Thank for your time .. ";
                                $Message = $Message."\n"."We promise to improve the level of service in the coming times 🤝";
                                SendMessage($To,$Message);
                                UpdateSession($To,2);
                            }else{
                                $Message = "Thanks for your time 🙏🏻";
                                $Message = $Message."\n"."We strive to provide the best level of service...and we always promise you the best 🤝";
                                SendMessage($To,$Message);
                                UpdateSession($To,2);
                            }
                        }else{
                            $Message = "Please choose a number from 1 to 5 🙏🏻";
                            SendMessage($To,$Message);
                        }
                    }
                }
                if($Flow == "RATING_COMPLAIN"){
                    $Message = "Thank you for your evaluation 😊";
                    $Message = $Message."\n"."But we need to improve our service level..so please write your feedback 🙏🏻";
                    $Message = $Message."\n"."After you finish please press #";
                    SendMessage($To,$Message);
                }
            }
        }

    }

    function SendMessage($To,$Message){
        $ultramsg_token="hrnjpqasenbv43ht"; // Ultramsg.com token
        $instance_id="instance73086"; // Ultramsg.com instance id
        $client = new UltraMsg\WhatsAppApi($ultramsg_token,$instance_id);
        $api=$client->sendChatMessage($To,$Message);
    }

    function GetMessages($IDChat){
        $params=array(
        'token' => 'hrnjpqasenbv43ht',
        'page' => '1',
        'limit' => '10',
        'status' => 'all',
        'sort' => 'desc',
        'id' => '',
        'referenceId' => '',
        'from' => '',
        'to' => $IDChat,
        'ack' => '',
        'msgId' => '',
        'start_date' => '',
        'end_date' => ''
        );
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.ultramsg.com/instance73086/messages?" .http_build_query($params),
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
          ),
        ));
        
        $GetMessages = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        $GetMessages = json_decode($GetMessages, true);

        return $GetMessages;
    }

    function GetBrands($Type,$Language){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://alghazal.sa/gazalservices/brands");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $Brands = curl_exec($ch);
        curl_close($ch);

        $Brands = json_decode($Brands, true);
        if($Type == "GET"){
            return $Brands;
        }
        $Counter = 1;
        $BrandList = "";
        foreach($Brands as $Brand){
            if($Language == "AR"){
                $BrandList = $BrandList."\n *".$Counter.".* ".$Brand['arabic_name'];
            }
            if($Language == "EN"){
                $BrandList = $BrandList."\n *".$Counter.".* ".$Brand['name'];
            }
            $Counter++;
        }

        return $BrandList;
    }

    function GetIDBrand($Body,$Language){
        $Brands = GetBrands("GET",$Language);
        $Body = $Body - 1;
        $IDBrand = $Brands[$Body]['id'];
        return $IDBrand;
    }

    function GetIDSubBrand($Body,$IDChat,$Language){
        $Messages = GetAllMessages($IDChat);
        $Flag = False;
        foreach($Messages as $Message){
            $Text = $Message['body'];
            $MyMessage = $Message['fromMe'];
            if (str_contains($Text, 'برجاء اختيار رقم ماركة') || str_contains($Text, 'Please select the desired car brand number')) { 
                $Flag = True;
                continue;
            }
            if($Flag){
                $IDBrandInput = $Text;
                $Flag = false;
                continue;
            }
        }
        $IDBrandInput = EnglishConverter($IDBrandInput);
        $IDBrand = GetIDBrand($IDBrandInput,$Language);
        $SubBrands = GetSubBrands($IDBrand,"GET",$Language);
        if(is_numeric($Body)){
            $Body = $Body - 1;
            $IDSubBrand = $SubBrands[$Body]['id'];
            return $IDSubBrand;
        }
        foreach($SubBrands as $SubBrand){
            if($Body == $SubBrand['arabic_name']){
                $IDSubBrand = $SubBrand['id'];
                break;
            }
        }
        return $IDSubBrand;
    }

    function GetAllMessages($IDChat){
        $params=array(
        'token' => 'hrnjpqasenbv43ht',
        'chatId' => $IDChat,
        'limit' => '25'
        );
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ultramsg.com/instance73086/chats/messages?" .http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "content-type: application/json"
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        $response = json_decode($response, true);
        return $response;
    }

    function Ticket($Mobile,$Name,$Type,$Message,$Days,$IDReservation){
        $curl = curl_init();

        $Fields = [
            'mobile' => $Mobile,
            'name' => $Name,
            'type' => $Type,
            'msg' => $Message,
            'days' => $Days,
            'res' => $IDReservation,
        ];

        $Url = "https://alghazal.sa/gazalservices/ticket" . '?' . http_build_query($Fields);
    
        curl_setopt_array($curl, [
            CURLOPT_URL => $Url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Fields,
            CURLOPT_HTTPHEADER => [
              "content-type: application/json"
            ],
        ]);
          
        $response = curl_exec($curl);
        $err = curl_error($curl);
          
        curl_close($curl);
    }

    function GetName($IDChat){
        $params=array(
        'token' => 'hrnjpqasenbv43ht',
        'chatId' => $IDChat
        );
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ultramsg.com/instance73086/contacts/contact?" .http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, true);
        $Name = $response['name'];

        return $Name;

    }

    function UpdateSession($Mobile,$Type){
        $params=array(
        'mobile' => $Mobile,
        'type' => $Type
        );
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://alghazal.sa/gazalservices/wreg?" .http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        return $response;
    }

    function GetSubBrands($IDBrand,$Type,$Language){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://alghazal.sa/gazalservices/subs?brandId=".$IDBrand);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $SubBrands = curl_exec($ch);
        curl_close($ch);

        $SubBrands = json_decode($SubBrands, true);
        if($Type == "GET"){
            return $SubBrands;
        }
        $Counter = 1;
        $SubBrandList = "";
        foreach($SubBrands as $SubBrand){
            if($Language == "AR"){
                $SubBrandList = $SubBrandList."\n *".$Counter.".* ".$SubBrand['arabic_name'];
            }
            if($Language == "EN"){
                $SubBrandList = $SubBrandList."\n *".$Counter.".* ".$SubBrand['name'];
            }
            $Counter++;
        }

        return $SubBrandList;
    }

    function GetCars($IDSubBrand){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://alghazal.sa/gazalservices/wcars?sub_id=".$IDSubBrand);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $Cars = curl_exec($ch);
        curl_close($ch);

        $Cars = json_decode($Cars, true);
        return $Cars;
    }

    function SendImage($To,$Image,$Caption){
        $Priority = 10;
        $ReferenceId="SDK";
        $Nocache=false; 
        $Ultramsg_token="hrnjpqasenbv43ht"; // Ultramsg.com token
        $Instance_id="instance73086"; // Ultramsg.com instance id
        $Client = new UltraMsg\WhatsAppApi($Ultramsg_token,$Instance_id);
        $Api = $Client->sendImageMessage($To,$Image,$Caption,$Priority,$ReferenceId,$Nocache);
    }

    function GetContracts($To,$Type){
        if($To[0] == "9"){
            $To = substr($To, 3);
            $To = "0".$To;
        }else{
            $To = substr($To, 1);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://alghazal.sa/gazalservices/wres?mobile=".$To);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $Contracts = curl_exec($ch);
        curl_close($ch);

        $Contracts = json_decode($Contracts, true);
        if($Type == "GET"){
            return $Contracts;
        }
        if(!count($Contracts)){
            return Null;
        }
        $Counter = 1;
        $ContractList = "";
        foreach($Contracts as $Contract){
            $ContractList = $ContractList."\n *".$Counter.".* ".$Contract['name'];
            $Counter++;
        }

        return $ContractList;
    }

    function GetIDReservation($To,$IDChat){
        $Messages = GetAllMessages($IDChat);
        $Flag = False;
        foreach($Messages as $Message){
            $Text = $Message['body'];
            $MyMessage = $Message['fromMe'];
            if (str_contains($Text, 'برجاء كتابة عدد الايام المطلوب تمديدها') || str_contains($Text, 'Please write the number of days to be extended')) { 
                $Flag = True;
                continue;
            }
            if($Flag){
                $Body = $Text;
                $Flag = false;
                continue;
            }
        }

        $Body = EnglishConverter($Body);
        $Contracts = GetContracts($To,"GET");
        if(is_numeric($Body)){
            $Body = $Body - 1;
            $IDReservation = $Contracts[$Body]['res_id'];
            return $IDReservation;
        }

        return $IDReservation;
    }

    function CheckClientExist($To){
        if($To[0] == "9"){
            $To = substr($To, 3);
            $To = "0".$To;
        }else{
            $To = substr($To, 1);
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://alghazal.sa/gazalservices/wcheck?mobile=".$To);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ClientExist = curl_exec($ch);
        curl_close($ch);

        $ClientExist = json_decode($ClientExist, true);
        $ClientExist = $ClientExist['status'];
        return $ClientExist;
    }

    function EnglishConverter($String) {
        $Arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    
        $Num = range(0, 9);
        $EnglishNumber = str_replace($Arabic, $Num, $String);
        
        return $EnglishNumber;
    }

    function GetComplain($IDChat){
        $Messages = GetAllMessages($IDChat);
        $Flag = False;
        $Body = "";
        foreach($Messages as $Message){
            $Text = $Message['body'];
            $MyMessage = $Message['fromMe'];
            if (str_contains($Text, 'برجاء كتابة الشكوي') || str_contains($Text, 'Please write your complaint')) { 
                $Flag = True;
                $Body = "";
                continue;
            }
            if($Text == "#"){
                $Flag = false;
                continue;
            }
            if($Flag){
                $Body = $Body." ".$Text;
                continue;
            }
        }

        return $Body;
    }

    function GetLemoData($IDChat){
        $Messages = GetAllMessages($IDChat);
        $NameFlag = False;
        $FromFlag = False;
        $ToFlag = False;
        $DateFlag = False;
        $Name = "";
        $From = "";
        $To = "";
        $Date = "";
        foreach($Messages as $Message){
            $Text = $Message['body'];
            $MyMessage = $Message['fromMe'];
            if (str_contains($Text, 'برجاء ارسال اسم سيادتكم') || str_contains($Text, 'Please write your name')) { 
                $NameFlag = True;
                $Body = "";
                continue;
            }
            if (str_contains($Text, 'برجاء ارسال من أين تبدأ الرحلة') || str_contains($Text, 'Please write where you start the journey')) { 
                $FromFlag = True;
                $Body = "";
                continue;
            }
            if (str_contains($Text, 'برجاء ارسال الى أين تنتهي الرحلة') || str_contains($Text, 'Please write where the journey ends')) { 
                $ToFlag = True;
                $Body = "";
                continue;
            }
            if (str_contains($Text, 'برجاء ارسال تاريخ الرحلة 📆') || str_contains($Text, 'Please write the journey date 📆')) { 
                $DateFlag = True;
                $Body = "";
                continue;
            }
            if($NameFlag){
                $Name = $Text;
                $NameFlag = false;
                continue;
            }
            if($FromFlag){
                $From = $Text;
                $FromFlag = false;
                continue;
            }
            if($ToFlag){
                $To = $Text;
                $ToFlag = false;
                continue;
            }
            if($DateFlag){
                $Date = $Text;
                $DateFlag = false;
                continue;
            }
        }

        $Data = ["Name"=>$Name,"From"=>$From,"To"=>$To,"Date"=>$Date];
        return $Data;
    }

    function GetBranches(){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://alghazal.sa/gazalservices/branches");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $Branches = curl_exec($ch);
        curl_close($ch);

        $Branches = json_decode($Branches, true);
        return $Branches;
    }

    function LemoTicket($Name,$Mobile,$From,$To,$Date){
        $curl = curl_init();

        $Fields = [
            'mobile' => $Mobile,
            'name' => $Name,
            'from' => $From,
            'to' => $To,
            'date' => $Date,
        ];

        $Url = "https://alghazal.sa/gazalservices/wlimo" . '?' . http_build_query($Fields);
    
        curl_setopt_array($curl, [
            CURLOPT_URL => $Url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Fields,
            CURLOPT_HTTPHEADER => [
              "content-type: application/json"
            ],
        ]);
          
        $response = curl_exec($curl);
        $err = curl_error($curl);
          
        curl_close($curl);
    }

    function GetOffers(){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://alghazal.sa/gazalservices/offers");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $Offers = curl_exec($ch);
        curl_close($ch);

        $Offers = json_decode($Offers, true);
        return $Offers;
    }

    function SendRate($Mobile,$Type,$Rate,$Comment){
        if($Mobile[0] == "9"){
            $Mobile = substr($Mobile, 3);
            $Mobile = "0".$Mobile;
        }else{
            $Mobile = substr($Mobile, 1);
        }

        $curl = curl_init();

        $Fields = [
            'type' => $Type,
            'rate' => $Rate,
            'mobile' => $Mobile,
            'comment' => $Comment,
        ];

        $Url = "https://alghazal.sa/gazalservices/wrate" . '?' . http_build_query($Fields);
    
        curl_setopt_array($curl, [
            CURLOPT_URL => $Url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $Fields,
            CURLOPT_HTTPHEADER => [
              "content-type: application/json"
            ],
        ]);
          
        $response = curl_exec($curl);
        $err = curl_error($curl);
          
        curl_close($curl);
    }

    function GetRateComplain($IDChat){
        $Messages = GetAllMessages($IDChat);
        $RateFlag = False;
        $ComplainFlag = False;
        $Complain = "";
        $Rate = "";
        foreach($Messages as $Message){
            $Text = $Message['body'];
            $MyMessage = $Message['fromMe'];
            if (str_contains($Text, 'برجاء تقييم مستوى الموظف') || str_contains($Text, 'برجاء تقييم مستوى الخدمة :') || str_contains($Text, 'برجاء تقييم السيارة  :') || str_contains($Text, "Please rate the employee's level") || str_contains($Text, 'Please rate the level of service:') || str_contains($Text, 'Please rate the car:')) { 
                $RateFlag = True;
                $Rate = "";
                continue;
            }
            if (str_contains($Text, 'ولكننا بحاجة لتحسين مستوى الخدمة لدينا ..  لذلك برجاء كتابة ملاحظاتك') || str_contains($Text, 'But we need to improve our service level..so please write your feedback')) { 
                $ComplainFlag = True;
                $Complain = "";
                continue;
            }
            if($RateFlag){
                $RateFlag = False;
                $Rate = $Text;
                continue;
            }
            if($Text == "#"){
                $ComplainFlag = false;
                continue;
            }
            if($ComplainFlag){
                $Complain = $Complain." ".$Text;
                continue;
            }
        }

        $Rating = ["Rate"=>$Rate,"Complain"=>$Complain];
        return $Rating;
    }

    function CheckForComplain($IDChat){
        $Messages = GetAllMessages($IDChat);
        $ComplainFlag = False;
        foreach($Messages as $Message){
            $Text = $Message['body'];
            if (str_contains($Text, 'ولكننا بحاجة لتحسين مستوى الخدمة لدينا ..  لذلك برجاء كتابة ملاحظاتك') || str_contains($Text, 'But we need to improve our service level..so please write your feedback')) { 
                $ComplainFlag = True;
                break;
            }
        }

        return $ComplainFlag;
    }

    function SaveLanguage($Mobile,$Language){
        $MainFile = fopen("Session.txt", "r") or die("Unable to open file!");
        $MainFile = fread($MainFile,filesize("Session.txt"));
        $File = "{".$MainFile."}";
        $FileData = json_decode($File, true);
        if( isset( $FileData[$Mobile] ) ){
            $From = '"'.$Mobile.'":"'.$FileData[$Mobile].'"';
            $To = '"'.$Mobile.'":"'.$Language.'"';
            $MainFile = str_replace($From,$To,$MainFile);
            $FHandle = fopen("Session.txt","w");
            fwrite($FHandle,$MainFile);
            fclose($FHandle);
        }else{
            $MainFile = $MainFile.",\n".'"'.$Mobile.'":"'.$Language.'"';
            $FHandle = fopen("Session.txt","w");
            fwrite($FHandle,$MainFile);
            fclose($FHandle);
        }
    }

    function GetLanguage($Mobile){
        $File = fopen("Session.txt", "r") or die("Unable to open file!");
        $File = fread($File,filesize("Session.txt"));
        $FileData = "{".$File."}";
        $FileData = json_decode($FileData, true);
        $Language = $FileData[$Mobile];
        return $Language;
    }

    function GetLastSentMessage($IDChat){
        $Messages = GetAllMessages($IDChat);
        $Flag = False;
        foreach($Messages as $Message){
            $Text = $Message['body'];
            if (str_contains($Text, 'برجاء اختيار رقم ماركة السيارة المطلوبة :') || str_contains($Text, 'Please select the desired car brand number')) { 
                $Flag = True;
                continue;
            }
            if($Flag){
                $Body = $Text;
                $Flag = False;
            }
        }

        return $Body;
    }

    function SetTimer($Mobile,$Type,$Time){
        $IDChat = $Mobile."@c.us";
        $Messages = GetAllMessages($IDChat);
        $MessageStatus = "";
        foreach($Messages as $Message){
            $Text = $Message['timestamp'];
            $Body = $Message['body'];
            $MyMessage = $Message['fromMe'];
            $MessageStatus = $Message['ack'];
            if($MyMessage != "false"){
                $TimeStamp = $Text;
                continue;
            }
        }
        $AddedHours = 9;
        if($Mobile[0] == "2"){
            $AddedHours = 8;
        }
        $LastMessage = date('Y-m-d H:i:s', $TimeStamp);
        $LastMessage = date('Y-m-d H:i:s', strtotime($LastMessage.' + '.$AddedHours.' hours'));
        $LastMessage = date('Y-m-d H:i:s', strtotime($LastMessage.' + '.$Time.' minutes'));
        $CurrentTime = date("Y-m-d H:i:s");
        $CurrentTime = date('Y-m-d H:i:s', strtotime($CurrentTime.' + '.$AddedHours.' hours'));
        if($CurrentTime <= $LastMessage){
            return 0;
        }

        $Language = GetLanguage($Mobile);
        if($Type == 0){
            if (str_contains($Body, 'برجاء تقييم مستوى الموظف') || str_contains($Body, 'برجاء تقييم مستوى الخدمة :') || str_contains($Body, 'برجاء تقييم السيارة  :') || str_contains($Body, "Please rate the employee's level") || str_contains($Body, 'Please rate the level of service:') || str_contains($Body, 'Please rate the car:')) { 
                if($MessageStatus != "read"){
                    return 0;
                }
            }
            if($Language == "AR"){
                $Message = "لم يصلنا اي رد منك منذ فترة";
                SendMessage($Mobile,$Message);
                $Message = "تشرفنا بخدمتكم .. ونسعي لتقديم افضل مستوي من الخدمة 🤝";
                $Message = $Message."\n"."شكرآ لاختيارك شركة الغزال";
                SendMessage($Mobile,$Message);
            }
            if($Language == "EN"){
                $Message = "We haven't received any response from you for a while";
                SendMessage($Mobile,$Message);
                $Message = "We are honored to serve you...and we strive to provide the best level of service 🤝";
                $Message = $Message."\n"."Thank you for choosing Al Ghazal Company";
                SendMessage($Mobile,$Message);
            }
        }
        if($Type == 1){
            $Name = GetName($IDChat);
            Ticket($Mobile,$Name,1,"",0,Null);
            if($Language == "AR"){
                $Message = "الحمد لله على سلامتك 🙏🏻 ";
                $Message = $Message."\n"."وسوف يتم التواصل معك في اقرب وقت .";
                SendMessage($Mobile,$Message);
            }
            if($Language == "EN"){
                $Message = "We're Glad You're Okay 🙏🏻 ";
                $Message = $Message."\n"."We will contact you as soon as possible .";
                SendMessage($Mobile,$Message);
            }
        }
        return 1;
    }