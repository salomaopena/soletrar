<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concurso;

use App\Controllers\Admin\AdminBaseController;
use RuntimeException;

/**
 * Painel de CONDUÇÃO AO VIVO de um evento — a mesa do júri.
 *
 * É deliberadamente um controller "burro": cada ação delega ao service
 * e devolve JSON, porque a interface do palco (Fase 8) atualiza por AJAX
 * sem recarregar a página durante o evento.
 *
 * Rotas (filtro permission:concurso.juri.avaliar):
 *   GET  admin/palco/(:num)                       → painel/$1
 *   POST admin/palco/round/abrir/(:num)           → abrirRound/$1
 *   POST admin/palco/vez/(:num)/(:num)            → iniciarVez/$1/$2  (round, participacao)
 *   POST admin/palco/tentativa/(:num)/pedido      → registarPedido/$1
 *   POST admin/palco/tentativa/(:num)/avaliar     → avaliar/$1
 *   POST admin/palco/tentativa/(:num)/apelacao    → apelacao/$1
 *   POST admin/palco/round/concluir/(:num)        → concluirRound/$1
 *   POST admin/palco/evento/concluir/(:num)       → concluirEvento/$1
 */
class PalcoController extends AdminBaseController
{
    /** Painel principal: sobreviventes, pool restante, round atual. */
    public function painel(int $eventoId)
    {
        $roundAtual = service('rounds')->emCurso($eventoId);

        return view('admin/concurso/palco', [
            'eventoId'      => $eventoId,
            'sobreviventes' => service('rounds')->sobreviventes($eventoId),
            'poolRestante'  => service('palavras')->restantesNoPool($eventoId),
            // Estado real do round, lido da BD — não de uma variável JS que
            // se perde ao recarregar a página (Fase 11: correção do palco).
            'roundAtual'    => $roundAtual,
            // Quem já soletrou NESTE round, para não o voltar a chamar.
            'jaTentaramNoRound' => $roundAtual
                ? db_connect()->table('tentativas_soletracao')
                    ->select('participacao_id')
                    ->where('round_id', $roundAtual->id)
                    ->get()->getResultArray()
                : [],
        ]);
    }

    public function abrirRound(int $eventoId)
    {
        return $this->executar(fn () => [
            'round_id' => service('rounds')->abrir($eventoId, (array) $this->request->getPost()),
        ]);
    }

    /** Sorteia a palavra para o candidato da vez e devolve o cartão completo. */
    public function iniciarVez(int $roundId, int $participacaoId)
    {
        return $this->executar(function () use ($roundId, $participacaoId) {
            $resultado = service('tentativas')->iniciarVez($roundId, $participacaoId, auth()->id());

            // O pronunciador vê tudo; o ecrã público (projeção) NUNCA
            // recebe a palavra antes da avaliação — a view do palco separa
            // os dois modos.
            return [
                'tentativa_id' => $resultado['tentativa_id'],
                'palavra'      => [
                    'texto'      => $resultado['palavra']->palavra,
                    'silabacao'  => $resultado['palavra']->silabacao,
                    'definicao'  => $resultado['palavra']->definicao,
                    'exemplo'    => $resultado['palavra']->exemplo_uso,
                    'etimologia' => $resultado['palavra']->etimologia,
                    'notas'      => $resultado['palavra']->notas_pronunciador,
                    'audio_url'  => $resultado['palavra']->audio_url,
                ],
            ];
        });
    }

    public function registarPedido(int $tentativaId)
    {
        return $this->executar(function () use ($tentativaId) {
            service('tentativas')->registarPedido($tentativaId, (string) $this->request->getPost('pedido'));

            return ['ok' => true];
        });
    }

    public function avaliar(int $tentativaId)
    {
        return $this->executar(function () use ($tentativaId) {
            service('tentativas')->avaliar(
                $tentativaId,
                (string) $this->request->getPost('resposta_dada'),
                $this->request->getPost('correta') === '1',
                auth()->id(),
            );

            return ['ok' => true];
        });
    }

    public function apelacao(int $tentativaId)
    {
        return $this->executar(function () use ($tentativaId) {
            if ($decisao = $this->request->getPost('decisao')) {
                service('tentativas')->decidirApelacao($tentativaId, $decisao === 'aceite', auth()->id());
            } else {
                service('tentativas')->solicitarApelacao($tentativaId, (string) $this->request->getPost('motivo'));
            }

            return ['ok' => true];
        });
    }

    public function concluirRound(int $roundId)
    {
        return $this->executar(fn () => [
            'sobreviventes' => service('rounds')->concluir($roundId),
        ]);
    }

    public function concluirEvento(int $eventoId)
    {
        return $this->executar(function () use ($eventoId) {
            service('eventos')->concluir($eventoId);

            return ['classificacao' => service('relatorios')->classificacaoEvento($eventoId)];
        });
    }

    /** Padrão de resposta JSON do palco: sucesso com dados, ou erro 422 traduzido. */
    private function executar(callable $acao)
    {
        try {
            return $this->response->setJSON(['sucesso' => true] + $acao());
        } catch (RuntimeException $e) {
            return $this->response->setStatusCode(422)
                ->setJSON(['sucesso' => false, 'erro' => $e->getMessage()]);
        }
    }
}
