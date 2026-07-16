<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Concurso;

use App\Controllers\Admin\AdminBaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;

/**
 * Gestão de eventos: criação, JÚRI, PARTICIPANTES, POOL DE PALAVRAS,
 * início e conclusão. A condução ao vivo está no PalcoController.
 *
 * Pré-condições para iniciar (EventoService::iniciar):
 *   presidente + pronunciador no júri · pool com palavras · ≥2 presentes
 */
class EventosController extends AdminBaseController
{
    private const ROTA = 'admin/eventos';

    // ------------------------- Listagem / CRUD -------------------------

    public function index()
    {
        $model = model('EventoModel')
            ->select('eventos_competicao.*, f.nome AS fase, c.nome AS categoria')
            ->join('fases_concurso f', 'f.id = eventos_competicao.fase_id')
            ->join('categorias_competicao c', 'c.id = eventos_competicao.categoria_id', 'left');

        if (! $this->escopo->eNacional() && $this->escopo->provincias !== []) {
            $model->whereIn('eventos_competicao.provincia_id', $this->escopo->provincias);
        }

        return view('admin/concurso/eventos_index', [
            'eventos' => $model->orderBy('eventos_competicao.data_evento', 'DESC')->paginate(25),
            'pager'   => $model->pager,
        ]);
    }

    public function nova()
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Novo evento',
            'rotaBase' => self::ROTA,
            'registo'  => null,
            'campos'   => $this->campos(),
        ]);
    }

    public function editar(int $id)
    {
        return view('admin/crud/formulario', [
            'titulo'   => 'Editar evento',
            'rotaBase' => self::ROTA,
            'registo'  => model('EventoModel')->find($id) ?? throw PageNotFoundException::forPageNotFound(),
            'campos'   => $this->campos(),
        ]);
    }

    public function guardar()
    {
        if (! $this->validate($this->regras())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model('EventoModel')->insert($this->dados());

        return redirect()->to(self::ROTA)->with('sucesso', 'Evento criado.');
    }

    public function atualizar(int $id)
    {
        if (! $this->validate($this->regras())) {
            return redirect()->back()->withInput()->with('erros', $this->validator->getErrors());
        }

        model('EventoModel')->update($id, $this->dados());

        return redirect()->to(self::ROTA)->with('sucesso', 'Evento atualizado.');
    }

    // --------------------------- Sala de controlo ---------------------------

    /** Painel do evento: júri, participantes, pool e ações. */
    public function ver(int $id)
    {
        $evento = model('EventoModel')
            ->select('eventos_competicao.*, f.nome AS fase, f.tipo_fase, c.nome AS categoria')
            ->join('fases_concurso f', 'f.id = eventos_competicao.fase_id')
            ->join('categorias_competicao c', 'c.id = eventos_competicao.categoria_id', 'left')
            ->find($id) ?? throw PageNotFoundException::forPageNotFound();

        return view('admin/concurso/evento_ver', [
            'evento'        => $evento,
            'juri'          => $this->juriDo($id),
            'participantes' => $this->participantesDo($id),
            'poolRestante'  => service('palavras')->restantesNoPool($id),
            'poolTotal'     => db_connect()->table('pool_palavras_evento')
                                  ->where('evento_id', $id)->countAllResults(),
            // Utilizadores elegíveis para júri (jurados e pronunciadores)
            'candidatosJuri' => db_connect()->table('users u')
                ->select('u.id, u.username')
                ->join('auth_groups_users g', 'g.user_id = u.id')
                ->whereIn('g.group', ['jurado', 'pronunciador', 'coord_nacional', 'coord_provincial'])
                ->groupBy('u.id, u.username')->orderBy('u.username')
                ->get()->getResult(),
        ]);
    }

    /** Atribuir um membro ao júri. */
    public function atribuirJuri(int $id)
    {
        try {
            service('eventos')->atribuirJuri(
                $id,
                (int) $this->request->getPost('user_id'),
                (string) $this->request->getPost('papel'),
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', 'Membro do júri atribuído.');
    }

    public function removerJuri(int $id, int $juriId)
    {
        model('JuriEventoModel')->delete($juriId);

        return redirect()->back()->with('sucesso', 'Membro removido do júri.');
    }

    /** Cria as participações a partir das inscrições elegíveis (EventoService). */
    public function confirmarParticipantes(int $id)
    {
        try {
            $n = service('eventos')->confirmarParticipantes($id);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', "{$n} participante(s) confirmado(s).");
    }

    /** Check-in no dia do evento. */
    public function presenca(int $id, int $participacaoId)
    {
        try {
            service('eventos')->registarPresenca(
                $participacaoId,
                (string) $this->request->getPost('presenca'),
            );
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', 'Presença registada.');
    }

    /** MONTAR O POOL DE PALAVRAS do evento (quantidades por dificuldade). */
    public function montarPool(int $id)
    {
        $quantidades = array_filter([
            'muito_facil'   => (int) $this->request->getPost('muito_facil'),
            'facil'         => (int) $this->request->getPost('facil'),
            'media'         => (int) $this->request->getPost('media'),
            'dificil'       => (int) $this->request->getPost('dificil'),
            'muito_dificil' => (int) $this->request->getPost('muito_dificil'),
        ]);

        if ($quantidades === []) {
            return redirect()->back()->with('erro', 'Indique quantas palavras deseja por dificuldade.');
        }

        try {
            $total = service('palavras')->montarPool($id, $quantidades);
        } catch (RuntimeException $e) {
            // Ex.: "Palavras insuficientes de dificuldade X: pedidas 40, disponíveis 12."
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->back()->with('sucesso', "{$total} palavra(s) adicionada(s) ao conjunto do evento.");
    }

    /** Iniciar o evento (valida júri, pool e presenças). */
    public function iniciar(int $id)
    {
        try {
            service('eventos')->iniciar($id);
        } catch (RuntimeException $e) {
            return redirect()->back()->with('erro', $e->getMessage());
        }

        return redirect()->to('admin/palco/' . $id)->with('sucesso', 'Evento iniciado. Bom concurso!');
    }

    /** Conteúdo do POOL: que palavras estão no conjunto e quais já saíram. */
    public function pool(int $id)
    {
        $evento = model('EventoModel')->find($id) ?? throw PageNotFoundException::forPageNotFound();

        $termo = trim((string) $this->request->getGet('q'));

        return view('admin/concurso/evento_pool', [
            'evento'    => $evento,
            'termo'     => $termo,
            'elegiveis' => service('palavras')->elegiveisParaEvento($id, $termo),
            'palavras'=> db_connect()->table('pool_palavras_evento ppe')
                ->select('ppe.id, ppe.usada, p.id AS palavra_id, p.palavra,
                          p.dificuldade, p.silabacao')
                ->join('palavras p', 'p.id = ppe.palavra_id')
                ->where('ppe.evento_id', $id)
                ->orderBy('ppe.usada')->orderBy('p.dificuldade')->orderBy('p.palavra')
                ->get()->getResult(),
        ]);
    }

    /** Adiciona palavras ESCOLHIDAS À MÃO ao conjunto. */
    public function adicionarAoPool(int $id)
    {
        $ids = (array) $this->request->getPost('palavras');

        if ($ids === []) {
            return redirect()->back()->with('erro', 'Selecione pelo menos uma palavra.');
        }

        $n = service('palavras')->adicionarAoPool($id, $ids);

        return redirect()->back()->with('sucesso', "{$n} palavra(s) adicionada(s) ao conjunto.");
    }

    /** Esvazia o conjunto (apenas as palavras AINDA NÃO USADAS). */
    public function limparPool(int $id)
    {
        db_connect()->table('pool_palavras_evento')
            ->where('evento_id', $id)
            ->where('usada', 0)
            ->delete();

        return redirect()->back()->with('sucesso', 'Conjunto esvaziado (as usadas mantêm-se).');
    }

    /** Remove uma palavra AINDA NÃO USADA do conjunto. */
    public function removerDoPool(int $id, int $poolId)
    {
        $linha = db_connect()->table('pool_palavras_evento')
            ->where(['id' => $poolId, 'evento_id' => $id])->get()->getRow();

        if ($linha === null) {
            return redirect()->back()->with('erro', 'Palavra não encontrada no conjunto.');
        }

        if ((int) $linha->usada === 1) {
            // Historial é intocável (RN-07): palavra usada não sai do conjunto.
            return redirect()->back()->with('erro', 'Não é possível remover uma palavra já usada.');
        }

        db_connect()->table('pool_palavras_evento')->where('id', $poolId)->delete();

        return redirect()->back()->with('sucesso', 'Palavra removida do conjunto.');
    }

    /** ROUNDS do evento (rounds_evento): configuração e estado de cada um. */
    public function rounds(int $id)
    {
        $evento = model('EventoModel')->find($id) ?? throw PageNotFoundException::forPageNotFound();

        $rounds = db_connect()->table('rounds_evento r')
            ->select('r.*, (SELECT COUNT(*) FROM tentativas_soletracao t
                             WHERE t.round_id = r.id) AS tentativas,
                      (SELECT COUNT(*) FROM tentativas_soletracao t
                        WHERE t.round_id = r.id AND t.correta = 1) AS acertos')
            ->where('r.evento_id', $id)
            ->orderBy('r.numero_round')
            ->get()->getResult();

        return view('admin/concurso/evento_rounds', [
            'evento' => $evento,
            'rounds' => $rounds,
        ]);
    }

    /** HISTÓRICO DE TENTATIVAS do evento (round a round). */
    public function tentativas(int $id)
    {
        $evento = model('EventoModel')->find($id) ?? throw PageNotFoundException::forPageNotFound();

        return view('admin/concurso/evento_tentativas', [
            'evento'     => $evento,
            'tentativas' => db_connect()->table('tentativas_soletracao t')
                ->select('t.id, t.correta, t.resposta_dada, t.tempo_resposta_seg,
                          t.pontos_atribuidos, t.apelacao_solicitada, t.apelacao_resultado,
                          t.pediu_repeticao, t.pediu_definicao, t.pediu_etimologia, t.pediu_exemplo,
                          r.numero_round, r.dificuldade,
                          p.palavra, c.nome_completo, pa.numero_concorrente')
                ->join('rounds_evento r', 'r.id = t.round_id')
                ->join('participacoes pa', 'pa.id = t.participacao_id')
                ->join('inscricoes i', 'i.id = pa.inscricao_id')
                ->join('candidatos c', 'c.id = i.candidato_id')
                ->join('palavras p', 'p.id = t.palavra_id')
                ->where('r.evento_id', $id)
                ->orderBy('r.numero_round')->orderBy('t.ordem_no_round')
                ->get()->getResult(),
        ]);
    }

    /** Pauta do evento pronta a imprimir (lista de concorrentes). */
    public function lista(int $id)
    {
        $evento = model('EventoModel')->find($id) ?? throw PageNotFoundException::forPageNotFound();

        return view('impressao/lista_participantes', [
            'evento'        => $evento,
            'participantes' => $this->participantesDo($id),
            'titulo'        => 'Pauta de concorrentes',
        ]);
    }

    // ------------------------------ Internos ------------------------------

    private function juriDo(int $eventoId): array
    {
        return db_connect()->table('juri_evento j')
            ->select('j.id, j.papel, u.username')
            ->join('users u', 'u.id = j.user_id')
            ->where('j.evento_id', $eventoId)
            ->orderBy('j.papel')
            ->get()->getResult();
    }

    private function participantesDo(int $eventoId): array
    {
        return db_connect()->table('participacoes p')
            ->select('p.id, p.numero_concorrente, p.presenca, p.pontuacao_total,
                      p.eliminado_round, p.posicao_final,
                      c.nome_completo, c.classe_atual, e.nome AS escola')
            ->join('inscricoes i', 'i.id = p.inscricao_id')
            ->join('candidatos c', 'c.id = i.candidato_id')
            ->join('escolas e', 'e.id = i.escola_id')
            ->where('p.evento_id', $eventoId)
            ->orderBy('p.numero_concorrente')
            ->get()->getResult();
    }

    private function campos(): array
    {
        $ops = static function (array $rs, string $campo = 'nome'): array {
            $o = [];
            foreach ($rs as $r) { $o[$r->id] = $r->{$campo}; }
            return $o;
        };

        return [
            ['nome' => 'nome', 'rotulo' => 'Nome do evento', 'obrigatorio' => true, 'largura' => 12],
            ['nome' => 'fase_id', 'rotulo' => 'Fase', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 6,
             'opcoes' => $ops(model('FaseModel')->orderBy('ordem')->findAll())],
            ['nome' => 'categoria_id', 'rotulo' => 'Categoria', 'tipo' => 'select', 'obrigatorio' => true, 'largura' => 6,
             'opcoes' => $ops(model('CategoriaModel')->orderBy('ordem')->findAll())],
            ['nome' => 'data_evento', 'rotulo' => 'Data do evento', 'tipo' => 'date', 'obrigatorio' => true, 'largura' => 4],
            ['nome' => 'status', 'rotulo' => 'Estado', 'tipo' => 'select', 'largura' => 4,
             'opcoes' => ['agendado' => 'Agendado', 'em_curso' => 'Em curso', 'pausado' => 'Pausado',
                          'concluido' => 'Concluído', 'adiado' => 'Adiado', 'cancelado' => 'Cancelado']],
            ['nome' => 'local_id', 'rotulo' => 'Local', 'tipo' => 'select', 'largura' => 4,
             'opcoes' => $ops(model('LocalEventoModel')->orderBy('nome')->findAll())],
            ['nome' => 'provincia_id', 'rotulo' => 'Província', 'tipo' => 'select', 'largura' => 4,
             'opcoes' => $ops(model('ProvinciaModel')->orderBy('nome')->findAll())],
            ['nome' => 'municipio_id', 'rotulo' => 'Município', 'tipo' => 'select', 'largura' => 4,
             'opcoes' => $ops(model('MunicipioModel')->orderBy('nome')->findAll())],
            ['nome' => 'escola_id', 'rotulo' => 'Escola (fase escolar)', 'tipo' => 'select', 'largura' => 4,
             'opcoes' => $ops(model('EscolaModel')->where('ativo', 1)->orderBy('nome')->findAll())],
            ['nome' => 'data_fim_prevista', 'rotulo' => 'Fim previsto', 'tipo' => 'date', 'largura' => 4],
            ['nome' => 'transmissao_url', 'rotulo' => 'Transmissão (URL)', 'tipo' => 'text', 'largura' => 8,
             'ajuda' => 'Link do direto (YouTube, Facebook…).'],
            ['nome' => 'observacoes', 'rotulo' => 'Observações', 'tipo' => 'textarea', 'largura' => 12],
        ];
    }

    private function regras(): array
    {
        return [
            'nome'         => 'required|min_length[3]',
            'fase_id'      => 'required|is_natural_no_zero',
            'categoria_id' => 'required|is_natural_no_zero',
            'data_evento'  => 'required|valid_date',
        ];
    }

    private function dados(): array
    {
        $d = $this->request->getPost([
            'nome', 'fase_id', 'categoria_id', 'local_id', 'escola_id',
            'municipio_id', 'provincia_id', 'data_evento', 'data_fim_prevista',
            'status', 'transmissao_url', 'observacoes',
        ]);

        $d['data_fim_prevista'] = $d['data_fim_prevista'] ?: null;

        // FK vazias → NULL (nunca 0)
        foreach (['local_id', 'escola_id', 'municipio_id', 'provincia_id'] as $fk) {
            $d[$fk] = ($d[$fk] === '' || $d[$fk] === null) ? null : (int) $d[$fk];
        }

        return $d;
    }
}
