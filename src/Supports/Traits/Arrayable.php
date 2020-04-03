<?php

declare(strict_types=1);

namespace altairAki\Pay\Supports\Traits;

use ReflectionClass;
use altairAki\Pay\Supports\Str;

trait Arrayable
{
    /**
     * toArray.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @throws \ReflectionException
     */
    public function toArray(): array
    {
        $result = [];

        foreach ((new ReflectionClass($this))->getProperties() as $item) {
            $k = $item->getName();

            $result[Str::snake($k)] = $this->{$item->getName()};
        }

        return $result;
    }
}
