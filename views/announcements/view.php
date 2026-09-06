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
            
            <?php if (!empty($model->youtube_url)): ?>
            <div class="mb-6">
                <a href="<?= Html::encode($model->youtube_url) ?>" class="glightbox btn btn-outline btn-error w-full max-w-sm gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    Ver Video en YouTube
                </a>
            </div>
            <?php endif; ?>

            <div class="prose max-w-none mb-8">
                <?= $model->getFormattedContent() ?>
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
    <div class="mt-8">
        <h3 class="text-xl font-bold mb-4">Comentarios</h3>
        
        <?php if (!Yii::$app->user->isGuest): ?>
        <div class="mb-6">
            <?= Html::beginForm(['announcements/comment', 'id' => $model->id], 'post') ?>
            <div class="form-control">
                <?= Html::textarea('comment', '', ['class' => 'textarea textarea-bordered w-full', 'placeholder' => 'Escribe un comentario...', 'rows' => 3, 'required' => true]) ?>
            </div>
            <div class="mt-2 text-right">
                <?= Html::submitButton('Comentar', ['class' => 'btn btn-primary btn-sm']) ?>
            </div>
            <?= Html::endForm() ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-6">
            Inicia sesión para poder comentar.
        </div>
        <?php endif; ?>

        <div class="space-y-4">
            <?php 
            $comments = \app\models\AnnouncementComments::find()
                ->where(['announcement_id' => $model->id, 'parent_id' => null])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();
            
            if (empty($comments)): ?>
                <p class="text-sm opacity-70">Aún no hay comentarios. ¡Sé el primero en comentar!</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="bg-base-200 p-4 rounded-lg">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-bold"><?= Html::encode($comment->user->first_name ?? $comment->user->email) ?></span>
                            <span class="text-xs opacity-70"><?= Yii::$app->formatter->asRelativeTime($comment->created_at) ?></span>
                        </div>
                        <p class="text-sm"><?= nl2br(Html::encode($comment->comment)) ?></p>
                        
                        <?php if (!Yii::$app->user->isGuest): ?>
                        <div class="mt-2">
                            <button class="text-xs text-primary hover:underline" onclick="document.getElementById('reply-form-<?= $comment->id ?>').classList.toggle('hidden')">Responder</button>
                        </div>
                        <div id="reply-form-<?= $comment->id ?>" class="hidden mt-2 ml-4">
                            <?= Html::beginForm(['announcements/comment', 'id' => $model->id], 'post') ?>
                            <?= Html::hiddenInput('parent_id', $comment->id) ?>
                            <div class="flex gap-2">
                                <?= Html::textInput('comment', '', ['class' => 'input input-bordered input-sm w-full', 'placeholder' => 'Escribe una respuesta...', 'required' => true]) ?>
                                <?= Html::submitButton('Enviar', ['class' => 'btn btn-primary btn-sm']) ?>
                            </div>
                            <?= Html::endForm() ?>
                        </div>
                        <?php endif; ?>

                        <?php $replies = $comment->getReplies()->orderBy(['created_at' => SORT_ASC])->all(); ?>
                        <?php if (!empty($replies)): ?>
                            <div class="mt-4 ml-6 space-y-3 border-l-2 border-base-300 pl-4">
                                <?php foreach ($replies as $reply): ?>
                                    <div>
                                        <div class="flex justify-between items-start">
                                            <span class="font-semibold text-sm"><?= Html::encode($reply->user->first_name ?? $reply->user->email) ?></span>
                                            <span class="text-xs opacity-70"><?= Yii::$app->formatter->asRelativeTime($reply->created_at) ?></span>
                                        </div>
                                        <p class="text-sm mt-1"><?= nl2br(Html::encode($reply->comment)) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="mt-6">
        <?= Html::a('← Volver a Novedades', ['index'], ['class' => 'btn btn-ghost']) ?>
    </div>
</div>

<?php
$this->registerCssFile('https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', ['position' => \yii\web\View::POS_END]);

$jsLightbox = <<<JS
if (typeof GLightbox !== 'undefined') {
    GLightbox({
        selector: '.glightbox',
    });
}
JS;
$this->registerJs($jsLightbox, \yii\web\View::POS_END);
?>

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
        let colorClass = 'bg-base-200';
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