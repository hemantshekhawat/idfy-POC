<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class idfyAadharOcr extends Model
{
    protected $table = 'idfy_aadhar_ocr';

    public $fillable = ["uuid","kycCaseId","task_id", "group_id", "doc_url", "aadhaar_consent", "request_data", "idfy_request_id",
        "status", "aadhaar_number", "gender", "is_scanned", "name_on_card", "raw_text", "year_of_birth", "response_data",
    ];

    public $guarded = [];

    protected $primaryKey = 'id';

    protected $connection = 'idfy';

    /**
     * idfyAadharOcr constructor.
     */
    public function __construct($attributes = [])
    {
        // To not override the existing mass assignment attributes
        parent::__construct($attributes);
    }

    public function getIdfyInProgressJobs($records = 10){
        return $this->select(['*'])->where('status','=','in_progres')->take($records)->get();
    }

}
