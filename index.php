<?php
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

require 'vendor\autoload.php';
require 'controllers\Api.php';

date_default_timezone_set('America/Sao_paulo');

$app = new \Silex\Application(array(
    'debug' => true
)); 

$request = json_decode(file_get_contents("php://input"));

$app->error(function (\Exception $e) use ($app) {
    if (!$app['debug']) {
        return $app->json(['status' => false, 'error_info' => $e->errorInfo]);
    }
});

// =====================================================
// *********************** LOCAL ***********************
// =====================================================
$app->post('/getLocais', function() use ($app, $request){
	return (new \controllers\Api($app))->getLocais($request);
});

$app->post('/setLocal', function() use ($app, $request){
	return (new \controllers\Api($app))->setLocal($request);
});

// =====================================================
// ********************* Plantações ********************
// =====================================================
$app->post('/getPlantacoes', function() use ($app, $request){
	return (new \controllers\Api($app))->getPlantacoes($request);
});

$app->post('/setPlantacao', function() use ($app, $request){
	return (new \controllers\Api($app))->setPlantacao($request);
});

// =====================================================
// *********************** Grupos **********************
// =====================================================
$app->post('/getGrupos', function() use ($app, $request){
    return (new \controllers\Api($app))->getGrupos($request);
});

$app->post('/setGrupo', function() use ($app, $request){
    return (new \controllers\Api($app))->setGrupo($request);
});

// =====================================================
// ********************* Usuarios **********************
// =====================================================
$app->post('/getUsuarios', function() use ($app, $request){
    return (new \controllers\Api($app))->getUsuarios($request);
});

$app->post('/setUsuario', function() use ($app, $request){
    return (new \controllers\Api($app))->setUsuario($request);
});

$app->post('/login', function() use ($app, $request){
    return (new \controllers\Api($app))->auth($request);
});

$app->post('/uploadImage', function() use ($app, $request){
    return (new \controllers\Api($app))->uploadImage($request);
});

// =====================================================
// *********************** Cameras *********************
// =====================================================
$app->post('/getCameras', function() use ($app, $request){
    return (new \controllers\Api($app))->getCameras($request);
});

$app->post('/setCameras', function() use ($app, $request){
    return (new \controllers\Api($app))->setCameras($request);
});

// =====================================================
// ********************** Clientes *********************
// =====================================================
$app->post('/setClient', function() use ($app, $request){
    return (new \controllers\Api($app))->setClient($request);
});

// =====================================================
// ************* Muda o Status da Plantação ************
// =====================================================
$app->post('/changeStatusPlantation', function() use ($app, $request){
    return (new \controllers\Api($app))->changeStatusPlantation($request);
});

// =====================================================
// ****************** Microcontrollers *****************
// =====================================================
$app->post('/setMicrocontrollers', function() use ($app, $request){
    return (new \controllers\Api($app))->setMicrocontrollers($request);
});

$app->post('/getMicrocontrollers', function() use ($app, $request){
    return (new \controllers\Api($app))->getMicrocontrollers($request);
});

// =====================================================
// *********************** Tipos ***********************
// =====================================================
$app->get('/getTipos', function() use ($app){
    return (new \controllers\Api($app))->getTipos();
});

// =====================================================
// ********************** Modulos **********************
// =====================================================
$app->get('/getModules', function() use ($app){
    return (new \controllers\Api($app))->getModules();
});


$app->run();
?>
