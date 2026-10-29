<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

if (getenv('APP_ENV') !== 'testing' || ! str_starts_with((string) getenv('DB_DATABASE'), 'agendturnos_test')) {
    throw new RuntimeException('Este proceso solo puede ejecutarse en la base aislada de tests.');
}

$app = require __DIR__.'/../../bootstrap/app.php';
$request = Request::create('/panel/disponibilidad/bloqueos', 'POST', json_decode(getenv('AGENDA_TEST_PAYLOAD'), true));
$app->instance('request', $request);
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
DB::select("select set_config('application_name', ?, false)", [getenv('AGENDA_TEST_CONNECTION')]);
Auth::onceUsingId((int) getenv('AGENDA_TEST_USER'));
$request->headers->set('referer', 'http://localhost/panel/disponibilidad');
$response = $kernel->handle($request);
$request->session()->start();
$errors = $request->session()->get('errors');
echo json_encode(['status' => $response->getStatusCode(), 'conflicto' => $errors?->getBag('default')->has('confirmar') ?? false]).PHP_EOL;
$kernel->terminate($request, $response);
