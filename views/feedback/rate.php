<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\ServiceFeedback */

$this->title = 'Encuesta de Satisfacción';
?>

<div class="feedback-create container" style="max-width: 600px; margin-top: 20px;">
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
        </div>
        <div class="card-body">
            
            <?php if (Yii::$app->session->hasFlash('success')): ?>
            <?php else: ?>

                <p class="text-muted">Tu opinión es muy importante para nosotros.</p>

                <?php $form = ActiveForm::begin(); ?>

                <?= $form->field($model, 'rating_service')->radioList([
                    1 => '★ 1 - Malo',
                    2 => '★ 2 - Regular',
                    3 => '★ 3 - Normal',
                    4 => '★ 4 - Bueno',
                    5 => '★ 5 - Excelente'
                ], ['itemOptions' => ['class' => 'radio-inline']]) ?>

                <?= $form->field($model, 'nps_score')->dropDownList(
                    array_combine(range(0, 10), range(0, 10)),
                    ['prompt' => 'Selecciona un valor (0=Nada probable, 10=Muy probable)']
                ) ?>

                <?= $form->field($model, 'effort_score')->radioList([
                    1 => 'Muy difícil',
                    2 => 'Difícil',
                    3 => 'Normal',
                    4 => 'Fácil',
                    5 => 'Muy fácil'
                ]) ?>

                <?= $form->field($model, 'is_resolved')->checkbox(['label' => '¿Pudimos resolver tu problema completamente?']) ?>

                <?= $form->field($model, 'comments')->textarea(['rows' => 4, 'placeholder' => 'Cuéntanos más (opcional)...']) ?>
                
                <?php if($model->ticket_id): ?>
                    <?= $form->field($model, 'ticket_id')->hiddenInput()->label(false) ?>
                <?php endif; ?>

                <div class="form-group text-center">
                    <?= Html::submitButton('Enviar Calificación', ['class' => 'btn btn-success btn-lg btn-block']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            <?php endif; ?>
        </div>
    </div>
</div>