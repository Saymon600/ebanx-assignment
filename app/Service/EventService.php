<?php 

namespace App\Service;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Trait\HelperTrait;


class EventService {
    use HelperTrait;
    
    /**
     * @return array{0: int, 1: string}
     */
    public function processEvent(Request $request): array{
        $data = json_decode((string)$request->getBody(), true) ?? [];

        //Error Validations
        if(empty($data)){  
            return $this->errorResponse(400,"Invalid JSON object.");
        }

        if(!isset($data["type"]) || !in_array($data["type"],["deposit","withdraw","transfer"])){
            return $this->errorResponse(400,"Invalid Type.");
        }

        switch($data["type"]){
            case "deposit": 
                if(!$this->validateId($data["destination"])){
                    return $this->errorResponse(400,"Invalid destination.");
                }
                if(!$this->validateAmount($data["amount"])){
                    return $this->errorResponse(400,"Invalid amount.");
                }
                return $this->deposit($data["destination"],$data["amount"]);
            break;
            case "withdraw": 
                if(!$this->validateId($data["origin"])){
                    return $this->errorResponse(400,"Invalid origin.");
                }
                if(!$this->validateAmount($data["amount"])){
                    return $this->errorResponse(400,"Invalid amount.");
                }
                return $this->withdraw($data["origin"],$data["amount"]);
            break;
            case "transfer": 
                if(!$this->validateId($data["destination"])){
                    return $this->errorResponse(400,"Invalid destination.");
                }
                if(!$this->validateId($data["origin"])){
                    return $this->errorResponse(400,"Invalid origin.");
                }
                if(!$this->validateAmount($data["amount"])){
                    return $this->errorResponse(400,"Invalid amount.");
                }
                return $this->transfer($data["destination"],$data["origin"],$data["amount"]);
            break;
        }

        return [404,"0"];
    } 

    /**
     * @return array{0:int,1:String}
     */
    private function deposit(int $destination, float $amount): array{
        $accountData = $this->getAccountData($destination);
        if(empty($accountData)){
            $createResult = $this->createAccount($destination);
            if(!$createResult){
                return $this->errorResponse(400,"Could not create a new account.");
            }
            $accountData = $createResult;
        }

        $depositResult = $this->addAmountToBalance($accountData,$amount);
        if(empty($depositResult)){
            return $this->errorResponse(400,"Failed to add amount to account.  Could not store destination data.");
        }
        
        $response = [
            "destination" => $depositResult
        ];
        return [201,json_encode($response)];
    }

    /**
     * @return array{0:int,1:String}
     */
    private function withdraw(int $origin, float $amount): array{
        $accountData = $this->getAccountData($origin);
        if(empty($accountData)){
            return [404,"0"];
        }
        if(($accountData["balance"] - $amount) < 0){
            return $this->errorResponse(422,"Failed to withdraw amount. Insufficient balance.");
        }

        $withdrawResult = $this->removeAmountToBalance($accountData,$amount);
        if(empty($withdrawResult)){
            return $this->errorResponse(400,"Failed to withdraw amount. Could not store origin data.");
        }
        $response = [
            "origin" => $withdrawResult
        ];
        return [201,json_encode($response)];
    }

    /**
     * @return array{0:int,1:String}
     */
    private function transfer(int $destination, int $origin, float $amount): array{
        $originData = $this->getAccountData($origin);
        if(empty($originData)){
            return [404,"0"];
        }
        
        $destinationData = $this->getAccountData($destination);
        if(empty($destinationData)){
            $createResult = $this->createAccount($destination);
            if(!$createResult){
                return $this->errorResponse(400,"Could not create a new account.");
            }
            $destinationData = $createResult;
        }

        if(($originData["balance"] - $amount) < 0){
            return $this->errorResponse(422,"Failed to transfer amount. Insufficient balance.");
        }

        $transferRemoveResult = $this->removeAmountToBalance($originData,$amount);
        if(empty($transferRemoveResult)){
            return $this->errorResponse(400,"Failed to transfer amount. Could not store origin data.");
        }

        $transferAddResult = $this->addAmountToBalance($destinationData,$amount);
        if(empty($transferAddResult)){
            return $this->errorResponse(400,"Failed to add amount to account. Could not store destination data.");
        }
        
        $response = [
            "origin" => $transferRemoveResult,
            "destination" => $transferAddResult,
        ];
        return [201,json_encode($response)];
    }

    /**
     * @param array{id:int,balance:float}
     * @return array{id:int,balance:float}|null
     */
    private function addAmountToBalance(array $accountData,float $amount): ?array{
        $accountId = $accountData["id"];
        $total = $accountData["balance"] += $amount; 
        $accountData["balance"] = (float)number_format($total,2,".","");
        $result = apcu_store("account:{$accountId}",$accountData,0);
        if($result){
            return $accountData;
        }

        return null;
    }

    /**
     * @param array{id:int,balance:float}
     * @return array{id:int,balance:float}|null
     */
    private function removeAmountToBalance(array $accountData,float $amount): ?array{
        $accountId = $accountData["id"];
        $total = $accountData["balance"] -= $amount; 
        $accountData["balance"] = (float)number_format($total,2,".","");
        $result = apcu_store("account:{$accountId}",$accountData,0);
        if($result){
            return $accountData;
        }

        return null;
    }
}