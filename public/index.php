<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Router;
use App\Controllers\TransportadoraController;
use App\Controllers\EntregaController;
use App\Controllers\MotivoController;

header('Content-Type: application/json; charset=utf-8');

function json(mixed $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function body(): array
{
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

$router = new Router();

// Transportadoras
$router->get('/transportadoras',                  [TransportadoraController::class, 'index']);
$router->post('/transportadoras',                 [TransportadoraController::class, 'store']);
$router->get('/transportadoras/{id}',             [TransportadoraController::class, 'show']);
$router->patch('/transportadoras/{id}/desativar', [TransportadoraController::class, 'desativar']);
$router->patch('/transportadoras/{id}/reativar',  [TransportadoraController::class, 'reativar']);

// Entregas
$router->get('/entregas',               [EntregaController::class, 'index']);
$router->post('/entregas',              [EntregaController::class, 'store']);
$router->get('/entregas/{id}',          [EntregaController::class, 'show']);
$router->patch('/entregas/{id}/status', [EntregaController::class, 'updateStatus']);
//Motivos_Nao_Conformidade
$router->get('/motivos-nao-conformidade', [MotivoController::class, 'index']);


$router->dispatch();
