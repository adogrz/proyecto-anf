<?php

namespace App\Services;

class FinancialRatioService
{
    private function n($v){ return is_numeric($v) ? (float)$v : 0.0; }
    private function sdiv($n,$d){ $d=$this->n($d); return $d!=0.0 ? $this->n($n)/$d : 0.0; }
    private function avg($a,$b){ return ($this->n($a)+$this->n($b))/2.0; }

    private function yearRatios(array $y){
        $AC=$this->n($y['AC']??0);  $PC=$this->n($y['PC']??0);  $Inv=$this->n($y['Inv']??0);
        $AT=$this->n($y['AT']??0);  $PT=$this->n($y['PT']??0);  $PAT=$this->n($y['PAT']??0);
        $VN=$this->n($y['VN']??0);  $COGS=$this->n($y['COGS']??0); $UN=$this->n($y['UN']??0);

        $LC  = $this->sdiv($AC,$PC);            // Liquidez Corriente
        $PA  = $this->sdiv($AC-$Inv,$PC);       // Prueba Ácida
        $END = $this->sdiv($PT,$AT);            // Endeudamiento
        $APA = $this->sdiv($AT,$PAT);           // Apalancamiento
        $MB  = $this->sdiv($VN-$COGS,$VN);      // Margen Bruto
        $MN  = $this->sdiv($UN,$VN);            // Margen Neto

        // Aproximados con saldo del mismo año (si no quieres promedios)
        $ROT_INV = $this->sdiv($COGS, max($Inv, 0.0000001));
        $D_INV   = $ROT_INV ? 365/$ROT_INV : 0.0;
        $ROT_ACT = $this->sdiv($VN, max($AT, 0.0000001));
        $ROA     = $this->sdiv($UN, max($AT, 0.0000001));
        $ROE     = $this->sdiv($UN, max($PAT,0.0000001));

        return compact('LC','PA','END','APA','MB','MN','ROT_INV','D_INV','ROT_ACT','ROA','ROE');
    }

    /** Punto de entrada usado por RatiosController */
    public function compute(array $y1, array $y2): array
    {
        $r1 = $this->yearRatios($y1);
        $r2 = $this->yearRatios($y2);

        // Promedios entre años para algunos ratios
        $ATp  = $this->avg($y1['AT']??0,  $y2['AT']??0);
        $PATp = $this->avg($y1['PAT']??0, $y2['PAT']??0);
        $Invp = $this->avg($y1['Inv']??0, $y2['Inv']??0);

        $ROT_INV_y1_prom = $this->sdiv(($y1['COGS']??0), max($Invp,0.0000001));
        $ROT_INV_y2_prom = $this->sdiv(($y2['COGS']??0), max($Invp,0.0000001));
        $D_INV_y1_prom   = $ROT_INV_y1_prom ? 365/$ROT_INV_y1_prom : 0.0;
        $D_INV_y2_prom   = $ROT_INV_y2_prom ? 365/$ROT_INV_y2_prom : 0.0;
        $ROT_ACT_y1_prom = $this->sdiv(($y1['VN']??0), max($ATp,0.0000001));
        $ROT_ACT_y2_prom = $this->sdiv(($y2['VN']??0), max($ATp,0.0000001));
        $ROA_y1_prom     = $this->sdiv(($y1['UN']??0), max($ATp,0.0000001));
        $ROA_y2_prom     = $this->sdiv(($y2['UN']??0), max($ATp,0.0000001));
        $ROE_y1_prom     = $this->sdiv(($y1['UN']??0), max($PATp,0.0000001));
        $ROE_y2_prom     = $this->sdiv(($y2['UN']??0), max($PATp,0.0000001));

        return [
            'labels' => [
                'y1' => $y1['label'] ?? 'Año 1',
                'y2' => $y2['label'] ?? 'Año 2',
            ],
            'y1' => $r1,
            'y2' => $r2,
            'comparacion' => [
                'LC'  => ['v1'=>$r1['LC'],  'v2'=>$r2['LC'],  'type'=>'num', 'name'=>'Liquidez Corriente'],
                'PA'  => ['v1'=>$r1['PA'],  'v2'=>$r2['PA'],  'type'=>'num', 'name'=>'Prueba Ácida'],
                'END' => ['v1'=>$r1['END'], 'v2'=>$r2['END'], 'type'=>'pct', 'name'=>'Endeudamiento'],
                'APA' => ['v1'=>$r1['APA'], 'v2'=>$r2['APA'], 'type'=>'num', 'name'=>'Apalancamiento'],
                'RIN' => ['v1'=>$ROT_INV_y1_prom, 'v2'=>$ROT_INV_y2_prom, 'type'=>'num', 'name'=>'Rotación de Inventarios'],
                'DIN' => ['v1'=>$D_INV_y1_prom,   'v2'=>$D_INV_y2_prom,   'type'=>'num', 'name'=>'Días de Inventario'],
                'RA'  => ['v1'=>$ROT_ACT_y1_prom, 'v2'=>$ROT_ACT_y2_prom, 'type'=>'num', 'name'=>'Rotación de Activos'],
                'MB'  => ['v1'=>$r1['MB'],  'v2'=>$r2['MB'],  'type'=>'pct', 'name'=>'Margen Bruto'],
                'MN'  => ['v1'=>$r1['MN'],  'v2'=>$r2['MN'],  'type'=>'pct', 'name'=>'Margen Neto'],
                'ROA' => ['v1'=>$ROA_y1_prom,     'v2'=>$ROA_y2_prom,     'type'=>'pct', 'name'=>'ROA'],
                'ROE' => ['v1'=>$ROE_y1_prom,     'v2'=>$ROE_y2_prom,     'type'=>'pct', 'name'=>'ROE'],
            ],
        ];
    }
}
