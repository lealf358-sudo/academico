
<?php

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class AcademicoTest extends TestCase
{
    private static $app;
    private static $dadosOriginais;

    public static function setUpBeforeClass(): void
    {
        self::$app = require __DIR__ . '/../src/app.php';

        $caminho = __DIR__ . '/../storage/situacoes.json';

        self::$dadosOriginais = file_get_contents($caminho);
    }

    protected function setUp(): void
    {
        $caminho = __DIR__ . '/../storage/situacoes.json';

        file_put_contents($caminho, self::$dadosOriginais);
    }

    public static function tearDownAfterClass(): void
    {
        $caminho = __DIR__ . '/../storage/situacoes.json';

        file_put_contents($caminho, self::$dadosOriginais);
    }

    private function requisicao($metodo, $rota, $dados = null)
    {
        $factory = new ServerRequestFactory();

        $request = $factory->createServerRequest($metodo, $rota);

        if ($dados !== null) {
            $request->getBody()->write(json_encode($dados));
            $request->getBody()->rewind();

            $request = $request->withHeader(
                'Content-Type',
                'application/json'
            );
        }

        return self::$app->handle($request);
    }

    private function respostaJson($response)
    {
        $response->getBody()->rewind();

        return json_decode(
            $response->getBody()->getContents(),
            true
        );
    }

    private function criarSituacao()
    {
        $dados = [
            'nome' => 'Aluno Teste',
            'unidadeCurricular' => 'Programação',
            'aprovado' => false
        ];

        $response = $this->requisicao(
            'POST',
            '/situacao',
            $dados
        );

        $this->assertSame(201, $response->getStatusCode());

        $resultado = $this->respostaJson($response);

        $this->assertArrayHasKey('id', $resultado);

        return $resultado['id'];
    }

    public function testCadastrarSituacao()
    {
        $dados = [
            'nome' => 'Aluno Teste',
            'unidadeCurricular' => 'Programação',
            'aprovado' => false
        ];

        $response = $this->requisicao(
            'POST',
            '/situacao',
            $dados
        );

        $this->assertSame(201, $response->getStatusCode());

        $resultado = $this->respostaJson($response);

        $this->assertNotNull($resultado);
        $this->assertArrayHasKey('id', $resultado);
    }

    public function testCadastrarSituacaoInvalida()
    {
        $dados = [
            'nome' => '',
            'unidadeCurricular' => '',
            'aprovado' => false
        ];

        $response = $this->requisicao(
            'POST',
            '/situacao',
            $dados
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testBuscarSituacao()
    {
        $id = $this->criarSituacao();

        $response = $this->requisicao(
            'GET',
            '/situacao/' . $id
        );

        $this->assertSame(200, $response->getStatusCode());

        $resultado = $this->respostaJson($response);

        $this->assertNotNull($resultado);
        $this->assertSame($id, $resultado['id']);
    }

    public function testBuscarSituacaoInexistente()
    {
        $response = $this->requisicao(
            'GET',
            '/situacao/999999'
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAtualizarSituacao()
    {
        $id = $this->criarSituacao();

        $dados = [
            'aprovado' => true
        ];

        $response = $this->requisicao(
            'PUT',
            '/situacao/' . $id,
            $dados
        );

        $this->assertSame(200, $response->getStatusCode());

        $resultado = $this->respostaJson($response);

        $this->assertNotNull($resultado);
    }

    public function testAtualizarSituacaoInexistente()
    {
        $dados = [
            'aprovado' => true
        ];

        $response = $this->requisicao(
            'PUT',
            '/situacao/999999',
            $dados
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testAtualizarSituacaoComDadosInvalidos()
    {
        $id = $this->criarSituacao();

        $dados = [
            'aprovado' => 'sim'
        ];

        $response = $this->requisicao(
            'PUT',
            '/situacao/' . $id,
            $dados
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testExcluirSituacao()
    {
        $id = $this->criarSituacao();

        $response = $this->requisicao(
            'DELETE',
            '/situacao/' . $id
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testExcluirSituacaoInexistente()
    {
        $response = $this->requisicao(
            'DELETE',
            '/situacao/999999'
        );

        $this->assertSame(404, $response->getStatusCode());
    }
}
