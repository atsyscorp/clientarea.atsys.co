<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Orders;

class OrdersSearch extends Orders
{
    public function rules()
    {
        return [
            [['id', 'customer_id', 'status'], 'integer'],
            [['code', 'currency', 'payment_method', 'transaction_ref', 'created_at', 'updated_at'], 'safe'],
            [['subtotal', 'tax', 'total', 'exchange_rate', 'total_usd'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Orders::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'payment_method', $this->payment_method])
            ->andFilterWhere(['like', 'transaction_ref', $this->transaction_ref]);

        return $dataProvider;
    }
}
