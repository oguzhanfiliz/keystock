<?php

function convertToSEO($text)
{

    $turkce = array("ç", "Ç", "ğ", "Ğ", "ü", "Ü", "ö", "Ö", "ı", "İ", "ş", "Ş", ".", ",", "!", "'", "\"", "/", " ", "?", "*", "_", "|", "=", "(", ")", "[", "]", "{", "}");
    $convert = array("c", "c", "g", "g", "u", "u", "o", "o", "i", "i", "s", "s", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-");

    return strtolower(str_replace($turkce, $convert, $text));

}


function get_readable_date($date)
{
    return strftime('%e %B %Y', strtotime($date));
}

function get_active_user(){

    $t = &get_instance();

    $user = $t->session->userdata("user");

    if($user)
        return $user;
    else
        return false;

}


function send_email($toEmail = "", $subject = "", $message = ""){
    
    $t = &get_instance();

    $t->load->model("emailsettings_model");

    $email_settings = $t->emailsettings_model->get(
        array(
            "isActive"  => 1
        )
    );

    $config = array(

        "protocol"   => $email_settings->protocol,
        "smtp_host"  => $email_settings->host,
        "smtp_port"  => $email_settings->port,
        "smtp_user"  => $email_settings->user,
        "smtp_pass"  => $email_settings->password,
        "starttls"   => true,
        "charset"    => "utf-8",
        "mailtype"   => "html",
        "wordwrap"   => true,
        "newline"    => "\r\n"
    );

    $t->load->library("email", $config);

    $t->email->from($email_settings->from, $email_settings->user_name);
    $t->email->to($toEmail);
    $t->email->subject($subject);
    $t->email->message($message);

    return $t->email->send();

}

function get_settings(){

    $t = &get_instance();

    $t->load->model("settings_model");

    if($t->session->userdata("settings")){
        $settings = $t->session->userdata("settings");
    } else {

        $settings = $t->settings_model->get();

        if(!$settings) {

            $settings = new stdClass();
            $settings->company_name = "KuvarSSoft";
            $settings->logo         = "default";
            
        }

        $t->session->set_userdata("settings", $settings);

    }

    return $settings;

}

function get_category_title($category_id=0){
    $t = &get_instance();
    $t->load->model("portfolio_category_model");
    $category=$t ->portfolio_category_model->get(
        array(
            "id" => $category_id
        )
    );
    if($category)
    return $category->title;
    else
    return "<b style='color:red'>Tanımlı Değil</b>";
}
function dd($var){
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
    die();
}

function createTriggerEvent($beforeRecord,$request){
    
    $beforeRecord = json_decode(json_encode($beforeRecord), true); 
    $requestProductCount = $request['count'];
    $requestProductPrice = $request['price'];
    $recordProductCount = $beforeRecord['count'];
    $recordProductTotalPrice = $beforeRecord['total'];
    $data = [];
    if($recordProductCount<1){
        array_push($data , $requestProductCount,$requestProductCount*$requestProductPrice);
    }else if ($requestProductPrice<1){
        array_push($data ,$recordProductCount + $requestProductCount ,$recordProductTotalPrice);
    }
    else{
            array_push($data ,$recordProductCount + $requestProductCount , ($requestProductPrice*$requestProductCount)+$recordProductTotalPrice);
    }
    return $data;
      
}
function updateTriggerEvent($record,$request,$recordProduct){
    $data = [];
    try{
    $newPrice = $record->total - ($recordProduct->count * $recordProduct->price);
    $newCount = $record->count - $recordProduct->count;
    $array = json_decode(json_encode($record), true);
    $request = json_decode(json_encode($request), true);
    $array['count'] = $newCount;
    $array['total'] = $newPrice;
        try{
            return createTriggerEvent($array,$request);
        }catch(Exception $e){
            return $e->getMessage();
        }
    }catch(Exception $e){
        return $e->getMessage();
    }
}
function deleteTriggerEvent($record,$request){
    $record =  json_decode(json_encode($record), true);
    $request =  json_decode(json_encode($request), true);
    if($record['total']<0) return 0;
    $newCount = $record['count']-$request['count'];
    $newTotal = $record['total']- ($request['price']*$request['count']);
    $record['count'] = $newCount;
    $record['total'] = $newTotal;
    return $record;
 
}