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
            [['id', 'rating_service', 'nps_score', 'effort_score', 'is_resolved'], 'integer'],
            [['ticket_id', 'client_email', 'ip_address', 'comments', 'created_at'], 'safe'],
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
            'pagination' => [
                'pageSize' => 20,
            ],
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
            'rating_service' => $this->rating_service,
            'nps_score' => $this->nps_score,
            'effort_score' => $this->effort_score,
            'is_resolved' => $this->is_resolved,
        ]);

        // Filtros de texto parcial y código de ticket
        $query->andFilterWhere(['like', 'ticket_id', $this->ticket_id])
              ->andFilterWhere(['like', 'client_email', $this->client_email])
              ->andFilterWhere(['like', 'ip_address', $this->ip_address])
              ->andFilterWhere(['like', 'comments', $this->comments])
              ->andFilterWhere(['like', 'created_at', $this->created_at]);

        return $dataProvider;
    }
}