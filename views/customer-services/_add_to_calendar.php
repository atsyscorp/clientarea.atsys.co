<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\CalendarHelper;

/** @var yii\web\View $this */
/** @var app\models\CustomerServices $model */
/** @var string|null $btnClass */
/** @var string|null $dropdownDirection */

if (!CalendarHelper::isEligible($model, 90)) {
    return;
}

$btnClass = $btnClass ?? 'btn-outline btn-sm';
$dropdownDirection = $dropdownDirection ?? 'dropdown-end';
$daysLeft = CalendarHelper::getDaysUntilDue($model);
?>

<div class="dropdown <?= $dropdownDirection ?> inline-block">
    <label tabindex="0" class="btn <?= $btnClass ?> gap-1.5 cursor-pointer" title="Agregar recordatorio de renovación a tu calendario">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-primary">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
        </svg>
        <span>Agendar Renovación</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3 opacity-60">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </label>
    
    <ul tabindex="0" class="dropdown-content z-[50] menu p-2 shadow-2xl bg-base-100 rounded-box w-64 border border-base-200 text-xs">
        <li class="menu-title px-3 py-1 font-semibold text-base-content/70 flex flex-row items-center justify-between">
            <span>Añadir a mi calendario</span>
            <?php if ($daysLeft !== null): ?>
                <span class="badge badge-warning badge-xs font-mono font-bold"><?= $daysLeft ?>d restantes</span>
            <?php endif; ?>
        </li>
        
        <!-- Google Calendar -->
        <li>
            <a href="<?= Html::encode(CalendarHelper::getGoogleCalendarUrl($model)) ?>" target="_blank" rel="noopener noreferrer" class="py-2 hover:bg-base-200 active:bg-primary active:text-white">
                <span class="w-5 text-center font-bold text-red-500">G</span>
                <div class="flex flex-col">
                    <span class="font-medium">Google Calendar</span>
                    <span class="text-[10px] opacity-60">Abre en el navegador</span>
                </div>
            </a>
        </li>

        <!-- Apple Calendar / iCal (.ics) -->
        <li>
            <a href="<?= Url::to(['/customer-services/calendar-ics', 'id' => $model->id]) ?>" class="py-2 hover:bg-base-200 active:bg-primary active:text-white">
                <span class="w-5 text-center">🍎</span>
                <div class="flex flex-col">
                    <span class="font-medium">Apple Calendar / iCal</span>
                    <span class="text-[10px] opacity-60">Descarga archivo .ics con alarmas</span>
                </div>
            </a>
        </li>

        <!-- Outlook Live -->
        <li>
            <a href="<?= Html::encode(CalendarHelper::getOutlookLiveUrl($model)) ?>" target="_blank" rel="noopener noreferrer" class="py-2 hover:bg-base-200 active:bg-primary active:text-white">
                <span class="w-5 text-center font-bold text-blue-500">O</span>
                <div class="flex flex-col">
                    <span class="font-medium">Outlook Web</span>
                    <span class="text-[10px] opacity-60">Hotmail / Outlook personal</span>
                </div>
            </a>
        </li>

        <!-- Office 365 -->
        <li>
            <a href="<?= Html::encode(CalendarHelper::getOffice365Url($model)) ?>" target="_blank" rel="noopener noreferrer" class="py-2 hover:bg-base-200 active:bg-primary active:text-white">
                <span class="w-5 text-center font-bold text-blue-600">365</span>
                <div class="flex flex-col">
                    <span class="font-medium">Microsoft 365</span>
                    <span class="text-[10px] opacity-60">Cuenta corporativa / educativa</span>
                </div>
            </a>
        </li>

        <!-- Yahoo Calendar -->
        <li>
            <a href="<?= Html::encode(CalendarHelper::getYahooCalendarUrl($model)) ?>" target="_blank" rel="noopener noreferrer" class="py-2 hover:bg-base-200 active:bg-primary active:text-white">
                <span class="w-5 text-center font-bold text-purple-600">Y!</span>
                <div class="flex flex-col">
                    <span class="font-medium">Yahoo Calendar</span>
                    <span class="text-[10px] opacity-60">Abre en Yahoo</span>
                </div>
            </a>
        </li>
    </ul>
</div>
