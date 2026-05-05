<?php

namespace App\Tests;

use App\Entity\User;
use App\Security\JwtTokenManager;
use PHPUnit\Framework\TestCase;

final class JwtTokenManagerTest extends TestCase
{
    public function testCreateAndParseToken(): void
    {
        $manager = new JwtTokenManager('test-secret', 60);

        $user = (new User())
            ->setEmail('courier@example.test')
            ->setRoles(['ROLE_COURIER'])
            ->setPassword('hashed');

        $reflection = new \ReflectionClass($user);
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($user, 123);

        $now = 1_700_000_000;
        $token = $manager->createToken($user, $now);
        $payload = $manager->parse($token, $now);

        self::assertSame(123, $payload['sub']);
        self::assertSame('courier@example.test', $payload['email']);
        self::assertContains('ROLE_COURIER', $payload['roles']);
        self::assertSame($now, $payload['iat']);
        self::assertSame($now + 60, $payload['exp']);
    }

    public function testExpiredTokenThrows(): void
    {
        $manager = new JwtTokenManager('test-secret', 1);

        $user = (new User())
            ->setEmail('courier@example.test')
            ->setRoles(['ROLE_COURIER'])
            ->setPassword('hashed');

        $reflection = new \ReflectionClass($user);
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($user, 1);

        $now = 1_700_000_000;
        $token = $manager->createToken($user, $now);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Token expired');

        $manager->parse($token, $now + 2);
    }

    public function testInvalidSignatureThrows(): void
    {
        $manager = new JwtTokenManager('test-secret', 60);

        $user = (new User())
            ->setEmail('courier@example.test')
            ->setRoles(['ROLE_COURIER'])
            ->setPassword('hashed');

        $reflection = new \ReflectionClass($user);
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($user, 1);

        $now = 1_700_000_000;
        $token = $manager->createToken($user, $now);

        $tampered = substr($token, 0, -1).(substr($token, -1) === 'a' ? 'b' : 'a');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid token signature');

        $manager->parse($tampered, $now);
    }
}

