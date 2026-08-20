<?php

namespace App\Dto;

/**
 * Regroupe les 4 compteurs affichés sur les écrans run/review d'une FAQ
 * (voir CouplesRepository::countAll), pour n'exécuter qu'une seule requête
 * au lieu des 4 requêtes séparées countTodoRun/countTodoReview/
 * countSelectRun/countSelectReview (cf. AUDIT.md, section performance).
 */
final readonly class CouplesCounters
{
    public function __construct(
        public int $todoRun,
        public int $todoReview,
        public int $selectRun,
        public int $selectReview,
    ) {
    }
}
