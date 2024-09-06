<?php

namespace Uwla\Lacl\Traits;

Trait Identifiable
{
    /**
     * Get the model id value.
     *
     * @return string|int The id of the instance.
     */
    public function getModelId()
    {
        return $this->id;
    }
}