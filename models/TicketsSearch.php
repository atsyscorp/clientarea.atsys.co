<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Tickets;

class TicketsSearch extends Tickets
{
    public function rules()
    {
        return [
            // AQUÍ ESTABA EL ERROR: Cambiado user_id por customer_id
            [['id', 'status', 'customer_id'], 'integer'],
            [['ticket_code', 'subject', 'email', 'source', 'created_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Tickets::find();

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
            'status' => $this->status,
            'customer_id' => $this->customer_id, // CORREGIDO AQUÍ TAMBIÉN
        ]);

        $query->andFilterWhere(['like', 'ticket_code', $this->ticket_code])
            ->andFilterWhere(['like', 'subject', $this->subject])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'source', $this->source]);

        return $dataProvider;
    }
}