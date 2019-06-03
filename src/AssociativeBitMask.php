<?php
declare(strict_types=1);

namespace BitMask;

use BitMask\Util\Bits;
use InvalidArgumentException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use function Safe\substr;

/**
 * Class AssociativeBitMask
 * @package BitMask
 */
class AssociativeBitMask extends IndexedBitMask
{
    /**
     * @var array $keys
     */
    protected $keys;

    /**
     * AssociativeBitMask constructor.
     * @param array $keys
     * @param int $mask
     *
     * @todo check keys. Must be valid PHP identifier name ^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$
     * @see https://www.php.net/manual/en/language.variables.basics.php
     */
    public function __construct(array $keys, int $mask = null)
    {
        if (empty($keys)) {
            throw new InvalidArgumentException('Keys must be non empty');
        }
        $this->keys = $keys;
        parent::__construct($mask);
    }

    /**
     * @param int $mask
     * @throws StringsException
     */
    final public function set(int $mask = null): void
    {
        $keysCount = count($this->keys);
        $maxValue = pow(2, $keysCount) - 1;
        if ($mask > $maxValue) {
            $message = sprintf('Invalid given mask "%d". Maximum value for %d keys is %d', $mask, $keysCount, $maxValue);
            throw new InvalidArgumentException($message);
        }
        parent::set($mask);
        for ($index = 0; $index < $keysCount - 1; $index++) {
            if (!isset($this->map[$index])) {
                $this->map[$index] = false;
            }
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws StringsException
     */
    final public function getByKey(string $key): bool
    {
        $index = array_search($key, $this->keys);
        if ($index === false) {
            throw new InvalidArgumentException(sprintf('Unknown key "%s"', $key));
        }
        return $this->map[$index];
    }

    /**
     * @param string $method
     * @param mixed[] $args
     * @return bool
     * @throws StringsException
     * @todo: Warning: Missing return statement
     */
    final public function __call(string $method, array $args)
    {
        if (!method_exists($this, $method) && strpos($method, 'is') === 0) {
            $key = lcfirst(substr($method, 2));
            return $this->getByKey($key);
        }
        return false;
    }

    /**
     * @param string $key
     * @return bool
     * @throws StringsException
     */
    final public function __get(string $key): bool
    {
        return $this->getByKey($key);
    }

    /**
     * @param string $key
     * @param bool $isSet
     * @throws StringsException
     */
    final public function __set(string $key, bool $isSet)
    {
        $state = $this->getByKey($key);
        if ($state === $isSet) {
            return;
        }
        $index = array_search($key, $this->keys);
        if (!is_int($index)) {
            throw new InvalidArgumentException('Key not found');
        }
        $bit = Bits::indexToBit($index);
        if ($isSet) {
            $this->setBit($bit);
        } else {
            $this->unsetBit($bit);
        }
    }

    /**
     * @param string $key
     * @return mixed
     * @throws StringsException
     */
    final public function __isset(string $key)
    {
        return $this->getByKey($key);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $array = [];
        foreach ($this->keys as $index => $key) {
            $array[$key] = $this->map[$index];
        }
        return $array;
    }
}
