<?php
namespace App\Http\Controllers;
use Cache;
use Illuminate\Http\Request;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\Factory\PublicKeyFactory;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\KeyToScript\Factory\P2pkhScriptDataFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Signature\SignatureFactory;
class AppController extends Controller
{

public function app(Request $request){
  

   if($request->get('add')!="")
    {
        //添加区块
        $data=$request->get('data');
        $time1 = time();
        $bc = BlockChain::NewBlockChain($data);
        $time2 = time();
        $spend = $time2 - $time1;
        echo('花费时间(s):'.$spend);
        echo('<br>创世块的哈希值是:<br>'.$bc->tips);
        echo('<hr/>所有区块信息:<br>');
   
        foreach ($bc as $block){
            print_r($block);
            echo('<hr>');
         } 
    }
    else if($request->get('send')!="")
    {
      $from=$request->get('from');
      $to=$request->get('to');
      $amount=$request->get('amount');

      AppController::send($from,$to,$amount);
    }

    else if($request->get('balance')!="")
    {
        $address=$request->get('address');
        AppController::getBalance($address);
    }
    else
    {

     $bc = BlockChain::GetBlockChain();
        //显示所有区块
      echo('所有区块信息:<br>');
        foreach ($bc as $block){
            echo('<hr>');
            print_r($block);
           
         } 
        echo('<hr>'); 
 
   
        
    } 

 }
 public static function test()
 {
  $time1 = time();
  $bc = BlockChain::NewBlockChain("18sHseZiDL9YM1HCcnYL7NJF3ALqUbJXcS");
  $time2 = time();
  $spend = $time2 - $time1;
  echo('花费时间(s):'.$spend);
  echo('<br>创世块的哈希值是:<br>'.$bc->tips);
  echo('<hr/>所有区块信息:<br>');

  foreach ($bc as $block){
      print_r($block);
      echo('<hr>');
   } 

 }

 public static function send($from,$to,$amount)
 {

        $bc = BlockChain::GetBlockChain();

        $tx = Transaction::NewUTXOTransaction($from, $to, $amount, $bc);
        $bc->mineBlock([$tx]);

        echo('send success');
        echo('<br>');
        foreach ($bc as $block) {
            echo("$block->hash");
            break;
        }
}
 public static function getBalance($address)
 {
    $bc = BlockChain::GetBlockChain();
    $wallets = new Wallets();
    $wallet = $wallets->getWallet($address);
    $UTXOs = $bc->findUTXO($wallet->getPubKeyHash());

    $balance = 0;
    foreach ($UTXOs as $output) {
         $balance += $output->value;
     }

    echo($address."的余额是:".$balance);
 }
 
}

