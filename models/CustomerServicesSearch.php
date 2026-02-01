<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\CustomerServices;

/**
 * CustomerServicesSearch representa el modelo detrás del formulario de búsqueda.
 */
class CustomerServicesSearch extends CustomerServices
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'customer_id', 'product_id', 'status'], 'integer'],
            [['description_label', 'domain', 'username_service', 'start_date', 'next_due_date', 'created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Crea una instancia de DataProvider con la consulta de búsqueda aplicada.
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = CustomerServices::find();

        // Cargar también las relaciones para evitar "N+1 queries" (optimización)
        $query->with(['customer', 'product']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['next_due_date' => SORT_ASC]], // Ordenar por vencimiento por defecto
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if (!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin) {
            $query->andWhere(['customer_id' => Yii::$app->user->identity->customer->id]);
        }

        // Filtros exactos
        $query->andFilterWhere([
            'id' => $this->id,
            //'customer_id' => $this->customer_id,
            'product_id' => $this->product_id,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'next_due_date' => $this->next_due_date,
        ]);

        if (Yii::$app->user->identity->isAdmin) {
            $query->andFilterWhere(['customer_id' => $this->customer_id]);
        }

        // Filtros parciales (LIKE)
        $query->andFilterWhere(['like', 'description_label', $this->description_label])
            ->andFilterWhere(['like', 'domain', $this->domain])
            ->andFilterWhere(['like', 'username_service', $this->username_service]);

        return $dataProvider;
    }
}