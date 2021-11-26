<?php
    session_start();
	$upOne = realpath(__DIR__ . '/..');
    $yubikey_username = $_SESSION['user_name'];
    if(!isset($_SESSION['user_name'])){
        http_response_code(401);
        $response = ['message'=> 'Not logged in'];
        echo json_encode($response);
        exit;
    }
    if(!isset($_GET['email']) || !isset($_GET['brand']) ){
        http_response_code(400);
        $response = ['message'=> 'Invalid query strings. Expected email and brand'];
        echo json_encode($response);
        exit;
    }
    $brand = $_GET['brand'];
    $email = $_GET['email'];

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        handleGetRequest($brand,$email);
    }
    else if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
        handleDeleteRequest($brand,$email);
    }
    else{
        http_response_code(405); //Method not allowed
        exit;
    }
    exit;


    function handleGetRequest($brand,$email){
        http_response_code(200);
        echo json_encode(['result'=> check_if_mail_is_in_spam_filter($brand,$email)]);
        exit;
    }

    function handleDeleteRequest($brand,$email){
        http_response_code(200);
        echo json_encode(['result' => remove_mail_from_spam_filter($brand,$email)]);
        exit;
    }


    function get_whmcs_connection_data($brand){
        switch($brand){
            case "Hosting":
                return ["host" => "hosting.cl", "user" => "hostingc_fabian","password" => "hosting.,", "database_name" => "hostingc_whmcsnew"];
            case "PlanetaHosting":
                return ["host" => "planetahosting.cl","user" => "igor_userph","password" => "whm*2008*.,","database_name" => "igor_whm2019"];
            case "Hostingcenter":
                return ["host" => "hostingcenter.cl","user" => "hcenter_admin","password" => "Planeta*1910Center","database_name" => "hcenter_whmcs"];
            case "iHost":
                return ["host" => "ihost.cl","user" => "ihostcl_adm","password" => "Adm18*.*H", "database_name" => "ihostcl_whmcs"];
            case "PlanetaPeru":
                return ["host" => "panel.planetahosting.pe","user" => "planetco_user","password" => "admin7440", "database_name" => "planetco_whmcs"];
            case "InkaHosting":
                return ["host" => "panel.inkahosting.com.pe","user" => "inkahost_user","password" => "inka_.,2013","database_name" => "inkahost_whmcs"];
            case "1Hosting":
                return ["host" => "panel.1hosting.com.pe","user" => "panel1ho_whmuser","password" => ")Z3~w#2aWL0g","database_name" => "panel1ho_whmcs"];
            case "NinjaHosting":
                return ["host" => "panel.ninjahosting.cl","user" => "panelnin_ting14","password" => "]^k5[OVvr!PZ","database_name" => "panelnin_whmcs"]; 
            case "PlanetaColombia":
                return ["host" => "panel.planetahosting.com.co","user" => "planecol_desarr","password" => "*desarrollo12*","database_name" => "planecol_whmc2"];
            case "HostingcenterColombia":
                return ["host" => "panel.hostingcenter.com.co","user" => "panelhco_desarr","password" => "*desarrollohcenter.,*","database_name" => "panelhco_whm"];
            case "NinjaPeru":
                return ["host" => "panel.ninjahosting.pe", "user" => "pninjape_adm", "password" => "qeK(YTu[1xa9", "database_name" => "pninjape_whmcs"];      
            case "Dehosting":
                return ["host" => "dehosting.net","user" => "dehostin_admin", "password" => "Hosting.,12***","database_name" => "dehostin_whmcs"];
            case "NinjaColombia":
                return ["host" => "panel.ninjahosting.com.co","user" => "ninjaco_desarrol", "password" => "*desarrolloninjaco.,*","database_name" => "ninjaco_whmcs81290"];
            case "HostingPro":
                return ["host" => "panel.hostingpro.com.co","user" => "panelhpr_alce", "password" => "_DA{cRav1y1,","database_name" => "panelhpr_alce"];
            default:
                return null;
        }
    }

    function get_connection($brand){
        $connection_data = get_whmcs_connection_data($brand);
        $mysqli_connection = new mysqli($connection_data['host'], $connection_data['user'], $connection_data['password'], $connection_data['database_name']);
        if($mysqli_connection->connect_errno){
            http_response_code(503);
            exit;
        }
        return $mysqli_connection;
    }

    function check_if_mail_is_in_spam_filter($brand, $email){
        $mysqli_connection = get_connection($brand);
        $query = "SELECT content FROM tblticketspamfilters WHERE type = 'sender' AND content = ?";
        $prepared_statement = $mysqli_connection->prepare($query);
        if(!$prepared_statement){
            http_response_code(503);
            exit;
        }
        $binded_statement = $prepared_statement->bind_param('s', $email);
        if(!$binded_statement){
            http_response_code(503);
            exit;
        }
        //Ejecuta la consulta
        if(!$prepared_statement->execute()){
            http_response_code(503);
            exit;
        }
        $prepared_statement->store_result();
        return $prepared_statement->num_rows >0;
    }

    function remove_mail_from_spam_filter($brand, $email){
        $mysqli_connection = get_connection($brand);
        $query = "DELETE FROM tblticketspamfilters WHERE type = 'sender' AND content = ?";
        $prepared_statement = $mysqli_connection->prepare($query);
        if(!$prepared_statement){
            http_response_code(503);
            exit;
        }
        $binded_statement = $prepared_statement->bind_param('s', $email);
        if(!$binded_statement){
            http_response_code(503);
            exit;
        }
        //Ejecuta la consulta
        if(!$prepared_statement->execute()){
            http_response_code(503);
            exit;
        }
        $prepared_statement->store_result();
        return $prepared_statement->affected_rows >0;
    }
    ?>
?>

