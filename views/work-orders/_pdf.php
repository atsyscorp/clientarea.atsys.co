<?php
/* @var $model app\models\WorkOrders */
use yii\helpers\HtmlPurifier;
// 1. Obtenemos el texto
$requirements = $model->requirements;

// 2. DETECCIÓN HÍBRIDA (Igual que en los tickets)
// Si es una orden de trabajo antigua (texto plano), le aplicamos nl2br.
// Si es nueva (TinyMCE), dejamos que HtmlPurifier haga su trabajo.
if (strpos($requirements, '<p') === false && strpos($requirements, '<br') === false && strpos($requirements, '<ul') === false) {
    // Escapamos por seguridad y convertimos saltos de línea
    $requirements = nl2br(\yii\helpers\Html::encode($requirements));
} else {
    // Es HTML de TinyMCE: lo purificamos para el PDF
    $requirements = HtmlPurifier::process($requirements, function ($config) {
        // Permitimos etiquetas estructurales Y atributos como 'style', 'width' y 'border' (vitales para tablas de TinyMCE)
        $config->set('HTML.Allowed', 'p[style],span[style],b,strong,i,em,u,ul,ol,li,br,h1[style],h2[style],h3,h4,h5,h6,table[border|width|style],tr,td[style|colspan|rowspan],th[style|colspan|rowspan],tbody,thead');
    });
}
?>

<table class="header-table">
    <tr>
        <td width="60%">
            <div class="company-name">
                <img src="<?= Yii::getAlias('@webroot') . '/images/atsys-logo-src-clear-2026.png' ?>" alt="ATSYS" class="company-logo">
            </div>
            <div class="company-slogan">Trascendemos</div>
        </td>
        <td width="40%" align="right">
            <div class="doc-title">Orden de Trabajo</div>
            <div class="doc-meta">
                <strong>Código:</strong> <?= $model->code ?><br>
                <strong>Fecha:</strong>
                <?= Yii::$app->formatter->asDate($model->created_at, 'long') ?>
            </div>
        </td>
    </tr>
</table>

<table class="info-table">
    <tr>
        <td class="info-cell">
            <div class="box">
                <div class="box-title">Cliente</div>
                <div class="box-content">
                    <div class="box-row">
                        <span class="box-label">NOMBRE / RAZÓN SOCIAL:</span><br>
                        <?= strtoupper($model->customer->business_name) ?>
                    </div>

                    <div class="box-row">
                        <span class="box-label">DOCUMENTO:</span>
                        <?= $model->customer->document_number ?? 'No registrado' ?>
                    </div>

                    <div class="box-row">
                        <span class="box-label">EMAIL:</span>
                        <?= $model->customer->email ?>
                    </div>
                </div>
            </div>
        </td>

        <td class="spacer-cell"></td>

        <td class="info-cell">
            <div class="box">
                <div class="box-title">Proveedor de Servicios</div>
                <div class="box-content">
                    <div class="box-row">
                        <span class="box-label">EMPRESA:</span><br>
                        Arkitech Systems SAS
                    </div>

                    <div class="box-row">
                        <span class="box-label">NIT / RUT:</span>
                        900.005.699-9
                    </div>

                    <div class="box-row">
                        <span class="box-label">WEB:</span>
                        https://atsys.co
                    </div>
                </div>
            </div>
        </td>
    </tr>
</table>

<div class="section-header">DETALLE DEL SERVICIO</div>

<div class="project-title">
    <?= strtoupper($model->title) ?>
</div>

<div class="requirements-text">
    <?= $requirements; ?>
</div>

<?php if (!empty($model->notes)): ?>
    <div class="notes-box">
        <strong>Notas y Condiciones:</strong><br>
        <?= nl2br($model->notes) ?>
    </div>
<?php endif; ?>

<table class="total-table">
    <tr>
        <td width="70%"></td>
        <td width="30%" align="right">
            <div class="total-label">INVERSIÓN TOTAL</div>
            <div class="total-amount">
                <?= Yii::$app->formatter->asCurrency($model->total_cost) ?>
            </div>
        </td>
    </tr>
</table>

<?php if ($model->status == 2): ?>
    <div style="text-align:center; margin-top:40px;">
        <div style="
            display:inline-block;
            border:3px solid #10b981;
            color:#10b981;
            padding:12px 24px;
            font-weight:bold;
            font-size:16px;
            border-radius:8px;
            transform:rotate(-3deg);
            opacity:0.85;
        ">
            APROBADO DIGITALMENTE<br>
            <span style="font-size:10px;">
                Fecha: <?= Yii::$app->formatter->asDatetime($model->updated_at) ?>
            </span>
        </div>
    </div>
<?php endif; ?>
