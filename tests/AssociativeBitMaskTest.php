<?php

namespace BitMask\Tests;

use BitMask\AssociativeBitMask;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AssociativeBitMaskTest extends TestCase
{
    public function testAssociativeBitMask()
    {
        $bitmask = new AssociativeBitMask(['r', 'w', 'x'], 6);
        $this->assertInstanceOf(AssociativeBitMask::class, $bitmask);
        $this->assertSame(['r' => false, 'w' => true, 'x' => true], $bitmask->jsonSerialize());
        $this->assertSame(6, $bitmask->get());
        try {
            $bitmask = new AssociativeBitMask([]);
            $this->assertNull($bitmask);
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Keys must be non empty', $exception->getMessage());
        }
    }

    public function testGet()
    {
        $bitmask = new AssociativeBitMask(['first']);
        $this->assertEquals(0, $bitmask->get());
    }

    public function testGetByKey()
    {
        $bitmask = new AssociativeBitMask(['r', 'w', 'x'], 5);
        $this->assertTrue($bitmask->getByKey('r'));
        $this->assertFalse($bitmask->getByKey('w'));
        $this->assertTrue($bitmask->getByKey('x'));
        try {
            $this->assertFalse($bitmask->getByKey('unknownKey'));
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Unknown key "unknownKey"', $exception->getMessage());
        }
    }

    public function testMagicMethods()
    {
        $bitmask = new AssociativeBitMask(['readable', 'writable', 'executable'], 5);
        /** __call */
        $this->assertTrue($bitmask->isReadable());
        $this->assertFalse($bitmask->isWritable());
        $this->assertTrue($bitmask->isExecutable());
        try {
            $result = $bitmask->isUnknownKey();
            $this->assertNull($result);
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Unknown key "unknownKey"', $exception->getMessage());
        }
        $this->assertNull($bitmask->unknownMethodName());

        /** __get */
        $this->assertTrue($bitmask->readable);
        $this->assertFalse($bitmask->writable);
        $this->assertTrue($bitmask->executable);
        try {
            $result = $bitmask->unknownKey;
            $this->assertNull($result);
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Unknown key "unknownKey"', $exception->getMessage());
        }

        /** __set */
        $bitmask->readable = false;
        $this->assertFalse($bitmask->readable);
        $bitmask->writable = true;
        $this->assertTrue($bitmask->writable);
        $bitmask->executable = false;
        $this->assertFalse($bitmask->executable);
        $bitmask->executable = false;
        try {
            $bitmask->unknownKey = true;
            $this->assertNull($result);
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Unknown key "unknownKey"', $exception->getMessage());
        }

        /** __isset */
        $this->assertFalse(isset($bitmask->readable));
        $this->assertTrue(isset($bitmask->writable));
    }

    public function testSet()
    {
        $bitmask = new AssociativeBitMask(['r', 'w', 'x']);
        $bitmask->set(7);
        $this->assertEquals(7, $bitmask->get());
        $bitmask->set();
        $this->assertEquals(0, $bitmask->get());
    }

    public function testSetInvalidMask()
    {
        $bitmask = new AssociativeBitMask(['r', 'w', 'x']);
        try {
            $bitmask->set(8);
        } catch (InvalidArgumentException $exception) {
            $this->assertRegExp('/Invalid given mask "[\d+]". Maximum value for [\d+] keys is [\d+]$/', $exception->getMessage());
        }
        $this->assertSame(0, $bitmask->get());
    }

    public function testUnset()
    {
        $bitmask = new AssociativeBitMask(['first'], 1);
        $bitmask->unset();
        $this->assertEquals(0, $bitmask->get());
    }

    public function testIsSet()
    {
        $bitmask = new AssociativeBitMask(['r', 'w', 'x'], 7);
        $this->assertTrue($bitmask->isSet(7));
        $bitmask->set(0);
        $this->assertFalse($bitmask->isSet(7));
    }

    public function testIsSetBit()
    {
        $bitmask = new AssociativeBitMask(['r', 'w', 'x', 's'], 7);
        $this->assertFalse($bitmask->isSetBit(8));
        $this->assertTrue($bitmask->isSetBit(4));
        $bitmask->set(15);
        $this->assertTrue($bitmask->isSetBit(8));
    }

    public function testSetBit()
    {
        $bitmask = new AssociativeBitMask(['r', 'w', 'x'], 1);
        $this->assertTrue($bitmask->isR());
        $this->assertFalse($bitmask->isW());
        $this->assertFalse($bitmask->isX());
        $bitmask->setBit(4);
        $this->assertTrue($bitmask->isSetBit(4));
        $this->assertSame(5, $bitmask->get());
        $this->assertTrue($bitmask->isR());
        $this->assertFalse($bitmask->isW());
        $this->assertTrue($bitmask->isX());
        try {
            $bitmask->setBit(3);
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Argument must be a single bit', $exception->getMessage());
        }
        $this->assertSame(5, $bitmask->get());
    }

    public function testUnsetBit()
    {
        $bitmask = new AssociativeBitMask(['read', 'write', 'execute'], 7);
        $bitmask->unsetBit(1);
        $this->assertFalse($bitmask->isSetBit(1));
        $bitmask->unsetBit(2);
        $this->assertFalse($bitmask->isSetBit(2));
        $bitmask->unsetBit(4);
        $this->assertFalse($bitmask->isSetBit(4));
        try {
            $bitmask->unsetBit(3);
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Argument must be a single bit', $exception->getMessage());
        }
        $this->assertSame(0, $bitmask->get());
    }

    public function testJsonSerialize()
    {
        $bitmask = new AssociativeBitMask(['read', 'write', 'execute'], 7);
        $this->assertSame(['read' => true, 'write' => true, 'execute' => true], $bitmask->jsonSerialize());
    }

    public function testIssue1()
    {
        $bitmask = new AssociativeBitMask(['readable', 'writable', 'executable'], 7);
        $this->assertTrue($bitmask->isReadable());
        $this->assertTrue($bitmask->isWritable());
        $this->assertTrue($bitmask->isExecutable());
        $bitmask->set(1);
        $this->assertTrue($bitmask->isReadable());
        $this->assertFalse($bitmask->isWritable());
        $this->assertFalse($bitmask->isExecutable());
    }
}
