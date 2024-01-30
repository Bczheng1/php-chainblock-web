<?php
namespace App\Http\Controllers;
define('targetBits',20);
class ProofOfWork
{
  
    /**
     * @var Block $block
     */
    public $block;

    /**
     * 目标值（计算结果要小于这个目标值才有效）
     * @var GMP $target
     */
    public $target;

    public function __construct(Block $block)
    {
       // $targetBits = config('blockchain.targetBits');

        $this->target = gmp_pow('2', (256 - targetBits));

        $this->block = $block;
    }
    public function prepareData(int $nonce): string
    {
        return implode('', [
            $this->block->prevBlockHash,
            $this->block->hashTransactions(),
            $this->block->timestamp,
            targetBits,
            $nonce
        ]);
    }

    public function run(): array
    {
        $nonce = 0;
        $hash = '';
        while (true) {
            $data = $this->prepareData($nonce);
            $hash = hash('sha256', $data);
            if (gmp_cmp('0x' . $hash, $this->target) == -1) {
                break;
            }
            $nonce++;
        }
        return [$nonce, $hash];
    }
}
