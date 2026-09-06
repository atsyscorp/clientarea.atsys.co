<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Mis Dominios';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="domains-index">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="overflow-x-auto bg-base-100 shadow-xl rounded-box">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-zebra w-full'],
            'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> dominios.</div>',
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'attribute' => 'domain',
                    'label' => 'Dominio',
                    'value' => function($model) {
                        return $model->domain ?: 'Sin asignar';
                    }
                ],
                [
                    'attribute' => 'next_due_date',
                    'label' => 'Próxima Renovación',
                    'format' => 'date',
                ],
                [
                    'label' => 'Estado',
                    'format' => 'raw',
                    'value' => function($model) {
                        return $model->getStatusHtml();
                    }
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'header' => 'Acciones',
                    'template' => '{calendar} {manage}',
                    'buttons' => [
                        'calendar' => function ($url, $model) {
                            return Yii::$app->controller->renderPartial('/customer-services/_add_to_calendar', [
                                'model' => $model,
                                'btnClass' => 'btn btn-xs btn-ghost border border-base-300',
                                'dropdownDirection' => 'dropdown-end'
                            ]);
                        },
                        'manage' => function ($url, $model) {
                            return Html::a('Gestionar', ['manage', 'id' => $model->id], [
                                'class' => 'btn btn-sm btn-outline btn-primary'
                            ]);
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>
