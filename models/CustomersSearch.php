<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Customers;

/**
 * CustomersSearch represents the model behind the search form of `app\models\Customers`.
 */
class CustomersSearch extends Customers
{
    public $name;
    public $phone;
    /**
     * Reglas para validar lo que escribes en las cajitas de búsqueda.
     * Por lo general, todo es 'safe' (texto) o 'integer' (números).
     */
    public function rules()
    {
        return [
            [['id', 'status'], 'integer'], // Campos numéricos
            [['name', 'email', 'phone', 'created_at'], 'safe'], // Campos de texto
        ];
    }

    /**
     * Bypasseamos scenarios (no es necesario tocar esto).
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * La lógica de búsqueda real.
     */
    public function search($params)
    {
        $query = Customers::find();

        // Configuración del Paginador y Ordenamiento
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 10], // Opcional
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]], // Opcional
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // --- FILTROS EXACTOS (Números, IDs, Estados) ---
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        // --- FILTROS PARCIALES (Texto, LIKE %...%) ---
        $query->andFilterWhere(['like', 'email', $this->email])
              ->andFilterWhere(['or', 
              ['like', 'primary_phone', $this->phone],
              ['like', 'secondary_phone', $this->phone]
        ]);

        if (!empty($this->name)) {
            $query->andFilterWhere(['or',
                ['like', 'business_name', $this->name],
                ['like', 'trade_name', $this->name],
            ]);
        }

        return $dataProvider;
    }
}