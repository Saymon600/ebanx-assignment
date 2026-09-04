<?php 

namespace App\Trait;

trait HelperTrait {
    /**
     * @return array{id:int,balance:float}|null
     */
    public function getAccountData(int $account_id): ?array{
        $accountData = apcu_fetch("account:{$account_id}");
        if(!empty($accountData)){
            return $accountData;
        }

        return null;
    }

    /**
     * @return array{id:int,balance:float}|null
     */
    public function createAccount(int $accountId): ?array{
        $accountData = [
            "id" => (String)$accountId,
            "balance" => 0
        ];

        $result = apcu_store("account:{$accountId}",$accountData,0);
        if($result){
            return $accountData;
        }

        return null;
    }
    
    public function validateId($var): bool{
        if(is_int(filter_var($var,FILTER_VALIDATE_INT)) && (int)$var > 0){
            return true;
        }

        return false;
    }

    public function validateAmount($var): bool{
        if(is_float(filter_var($var,FILTER_VALIDATE_FLOAT)) && (float)$var > 0){
            return true;
        }

        return false;
    }

    /**
     * @return array{0:int,1:string}
     */
    public function errorResponse(int $errorCode,String $message): array{
        $errorResponse = [
            "message" => $message
        ];
        return [$errorCode,json_encode($errorResponse)];
    }
}