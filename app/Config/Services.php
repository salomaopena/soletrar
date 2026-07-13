<?php



namespace Config;

use App\Libraries\Sms\Providers\NuloProvider;
use App\Libraries\Sms\Providers\Pro2SmsProvider;
use App\Libraries\Sms\SmsProviderInterface;
use App\Services\Cms\ComentarioService;
use App\Services\Cms\MaquinaEstadosNoticia;
use App\Services\Cms\MediaService;
use App\Services\Cms\MenuService;
use App\Services\Cms\NoticiaService;
use App\Services\Cms\SanitizadorHtml;
use App\Services\Comum\AuditoriaService;
use App\Services\Comum\ConfiguracaoService;
use App\Services\Comum\DataHoraService;
use App\Services\Comum\EscopoService;
use App\Services\Comum\ExportacaoService;
use App\Services\Comum\UploadService;
use App\Services\Comum\UuidService;
use App\Services\Concurso\ClassificacaoService;
use App\Services\Concurso\EventoService;
use App\Services\Concurso\InscricaoService;
use App\Services\Concurso\PalavraService;
use App\Services\Concurso\ProgressaoService;
use App\Services\Concurso\RelatorioService;
use App\Services\Concurso\RoundService;
use App\Services\Concurso\TentativaService;
use App\Services\Notificacoes\Canais\CanalEmail;
use App\Services\Notificacoes\Canais\CanalSistema;
use App\Services\Notificacoes\Canais\CanalSms;
use App\Services\Notificacoes\FilaService;
use App\Services\Notificacoes\Notificador;
use App\Services\Notificacoes\TemplateRenderer;
use App\Services\Seguranca\AutorizacaoService;
use App\Services\Seguranca\UrlCryptService;
use CodeIgniter\Config\BaseService;

/**
 * Registo central de TODOS os services do projeto (Fases 3–9).
 *
 * Convenção: um método por service, singleton por omissão. Substituir
 * qualquer peça (ex.: provedor de SMS) é alterar só o método respetivo —
 * o resto da aplicação depende de interfaces/fachadas, nunca de classes.
 */
/**
 * Registo central de TODOS os services do projeto (Fases 3–9).
 *
 * Convenção: um método por service, singleton por omissão. Substituir
 * qualquer peça (ex.: provedor de SMS) é alterar só o método respetivo —
 * o resto da aplicação depende de interfaces/fachadas, nunca de classes.
 */
class Services extends BaseService
{
    // ---------------------------- Comum ----------------------------

    public static function dataHora(bool $getShared = true): DataHoraService
    {
        return $getShared ? static::getSharedInstance('dataHora') : new DataHoraService();
    }

    public static function uuid(bool $getShared = true): UuidService
    {
        return $getShared ? static::getSharedInstance('uuid') : new UuidService();
    }

    public static function configuracao(bool $getShared = true): ConfiguracaoService
    {
        return $getShared ? static::getSharedInstance('configuracao') : new ConfiguracaoService(db_connect());
    }

    public static function auditoria(bool $getShared = true): AuditoriaService
    {
        return $getShared ? static::getSharedInstance('auditoria') : new AuditoriaService(db_connect());
    }

    public static function escopo(bool $getShared = true): EscopoService
    {
        return $getShared ? static::getSharedInstance('escopo') : new EscopoService(db_connect());
    }

    public static function uploads(bool $getShared = true): UploadService
    {
        return $getShared ? static::getSharedInstance('uploads') : new UploadService();
    }

    public static function exportacao(bool $getShared = true): ExportacaoService
    {
        return $getShared ? static::getSharedInstance('exportacao') : new ExportacaoService();
    }

    // -------------------------- Segurança --------------------------

    public static function urlCrypt(bool $getShared = true): UrlCryptService
    {
        if ($getShared) {
            return static::getSharedInstance('urlCrypt');
        }

        $config          = config('UrlCrypt');
        $encConfig       = config('Encryption');
        $encConfig->key    = $config->chave;
        $encConfig->driver = $config->driver;

        return new UrlCryptService(\Config\Services::encrypter($encConfig, false), $config);
    }

    public static function autorizacao(bool $getShared = true): AutorizacaoService
    {
        return $getShared ? static::getSharedInstance('autorizacao') : new AutorizacaoService();
    }

    // -------------------------- Concurso --------------------------

    public static function inscricoes(bool $getShared = true): InscricaoService
    {
        return $getShared ? static::getSharedInstance('inscricoes')
            : new InscricaoService(db_connect(), model('InscricaoModel'));
    }

    public static function palavras(bool $getShared = true): PalavraService
    {
        return $getShared ? static::getSharedInstance('palavras') : new PalavraService(db_connect());
    }

    public static function tentativas(bool $getShared = true): TentativaService
    {
        return $getShared ? static::getSharedInstance('tentativas')
            : new TentativaService(db_connect(), static::palavras());
    }

    public static function eventos(bool $getShared = true): EventoService
    {
        return $getShared ? static::getSharedInstance('eventos') : new EventoService(db_connect());
    }

    public static function rounds(bool $getShared = true): RoundService
    {
        return $getShared ? static::getSharedInstance('rounds') : new RoundService(db_connect());
    }

    public static function classificacao(bool $getShared = true): ClassificacaoService
    {
        return $getShared ? static::getSharedInstance('classificacao') : new ClassificacaoService(db_connect());
    }

    public static function progressao(bool $getShared = true): ProgressaoService
    {
        return $getShared ? static::getSharedInstance('progressao') : new ProgressaoService(db_connect());
    }

    public static function relatorios(bool $getShared = true): RelatorioService
    {
        return $getShared ? static::getSharedInstance('relatorios') : new RelatorioService(db_connect());
    }

    // ----------------------------- CMS -----------------------------

    public static function maquinaEstadosNoticia(bool $getShared = true): MaquinaEstadosNoticia
    {
        return $getShared ? static::getSharedInstance('maquinaEstadosNoticia') : new MaquinaEstadosNoticia();
    }

    public static function sanitizadorHtml(bool $getShared = true): SanitizadorHtml
    {
        return $getShared ? static::getSharedInstance('sanitizadorHtml') : new SanitizadorHtml();
    }

    public static function noticias(bool $getShared = true): NoticiaService
    {
        return $getShared ? static::getSharedInstance('noticias')
            : new NoticiaService(model('NoticiaModel'), static::maquinaEstadosNoticia(), static::sanitizadorHtml());
    }

    public static function media(bool $getShared = true): MediaService
    {
        return $getShared ? static::getSharedInstance('media') : new MediaService(db_connect(), static::uploads());
    }

    public static function menus(bool $getShared = true): MenuService
    {
        return $getShared ? static::getSharedInstance('menus') : new MenuService(db_connect());
    }

    public static function comentarios(bool $getShared = true): ComentarioService
    {
        return $getShared ? static::getSharedInstance('comentarios') : new ComentarioService(db_connect());
    }

    // ------------------------ Notificações ------------------------

    /**
     * Provedor de SMS: Pro2Sms em produção, Nulo em desenvolvimento.
     * TROCAR DE PROVEDOR = alterar SÓ este método.
     */
    public static function smsProvider(bool $getShared = true): SmsProviderInterface
    {
        if ($getShared) {
            return static::getSharedInstance('smsProvider');
        }

        $config = config('Pro2Sms');

        return $config->ativo
            ? new Pro2SmsProvider($config, \Config\Services::curlrequest(['baseURI' => $config->baseUrl]))
            : new NuloProvider();
    }

    public static function templateRenderer(bool $getShared = true): TemplateRenderer
    {
        return $getShared ? static::getSharedInstance('templateRenderer') : new TemplateRenderer(db_connect());
    }

    public static function filaNotificacoes(bool $getShared = true): FilaService
    {
        return $getShared ? static::getSharedInstance('filaNotificacoes')
            : new FilaService(db_connect(), config('Notificacoes'));
    }

    public static function canalSistema(bool $getShared = true): CanalSistema
    {
        return $getShared ? static::getSharedInstance('canalSistema') : new CanalSistema(db_connect());
    }

    public static function canalEmail(bool $getShared = true): CanalEmail
    {
        return $getShared ? static::getSharedInstance('canalEmail')
            : new CanalEmail(db_connect(), config('Notificacoes'));
    }

    public static function canalSms(bool $getShared = true): CanalSms
    {
        return $getShared ? static::getSharedInstance('canalSms')
            : new CanalSms(db_connect(), static::smsProvider());
    }

    public static function notificador(bool $getShared = true): Notificador
    {
        return $getShared ? static::getSharedInstance('notificador')
            : new Notificador(db_connect(), static::templateRenderer(), static::filaNotificacoes(), static::canalSistema());
    }
}
