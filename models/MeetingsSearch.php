<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * MeetingsSearch represents the model behind the search form of `app\models\Meetings`.
 */
class MeetingsSearch extends Meetings
{
    public $searchQuery;
    public $filterPeriod; // 'upcoming', 'today', 'past', 'all'

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'customer_id', 'created_by'], 'integer'],
            [['client_name', 'client_email', 'title', 'status', 'start_time', 'end_time', 'searchQuery', 'filterPeriod'], 'safe'],
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
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Meetings::find()->with(['customer', 'creator']);

        // Restringir a clientes si no es administrador (aislamiento estricto por cliente)
        if (!\Yii::$app->user->isGuest && !\Yii::$app->user->identity->isAdmin) {
            $user = \Yii::$app->user->identity;
            $realCustomerId = $user->getRealCustomerId() ?? -1;
            $query->andWhere([
                'or',
                ['customer_id' => $realCustomerId],
                ['client_email' => $user->email],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'start_time' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filtro por período
        if ($this->filterPeriod === 'pending') {
            $query->andWhere(['status' => self::STATUS_PENDING]);
        } elseif ($this->filterPeriod === 'upcoming') {
            $query->andWhere(['>=', 'start_time', date('Y-m-d H:i:s')]);
            $query->andWhere(['!=', 'status', self::STATUS_CANCELED]);
        } elseif ($this->filterPeriod === 'today') {
            $query->andWhere(['between', 'start_time', date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')]);
        } elseif ($this->filterPeriod === 'past') {
            $query->andWhere(['<', 'end_time', date('Y-m-d H:i:s')]);
        }

        // Filtros exactos
        $query->andFilterWhere([
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'status' => $this->status,
        ]);

        // Búsqueda libre
        if (!empty($this->searchQuery)) {
            $query->andWhere([
                'or',
                ['like', 'title', $this->searchQuery],
                ['like', 'client_name', $this->searchQuery],
                ['like', 'client_email', $this->searchQuery],
                ['like', 'description', $this->searchQuery],
            ]);
        }

        $query->andFilterWhere(['like', 'client_name', $this->client_name])
            ->andFilterWhere(['like', 'client_email', $this->client_email])
            ->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }
}
