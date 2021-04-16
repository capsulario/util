<?php
/*
 * MIT License
 *
 * Copyright (c) 2017 Eugene Bogachov
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Granule\Util\Tree;

use Granule\Util\Tree;

class ArrayTree implements Tree {
    /** @var array */
    protected $data;
    /** @var array */
    protected $srcPath = [];

    protected function __construct(array &$data, array $srcPath = []) {
        $this->data = &$data;
        $this->srcPath = $srcPath;
    }

    public static function fromArray(array $data, array $srcPath = []): ArrayTree {
        return new static($data, $srcPath);
    }

    public static function fromArrayByReference(array &$data, array $srcPath = []): ArrayTree {
        return new static($data, $srcPath);
    }

    public function key() {
        return key($this->data);
    }

    public function next(): void {
        next($this->data);
    }

    public function valid(): bool {
        return key($this->data) !== null;
    }

    public function current() {
        $element = &$this->data[$this->key()];

        if (!is_array($element)) {
            return $element;
        }

        $path = $this->srcPath;
        array_push($path, $this->key());

        return static::fromArrayByReference($element, $path);
    }

    public function rewind(): void {
        reset($this->data);
    }

    public function count(): int {
        return count($this->data);
    }

    public function serialize(): string {
        return serialize($this->data);
    }

    public function unserialize($serialized): ArrayTree {
        return unserialize($serialized);
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }

    public function offsetSet($offset, $value): void {
        throw new \BadMethodCallException('Immutable structure: You are not allowed to change data');
    }

    public function offsetUnset($offset) {
        throw new \BadMethodCallException('Immutable structure: You are not allowed to remove data');
    }

    public function toMutable(): MutableArrayTree {
        return MutableArrayTree::fromArrayByReference($this->data, $this->srcPath);
    }

    public function toImmutable(): ArrayTree {
        return $this;
    }

    public function toArray(): array {
        return $this->data;
    }

    public function hash(): string {
        return md5(serialize($this->data));
    }

    public function offsetExists($offset): bool {
        $offset = $this->extractKey($offset);

        return $this->find($this->data, array_shift($offset), [], $offset, function () {
            return true;
        }, function () {
            return false;
        });
    }

    public function offsetGet($offset) {
        $offset = $this->extractKey($offset);

        return $this->find($this->data, array_shift($offset), $this->srcPath, $offset, function (&$value, array $path) {
            return is_array($value) ? static::fromArrayByReference($value, $path) : $value;
        }, function (array $path) {
            throw new \OutOfBoundsException(sprintf('Element "%s" not found', implode('.', $path)));
        });
    }

    public function __invoke(string $offset, $default = null) {
        $offset = $this->extractKey($offset);

        return $this->find($this->data, array_shift($offset), $this->srcPath, $offset, function (&$value, array $path) {
            if (is_array($value)) {
                return static::fromArrayByReference($value, $path);
            } else {
                //TODO: This is example of check which we can add here for safety, it will limit names of parameter which can be used. Need to discus
                //if (function_exists($value)) {
                //    throw new \Exception('Restricted invoke of real function by similar property name');
                //}
             return $value;
            }
        }, function () use ($default) {
            return $default;
        });
    }

    protected function find(
        array &$data, string $key, array $path, array $next,
        \Closure $onFound = null, \Closure $onNotFound = null
    ) {
        $path[] = $key;
        if (array_key_exists($key, $data)) {
            if (is_array($data[$key])) {
                if (!$next) {
                    return $onFound($data[$key], $path, $data, $key); // the value
                } else {
                    return $this->find($data[$key], array_shift($next), $path, $next, $onFound, $onNotFound); // next step
                }
            } else {
                if ($next) {
                    //If need to search deeper, but it's not array, then stop nested search recursion
                    return $onNotFound(array_merge($path, $next));
                }
            }

            return $onFound($data[$key], $path, $data, $key); // also the value
        }

        return $onNotFound($path);
    }

    protected function extractKey(string $key): array {
        $key = str_replace('\.', '{{DOT}}', $key, $count);
        $key = explode('.', $key);

        if ($count) {
            foreach ($key as $index => $item) {
                $key[$index] = str_replace('{{DOT}}', '.', $item);
            }
        }

        return $key;
    }
}