<?php
namespace App\Http\Controllers;
use Cache;
class BlockChain implements \Iterator
{
 /**
     * // 存放最后一个块的hash
     * @var string $tips
     */
    public $tips;

    public function __construct(string $tips)
    {
        $this->tips = $tips;
    }

    // 加入一个块到区块链中
  /**
     * @param array $transactions
     * @throws \Exception
     */
    public function mineBlock(array $transactions): Block
    {
        $lastHash = Cache::get('l');
        if (is_null($lastHash)) {
            echo "还没有区块链，请先初始化";
            exit;
        }

        foreach ($transactions as $tx) {
            if (!$this->verifyTransaction($tx)) {
                echo "交易验证失败";
                exit(0);
            }
        }

        $block = new Block($transactions, $lastHash);

        $this->tips = $block->hash;
        Cache::forever('l', $block->hash);
        Cache::forever($block->hash, serialize($block));

        return $block;
    }

    public function signTransaction(Transaction $tx, string $privateKey)
    {
        $prevTXs = [];
        foreach ($tx->txInputs as $txInput) {
            $prevTx = $this->findTransaction($txInput->txId);
            $prevTXs[$prevTx->id] = $prevTx;
        }
        $tx->sign($privateKey, $prevTXs);
    }

    public function verifyTransaction(Transaction $tx): bool
    {
        $prevTXs = [];
        foreach ($tx->txInputs as $txInput) {
            $prevTx = $this->findTransaction($txInput->txId);
            $prevTXs[$prevTx->id] = $prevTx;
        }
        return $tx->verify($prevTXs);
    }

   // 还有些其他方法的修改
   public function findTransaction(string $txId): Transaction
   {
       /**
        * @var Block $block
        */
       foreach ($this as $block) {
           foreach ($block->transactions as $tx) {
               if ($tx->id == $txId) {
                   return $tx;
               }
           }
       }
       echo "Transaction is not found";
       exit(0);
   }

    // 新建区块链
  public static function NewBlockChain(string $address): BlockChain
    {
        if (Cache::has('l')) {
            // 存在区块链
            $tips = Cache::get('l');
        } else {
            $coinbase = Transaction::NewCoinbaseTX($address, 'genesisCoinbaseData');
 
            $genesis = Block::NewGenesisBlock($coinbase);
 
            Cache::forever($genesis->hash, serialize($genesis));
 
            Cache::forever('l', $genesis->hash);
 
            $tips = $genesis->hash;
        }
        return new BlockChain($tips);
    }
    public static function GetBlockChain(): BlockChain
    {
        if (!Cache::has('l')) {
            echo "还没有区块链，请先初始化";
            exit;
        }

        return new BlockChain(Cache::get('l'));
    }


    /**
     * 找出地址的所有未花费交易
     * @param string $address
     * @return Transaction[]
     */
    public function findUnspentTransactions(string $address): array
    {
        $unspentTXs = [];
        $spentTXOs = [];
 
        /**
         * @var Block $block
         */
        foreach ($this as $block) {
 
            foreach ($block->transactions as $tx) {
                $txId = $tx->id;
 
                foreach ($tx->txOutputs as $outIdx => $txOutput) {
                    if (isset($spentTXOs[$txId])) {
                        foreach ($spentTXOs[$txId] as $spentOutIdx) {
                            if ($spentOutIdx == $outIdx) {
                                continue 2;
                            }
                        }
                    }
 
                    if ($txOutput->isLockedWithKey($address)) {
                        $unspentTXs[$txId] = $tx;
                    }
                }
 
                if (!$tx->isCoinbase()) {
                    foreach ($tx->txInputs as $txInput) {
                        if ($txInput->usesKey($address)) {
                            $spentTXOs[$txInput->txId][] = $txInput->vOut;
                        }
                    }
                }
            }
        }
        return $unspentTXs;
    }
 
    /**
     * 找出所有已花费的输出
     * @param string $address
     * @return array
     */
    public function findSpentOutputs(string $address): array
    {
        $spentTXOs = [];
        /**
         * @var Block $block
         */
        foreach ($this as $block) {
            foreach ($block->transactions as $tx) {
                if (!$tx->isCoinbase()) {
                    foreach ($tx->txInputs as $txInput) {
                        if ($txInput->usesKey($address)) {
                            $spentTXOs[$txInput->txId][] = $txInput->vOut;
                        }
                    }
                }
            }
        }
        return $spentTXOs;
    }
 
    // 根据所有未花费的交易和已花费的输出，找出满足金额的未花费输出，用于构建交易
    public function findSpendableOutputs(string $address, int $amount): array
    {
        $unspentOutputs = [];
        $unspentTXs = $this->findUnspentTransactions($address);
        $spentTXOs = $this->findSpentOutputs($address);
        $accumulated = 0;
 
        /**
         * @var Transaction $tx
         */
        foreach ($unspentTXs as $tx) {
            $txId = $tx->id;
 
            foreach ($tx->txOutputs as $outIdx => $txOutput) {
                if (isset($spentTXOs[$txId])) {
                    foreach ($spentTXOs[$txId] as $spentOutIdx) {
                        if ($spentOutIdx == $outIdx) {
                            // 说明这个tx的这个outIdx被花费过
                            continue 2;
                        }
                    }
                }
 
                if ($txOutput->isLockedWithKey($address) && $accumulated < $amount) {
                    $accumulated += $txOutput->value;
                    $unspentOutputs[$txId][] = $outIdx;
                    if ($accumulated >= $amount) {
                        break 2;
                    }
                }
            }
        }
        return [$accumulated, $unspentOutputs];
    }
 
    /**
     * 找出所有未花费的输出
     * @param string $address
     * @return TXOutput[]
     */
    public function findUTXO(string $address): array
    {
        $UTXOs = [];
        $unspentTXs = $this->findUnspentTransactions($address);
        $spentTXOs = $this->findSpentOutputs($address);
 
        foreach ($unspentTXs as $transaction) {
            $txId = $transaction->id;
            foreach ($transaction->txOutputs as $outIdx => $output) {
                if (isset($spentTXOs[$txId])) {
                    foreach ($spentTXOs[$txId] as $spentOutIdx) {
                        if ($spentOutIdx == $outIdx) {
                            // 说明这个tx的这个outIdx被花费过
                            continue 2;
                        }
                    }
                }
 
                if ($output->isLockedWithKey($address)) {
                    $UTXOs[] = $output;
                }
            }
        }
        return $UTXOs;
    }
    // ......
   ////////////////////Iterator
    /**
     * 迭代器指向的当前块Hash
     * @var string $iteratorHash
     */
    private $iteratorHash;

    /**
     * 迭代器指向的当前块Hash
     * @var Block $iteratorBlock
     */
    private $iteratorBlock;

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->iteratorBlock = unserialize(Cache::get($this->iteratorHash));
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        return $this->iteratorHash = $this->iteratorBlock->prevBlockHash;
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->iteratorHash;
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return $this->iteratorHash != '';
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->iteratorHash = $this->tips;
    }
}
