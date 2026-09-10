<?php
use yii\helpers\Html;

// Compatibilidad: si se pasa un solo servicio a la antigua
if (!isset($servicesData) && isset($domain)) {
    $servicesData = [
        ['model' => (object)['domain' => $domain], 'date_long' => $date_long]
    ];
}

$serviceCount = count($servicesData ?? []);
$isMultiple = $serviceCount > 1;

// Verificar si todos los servicios tienen la misma fecha de vencimiento o diferentes fechas
$dates = array_unique(array_filter(array_map(function($d) {
    $m = $d['model'] ?? null;
    return (is_object($m) && !empty($m->next_due_date)) ? substr($m->next_due_date, 0, 10) : null;
}, $servicesData ?? [])));
$sameDueDate = count($dates) <= 1;

// Calcular total estimado de renovación si está disponible
$totalRenewal = 0;
$hasPrice = false;
$currency = 'COP';

foreach ($servicesData as $d) {
    $m = $d['model'] ?? null;
    $p = (is_object($m) && isset($m->product)) ? $m->product : null;
    if ($p) {
        $itemPrice = ($p->price_renewal > 0) ? $p->price_renewal : $p->price;
        if ($itemPrice > 0) {
            $totalRenewal += (float)$itemPrice;
            $hasPrice = true;
            if (!empty($p->currency)) {
                $currency = $p->currency;
            }
        }
    }
}
?>
<div style='font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b; line-height: 1.5;'>
    
    <!-- Encabezado de Urgencia / Vencimiento -->
    <div style='border-left: 5px solid <?=$color?>; padding-left: 15px; margin-bottom: 22px;'>
        <h2 style='color: <?=$color?>; margin: 0; font-size: 22px; font-weight: 700;'>Aviso de Renovación</h2>
        <?php if (isset($daysLeft) && $daysLeft == 0): ?>
            <p style='font-size: 17px; margin: 4px 0 0 0; color: #dc2626; font-weight: bold;'>
                <?= ($isMultiple && $sameDueDate) ? '⚠️ Tus servicios vencen HOY' : '⚠️ Vence HOY' ?>
            </p>
        <?php elseif (isset($daysLeft)): ?>
            <p style='font-size: 17px; margin: 4px 0 0 0; color: #475569;'>
                <?php if ($isMultiple && !$sameDueDate): ?>
                    Próximo vencimiento: en <strong><?=$daysLeft?> días</strong>
                <?php elseif ($isMultiple && $sameDueDate): ?>
                    Faltan <strong><?=$daysLeft?> días</strong> (vencen el mismo día)
                <?php else: ?>
                    Faltan <strong><?=$daysLeft?> días</strong> para el vencimiento
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <p style='margin: 0 0 12px 0; font-size: 15px;'>Hola <strong><?= Html::encode($business_name) ?></strong>,</p>
    <p style='margin: 0 0 16px 0; font-size: 14px; color: #334155;'><?=$msgIntro?></p>

    <!-- Banner aclaratorio cuando hay más de un servicio -->
    <?php if ($isMultiple): ?>
        <?php if ($sameDueDate): ?>
        <!-- Caso A: Vencen en la MISMA fecha (ej. Hosting y Dominio que se pueden pagar juntos) -->
        <div style='background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 14px 16px; margin: 20px 0; font-size: 13px; color: #0369a1; line-height: 1.5;'>
            <div style='font-weight: 700; margin-bottom: 4px;'>
                ℹ️ Información sobre tus servicios a renovar
            </div>
            <div>
                A continuación se detallan los <strong><?=$serviceCount?> servicios independientes</strong> activos en tu cuenta. Ten en cuenta que el <strong>Alojamiento Web (Hosting)</strong> y el <strong>Registro de Dominio</strong> son componentes distintos que <strong>vencen en la misma fecha</strong>; puedes renovarlos conjuntamente en un solo pago para mantener tu sitio web y correos operativos.
            </div>
        </div>
        <?php else: ?>
        <!-- Caso B: Tienen DIFERENTES fechas de vencimiento -->
        <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #0284c7; border-radius: 8px; padding: 14px 16px; margin: 20px 0; font-size: 13px; color: #334155; line-height: 1.5;'>
            <div style='font-weight: 700; margin-bottom: 4px; color: #0f172a;'>
                ℹ️ Servicios con fechas de vencimiento diferentes
            </div>
            <div>
                A continuación se detallan los <strong><?=$serviceCount?> servicios activos</strong> próximos a vencer. Ten en cuenta que estos servicios <strong>tienen fechas de vencimiento diferentes</strong>; cada uno cuenta con su fecha límite y valor individual para que puedas programar su renovación oportunamente.
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Listado de Tarjetas de Servicios -->
    <?php foreach ($servicesData as $data): 
        $model = $data['model'] ?? null;
        $product = (is_object($model) && isset($model->product)) ? $model->product : null;
        $serviceDomain = (is_object($model) && isset($model->domain)) ? $model->domain : ($data['domain'] ?? 'N/A');
        $serviceDate = isset($data['date_long']) 
            ? $data['date_long'] 
            : ((is_object($model) && isset($model->next_due_date)) ? Yii::$app->formatter->asDate($model->next_due_date, 'long') : '');

        // Configuración visual por tipo de producto
        $productType = $product ? ($product->type ?? 'other') : 'other';
        $productName = $product ? $product->name : ((is_object($model) && !empty($model->description_label)) ? $model->description_label : 'Servicio ATSYS');

        if ($productType === 'hosting') {
            $badgeText = '🚀 HOSTING WEB';
            $badgeBg = '#e0e7ff';
            $badgeColor = '#3730a3';
            $badgeBorder = '#c7d2fe';
            $domainLabel = 'Dominio vinculado:';
            $leftAccent = '#4f46e5';
        } elseif ($productType === 'domain') {
            $badgeText = '🌐 REGISTRO DE DOMINIO';
            $badgeBg = '#e0f2fe';
            $badgeColor = '#0369a1';
            $badgeBorder = '#bae6fd';
            $domainLabel = 'Nombre de dominio:';
            $leftAccent = '#0284c7';
        } elseif ($productType === 'license') {
            $badgeText = '🔑 LICENCIA';
            $badgeBg = '#dcfce7';
            $badgeColor = '#15803d';
            $badgeBorder = '#bbf7d0';
            $domainLabel = 'Referencia:';
            $leftAccent = '#16a34a';
        } elseif ($productType === 'development') {
            $badgeText = '💻 DESARROLLO';
            $badgeBg = '#fef3c7';
            $badgeColor = '#92400e';
            $badgeBorder = '#fde68a';
            $domainLabel = 'Proyecto:';
            $leftAccent = '#d97706';
        } elseif ($productType === 'support') {
            $badgeText = '🛠️ SOPORTE';
            $badgeBg = '#f3e8ff';
            $badgeColor = '#6b21a8';
            $badgeBorder = '#e9d5ff';
            $domainLabel = 'Servicio:';
            $leftAccent = '#9333ea';
        } else {
            $badgeText = '📦 SERVICIO';
            $badgeBg = '#f1f5f9';
            $badgeColor = '#475569';
            $badgeBorder = '#cbd5e1';
            $domainLabel = 'Dominio / Referencia:';
            $leftAccent = '#64748b';
        }

        // Precio individual de renovación
        $itemPriceFormatted = null;
        if ($product) {
            $itemPrice = ($product->price_renewal > 0) ? $product->price_renewal : $product->price;
            if ($itemPrice > 0) {
                $itemCurr = !empty($product->currency) ? $product->currency : 'COP';
                $itemPriceFormatted = Yii::$app->formatter->asCurrency($itemPrice, $itemCurr);
            }
        }
    ?>
    <div style='background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid <?=$leftAccent?>; padding: 18px 20px; border-radius: 8px; margin: 18px 0;'>
        
        <!-- Cabecera de la tarjeta: Badge de tipo y precio -->
        <table cellpadding='0' cellspacing='0' border='0' width='100%' style='margin-bottom: 10px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;'>
            <tr>
                <td align='left' valign='middle'>
                    <span style='background-color: <?=$badgeBg?>; color: <?=$badgeColor?>; border: 1px solid <?=$badgeBorder?>; font-size: 11px; font-weight: bold; padding: 3px 8px; border-radius: 4px; letter-spacing: 0.5px; text-transform: uppercase;'>
                        <?=$badgeText?>
                    </span>
                </td>
                <?php if ($itemPriceFormatted): ?>
                <td align='right' valign='middle'>
                    <span style='font-size: 13px; color: #64748b;'>Renovación: </span>
                    <strong style='font-size: 14px; color: #0f172a;'><?=$itemPriceFormatted?></strong>
                </td>
                <?php endif; ?>
            </tr>
        </table>

        <!-- Nombre del Producto / Plan -->
        <h3 style='margin: 0 0 10px 0; font-size: 16px; color: #0f172a; font-weight: 700;'>
            <?= Html::encode($productName) ?>
        </h3>

        <!-- Detalles del Servicio -->
        <table cellpadding='0' cellspacing='0' border='0' width='100%' style='font-size: 13px; color: #334155; line-height: 1.6;'>
            <tr>
                <td style='padding: 3px 0; width: 145px; color: #64748b;'><strong><?=$domainLabel?></strong></td>
                <td style='padding: 3px 0; font-weight: 600; color: #0f172a;'><?= Html::encode($serviceDomain) ?></td>
            </tr>
            <?php if (is_object($model) && !empty($model->description_label) && $model->description_label !== $serviceDomain && $model->description_label !== $productName): ?>
            <tr>
                <td style='padding: 3px 0; color: #64748b;'><strong>Descripción:</strong></td>
                <td style='padding: 3px 0; color: #334155;'><?= Html::encode($model->description_label) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style='padding: 3px 0; color: #64748b;'><strong>Vence el:</strong></td>
                <td style='padding: 3px 0; color: #0f172a; font-weight: 600;'>
                    <?=$serviceDate?>
                    <?php if ($isMultiple && !$sameDueDate && isset($data['daysLeft'])): ?>
                        <span style='font-size: 11px; font-weight: normal; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; margin-left: 6px;'>
                            <?= ($data['daysLeft'] == 0) ? 'Vence HOY' : "en {$data['daysLeft']} días" ?>
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style='padding: 3px 0; color: #64748b;'><strong>Estado:</strong></td>
                <td style='padding: 3px 0; color: <?=$color?>; font-weight: bold;'>Pendiente de Pago</td>
            </tr>
        </table>

        <!-- Agendar en Calendario para este servicio -->
        <?php if (is_object($model) && isset($model->id) && \app\helpers\CalendarHelper::isEligible($model, 90)): ?>
        <div style='margin-top: 14px; padding-top: 12px; border-top: 1px dashed #e2e8f0; font-size: 12px;'>
            <span style='color: #64748b; font-weight: 600; display: inline-block; margin-bottom: 6px;'>📅 Agendar recordatorio para este servicio:</span><br>
            <a href='<?= Html::encode(\app\helpers\CalendarHelper::getGoogleCalendarUrl($model)) ?>' target='_blank' style='display: inline-block; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 5px 9px; color: #1d4ed8; text-decoration: none; font-size: 11px; margin-right: 5px; margin-bottom: 5px; font-weight: 500;'>
                🗓️ Google Calendar
            </a>
            <a href='https://clientarea.atsys.co/customer-services/calendar-ics?id=<?= $model->id ?>' target='_blank' style='display: inline-block; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 5px 9px; color: #0f172a; text-decoration: none; font-size: 11px; margin-right: 5px; margin-bottom: 5px; font-weight: 500;'>
                🍎 Apple / iCal (.ics)
            </a>
            <a href='<?= Html::encode(\app\helpers\CalendarHelper::getOutlookLiveUrl($model)) ?>' target='_blank' style='display: inline-block; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 5px 9px; color: #0369a1; text-decoration: none; font-size: 11px; margin-bottom: 5px; font-weight: 500;'>
                📧 Outlook / Office 365
            </a>
        </div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>

    <!-- Resumen total si hay más de 1 servicio con precios -->
    <?php if ($isMultiple && $hasPrice && $totalRenewal > 0): ?>
    <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin: 22px 0;'>
        <table cellpadding='0' cellspacing='0' border='0' width='100%' style='font-size: 14px;'>
            <tr>
                <td align='left' style='color: #475569;'>
                    <strong><?= $sameDueDate ? "Total a renovar ({$serviceCount} servicios que vencen el mismo día):" : "Total estimado de renovación ({$serviceCount} servicios):" ?></strong>
                </td>
                <td align='right' style='font-size: 17px; font-weight: bold; color: #0f172a;'>
                    <?= Yii::$app->formatter->asCurrency($totalRenewal, $currency) ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <!-- Botón de Acción Principal -->
    <div style='text-align: center; margin: 32px 0 20px 0;'>
        <a href='<?=$renewLink?>' style='background-color: <?=$color?>; color: white; padding: 13px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 15px; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
            <?php 
            if ($isMultiple && $sameDueDate) {
                echo ($serviceCount == 2) ? 'Renovar Ambos Servicios Juntos' : 'Renovar Servicios Juntos';
            } elseif ($isMultiple) {
                echo 'Gestionar y Renovar Servicios';
            } else {
                echo 'Renovar Ahora';
            }
            ?>
        </a>
    </div>
    
    <p style='font-size: 12px; color: #94a3b8; text-align: center; margin: 15px 0 0 0;'>
        Si ya realizaste el pago de estos servicios, por favor haz caso omiso de este recordatorio.
    </p>
</div>