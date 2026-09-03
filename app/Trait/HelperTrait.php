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
    
    public function validateId($var): bool{
        //An ID must never be negative.
        if(is_int(filter_var($var,FILTER_VALIDATE_INT)) && (int)$var > 0){
            return true;
        }

        return false;
    }

    public function validateAmount($var): bool{
        //Amount must never be negative.
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