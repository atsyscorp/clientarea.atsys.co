<?php

namespace app\components;

use Yii;
use yii\httpclient\Client;
use easedevs\yii2\turnstile\TurnstileInputValidator;

/**
 * Validador de Turnstile con timeout propio y tolerante a fallos de red.
 *
 * Resuelve dos problemas del validador original:
 *
 * 1. Cliente HTTP sin timeout. El constructor de la librería declara
 *    `Client $httpClient = null`, un parámetro tipado, así que el contenedor DI
 *    de Yii lo autoinyecta con un Client por defecto (StreamTransport, sin
 *    opciones). Al no quedar nulo, `configureComponent()` nunca lee el
 *    `httpClient` del componente `turnstile`: esa clave de configuración es
 *    código muerto. El resultado es que toda verificación usa StreamTransport
 *    sin límite y se cuelga hasta default_socket_timeout (60s), lo que el
 *    proxy traduce en 504. Aquí se reemplaza el cliente en init().
 *
 * 2. Excepciones sin capturar. Cualquier fallo de red escapaba como error 500.
 *    Ahora se captura y se decide según $allowOnFailure.
 */
class TurnstileValidator extends TurnstileInputValidator
{
    /**
     * @var int Segundos máximos para establecer la conexión con Cloudflare.
     */
    public $connectTimeout = 5;

    /**
     * @var int Segundos máximos para la verificación completa.
     */
    public $timeout = 8;

    /**
     * @var bool Si Cloudflare no responde, ¿se permite continuar sin verificar?
     *
     * false (por defecto): rechaza el envío con un mensaje claro. Seguro, pero
     *   el registro queda bloqueado mientras Cloudflare sea inalcanzable.
     * true: deja pasar el registro sin verificar el captcha. Restablece el
     *   servicio a costa de perder la protección anti-bots.
     */
    public $allowOnFailure = false;

    /**
     * @var string Mensaje cuando el servicio de verificación no está disponible.
     */
    public $unavailableMessage = 'No pudimos verificar el captcha en este momento. Vuelve a intentarlo en unos minutos.';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        // Se asigna antes de parent::init() para desplazar al cliente que
        // autoinyecta el contenedor. configureComponent() respeta este valor.
        $this->httpClient = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
            'requestConfig' => [
                'options' => [
                    CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                    CURLOPT_TIMEOUT => $this->timeout,
                    // Fuerza IPv4: un registro AAAA sin ruta IPv6 de salida
                    // produce justamente un cuelgue silencioso hasta el timeout.
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ],
        ]);

        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        try {
            return parent::validateValue($value);
        } catch (\Throwable $e) {
            // Timeout, DNS, conexión rechazada o respuesta no-JSON de Cloudflare.
            Yii::error(
                'Turnstile no disponible (' . get_class($e) . '): ' . $e->getMessage(),
                __METHOD__
            );

            if ($this->allowOnFailure) {
                Yii::warning('Registro permitido sin verificar captcha (allowOnFailure).', __METHOD__);
                return null;
            }

            return [$this->unavailableMessage, []];
        }
    }
}
