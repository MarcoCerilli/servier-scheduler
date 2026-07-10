<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Numero massimo di occorrenze calcolate per pianificazione
    |--------------------------------------------------------------------------
    |
    | Se una pianificazione non ha data di fine, viene usato questo limite.
    | Configurabile via .env SCHEDULE_MAX_OCCURRENCES.
    |
    */
    'max_occurrences' => (int) env('SCHEDULE_MAX_OCCURRENCES', 365),
];
