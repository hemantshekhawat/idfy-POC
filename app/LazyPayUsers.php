<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LazyPayUsers extends Model
{
    //
    /**
     * LazyPayUsers constructor.
     */
    public function __construct()
    {
        $this->setConnection('idfy');
    }

}
