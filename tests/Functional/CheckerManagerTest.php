<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2016 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Jose\Factory\JWEFactory;
use Jose\Test\TestCase;

/**
 * @group CheckerManager
 * @group Functional
 */
class CheckerManagerTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The JWT has expired.
     */
    public function testExpiredJWT()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'exp' => time() - 1,
            ],
            [
                'enc' => 'A256CBC-HS512',
                'alg' => 'RSA-OAEP-256',
                'zip' => 'DEF',
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The JWT is issued in the futur.
     */
    public function testJWTIssuedInTheFuture()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'exp' => time() + 3600,
                'iat' => time() + 100,
            ],
            [
                'enc' => 'A256CBC-HS512',
                'alg' => 'RSA-OAEP-256',
                'zip' => 'DEF',
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The JWT can not be used yet.
     */
    public function testJWTNotNow()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'exp' => time() + 3600,
                'iat' => time() - 100,
                'nbf' => time() + 100,
            ],
            [
                'enc' => 'A256CBC-HS512',
                'alg' => 'RSA-OAEP-256',
                'zip' => 'DEF',
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Bad audience.
     */
    public function testJWTNotForAudience()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'exp' => time() + 3600,
                'iat' => time() - 100,
                'nbf' => time() - 100,
                'aud' => 'Other Service',
            ],
            [
                'enc' => 'A256CBC-HS512',
                'alg' => 'RSA-OAEP-256',
                'zip' => 'DEF',
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage One or more claims are marked as critical, but they are missing or have not been checked (["iss"]).
     */
    public function testJWTHasCriticalClaimsNotSatisfied()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'exp' => time() + 3600,
                'iat' => time() - 100,
                'nbf' => time() - 100,
            ],
            [
                'enc'  => 'A256CBC-HS512',
                'alg'  => 'RSA-OAEP-256',
                'zip'  => 'DEF',
                'crit' => ['exp', 'iss'],
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The issuer "foo" is not allowed.
     */
    public function testJWTBadIssuer()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'exp' => time() + 3600,
                'iat' => time() - 100,
                'nbf' => time() - 100,
                'iss' => 'foo',
            ],
            [
                'enc'  => 'A256CBC-HS512',
                'alg'  => 'RSA-OAEP-256',
                'zip'  => 'DEF',
                'crit' => ['exp', 'iss'],
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The subject "foo" is not allowed.
     */
    public function testJWTBadSubject()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'exp' => time() + 3600,
                'iat' => time() - 100,
                'nbf' => time() - 100,
                'iss' => 'ISS1',
                'sub' => 'foo',
            ],
            [
                'enc'  => 'A256CBC-HS512',
                'alg'  => 'RSA-OAEP-256',
                'zip'  => 'DEF',
                'crit' => ['exp', 'iss', 'sub', 'aud'],
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid token ID "bad jti".
     */
    public function testJWTBadTokenID()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'jti' => 'bad jti',
                'exp' => time() + 3600,
                'iat' => time() - 100,
                'nbf' => time() - 100,
                'iss' => 'ISS1',
                'sub' => 'SUB1',
            ],
            [
                'enc'  => 'A256CBC-HS512',
                'alg'  => 'RSA-OAEP-256',
                'zip'  => 'DEF',
                'crit' => ['exp', 'iss', 'sub', 'aud', 'jti'],
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    public function testJWTSuccessfullyCheckedWithCriticalHeaders()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'jti' => 'JTI1',
                'exp' => time() + 3600,
                'iat' => time() - 100,
                'nbf' => time() - 100,
                'iss' => 'ISS1',
                'sub' => 'SUB1',
                'aud' => 'My Service',
            ],
            [
                'enc'  => 'A256CBC-HS512',
                'alg'  => 'RSA-OAEP-256',
                'zip'  => 'DEF',
                'crit' => ['exp', 'iss', 'sub', 'aud', 'jti'],
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    public function testJWTSuccessfullyCheckedWithoutCriticalHeaders()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'jti' => 'JTI1',
                'exp' => time() + 3600,
                'iat' => time() - 100,
                'nbf' => time() - 100,
                'iss' => 'ISS1',
                'sub' => 'SUB1',
                'aud' => 'My Service',
            ],
            [
                'enc'  => 'A256CBC-HS512',
                'alg'  => 'RSA-OAEP-256',
                'zip'  => 'DEF',
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }

    public function testJWTSuccessfullyCheckedWithUnsupportedClaims()
    {
        $jwe = JWEFactory::createEmptyJWE(
            [
                'foo' => 'bar',
            ],
            [
                'enc'  => 'A256CBC-HS512',
                'alg'  => 'RSA-OAEP-256',
                'zip'  => 'DEF',
            ],
            [],
            'foo,bar,baz'
        );
        $jwe = $jwe->addRecipientWithEncryptedKey();

        $this->getCheckerManager()->checkJWE($jwe, 0);
    }
}