<?php 

namespace App\Service;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Trait\HelperTrait;

class BalanceService {
    use HelperTrait;
    
    /**
    * @return array{0: int, 1: string}
    */
    public function checkBalance(Request $request): array{
        $params = $request->getQueryParams();
        if(!isset($params["account_id"])){
            return [404,"0"];
        }

        if($this->validateId($params["account_id"])){
            $accountId = (int)$params["account_id"];
            $accountData = $this->getAccountData($accountId);
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