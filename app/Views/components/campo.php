<?php
/**
 * Campo de formulário padronizado (label + controlo + erro + ajuda).
 *
 * O componente é AUTO-SUFICIENTE: normaliza todos os parâmetros opcionais
 * no topo, para nunca herdar valores de outra chamada (ver Config\View::$saveData).
 *
 * Uso:
 *   <?= view('components/campo', [
 *       'nome'        => 'nome_completo',
 *       'rotulo'      => 'Nome completo',
 *       'tipo'        => 'text',      // text|email|date|datetime-local|number|tel|select|multi|textarea|password
 *       'valor'       => old('nome_completo'),
 *       'obrigatorio' => true,
 *       'opcoes'      => [1 => 'Opção'],   // só para select
 *       'ajuda'       => 'Texto de apoio',
 *       'linhas'      => 4,               // só para textarea
 *       'erros'       => session('erros'),
 *   ]) ?>
 */

// --- Normalização defensiva (NÃO remover) ---
$nome        = $nome        ?? '';
$rotulo      = $rotulo      ?? '';
$tipo        = $tipo        ?? 'text';
$valor       = $valor       ?? '';
$obrigatorio = ! empty($obrigatorio);
$opcoes      = in_array($tipo, ['select', 'multi'], true) ? ($opcoes ?? []) : [];
$ajuda       = $ajuda       ?? null;
$linhas      = $linhas      ?? 4;
$erros       = $erros       ?? [];
$placeholder = $placeholder ?? '';

$id   = 'campo-' . $nome;
$erro = is_array($erros) ? ($erros[$nome] ?? null) : null;
?>
<div class="mb-3">
  <label class="form-label" for="<?= esc($id, 'attr') ?>">
    <?= esc($rotulo) ?><?= $obrigatorio ? ' <span class="text-danger" aria-hidden="true">*</span>' : '' ?>
  </label>

  <?php if ($tipo === 'select'): ?>
    <select class="form-select <?= $erro ? 'is-invalid' : '' ?>"
            id="<?= esc($id, 'attr') ?>" name="<?= esc($nome, 'attr') ?>"
            <?= $obrigatorio ? 'required' : '' ?>>
      <option value="">— Selecionar —</option>
      <?php foreach ($opcoes as $chave => $texto): ?>
        <option value="<?= esc((string) $chave, 'attr') ?>"
          <?= (string) $valor === (string) $chave ? 'selected' : '' ?>>
          <?= esc((string) $texto) ?>
        </option>
      <?php endforeach ?>
    </select>

  <?php elseif ($tipo === 'multi'): ?>
    <?php
    // Coluna SET: o valor guardado é uma lista separada por vírgulas.
    $marcados = is_array($valor)
        ? $valor
        : array_filter(explode(',', (string) $valor));
    ?>
    <div class="border rounded p-2">
      <?php foreach ($opcoes as $chave => $texto): ?>
        <div class="form-check">
          <input class="form-check-input" type="checkbox"
                 name="<?= esc($nome, 'attr') ?>[]" value="<?= esc((string) $chave, 'attr') ?>"
                 id="<?= esc($id . '-' . $chave, 'attr') ?>"
                 <?= in_array((string) $chave, $marcados, true) ? 'checked' : '' ?>>
          <label class="form-check-label" for="<?= esc($id . '-' . $chave, 'attr') ?>">
            <?= esc((string) $texto) ?>
          </label>
        </div>
      <?php endforeach ?>
    </div>

  <?php elseif ($tipo === 'textarea'): ?>
    <textarea class="form-control <?= $erro ? 'is-invalid' : '' ?>"
              id="<?= esc($id, 'attr') ?>" name="<?= esc($nome, 'attr') ?>"
              rows="<?= (int) $linhas ?>"
              placeholder="<?= esc($placeholder, 'attr') ?>"
              <?= $obrigatorio ? 'required' : '' ?>><?= esc((string) $valor) ?></textarea>

  <?php else: ?>
    <?php
    // datetime-local exige "AAAA-MM-DDTHH:MM" em hora local — um valor
    // vindo direto da BD (UTC, com espaço e segundos) fica em branco no
    // browser sem avisar, e ao gravar apaga a data (bug real corrigido).
    $valorCampo = ($tipo === 'datetime-local')
        ? campo_datetime_local((string) $valor)
        : (string) $valor;
    ?>
    <input class="form-control <?= $erro ? 'is-invalid' : '' ?>"
           id="<?= esc($id, 'attr') ?>" name="<?= esc($nome, 'attr') ?>"
           type="<?= esc($tipo, 'attr') ?>"
           value="<?= esc($valorCampo, 'attr') ?>"
           placeholder="<?= esc($placeholder, 'attr') ?>"
           <?= $obrigatorio ? 'required' : '' ?>>
  <?php endif ?>

  <?php if ($erro): ?>
    <p class="campo-erro" role="alert"><?= esc($erro) ?></p>
  <?php elseif ($ajuda): ?>
    <p class="form-text"><?= esc($ajuda) ?></p>
  <?php endif ?>
</div>
