<?php
use yii\helpers\Html;

/* @var $user app\models\User */
/* @var $notifications app\models\Notifications[] */

$loginLink = Yii::$app->urlManager->createAbsoluteUrl(['/site/login']);
$totalNotifications = count($notifications);

// Mapeo de estilos según el tipo de notificación
$badgeStyles = [
    'info' => ['bg' => '#e0e7ff', 'color' => '#3730a3', 'label' => 'Información'],
    'success' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Éxito'],
    'warning' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Advertencia'],
    'danger' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Importante'],
    'promo' => ['bg' => '#f3e8ff', 'color' => '#6b21a8', 'label' => 'Especial'],
];
?>
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1f2937; line-height: 1.6; background-color: #f9fafb; padding: 20px; border-radius: 8px;">
    <!-- Header -->
    <div style="background-color: #4F46E5; padding: 24px 20px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="color: #ffffff; margin: 0; font-size: 22px; font-weight: bold;">
            🔔 Resumen de Novedades Pendientes
        </h1>
        <p style="color: #c7d2fe; margin: 8px 0 0 0; font-size: 14px;">
            Tienes <?= $totalNotifications ?> <?= $totalNotifications === 1 ? 'notificación no leída' : 'notificaciones no leídas' ?> en tu plataforma
        </p>
    </div>

    <!-- Main Content -->
    <div style="background-color: #ffffff; padding: 25px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <p style="font-size: 15px; margin-top: 0;">
            Hola <strong><?= Html::encode($user->username ?? 'Cliente') ?></strong>,
        </p>
        <p style="font-size: 14px; color: #4b5563; margin-bottom: 24px;">
            Te compartimos un resumen con las últimas actualizaciones y novedades registradas en tu área de cliente que aún no has revisado:
        </p>

        <!-- Notification List -->
        <div style="margin-bottom: 25px;">
            <?php foreach ($notifications as $index => $notif): ?>
                <?php
                $style = $badgeStyles[$notif->type] ?? $badgeStyles['info'];
                $itemLink = $notif->link ? (str_starts_with($notif->link, 'http') ? $notif->link : Yii::$app->urlManager->createAbsoluteUrl([$notif->link])) : null;
                ?>
                <div style="border-left: 4px solid #4F46E5; background-color: #f8fafc; padding: 16px; margin-bottom: 12px; border-radius: 4px; border: 1px solid #e2e8f0; border-left-width: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 11px; font-weight: bold; background-color: <?= $style['bg'] ?>; color: <?= $style['color'] ?>; padding: 3px 8px; border-radius: 12px; text-transform: uppercase;">
                            <?= $style['label'] ?>
                        </span>
                        <span style="font-size: 12px; color: #94a3b8;">
                            <?= date('d/m/Y H:i', strtotime($notif->created_at)) ?>
                        </span>
                    </div>
                    <h4 style="margin: 8px 0 4px 0; font-size: 15px; color: #0f172a;">
                        <?= Html::encode($notif->title) ?>
                    </h4>
                    <p style="margin: 0 0 10px 0; font-size: 13px; color: #475569; white-space: pre-line;">
                        <?= Html::encode($notif->body) ?>
                    </p>
                    <?php if ($itemLink): ?>
                        <div style="margin-top: 8px;">
                            <a href="<?= $itemLink ?>" style="font-size: 13px; color: #4F46E5; font-weight: bold; text-decoration: underline;">
                                Ver esta novedad &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA Button -->
        <div style="text-align: center; margin: 30px 0 15px 0;">
            <a href="<?= $loginLink ?>" style="background-color: #4F46E5; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 14px;">
                Ingresar al Área de Clientes
            </a>
        </div>

        <p style="font-size: 12px; color: #9ca3af; text-align: center; margin-top: 20px; border-top: 1px solid #f3f4f6; padding-top: 15px;">
            Este es un correo automático de resumen enviado únicamente si tienes notificaciones sin leer.
        </p>
    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 15px; font-size: 12px; color: #6b7280;">
        <p style="margin: 0;">&copy; <?= date('Y') ?> <?= Html::encode(Yii::$app->name) ?>. Todos los derechos reservados.</p>
    </div>
</div>
