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
            [['customer_id'], 'integer'],
            [['ticket_code', 'department', 'priority', 'subject', 'email', 'source', 'status', 'updated_at'], 'safe'],
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
            'sort' => ['defaultOrder' => ['updated_at' => SORT_DESC]],
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filtro parcial (Búsqueda de texto)
        $query->andFilterWhere(['like', 'ticket_code', $this->ticket_code]);
        $query->andFilterWhere([
            'status' => $this->status,
            'customer_id' => $this->customer_id,
            'department' => $this->department,
        ]);

        $query->andFilterWhere(['like', 'ticket_code', $this->ticket_code])
            ->andFilterWhere(['like', 'subject', $this->subject])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'source', $this->source]);

        return $dataProvider;
    }
}