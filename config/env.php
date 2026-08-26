<?php

/**
 * Carga las variables de entorno desde el archivo .env de la raíz del proyecto.
 *
 * Se puede requerir varias veces sin efectos secundarios: la carga real ocurre
 * una sola vez. Cada archivo de configuración que necesite un secreto debe hacer
 * `require_once __DIR__ . '/env.php';` antes de llamar a env().
 */

if (!function_exists('env')) {
    /**
     * Devuelve una variable de entorno con casteo de los literales habituales.
     *
     * @param string $key
     * @param mixed $default valor si la variable no está definida
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
        }

        return $value;
    }
}

if (!function_exists('env_required')) {
    /**
     * Igual que env(), pero falla temprano si el secreto no está configurado.
     * Evita que la aplicación arranque con credenciales vacías y produzca
     * errores confusos más adelante (conexiones rechazadas, correos no enviados).
     *
     * @param string $key
     * @return mixed
     * @throws RuntimeException
     */
    function env_required($key)
    {
        $value = env($key);

        if ($value === null) {
            throw new RuntimeException(
                "Falta la variable de entorno obligatoria '{$key}'. " .
                "Cópiala desde .env.example al archivo .env del servidor."
            );
        }

        return $value;
    }
}

// Carga única del archivo .env
if (!defined('ATSYS_ENV_LOADED')) {
    define('ATSYS_ENV_LOADED', true);

    $rootPath = dirname(__DIR__);

    if (is_file($rootPath . '/.env')) {
        // createUnsafeImmutable también publica en getenv(), útil para
        // herramientas externas (workers, cron) que leen del entorno del proceso.
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable($rootPath);
        $dotenv->load();
    }
}
