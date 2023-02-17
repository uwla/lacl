<?php

namespace Uwla\Lacl\Contracts;

interface ResourcePolicy
{
    /**
     * Get the class name of the model associated with this resource.
     * The model must be of type Illuminate\Database\Eloquent\Model.
     *
     * @return string
    */
    public function getResourceModel();
}

?>
