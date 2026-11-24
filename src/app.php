
<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

$pastaDados = __DIR__ . '/../storage';

if (!is_dir($pastaDados)) {
    mkdir($pastaDados, 0777, true);
}

$arquivoSituacoes = $pastaDados . '/situacoes.json';

function lerDados($arquivo, $padrao = [])
{
    if (!file_exists($arquivo)) {
        file_put_contents(
            $arquivo,
            json_encode($padrao, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $padrao;
    }

    $dados = json_decode(file_get_contents($arquivo), true);

    return is_array($dados) ? $dados : [];
}

function salvarDados($arquivo, $dados)
{
    file_put_contents(
        $arquivo,
        json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

function respostaJson($response, $dados, $status = 200)
{
    $response->getBody()->write(
        json_encode($dados, JSON_UNESCAPED_UNICODE)
    );

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

function proximoId($dados)
{
    if (empty($dados)) {
        return 1;
    }

    return max(array_column($dados, 'id')) + 1;
}

function validarSituacao($dados)
{
    if (
        empty(trim($dados['nome'] ?? '')) ||
        empty(trim($dados['unidadeCurricular'] ?? '')) ||
        !array_key_exists('aprovado', $dados)
    ) {
        return "Todos os campos são obrigatórios";
    }

    if (!is_bool($dados['aprovado'])) {
        return "O campo aprovado deve ser true ou false";
    }

    return null;
}

lerDados($arquivoSituacoes);

$app->post('/situacao', function ($request, $response) use ($arquivoSituacoes) {

    $situacoes = lerDados($arquivoSituacoes);
    $dados = $request->getParsedBody() ?? [];

    $erro = validarSituacao($dados);

    if ($erro) {
        return respostaJson($response, ["erro" => $erro], 422);
    }

    $novaSituacao = [
        "id" => proximoId($situacoes),
        "nome" => trim($dados['nome']),
        "unidadeCurricular" => trim($dados['unidadeCurricular']),
        "aprovado" => $dados['aprovado']
    ];

    $situacoes[] = $novaSituacao;

    salvarDados($arquivoSituacoes, $situacoes);

    return respostaJson($response, $novaSituacao, 201);
});

$app->get('/situacao/{id}', function ($request, $response, $args) use ($arquivoSituacoes) {

    $situacoes = lerDados($arquivoSituacoes);
    $id = (int) $args['id'];

    foreach ($situacoes as $situacao) {
        if ($situacao['id'] === $id) {
            return respostaJson($response, $situacao);
        }
    }

    return respostaJson($response, [
        "erro" => "Situação acadêmica não encontrada"
    ], 404);
});

$app->put('/situacao/{id}', function ($request, $response, $args) use ($arquivoSituacoes) {

    $situacoes = lerDados($arquivoSituacoes);
    $dados = $request->getParsedBody() ?? [];
    $id = (int) $args['id'];

    if (
        !array_key_exists('aprovado', $dados) ||
        count($dados) !== 1
    ) {
        return respostaJson($response, [
            "erro" => "Informe somente o campo aprovado"
        ], 422);
    }

    if (!is_bool($dados['aprovado'])) {
        return respostaJson($response, [
            "erro" => "O campo aprovado deve ser true ou false"
        ], 422);
    }

    foreach ($situacoes as $indice => $situacao) {

        if ($situacao['id'] === $id) {

            $situacoes[$indice]['aprovado'] = $dados['aprovado'];

            salvarDados($arquivoSituacoes, $situacoes);

            return respostaJson($response, $situacoes[$indice]);
        }
    }

    return respostaJson($response, [
        "erro" => "Situação acadêmica não encontrada"
    ], 404);
});

$app->delete('/situacao/{id}', function ($request, $response, $args) use ($arquivoSituacoes) {

    $situacoes = lerDados($arquivoSituacoes);
    $id = (int) $args['id'];

    foreach ($situacoes as $indice => $situacao) {

        if ($situacao['id'] === $id) {

            unset($situacoes[$indice]);
            $situacoes = array_values($situacoes);

            salvarDados($arquivoSituacoes, $situacoes);

            return respostaJson($response, [
                "mensagem" => "Registro removido com sucesso."
            ]);
        }
    }

    return respostaJson($response, [
        "erro" => "Situação acadêmica não encontrada"
    ], 404);
});

return $app;
