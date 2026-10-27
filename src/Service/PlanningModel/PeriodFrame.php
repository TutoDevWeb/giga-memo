<?php

namespace App\Service\PlanningModel;

enum PeriodFrame: string
{
    case ACQUISITION = 'Acquisition des connaissances';
    case MAINTENANCE = 'Maintien des connaissances';

    public function getFrequencies(): array
    {

        return match ($this) {
            self::ACQUISITION => [
                7  => '7 jours (Rapide)',
                14 => '14 jours (Normal)',
                21 => '21 jours (Lent)',
            ],
            self::MAINTENANCE => [
                15 => '15 jours (Rapide)',
                30 => '1 mois (Normal)',
                60 => '2 mois (Lent)',
            ],
        };
    }

    public function getInfoMessages(): string
    {
        return match ($this) {
            self::ACQUISITION => 'Phase d’apprentissage intensif avec des cycles courts dégressifs.',
            self::MAINTENANCE => 'Phase de révision espacée pour ancrer les acquis sur le long terme.',
        };
    }
}
