<?php

declare(strict_types=1);

namespace App\Services\Comum;

use CodeIgniter\I18n\Time;

/**
 * ÚNICO conversor de datas da aplicação (estratégia da Fase 3):
 * armazenamento SEMPRE em UTC; apresentação em Africa/Luanda (UTC+1,
 * sem horário de verão). Nenhuma view ou service formata datas por si.
 */
final class DataHoraService
{
    private const TZ_LOCAL = 'Africa/Luanda';

    private const MESES = [
        1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
    ];

    /** Agora, em UTC (para gravação em BD). */
    public function agoraUtc(): Time
    {
        return Time::now('UTC');
    }

    /**
     * Formata para exibição pública (hora local de Angola).
     * Formatos: curta 14/06/2026 · longa 14 de junho de 2026 ·
     *           hora 16:30 · dia 14 · mes jun · curta_hora 14/06/2026 16:30
     */
    public function paraExibicao(Time|string|null $valor, string $formato = 'curta'): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        $t = ($valor instanceof Time ? $valor : Time::parse($valor, 'UTC'))
            ->setTimezone(self::TZ_LOCAL);

        return match ($formato) {
            'longa'      => sprintf('%d de %s de %d', $t->getDay(), self::MESES[$t->getMonth()], $t->getYear()),
            'hora'       => $t->format('H:i'),
            'dia'        => $t->format('d'),
            'mes'        => mb_substr(self::MESES[$t->getMonth()], 0, 3),
            'curta_hora' => $t->format('d/m/Y H:i'),
            default      => $t->format('d/m/Y'),
        };
    }

    /**
     * Interpreta input de formulário (datetime-local, hora de Luanda)
     * e devolve Time em UTC pronto a gravar. null se vazio/inválido.
     */
    public function deFormulario(?string $input): ?Time
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        try {
            return Time::parse(str_replace('T', ' ', trim($input)), self::TZ_LOCAL)
                ->setTimezone('UTC');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Idade completa em anos numa data de referência. */
    public function idadeEm(Time|string $nascimento, Time|string $referencia): int
    {
        $n = $nascimento instanceof Time ? $nascimento : Time::parse($nascimento, 'UTC');
        $r = $referencia instanceof Time ? $referencia : Time::parse($referencia, 'UTC');

        return $n->difference($r)->getYears();
    }

    /** Verifica se $agora ∈ [inicio, fim] (limites em UTC, inclusive). */
    /**
     * Converte um valor UTC (vindo da BD) para o formato exato que o
     * input HTML5 <input type="datetime-local"> exige, já em hora de
     * Angola: "AAAA-MM-DDTHH:MM" (com "T", sem segundos).
     *
     * CORRIGE UM BUG REAL: sem esta conversão, os formulários de edição
     * mostravam a data em bruto ("2026-07-17 10:00:00" — com espaço e
     * segundos), que o browser não consegue interpretar num campo
     * datetime-local. O campo aparecia em BRANCO (sem erro visível), e
     * ao gravar, esse branco virava `null` — apagando silenciosamente
     * datas que estavam corretas (foi o que aconteceu ao reabrir uma
     * edição: a data de encerramento das inscrições desapareceu).
     */
    public function paraCampoDatetimeLocal(Time|string|null $valorUtc): string
    {
        if ($valorUtc === null || $valorUtc === '') {
            return '';
        }

        $t = ($valorUtc instanceof Time ? $valorUtc : Time::parse((string) $valorUtc, 'UTC'))
            ->setTimezone(self::TZ_LOCAL);

        return $t->format('Y-m-d\TH:i');
    }

    public function dentroDoPrazo(Time|string|null $inicio, Time|string|null $fim, ?Time $agora = null): bool
    {
        $agora ??= $this->agoraUtc();

        $i = $inicio ? ($inicio instanceof Time ? $inicio : Time::parse((string) $inicio, 'UTC')) : null;
        $f = $fim ? ($fim instanceof Time ? $fim : Time::parse((string) $fim, 'UTC')) : null;

        return ($i === null || $agora->getTimestamp() >= $i->getTimestamp())
            && ($f === null || $agora->getTimestamp() <= $f->getTimestamp());
    }
}
