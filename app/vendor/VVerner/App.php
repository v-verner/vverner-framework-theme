<?php

namespace VVerner;

use Exception;

defined('ABSPATH') || exit('No direct script access allowed');

class App
{
    private static $instances = [];

    public const VERSION = VV_THEME_VERSION;
    public const PREFIX  = 'vv-';

    protected function __construct()
    {
        add_action('init', function () {
            global $vverner_app;
            $vverner_app = $this;
        });
    }

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }

    public static function loadDependencies(string $path): void
    {
        if (is_dir($path)) :
            $ignoredFiles = ['index.php', '..', '.'];
            $dependencies = array_diff(scandir($path), $ignoredFiles);

            foreach ($dependencies as $dependency) :
                $dPath = $path . DIRECTORY_SEPARATOR . $dependency;
                self::loadDependencies($dPath);
            endforeach;

        elseif (is_file($path)) :
            $isPhpFile = strpos($path, '.php') !== false;
            $isDevFile = strpos($path, '.dev') !== false;
            $isDevMode = self::isDevMode();

            if  ( $isPhpFile || ( $isDevMode && $isDevFile && $isPhpFile ) ) : 
                require_once $path;
            endif;
            
        endif;
    }

    public static function getInstance(): self
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) :
            self::$instances[$cls] = new static();
        endif;

        return self::$instances[$cls];
    }

    public function isVVernerUser(): bool
    {
        $data = get_userdata(get_current_user_id());
        return strpos($data->user_email, 'vverner') !== false;
    }

    public function log($thing, bool $print = false): void
    {
        error_log('=== VV_LOG ===');
        error_log(print_r($thing, true));

        if ($print && $this->isVVernerUser()) :
            add_action('the_content', function () use ($thing) {
                echo '<pre>';
                var_dump($thing);
                echo '<pre>';
            });
        endif;
    }

    public static function getVersion(): string
    {
       return self::isDevMode() ? uniqid() : self::VERSION;
    }

    public static function isDevMode(): bool
    {
        return self::getEnvironmentType() === 'DEV';
    }

    public static function getEnvironmentType(): string
    {
        if (defined('VV_ENVIRONMENT_TYPE') && VV_ENVIRONMENT_TYPE) : 
            return VV_ENVIRONMENT_TYPE;
        endif;

        $url        = home_url();
        $currentEnv = 'PRD';
        $knownPaths = [
            '.dev'          => 'DEV', 
            '-sandbox.com'  => 'DEV'  
        ];

        foreach ($knownPaths as $path => $env) :
            if ( strpos($url, $path) ) :
                $currentEnv = $env;
                break;
            endif;
        endforeach;
        
        return $currentEnv;
    }
}
