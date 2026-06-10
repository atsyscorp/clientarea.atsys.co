<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model app\models\TicketSpamBlacklist */

$this->title = 'Filtro de SPAM';
$this->params['breadcrumbs'][] = ['label' => 'Tickets', 'url' => ['/tickets']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ticket-spam-blacklist-index min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
            <p class="text-xs text-gray-400 mt-1 font-semibold">Administra los correos electrónicos bloqueados para la creación automática de tickets.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tarjeta de Registro (Formulario) -->
        <div class="card bg-base-100 shadow-xl border border-base-200 h-fit">
            <div class="card-body p-6">
                <h3 class="card-title text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-ban text-error mr-1"></i> Bloquear Correo
                </h3>

                <?php $form = ActiveForm::begin([
                    'id' => 'spam-blacklist-form',
                    'options' => ['class' => 'space-y-4'],
                ]); ?>

                <?= $form->field($model, 'email', [
                    'options' => ['class' => 'form-control w-full'],
                    'labelOptions' => ['class' => 'label text-sm font-semibold text-gray-600'],
                    'errorOptions' => ['class' => 'text-error text-xs mt-1 font-semibold'],
                ])->textInput([
                    'class' => 'input input-bordered w-full focus:input-primary',
                    'placeholder' => 'ejemplo@spam.com',
                ])->label('Dirección de Correo') ?>

                <?= $form->field($model, 'reason', [
                    'options' => ['class' => 'form-control w-full'],
                    'labelOptions' => ['class' => 'label text-sm font-semibold text-gray-600'],
                    'errorOptions' => ['class' => 'text-error text-xs mt-1 font-semibold'],
                ])->textInput([
                    'class' => 'input input-bordered w-full focus:input-primary',
                    'placeholder' => 'Publicidad masiva, phishing, etc.',
                ])->label('Motivo / Notas') ?>

                <div class="form-control mt-6">
                    <?= Html::submitButton('<i class="fas fa-plus mr-1"></i> Registrar en Lista Negra', [
                        'class' => 'btn btn-primary text-white shadow-md w-full',
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>

        <!-- Tarjeta de Listado (Tabla) -->
        <div class="lg:col-span-2 card bg-base-100 shadow-xl border border-base-200">
            <div class="card-body p-6">
                <h3 class="card-title text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-list text-primary mr-1"></i> Correos Bloqueados
                </h3>

                <div class="overflow-x-auto w-full">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'emptyText' => 'No hay correos registrados en la lista negra.',
                        'summary' => '<div class="text-xs text-gray-400 mb-4 font-semibold">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> correos bloqueados.</div>',
                        'tableOptions' => ['class' => 'table table-zebra table-hover w-full'],
                        'layout' => "{summary}\n{items}\n{pager}",
                        'pager' => [
                            'options' => ['class' => 'join mt-4 justify-center w-full'],
                            'linkOptions' => ['class' => 'join-item btn btn-sm'],
                            'disabledListItemSubTagOptions' => ['class' => 'join-item btn btn-sm btn-disabled'],
                            'activePageCssClass' => 'btn-active btn-primary text-white',
                        ],
                        'columns' => [
                            [
                                'attribute' => 'email',
                                'format' => 'email',
                                'label' => 'Correo Electrónico',
                                'contentOptions' => ['class' => 'font-semibold text-gray-800'],
                            ],
                            [
                                'attribute' => 'reason',
                                'label' => 'Motivo / Notas',
                                'value' => function($model) {
                                    return $model->reason ? Html::encode($model->reason) : Html::tag('span', 'Sin especificar', ['class' => 'text-gray-400 italic']);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'created_at',
                                'label' => 'Fecha de Bloqueo',
                                'value' => function($model) {
                                    return date('d/m/Y H:i', strtotime($model->created_at));
                                },
                                'contentOptions' => ['class' => 'text-xs text-gray-500'],
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{delete}',
                                'header' => 'Acción',
                                'headerOptions' => ['class' => 'text-center w-24'],
                                'contentOptions' => ['class' => 'text-center'],
                                'buttons' => [
                                    'delete' => function ($url, $model) {
                                        return Html::a(
                                            '<i class="fas fa-trash-alt mr-1"></i> Desbloquear',
                                            ['delete', 'id' => $model->id],
                                            [
                                                'class' => 'btn btn-ghost btn-xs text-error hover:bg-error/10 font-bold',
                                                'data' => [
                                                    'confirm' => '¿Estás seguro de que deseas eliminar este correo de la lista negra de SPAM?',
                                                    'method' => 'post',
                                                ],
                                            ]
                                        );
                                    },
                                ],
                            ],
                        ],
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>
