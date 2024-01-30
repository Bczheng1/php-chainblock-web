<?php
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
 $privateKeyFactory = new PrivateKeyFactory();
 $privateKey = $privateKeyFactory->generateCompressed(new Random());
 $publicKey = $privateKey->getPublicKey();
 echo('<hr>');
 echo $privateKey->getHex();
