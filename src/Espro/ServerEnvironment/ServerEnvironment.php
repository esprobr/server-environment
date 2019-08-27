<?php
namespace Espro\ServerEnvironment;

use Espro\Utils\SingletonTrait;

/**
 * Class ServerEnvironment
 * Utilizada para poder pegar as variáveis de ambiente no php 5.6 de forma mais simples, visto que não é possível
 * sobrescrever uma variável de ambiente já definida para poder retornar via getenv, devido a restrições de segurança.
 * @method static ServerEnvironment getInstance($_envCodeString = null)
 */
class ServerEnvironment
{
    use SingletonTrait;

    /**
     * @var string
     */
    public static $defaultEnvSeparator = '_';
    /**
     * @var string
     */
    protected static $rootPath = null;
    /**
     * @var string
     */
    protected static $dotEnvFileName = null;
    /**
     * @var string
     */
    protected static $apacheEnvFilePath = null;

    public static function setRootPathOnce( $_rootPath )
    {
        if(is_null(self::$rootPath)) {
            self::$rootPath = rtrim($_rootPath, '/\\');
        }
    }

    public static function setDotEnvFileNameOnce( $_dotEnvFileName )
    {
        if(is_null(self::$dotEnvFileName)) {
            self::$dotEnvFileName = $_dotEnvFileName;
        }
    }

    public static function setApacheEnvFilePathOnce( $_apacheEnvFilePath )
    {
        if(is_null(self::$apacheEnvFilePath)) {
            self::$apacheEnvFilePath = $_apacheEnvFilePath;
        }
    }

    const DEVELOPMENT = 0;
    const HOMOLOGA = 1;
    const OFICIAL = 2;
    const MIRROR = 3;
    const TESTE = 4;

    /**
     * @var int
     */
    protected $code = 0;
    /**
     * @var string
     */
    protected $name = '';
    /**
     * @var array
     */
    protected $envStrings = [
        0 => 'Desenvolvimento',
        1 => 'Homologação',
        2 => 'Produção',
        3 => 'Espelho',
        4 => 'Teste'
    ];

    /**
     * ServerEnvironment constructor.
     * @param $_envCodeString
     */
    protected function __construct( $_envCodeString = null )
    {
        //Para tornar possível utilizar as variáveis de ambiente do apache no cli com o fpm
        if( PHP_SAPI === 'cli' || !is_null(self::$apacheEnvFilePath) ) {
            (new \Espro\SimpleApacheEnvParser\Parser())->parse(self::$apacheEnvFilePath);
        }

        if( class_exists( '\Dotenv\Dotenv' ) && file_exists(self::$rootPath.'/'.self::$dotEnvFileName) ) {
            $dotenv = \Dotenv\Dotenv::create(self::$rootPath, self::$dotEnvFileName, new \Dotenv\Environment\DotenvFactory([
                new \Dotenv\Environment\Adapter\PutenvAdapter(),
                new \Dotenv\Environment\Adapter\ApacheAdapter()
            ]));
            $dotenv->overload();
        }

        if(!is_null($_envCodeString)) {
            $serverAppEnv = getenv('LOCAL' . self::$defaultEnvSeparator . $_envCodeString);

            if (!($serverAppEnv === false) && self::isEnvCodeValid(intval($serverAppEnv))) {
                $this->code = intval($serverAppEnv);
            } else {
                $this->code = intval(getenv($_envCodeString));
            }
        }

        $this->name = $this->envStrings[$this->code];
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $_envCode
     * @return bool
     */
    public function is($_envCode)
    {
        return self::getCode() == $_envCode;
    }

    /**
     * @param array $_envList
     * @return bool
     */
    public function in(array $_envList = [])
    {
        return in_array(self::getCode(), $_envList);
    }

    /**
     * @param $_chave
     * @return array|false|string
     */
    /**
     * @param string ...$_input
     * @return array|false|string
     */
    public static function getEnvVar(...$_input)
    {
        $key = implode( self::$defaultEnvSeparator, $_input );
        return getenv("LOCAL" . $key)
            ? getenv("LOCAL" . $key)
            : getenv($key);
    }

    /**
     * @return array
     */
    public function getEnvStrings()
    {
        return $this->envStrings;
    }

    /**
     * @param $_envCode
     * @return bool
     */
    public static function isEnvCodeValid( $_envCode )
    {
        return in_array(
            $_envCode,
            [
                self::DEVELOPMENT,
                self::HOMOLOGA,
                self::OFICIAL,
                self::MIRROR,
                self::TESTE
            ]
        );
    }
}