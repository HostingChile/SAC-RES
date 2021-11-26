<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ERROR);

if(!isset($_POST["action"])){
    $response['success'] = false;
    $response['msg'] = 'Action not sent';
    echo json_encode($response);
    exit;
}

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/ClientsManager.class.php';

$client = new ClientsManager($hosting_data);
$action = $_POST["action"];

if(!method_exists($client, $action)){
    $response['success'] = false;
    $response['msg'] = "Invalid action ('$action')";
    echo json_encode($response);
    exit;
}

try{
    $response = $client->$action($_POST['params']);
    echo json_encode($response);
    exit;
} catch(Exception $e){
    $response['success'] = false;
    $response['msg'] = $e->getMessage();
    echo json_encode($response);
    exit;
}

?>
