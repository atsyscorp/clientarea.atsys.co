<?php

namespace app\widgets;

use yii\base\Widget;
use yii\helpers\Html;

/**
 * Pinta el badge de estado de un modelo a partir de su mapa canónico.
 *
 * Evita que cada vista defina su propia tabla de estado a color, que era el
 * origen de que un mismo estado saliera de distinto color según la pantalla.
 *
 * Uso con un modelo que expone getStatusBadge():
 *
 *     StatusBadge::widget(['model' => $ticket])
 *     StatusBadge::widget(['model' => $ticket, 'size' => 'sm'])
 *
 * Uso con un estado suelto (arrays de GridView, datos crudos de consulta):
 *
 *     StatusBadge::widget([
 *         'status' => $row['status'],
 *         'map'    => Tickets::statusBadgeMap(),
 *     ])
 */
class StatusBadge extends Widget
{
    /**
     * @var object|null Modelo con el método getStatusBadge().
     */
    public $model;

    /**
     * @var string|null Estado suelto. Se ignora si se pasa $model.
     */
    public $status;

    /**
     * @var array Mapa de estado a ['class' => ..., 'label' => ...]. Requerido con $status.
     */
    public $map = [];

    /**
     * @var string|null Tamaño de DaisyUI: xs, sm, md o lg.
     */
    public $size;

    /**
     * @var array Atributos HTML extra para el span.
     */
    public $options = [];

    /**
     * @var array Badge para un estado no contemplado en el mapa.
     */
    public $fallback = ['class' => 'badge-ghost', 'label' => 'Desconocido'];

    public function run()
    {
        $badge = $this->resolveBadge();

        $classes = ['badge', $badge['class']];
        if ($this->size !== null) {
            $classes[] = 'badge-' . $this->size;
        }

        $options = $this->options;
        Html::addCssClass($options, $classes);

        return Html::tag('span', Html::encode($badge['label']), $options);
    }

    /**
     * @return array{class: string, label: string}
     */
    protected function resolveBadge()
    {
        if ($this->model !== null && method_exists($this->model, 'getStatusBadge')) {
            return $this->model->getStatusBadge();
        }

        return $this->map[$this->status] ?? $this->fallback;
    }
}
