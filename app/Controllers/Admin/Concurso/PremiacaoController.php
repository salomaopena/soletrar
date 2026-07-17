<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concurso;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;

/**
 * ATRIBUIÇÃO DE PRÉMIOS (tabela `premios_atribuidos`).
 *
 * QUANDO acontece: depois de o evento estar CONCLUÍDO (a classificação
 * já foi calculada em ClassificacaoService::calcular, chamado por
 * EventoService::concluir). A homologação NÃO é pré-requisito — pode
 * atribuir-se prémios de uma fase escolar/provincial assim que a
 * classificação existir; normalmente só a Final Nacional tem prémios
 * configurados, mas o sistema não impõe essa limitação.
 *
 * COMO um prémio "encontra" o vencedor: um registo em `premios` liga-se
 * a uma `posicao` (1=ouro, 2=prata, 3=bronze…) e, opcionalmente, a uma
 * categoria e/ou fase específicas. Ao atribuir, o sistema cruza:
 *   premios.edicao_id  = edição do evento (via fase)
 *   premios.categoria_id  = categoria do evento (ou NULL = qualquer)
 *   premios.fase_id       = fase do evento (ou NULL = qualquer fase)
 *   premios.posicao       = participacoes.posicao_final NESTE evento
 */
class PremiacaoController extends AdminBaseController
{
    public function ver(int $eventoId)
    {
        $evento = $this->obterEventoComEdicao($eventoId);

        return view('admin/concurso/premiacao', [
            'evento'      => $evento,
            'candidatos'  => $this->candidatosCorrespondentes($evento),
            'naoAtribuidos_aviso' => null,
        ]);
    }

    /** Atribui os prémios configurados que ainda não foram atribuídos. */
    public function atribuir(int $eventoId)
    {
        $evento = $this->obterEventoComEdicao($eventoId);

        $temPosicoes = db_connect()->table('participacoes')
            ->where('evento_id', $eventoId)
            ->where('posicao_final IS NOT NULL')
            ->countAllResults() > 0;

        if (! $temPosicoes) {
            return redirect()->back()->with('erro', lang('Concurso.eventoSemPosicoes'));
        }

        $candidatos = $this->candidatosCorrespondentes($evento);
        $novos = 0;

        foreach ($candidatos as $c) {
            if ($c->ja_atribuido || $c->participacao_id === null) {
                continue;
            }

            db_connect()->table('premios_atribuidos')->insert([
                'premio_id'       => $c->premio_id,
                'participacao_id' => $c->participacao_id,
                'created_at'      => utc_agora(),
            ]);
            $novos++;
        }

        service('auditoria')->registar('atribuir_premios', 'eventos_competicao', $eventoId,
            descricao: "{$novos} prémio(s) atribuído(s)");

        return redirect()->back()->with('sucesso',
            $novos > 0 ? "{$novos} prémio(s) atribuído(s)." : 'Nenhum prémio novo para atribuir.');
    }

    /** Lista de premiados pronta a imprimir (para a cerimónia). */
    public function imprimir(int $eventoId)
    {
        $evento = $this->obterEventoComEdicao($eventoId);

        return view('impressao/lista_premiados', [
            'evento'     => $evento,
            'candidatos' => array_filter($this->candidatosCorrespondentes($evento),
                static fn ($c) => $c->participacao_id !== null),
            'titulo'     => 'Lista de premiados',
        ]);
    }

    // ------------------------------ Internos ------------------------------

    private function obterEventoComEdicao(int $eventoId): object
    {
        return db_connect()->table('eventos_competicao ev')
            ->select('ev.*, f.edicao_id, f.nome AS fase_nome, cat.nome AS categoria_nome')
            ->join('fases_concurso f', 'f.id = ev.fase_id')
            ->join('categorias_competicao cat', 'cat.id = ev.categoria_id', 'left')
            ->where('ev.id', $eventoId)
            ->get()->getRow() ?? throw PageNotFoundException::forPageNotFound();
    }

    /**
     * Cruza os prémios elegíveis para este evento com quem ficou em cada
     * posição. Devolve uma linha por prémio (mesmo que ninguém a preencha
     * ainda, para o coordenador ver o que falta).
     */
    private function candidatosCorrespondentes(object $evento): array
    {
        return db_connect()->query(
            'SELECT pr.id AS premio_id, pr.nome AS premio_nome, pr.posicao, pr.tipo,
                    pr.valor_monetario, pr.moeda,
                    pat.nome AS patrocinador,
                    pa.id AS participacao_id, pa.numero_concorrente,
                    c.nome_completo, c.numero_inscricao,
                    e.nome AS escola,
                    (pat_atr.id IS NOT NULL) AS ja_atribuido
               FROM premios pr
          LEFT JOIN patrocinadores pat ON pat.id = pr.patrocinador_id
          LEFT JOIN participacoes pa
                 ON pa.evento_id = ? AND pa.posicao_final = pr.posicao
          LEFT JOIN inscricoes i  ON i.id = pa.inscricao_id
          LEFT JOIN candidatos c  ON c.id = i.candidato_id
          LEFT JOIN escolas e     ON e.id = i.escola_id
          LEFT JOIN premios_atribuidos pat_atr
                 ON pat_atr.premio_id = pr.id AND pat_atr.participacao_id = pa.id
              WHERE pr.edicao_id = ?
                AND (pr.categoria_id IS NULL OR pr.categoria_id = ?)
                AND (pr.fase_id IS NULL OR pr.fase_id = ?)
              ORDER BY pr.posicao',
            [$evento->id, $evento->edicao_id, $evento->categoria_id, $evento->fase_id]
        )->getResult();
    }
}
