<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Announcements */

$this->title = $model->title;

// Contar vistas
$viewCount = (new \yii\db\Query())->from('announcement_views')->where(['announcement_id' => $model->id])->count();

// Obtener reacción actual del usuario
$myReaction = (new \yii\db\Query())
    ->select('reaction_type')
    ->from('announcement_reactions')
    ->where(['announcement_id' => $model->id, 'user_id' => Yii::$app->user->id])
    ->scalar(); // Devuelve false o el string 'like', 'love', etc.
?>

<div class="max-w-4xl mx-auto mt-6">
    
    <div class="card bg-base-100 shadow-xl border border-base-200">
        <div class="card-body">
            
            <div class="flex justify-between items-start text-sm text-base-content/60 mb-4">
                <span><?= Yii::$app->formatter->asDate($model->created_at, 'long') ?></span>
                
                <div class="flex items-center gap-1 tooltip" data-tip="Personas que han leído esto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    <span class="font-bold"><?= $viewCount ?></span> Vistas
                </div>
            </div>

            <h1 class="text-3xl font-bold mb-6"><?= Html::encode($model->title) ?></h1>
            
            <div class="prose max-w-none mb-8">
                <?= Html::decode($model->content) ?>
            </div>

            <div class="divider"></div>

            <div class="flex flex-col items-center gap-4">
                <span class="text-sm opacity-70">¿Qué opinas de esta novedad?</span>
                
                <div class="join shadow-sm bg-base-200 p-1 rounded-full">
                    <button onclick="react(<?= $model->id ?>, 'like', this)" 
                            class="btn btn-circle btn-ghost text-2xl join-item transition-all hover:scale-125 <?= $myReaction == 'like' ? 'bg-blue-100 border-blue-300' : '' ?>" 
                            title="Me gusta">
                        👍
                    </button>
                    
                    <button onclick="react(<?= $model->id ?>, 'love', this)" 
                            class="btn btn-circle btn-ghost text-2xl join-item transition-all hover:scale-125 <?= $myReaction == 'love' ? 'bg-red-100 border-red-300' : '' ?>" 
                            title="Me encanta">
                        ❤️
                    </button>
                    
                    <button onclick="react(<?= $model->id ?>, 'clap', this)" 
                            class="btn btn-circle btn-ghost text-2xl join-item transition-all hover:scale-125 <?= $myReaction == 'clap' ? 'bg-green-100 border-green-300' : '' ?>" 
                            title="Buen trabajo">
                        👏
                    </button>
                    
                    <button onclick="react(<?= $model->id ?>, 'idea', this)" 
                            class="btn btn-circle btn-ghost text-2xl join-item transition-all hover:scale-125 <?= $myReaction == 'idea' ? 'bg-yellow-100 border-yellow-300' : '' ?>" 
                            title="Interesante">
                        💡
                    </button>
                </div>
                
                <div id="reaction-feedback" class="text-xs text-primary h-4"></div>
            </div>

        </div>
    </div>
    
    <div class="mt-4">
        <?= Html::a('← Volver a Novedades', ['index'], ['class' => 'btn btn-ghost']) ?>
    </div>
</div>

<script>
function react(id, type, btn) {
    // Animación visual inmediata (UX)
    const buttons = btn.parentElement.querySelectorAll('button');
    const isActive = btn.classList.contains('bg-blue-100') || btn.classList.contains('bg-red-100') || btn.classList.contains('bg-green-100') || btn.classList.contains('bg-yellow-100');

    // Reset visual de todos
    buttons.forEach(b => {
        b.className = 'btn btn-circle btn-ghost text-2xl join-item transition-all hover:scale-125';
    });

    if (!isActive) {
        // Asignar color según tipo
        let colorClass = 'bg-gray-200';
        if(type === 'like') colorClass = 'bg-blue-100 border-blue-300';
        if(type === 'love') colorClass = 'bg-red-100 border-red-300';
        if(type === 'clap') colorClass = 'bg-green-100 border-green-300';
        if(type === 'idea') colorClass = 'bg-yellow-100 border-yellow-300';
        
        btn.classList.add(...colorClass.split(' '));
    }

    // Llamada al servidor
    fetch('<?= \yii\helpers\Url::to(['announcements/react']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
        },
        body: 'id=' + id + '&type=' + type
    })
    .then(response => response.json())
    .then(data => {
        // Feedback opcional
        const feedback = document.getElementById('reaction-feedback');
        if(data.status === 'created' || data.status === 'updated') {
            feedback.innerText = '¡Reacción guardada!';
        } else {
            feedback.innerText = '';
        }
        setTimeout(() => feedback.innerText = '', 2000);
    });
}
</script>