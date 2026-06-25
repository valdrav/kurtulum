<?php

namespace App\Services\DocumentTools;

use setasign\Fpdi\Fpdi;

class PdfEditorEngine extends Fpdi
{
    protected float $angle = 0;

    public function rotateTransform(float $angle, float $x = -1, float $y = -1): void
    {
        if ($x === -1.0) {
            $x = $this->x;
        }
        if ($y === -1.0) {
            $y = $this->y;
        }
        if ($this->angle != 0.0) {
            $this->_out('Q');
        }
        $this->angle = $angle;
        if ($angle != 0.0) {
            $angleRad = $angle * M_PI / 180;
            $c = cos($angleRad);
            $s = sin($angleRad);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf(
                'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                $c,
                $s,
                -$s,
                $c,
                $cx,
                $cy,
                -$cx,
                -$cy
            ));
        }
    }

    public function endTransform(): void
    {
        if ($this->angle != 0.0) {
            $this->angle = 0.0;
            $this->_out('Q');
        }
    }

    public function _endpage(): void
    {
        $this->endTransform();
        parent::_endpage();
    }
}
