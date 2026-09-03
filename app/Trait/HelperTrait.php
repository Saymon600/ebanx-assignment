<?php 

namespace App\Trait;

trait HelperTrait {
    /**
     * @@return array{id:int,balance:float}|null
     */
    public static function getAccountData(int $account_id): ?array{
        $accountData = apcu_fetch("account:{$account_id}");
        if(!empty($accountData)){
            return $accountData;
        }

        return null;
    }
}