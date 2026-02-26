<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\ServiceFeedback;

class ServiceFeedbackSearch extends ServiceFeedback
{
    public function rules()
    {
        return [
            // Definimos qué campos son números y cuáles son texto (safe)
            [['id', 'ticket_id', 'rating_service'], 'integer'],
            [['comments', 'created_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // Bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = ServiceFeedback::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC], // Los más recientes primero
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filtros exactos
        $query->andFilterWhere([
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'rating_service' => $this->rating_service,
        ]);

        // Filtros de texto parcial
        $query->andFilterWhere(['like', 'comments', $this->comments])
              ->andFilterWhere(['like', 'created_at', $this->created_at]);

        return $dataProvider;
    }
}