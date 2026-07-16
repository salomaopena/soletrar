<?php

namespace Config;

use App\Validation\RegrasAngola;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
        RegrasAngola::class,          // ← regras do domínio

    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list' => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /** Regras nomeadas reutilizáveis (controllers, services e CLI). */
    public array $criarCandidato = [
        'nome_completo' => 'required|min_length[5]|max_length[180]',
        'data_nascimento' => 'required|valid_date[Y-m-d]|data_nao_futura|idade_entre[6,17]',
        'classe_atual' => 'required|classe_valida',
        'telefone_contacto' => 'permit_empty|telefone_ao',
        'bi_numero' => 'permit_empty|bi_angola|is_unique[candidatos.bi_numero,id,{id}]',
        'escola_id' => 'required|is_natural_no_zero',
        'provincia_id' => 'required|is_natural_no_zero',
    ];

    public array $rejeitarInscricao = [
        'motivo_rejeicao' => 'required|min_length[10]|max_length[255]',
    ];

    // Mensagens em app/Language/pt-AO/Validation.php, ex.:
    //   'telefone_ao' => 'O campo {field} deve ser um telefone angolano válido (9XXXXXXXX).',

    // ----------------------- REGRAS NOMEADAS -----------------------

    public array $inscricaoPublica = [
        'nome_completo' => 'required|min_length[5]|max_length[180]',
        'data_nascimento' => 'required|valid_date[Y-m-d]|data_nao_futura|idade_entre[5,18]',
        'genero' => 'required|in_list[M,F]',
        'classe_atual' => 'required|classe_valida',
        'escola_id' => 'required|is_natural_no_zero',
        'categoria_id' => 'required|is_natural_no_zero',
        'enc_nome_completo' => 'required|min_length[5]|max_length[180]',
        'enc_parentesco' => 'required|in_list[mae,pai,tutor,outro]',
        'enc_telefone' => 'required|telefone_ao',
        'enc_email' => 'permit_empty|valid_email',
        'enc_autorizou' => 'required',
    ];

    public array $guardarNoticia = [
        'titulo' => 'required|min_length[5]|max_length[200]',
        'conteudo' => 'permit_empty|max_length[65000]',
    ];

    public array $comentarioPublico = [
        'noticia_id' => 'required|is_natural_no_zero',
        'nome_autor' => 'required|min_length[2]|max_length[100]',
        'conteudo' => 'required|min_length[3]|max_length[2000]',
    ];
}
