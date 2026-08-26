<?php

use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string|null $q */

$this->title = 'Gestión de Usuarios';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="users-index">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
    </div>

    <!-- Buscador simple -->
    <div class="bg-base-100 p-4 rounded-box border border-base-200 shadow-sm mb-6 max-w-xl">
        <form method="get" action="" class="flex gap-2">
            <div class="relative w-full">
                <input type="text" name="q" value="<?= Html::encode($q) ?>" placeholder="Buscar por nombre de usuario o correo..." class="input input-bordered w-full pr-10" />
                <?php if (!empty($q)): ?>
                    <a href="/users/" class="absolute inset-y-0 right-0 flex items-center pr-3 text-base-content/50 hover:text-base-content">
                        ✕
                    </a>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary text-white font-semibold">Buscar</button>
        </form>
    </div>

    <div class="overflow-x-auto w-full bg-base-100 shadow-xl rounded-box border border-base-200">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'table table-zebra w-full'],
            'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> usuarios.</div>',
            'layout' => "{items}\n{summary}\n{pager}",
            'pager' => [
                'options' => ['class' => 'join mt-4 justify-center w-full'],
                'linkOptions' => ['class' => 'join-item btn btn-sm'],
                'disabledListItemSubTagOptions' => ['class' => 'join-item btn btn-sm btn-disabled'],
                'activePageCssClass' => 'btn-active btn-primary text-white',
            ],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],

                [
                    'attribute' => 'id',
                    'label' => 'ID',
                    'contentOptions' => ['class' => 'w-16 font-mono text-xs'],
                ],
                [
                    'attribute' => 'username',
                    'label' => 'Nombre de Usuario',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return '<div class="font-bold text-base-content">' . Html::encode($model->username) . '</div>';
                    }
                ],
                [
                    'attribute' => 'email',
                    'label' => 'Correo Electrónico',
                    'format' => 'email',
                ],
                [
                    'attribute' => 'role',
                    'label' => 'Rol',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $roles = [
                            20 => ['label' => 'Administrador', 'class' => 'badge-primary text-white'],
                            10 => ['label' => 'Cliente', 'class' => 'badge-success'],
                            11 => ['label' => 'Subcuenta (Soporte)', 'class' => 'badge-info'],
                            12 => ['label' => 'Delegado Admin (Backup)', 'class' => 'badge-secondary '],
                        ];
                        $roleInfo = $roles[$model->role] ?? ['label' => 'Desconocido (#' . $model->role . ')', 'class' => 'badge-ghost'];
                        return "<span class='badge {$roleInfo['class']} badge-sm font-semibold'>{$roleInfo['label']}</span>";
                    }
                ],
                [
                    'attribute' => 'status',
                    'label' => 'Estado',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $statuses = [
                            10 => ['label' => 'Activo', 'class' => 'badge-success'],
                            9 => ['label' => 'Inactivo', 'class' => 'badge-warning'],
                            0 => ['label' => 'Eliminado', 'class' => 'badge-error'],
                        ];
                        $statusInfo = $statuses[$model->status] ?? ['label' => 'Desconocido', 'class' => 'badge-ghost'];
                        return "<span class='badge {$statusInfo['class']} badge-sm font-semibold'>{$statusInfo['label']}</span>";
                    }
                ],
                [
                    'attribute' => 'mobile',
                    'label' => 'Móvil / WhatsApp',
                    'value' => function ($model) {
                        return $model->mobile ?: 'No registrado';
                    }
                ],
                [
                    'label' => 'Acciones',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'text-right'],
                    'value' => function ($model) {
                        // Evitamos impersonar a uno mismo
                        $btnImpersonate = '';
                        if ($model->id != Yii::$app->user->id) {
                            $iconImpersonate = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3.004-3h-11.25m11.25 0-3-3m3 3-3 3" />
                            </svg>';

                            $btnImpersonate = Html::a($iconImpersonate, ['impersonate', 'id' => $model->id], [
                                'class' => 'btn btn-square btn-ghost btn-sm text-warning tooltip tooltip-left',
                                'data-tip' => 'Ingresar como este usuario',
                                'title' => 'Ingresar como este usuario'
                            ]);
                        }

                        $iconEdit = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>';

                        $btnEdit = Html::a($iconEdit, ['update', 'id' => $model->id], [
                            'class' => 'btn btn-square btn-ghost btn-sm text-info tooltip tooltip-left',
                            'data-tip' => 'Editar Usuario',
                            'title' => 'Editar'
                        ]);

                        return '<div class="flex justify-end gap-1">' . $btnImpersonate . $btnEdit . '</div>';
                    }
                ]
            ]
        ]); ?>
    </div>
</div>
