<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Contracts;
use Yii;

/**
 * ContractsSearch represents the model behind the search form of `app\models\Contracts`.
 */
class ContractsSearch extends Contracts
{
    public function rules()
    {
        return [
            [['id', 'customer_id', 'status', 'progress_mode'], 'integer'],
            [['code', 'title', 'description', 'currency', 'start_date', 'end_date', 'created_at'], 'safe'],
            [['total_amount', 'progress_percentage'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Contracts::find();

        $query->with(['customer', 'workOrders', 'tasks']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filtro de seguridad para clientes
        if (!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin) {
            $customerId = Yii::$app->user->identity->getRealCustomerId() ?: -1;
            $query->andWhere(['customer_id' => $customerId]);
            $query->andWhere(['!=', 'status', Contracts::STATUS_DRAFT]);
        } else {
            if ($this->customer_id) {
                $query->andFilterWhere(['customer_id' => $this->customer_id]);
            }
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'progress_mode' => $this->progress_mode,
            'total_amount' => $this->total_amount,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);

        $query->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'currency', $this->currency]);

        return $dataProvider;
    }
}
