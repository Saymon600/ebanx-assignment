<?php 

namespace App\Service;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Trait\HelperTrait;


class EventService {
    use HelperTrait;
    
    /**
     * @return array{0: int, 1: string}
     */
    public static function processEvent(Request $request): array{
        
    } 

    private function deposit(){
        
    }

    private function withdraw(){
        
    }

    private function transfer(){
        
    }
}