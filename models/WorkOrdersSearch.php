<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\WorkOrders;
use Yii;

/**
 * WorkOrdersSearch represents the model behind the search form of `app\models\WorkOrders`.
 */
class WorkOrdersSearch extends WorkOrders
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'customer_id', 'project_id', 'status', 'has_service_contract'], 'integer'],
            [['code', 'title', 'requirements', 'notes', 'created_at'], 'safe'],
            [['total_cost'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = WorkOrders::find();
        
        // Optimización: Cargar datos del cliente y proyecto de una vez
        $query->with(['customer', 'project']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]], // Las más recientes primero
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // --- LÓGICA DE SEGURIDAD (CRÍTICO) ---
        // Si NO es admin, solo ver SUS propias órdenes
        if (!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin) {
            
            // Opción B (Si usas tabla separada): Descomenta si usas la relación user_id
            $customerId = Yii::$app->user->identity->getRealCustomerId() ?: -1;
            $query->andWhere(['customer_id' => $customerId]);
        }
        // -------------------------------------

        // Filtros exactos
        $query->andFilterWhere([
            'id' => $this->id,
            'project_id' => $this->project_id,
            'status' => $this->status,
            'has_service_contract' => $this->has_service_contract,
            'total_cost' => $this->total_cost,
            'created_at' => $this->created_at,
        ]);


        // Si es Admin, permitimos filtrar por cliente específico desde el Grid
        if (Yii::$app->user->identity->isAdmin) {
            $query->andFilterWhere(['customer_id' => $this->customer_id]);
        }

        // Búsqueda parcial de texto
        $query->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'requirements', $this->requirements]);

        return $dataProvider;
    }
}