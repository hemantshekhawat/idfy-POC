<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LazyPayUsers extends Model
{
    protected $primaryKey = 'id';

    protected $connection = 'idfy';

    const JOB_NOT_PICKED_UP = 0;
    const JOB_IN_PROGRESS = 1;
    const JOB_EXCEPTION = 2;
    const JOB_IDFY_AADHAR_RESPONSE_EXCEPTION = 3;
    const JOB_IDFY_SELFIE_RESPONSE_EXCEPTION = 4;
    const JOB_AADHAR_DOCUMENTS_NOT_FOUND = 5;
    const JOB_SELFIE_DOCUMENTS_NOT_FOUND = 6;
    const JOB_UUID_NOT_FOUND = 7;
    const JOB_COMPLETED = 9;

    public $timestamps = false;

    /**
     * LazyPayUsers constructor.
     */
    public function __construct($attributes = [])
    {
        // To not override the existing mass assignment attributes
        parent::__construct($attributes);
    }


    public function getNotPickedUpData($records = 10)
    {
        return self::select(['*'])->where('status', '=', self::JOB_NOT_PICKED_UP)->where('request_id','<', 22000)->take($records)->get();
    }

}
