<?php

use app\models\Customers;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\CustomersSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Clientes';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="customers-index">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= Html::encode($this->title) ?></h1>
        <?= Html::a(
            '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 mr-1"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg> Nuevo cliente', 
            ['create'], ['class' => 'btn btn-primary text-white shadow-lg']) ?>
    </div>

    <div class="overflow-x-auto w-full bg-base-100 shadow-xl rounded-box border border-base-200">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            // 'filterModel' => $searchModel, // Descomenta si quieres los filtros inputs arriba
            'tableOptions' => ['class' => 'table table-zebra w-full'], // Clases de DaisyUI
            'summary' => '<div class="p-4 text-sm text-base-content/70">Mostrando <b>{begin}-{end}</b> de <b>{totalCount}</b> clientes.</div>',
            'layout' => "{items}\n{summary}\n{pager}",
            'pager' => [
                'options' => ['class' => 'join mt-4 justify-center w-full'],
                'linkOptions' => ['class' => 'join-item btn btn-sm'],
                'disabledListItemSubTagOptions' => ['class' => 'join-item btn btn-sm btn-disabled'],
                'activePageCssClass' => 'btn-active btn-primary text-white',
            ],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],

                // COLUMNA 1: DOCUMENTO
                [
                    'attribute' => 'document_number',
                    'label' => 'Identificación',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'w-24'], // Ancho fijo opcional
                    'value' => function ($model) {
                        return '<div class="font-bold text-gray-700">' . Html::encode($model->document_number) . '</div>' .
                            '<div class="text-xs uppercase text-gray-400 font-semibold">' . Html::encode($model->document_type) . '</div>';
                    },
                ],

                // COLUMNA 2: CLIENTE / EMPRESA (Lógica Corregida)
                [
                    'label' => 'Cliente / Empresa',
                    'format' => 'raw',
                    'value' => function ($model) {
                        // Lógica:
                        // 1. Si tiene Nombre Comercial, ese es el título principal. Badge = Razón Social.
                        // 2. Si solo tiene Razón Social, ese es el título. Badge = vacío.
                        // 3. Si no tiene ninguno (Persona), Nombre Contacto es el título. Badge = 'Particular'.
                        
                        $mainName = $model->trade_name ?: $model->business_name;
                        $badge = '';

                        if ($model->trade_name && $model->business_name && $model->trade_name !== $model->business_name) {
                            $badge = $model->business_name;
                        }

                        // Si es persona natural (sin datos de empresa)
                        if (empty($mainName)) {
                            $mainName = $model->contact_name;
                            $badge = 'Particular'; // O puedes dejarlo vacío si prefieres
                        }

                        $html = '<div class="font-bold text-base text-gray-800">' . Html::encode($mainName) . '</div>';
                        
                        if (!empty($badge)) {
                            $html .= '<span class="badge badge-ghost badge-sm text-xs mt-1">' . Html::encode($badge) . '</span>';
                        }
                        
                        return $html;
                    },
                ],

                // COLUMNA 3: CONTACTO (Solo si es diferente al cliente principal para no repetir visualmente)
                [
                    'attribute' => 'contact_name',
                    'label' => 'Contacto',
                    'format' => 'raw',
                    'value' => function ($model) {
                        // Si el nombre del contacto ya se mostró como nombre principal, no repetirlo tanto
                        $nameDisplay = Html::encode($model->contact_name);
                        $posDisplay = $model->contact_position ? '<div class="text-xs text-gray-500">' . Html::encode($model->contact_position) . '</div>' : '';

                        return '<div>
                                    <div class="font-semibold text-sm">' . $nameDisplay . '</div>
                                    ' . $posDisplay . '
                                </div>';
                    }
                ],

                // COLUMNA 4: COMUNICACIÓN
                [
                    'label' => 'Comunicación',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $html = '';
                        if ($model->email) {
                            // Usamos text-blue-600 para que parezca enlace
                            $html .= '<div class="flex items-center gap-2 text-sm mb-1 text-blue-600 truncate" title="'.$model->email.'">
                                        <i class="fas fa-envelope text-xs opacity-70"></i> ' . Html::encode($model->email) . 
                                    '</div>';
                        }
                        if ($model->primary_phone) {
                            $html .= '<div class="flex items-center gap-2 text-sm text-gray-600">
                                        <i class="fas fa-phone text-xs opacity-70"></i> ' . Html::encode($model->primary_phone) . 
                                    '</div>';
                        }
                        return $html;
                    }
                ],

                // COLUMNA 5: ESTADO
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'text-center'],
                    'headerOptions' => ['class' => 'text-center'],
                    'value' => function ($model) {
                        $colors = [
                            'active' => 'badge-success text-white',
                            'inactive' => 'badge-error text-white',
                            'prospect' => 'badge-warning text-white',
                        ];
                        $labels = [
                            'active' => 'Activo',
                            'inactive' => 'Inactivo',
                            'prospect' => 'Prospecto',
                        ];
                        
                        $colorClass = $colors[$model->status] ?? 'badge-ghost';
                        $labelText = $labels[$model->status] ?? $model->status;

                        return "<span class='badge {$colorClass} badge-sm font-semibold'>{$labelText}</span>";
                    },
                ],

                // COLUMNA 6: ACCIONES (MANUAL) - Aquí arreglamos los íconos
                [
                    'label' => 'Acciones',
                    'format' => 'raw',
                    'contentOptions' => ['class' => 'text-right'], 
                    'value' => function ($model) {
                        
                        // SVG ICONO VER (Ojo)
                        $iconView = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>';

                        // SVG ICONO EDITAR (Lápiz)
                        $iconEdit = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>';

                        // SVG ICONO ELIMINAR (Basura)
                        $iconDelete = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>';

                        // Botón Ver
                        $btnView = Html::a($iconView, ['view', 'id' => $model->id], [
                            'class' => 'btn btn-square btn-ghost btn-sm text-primary tooltip tooltip-left',
                            'data-tip' => 'Ver Detalle',
                            'title' => 'Ver'
                        ]);

                        // Botón Editar
                        $btnUpdate = Html::a($iconEdit, ['update', 'id' => $model->id], [
                            'class' => 'btn btn-square btn-ghost btn-sm text-info tooltip tooltip-left',
                            'data-tip' => 'Editar',
                            'title' => 'Editar'
                        ]);

                        // Botón Eliminar
                        $btnDelete = Html::a($iconDelete, ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-square btn-ghost btn-sm text-error tooltip tooltip-left',
                            'data-confirm' => '¿Estás seguro de eliminar este cliente?',
                            'data-method' => 'post',
                            'data-tip' => 'Eliminar',
                            'title' => 'Eliminar'
                        ]);

                        return '<div class="flex justify-end gap-1">' . $btnView . $btnUpdate . $btnDelete . '</div>';
                    },
                ],
            ],
        ]); ?>
    </div>
</div>