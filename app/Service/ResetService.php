<?php 

namespace App\Service;

class ResetService {
    public static function resetAll(): bool{
        apcu_clear_cache();

        return true;
    }
}