<?php
namespace App\Http\Controllers;
class Block
{
    /**
     * 当前时间戳，也就是区块创建的时间
     * @var int $timestamp
     */
    public $timestamp;

    /**
     * 区块存储的信息，也就是交易
     * @var string $data
     */
    public $transactions;

    /**
     * 前一个块的哈希，即父哈希
     * @var string $prevBlockHash
     */
    public $prevBlockHash;



    /**
     * 当前块的哈希 
     * @var string $hash
     */
    public $hash;
    public $nonce;

    public function __construct(array $transactions, string $prevBlockHash)
    {
        $this->prevBlockHash = $prevBlockHash;
        $this->transactions = $transactions;
        $this->timestamp = time();

        $pow = new ProofOfWork($this);
        list($nonce, $hash) = $pow->run();

        $this->nonce = $nonce;
        $this->hash = $hash;
    }
    
    public static function NewGenesisBlock(Transaction $coinbase)
    {
        return $block = new Block([$coinbase], '');
    }

    public function hashTransactions(): string
    {
        $txsHashArr = [];
        foreach ($this->transactions as $transaction) {
            $txsHashArr[] = $transaction->id;
        }
        return hash('sha256', implode('', $txsHashArr));
    }

    public function setHash(): string
    {
        return hash('sha256', implode('', [$this->timestamp, $this->prevBlockHash, $this->data]));
    }
}
