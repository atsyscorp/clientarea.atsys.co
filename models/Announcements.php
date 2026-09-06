<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "announcements".
 *
 * @property int $id
 * @property string|null $type
 * @property string $title
 * @property string $content
 * @property int|null $is_active
 * @property string|null $created_at
 * @property string|null $expires_at
 * @property int|null $created_by
 */
class Announcements extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_DANGER = 'danger';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'announcements';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['expires_at', 'created_by'], 'default', 'value' => null],
            [['type'], 'default', 'value' => 'info'],
            [['is_active'], 'default', 'value' => 1],
            [['is_pinned'], 'default', 'value' => 0],
            [['type', 'content'], 'string'],
            [['title', 'content'], 'required'],
            [['is_active', 'is_pinned', 'created_by'], 'integer'],
            [['created_at', 'expires_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['youtube_url'], 'string', 'max' => 255],
            [['youtube_url'], 'url'],
            ['type', 'in', 'range' => array_keys(self::optsType())],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'title' => 'Title',
            'content' => 'Content',
            'youtube_url' => 'Video (YouTube URL)',
            'is_active' => 'Is Active',
            'is_pinned' => 'Fijar en la parte superior',
            'created_at' => 'Created At',
            'expires_at' => 'Expires At',
            'created_by' => 'Created By',
        ];
    }


    /**
     * column type ENUM value labels
     * @return string[]
     */
    public static function optsType()
    {
        return [
            self::TYPE_INFO => 'info',
            self::TYPE_SUCCESS => 'success',
            self::TYPE_WARNING => 'warning',
            self::TYPE_DANGER => 'danger',
        ];
    }

    /**
     * @return string
     */
    public function displayType()
    {
        return self::optsType()[$this->type];
    }

    /**
     * @return bool
     */
    public function isTypeInfo()
    {
        return $this->type === self::TYPE_INFO;
    }

    public function setTypeToInfo()
    {
        $this->type = self::TYPE_INFO;
    }

    /**
     * @return bool
     */
    public function isTypeSuccess()
    {
        return $this->type === self::TYPE_SUCCESS;
    }

    public function setTypeToSuccess()
    {
        $this->type = self::TYPE_SUCCESS;
    }

    /**
     * @return bool
     */
    public function isTypeWarning()
    {
        return $this->type === self::TYPE_WARNING;
    }

    public function setTypeToWarning()
    {
        $this->type = self::TYPE_WARNING;
    }

    /**
     * @return bool
     */
    public function isTypeDanger()
    {
        return $this->type === self::TYPE_DANGER;
    }

    public function setTypeToDanger()
    {
        $this->type = self::TYPE_DANGER;
    }

    // Helper para verificar si está vigente (No ha expirado)
    public static function findActive()
    {
        return static::find()
            ->where(['is_active' => 1])
            ->andWhere(['OR', ['expires_at' => null], ['>=', 'expires_at', date('Y-m-d H:i:s')]]);
    }

    /**
     * Relación con las Vistas
     */
    public function getViews()
    {
        return $this->hasMany(AnnouncementViews::class, ['announcement_id' => 'id']);
    }

    /**
     * Relación con las Reacciones
     */
    public function getReactions()
    {
        return $this->hasMany(AnnouncementReactions::class, ['announcement_id' => 'id']);
    }

    /**
     * Relación con los Comentarios
     */
    public function getComments()
    {
        return $this->hasMany(AnnouncementComments::class, ['announcement_id' => 'id']);
    }

    /**
     * Helper: Cuenta total de vistas (para no traer todos los registros si solo quieres el número)
     */
    public function getViewsCount()
    {
        return $this->getViews()->count();
    }
    
    /**
     * Helper: Obtener usuarios que dieron Like/Love/etc
     */
    public function getReactors()
    {
        // Esto te devolvería los IDs de usuarios que reaccionaron
        return $this->getReactions()->select('user_id')->column();
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Si es urgente (danger), automáticamente debe fijarse arriba
        if ($this->type === self::TYPE_DANGER) {
            $this->is_pinned = 1;
        }

        return true;
    }

    /**
     * Devuelve el contenido con saltos de línea y detecta URLs convirtiéndolas en enlaces <a>.
     * Si la URL es externa se abre en nueva pestaña (_blank), si es del mismo host en la misma pestaña (_self).
     *
     * @return string
     */
    public function getFormattedContent()
    {
        $content = \yii\helpers\Html::decode($this->content);

        // Detectar si el texto contiene HTML estructurado (p.ej. tags p, div, br)
        $hasStructure = preg_match('/<(?:p|div|br|ul|ol|table|h[1-6])\b[^>]*>/i', $content);

        $formatted = self::formatUrls($content);

        if (!$hasStructure) {
            $formatted = nl2br($formatted);
        }

        return $formatted;
    }

    /**
     * Convierte URLs de texto plano en enlaces HTML respetando la procedencia (interna o externa).
     *
     * @param string $content
     * @return string
     */
    public static function formatUrls($content)
    {
        if (empty($content)) {
            return '';
        }

        $currentHost = '';
        if (\Yii::$app->has('request') && !\Yii::$app->request->isConsoleRequest) {
            $currentHost = \Yii::$app->request->serverName ?? '';
            if (empty($currentHost)) {
                $currentHost = parse_url(\Yii::$app->request->hostInfo, PHP_URL_HOST);
            }
        }

        // Expresión regular para no re-reemplazar etiquetas <a> ya existentes
        $pattern = '~(?><a\b[^>]*>.*?</a>)|(?<url>(?:https?://|www\.)[^\s<"\'\)\(\]]+)~is';

        return preg_replace_callback($pattern, function ($matches) use ($currentHost) {
            if (empty($matches['url'])) {
                return $matches[0];
            }

            $rawUrl = $matches['url'];
            $cleanUrl = $rawUrl;

            // Quitar puntuación final (puntos, comas, etc.) al final de la URL
            $trailingPunctuation = '';
            if (preg_match('/[.,;:!]+$/', $cleanUrl, $pMatch)) {
                $trailingPunctuation = $pMatch[0];
                $cleanUrl = substr($cleanUrl, 0, -strlen($trailingPunctuation));
            }

            $href = $cleanUrl;
            if (strpos(strtolower($href), 'www.') === 0) {
                $href = 'https://' . $href;
            }

            $linkHost = parse_url($href, PHP_URL_HOST);
            $isExternal = true;

            if (!empty($currentHost) && !empty($linkHost)) {
                $currentHostLower = strtolower($currentHost);
                $linkHostLower = strtolower($linkHost);
                if ($linkHostLower === $currentHostLower || (strlen($linkHostLower) > strlen($currentHostLower) && substr($linkHostLower, -strlen('.' . $currentHostLower)) === '.' . $currentHostLower)) {
                    $isExternal = false;
                }
            }

            $targetAttr = $isExternal ? ' target="_blank" rel="noopener noreferrer"' : ' target="_self"';
            $linkHtml = '<a href="' . \yii\helpers\Html::encode($href) . '"' . $targetAttr . ' class="underline hover:text-primary font-semibold text-current">' . \yii\helpers\Html::encode($cleanUrl) . '</a>';

            return $linkHtml . $trailingPunctuation;
        }, $content);
    }
}
