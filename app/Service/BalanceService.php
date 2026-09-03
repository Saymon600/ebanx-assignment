<?php 

namespace App\Service;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Trait\HelperTrait;

class BalanceService {
    use HelperTrait;
    
    /**
    * @return array{0: int, 1: string}
    */
    public static function checkBalance(Request $request): array{
        // var_dump(filter_var("3",FILTER_VALIDATE_INT));die;

        $params = $request->getQueryParams();
        if(!isset($params["account_id"])){
            return [404,"0"];
        }

        if(is_int(filter_var($params["account_id"],FILTER_VALIDATE_INT)) && (int)$params["account_id"] > 0){
            $account_id = (int)$params["account_id"];
            $accountData = self::getAccountData($account_id);
            if(empty($accountData)){
                return [404,"0"];
            }

            if(
                !isset($accountData["balance"]) ||
                !is_float(filter_var($accountData["balance"],FILTER_VALIDATE_FLOAT))
            ){
                return [404,"0"];
            }

            $accountBalance = (String)$accountData["balance"];
            return [200,$accountBalance];
        }

        return [404,"0"];
    }
}