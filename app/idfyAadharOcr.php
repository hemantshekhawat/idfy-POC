<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class idfyAadharOcr extends Model
{

    public $fillable = ["task_id", "group_id", "doc_url", "aadhaar_consent", "request_data", "idfy_request_id",
        "status", "aadhaar_number", "gender", "is_scanned", "name_on_card", "raw_text", "year_of_birth", "response_data",
    ];

    /**
     * idfyAadharOcr constructor.
     */
    public function __construct()
    {
//        $this->setConnection('idfy');
    }
}
