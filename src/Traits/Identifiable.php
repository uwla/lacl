<?php

namespace Uwla\Lacl\Traits;

Trait Identifiable
{
    public function getModelId()
    {
        return $this->id;
    }
}
