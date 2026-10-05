<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * TodosSearch represents the model behind the search form of `app\models\Todos`.
 */
class TodosSearch extends Todos
{
    public $q;
    public $overdue;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'customer_id', 'assigned_to', 'created_by', 'overdue'], 'integer'],
            [['title', 'priority', 'status', 'due_date', 'q'], 'safe'],
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
        $query = Todos::find()->with(['customer', 'assignedToUser', 'createdByUser', 'checklistItems']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Exact filters
        $query->andFilterWhere([
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'assigned_to' => $this->assigned_to,
            'status' => $this->status,
            'priority' => $this->priority,
        ]);

        if (!empty($this->q)) {
            $query->andWhere([
                'or',
                ['like', 'title', $this->q],
                ['like', 'description', $this->q],
            ]);
        }

        if ($this->overdue == 1) {
            $query->andWhere(['not in', 'status', [Todos::STATUS_COMPLETED, Todos::STATUS_CANCELLED]])
                  ->andWhere(['not', ['due_date' => null]])
                  ->andWhere(['<', 'due_date', date('Y-m-d H:i:s')]);
        }

        return $dataProvider;
    }
}
