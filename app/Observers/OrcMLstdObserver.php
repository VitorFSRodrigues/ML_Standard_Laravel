<?php

namespace App\Observers;

use App\Modules\MLStandard\Models\EvidenciaUsoMl;
use App\Modules\MLStandard\Models\OrcMLstd;

class OrcMLstdObserver
{
    public function created(OrcMLstd $orcMlStd): void
    {
        EvidenciaUsoMl::query()->firstOrCreate(
            ['orc_ml_std_id' => $orcMlStd->id],
            [
                'qtd_itens_ele' => null,
                'qtd_itens_tub' => null,
                'tempo_normal_hr' => null,
                'tempo_ml_hr' => null,
            ]
        );
    }
}

