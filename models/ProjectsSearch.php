<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Projects;
use Yii;

/**
 * ProjectsSearch represents the model behind the search form of `app\models\Projects`.
 */
class ProjectsSearch extends Projects
{
    public function rules()
    {
        return [
            [['id', 'customer_id', 'is_default', 'status'], 'integer'],
            [['code', 'name', 'business_name', 'document_number', 'address', 'notes', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Projects::find();

        $query->with(['customer', 'workOrders']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Seguridad según rol
        if (!Yii::$app->user->isGuest && !Yii::$app->user->identity->isAdmin) {
            $customerId = Yii::$app->user->identity->getRealCustomerId() ?: -1;
            $query->andWhere(['customer_id' => $customerId]);
        } else {
            if ($this->customer_id) {
                $query->andFilterWhere(['customer_id' => $this->customer_id]);
            }
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'is_default' => $this->is_default,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'code', $this->code])
            ->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'business_name', $this->business_name])
            ->andFilterWhere(['like', 'document_number', $this->document_number])
            ->andFilterWhere(['like', 'address', $this->address])
            ->andFilterWhere(['like', 'notes', $this->notes]);

        return $dataProvider;
    }
}
