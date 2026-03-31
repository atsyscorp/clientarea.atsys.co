<?php
use yii\helpers\Html;

/* @var $userName string */
/* @var $titularName string */
?>
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333;">
    <h2 style="color: #d9534f;">Aviso de revocación de acceso</h2>
    
    <p>Hola, <strong><?= Html::encode($userName) ?></strong>:</p>
    
    <p>Te informamos que tu acceso como delegado en la plataforma de soporte de <strong>ATSYS</strong> ha sido revocado por el administrador de la cuenta (<?= Html::encode($titularName) ?>).</p>
    
    <div style="background-color: #fcf8f2; border-left: 4px solid #f0ad4e; padding: 15px; margin: 20px 0;">
        <p style="margin: 0;">A partir de este momento, tus credenciales de ingreso ya no se encuentran activas y no podrás acceder al área de clientes.</p>
    </div>

    <p>Si consideras que esto es un error, por favor ponte en contacto directamente con el responsable de tu cuenta.</p>
</div>