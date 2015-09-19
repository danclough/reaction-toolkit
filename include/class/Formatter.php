<?php
class Formatter {
    public function formatAsISK($number) {
        return number_format($number,2)." ISK";
    }

    public function formatAsM3($number) {
        return number_format($number,2)." m³";
    }
} 