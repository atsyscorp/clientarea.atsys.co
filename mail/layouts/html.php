<?php
use yii\helpers\Html;

/* @var \yii\web\View $this view component instance */
/* @var \yii\mail\MessageInterface $message the message being composed */
/* @var string $content main view render result */
?>
<?php $this->beginPage() ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= Yii::$app->charset ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4;">
    <?php $this->beginBody() ?>
    
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width:600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; font-family: Arial, sans-serif; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    
                    <tr>
                        <td style="padding: 20px; text-align:left; border-bottom: 1px solid #eeeeee;">
                            <img src="https://static.atsys.co/img/email/atsys-email-customer-tpl.png" alt="Logo ATSYS" style="max-height: 50px; display: block;" />
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="padding: 30px 20px; color: #333333; line-height: 1.6;">
                            <?= $content ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <td style="background-color: #333; color: #ffffff; padding: 15px 20px; text-align: center; font-size: 12px;">
                            <p style="margin: 0;">&copy; <?= date('Y') ?> ATSYS - Trascendemos.</p>
                            <p style="margin: 5px 0 0 0; color: #cccccc;">Este es un mensaje automático, por favor no responder directamente a menos que se indique.</p>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
        </tr>
    </table>
    
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>