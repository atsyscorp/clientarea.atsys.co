<?php

namespace app\helpers;

use Yii;

class CurrencyHelper
{
    /**
     * Obtiene la TRM de una moneda (USD o EUR) con respecto a COP.
     * Utiliza caché por 12 horas y consulta un API externo (open.er-api.com).
     * Si falla, recurre a los parámetros de fallback configurados.
     *
     * @param string $currency 'USD' o 'EUR'
     * @return float
     */
    public static function getTrm($currency)
    {
        $currency = strtoupper($currency);
        if ($currency === 'COP') {
            return 1.0;
        }

        if (!in_array($currency, ['USD', 'EUR'])) {
            return 1.0;
        }

        $cacheKey = 'trm_' . strtolower($currency);
        $cachedValue = Yii::$app->cache->get($cacheKey);

        if ($cachedValue !== false) {
            return (float)$cachedValue;
        }

        // Si no está en caché, intentar consultar el API
        $trm = self::fetchTrmFromApi($currency);

        if ($trm !== null) {
            // Guardar en caché por 12 horas (43200 segundos)
            Yii::$app->cache->set($cacheKey, $trm, 43200);
            return $trm;
        }

        // Si el API falla, obtener el fallback de params.php
        $paramKey = $currency === 'USD' ? 'fallback_trm' : 'fallback_trm_eur';
        $defaultFallback = $currency === 'USD' ? 4000.00 : 4300.00;

        return (float)(Yii::$app->params[$paramKey] ?? $defaultFallback);
    }

    /**
     * Consulta el API externo para obtener el valor de la moneda en COP.
     *
     * @param string $currency
     * @return float|null
     */
    private static function fetchTrmFromApi($currency)
    {
        // Usamos open.er-api.com porque no requiere API Key y es gratuito y rápido.
        $url = "https://open.er-api.com/v6/latest/" . $currency;

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout corto de 3 segundos
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['result']) && $data['result'] === 'success' && isset($data['rates']['COP'])) {
                    return (float)$data['rates']['COP'];
                }
            }
        } catch (\Exception $e) {
            Yii::warning("Error al obtener la TRM desde el API para {$currency}: " . $e->getMessage(), __METHOD__);
        }

        return null;
    }
}
