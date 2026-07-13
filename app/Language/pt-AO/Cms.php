<?php

return [
    'transicaoInvalida'     => 'Transição "{0}" inválida a partir do estado "{1}".',
    'semPermissaoEditorial' => 'Não tem permissão para esta ação editorial.',
    'dataAgendamentoInvalida' => 'A data de agendamento deve ser no futuro.',
    'noticiaNaoEncontrada'  => 'Notícia não encontrada.',
    'rascunhoCriado'        => 'Rascunho criado com sucesso.',
    'noticiaAtualizada'     => 'Notícia atualizada.',
    'transicaoEfetuada'     => 'Ação efetuada com sucesso.',
    'naoEAutor'             => 'Só pode editar os seus próprios conteúdos.',
    'mediaEmUso'            => 'Este ficheiro está a ser usado como imagem destacada em {0} notícia(s).',
    'mediaNaoEncontrado'    => 'Ficheiro não encontrado.',
    'comentariosFechados'   => 'Os comentários estão fechados para esta notícia.',

    // Estados (para entity Noticia::estadoRotulo)
    'estado_rascunho'  => 'Rascunho',
    'estado_revisao'   => 'Em revisão',
    'estado_agendada'  => 'Agendada',
    'estado_publicada' => 'Publicada',
    'estado_arquivada' => 'Arquivada',
    'estado_lixeira'   => 'Lixeira',
];
