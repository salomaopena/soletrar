<?php

declare(strict_types=1);

namespace App\Libraries\Sms;

use RuntimeException;

/**
 * Value object de uma mensagem SMS.
 *
 * Normaliza o número para E.164 angolano e calcula os segmentos,
 * para que TODOS os provedores recebam dados já saneados.
 */
final class SmsMensagem
{
    /** Alfabeto GSM 03.38 básico (sem extensões) — o suficiente para decidir a codificação. */
    private const GSM_BASICO = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?"
        . "¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    public readonly string $telefone;   // E.164: +2449XXXXXXXX
    public readonly string $texto;
    public readonly int $partes;
    public readonly string $codificacao; // 'GSM-7' | 'UCS-2'

    public function __construct(string $telefone, string $texto)
    {
        $this->telefone = $this->normalizarTelefone($telefone);
        $this->texto    = trim($texto);

        [$this->codificacao, $this->partes] = $this->calcularSegmentos($this->texto);
    }

    /** Aceita 923123456, 244923..., +244 923 123 456 → +244923123456 */
    private function normalizarTelefone(string $telefone): string
    {
        $digitos = preg_replace('/\D/', '', $telefone);

        if (preg_match('/^2449\d{8}$/', $digitos)) {
            return '+' . $digitos;
        }
        if (preg_match('/^9\d{8}$/', $digitos)) {
            return '+244' . $digitos;
        }

        throw new RuntimeException(lang('Notificacoes.telefoneInvalido', [$telefone]));
    }

    /**
     * GSM-7: 160 chars (1 parte) ou 153/parte em multiparte.
     * Qualquer caractere fora do alfabeto GSM força UCS-2: 70 ou 67/parte.
     * (Acentos portugueses como ã/õ/ç NÃO estão todos no GSM básico —
     * por isso os templates SMS semeados na Fase 2 evitam acentuação.)
     */
    private function calcularSegmentos(string $texto): array
    {
        $ehGsm = true;
        foreach (mb_str_split($texto) as $char) {
            if (mb_strpos(self::GSM_BASICO, $char) === false) {
                $ehGsm = false;
                break;
            }
        }

        $tamanho = mb_strlen($texto);

        if ($ehGsm) {
            return ['GSM-7', $tamanho <= 160 ? 1 : (int) ceil($tamanho / 153)];
        }

        return ['UCS-2', $tamanho <= 70 ? 1 : (int) ceil($tamanho / 67)];
    }
}
