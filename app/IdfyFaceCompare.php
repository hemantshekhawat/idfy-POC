<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IdfyFaceCompare extends Model
{
    protected $table = 'idfy_face_compare';

    public $fillable=[
        "uuid",
        "kycCaseId",
        "task_id",
        "group_id",
        "url_1",
        "url_2",
        "request_data",
        "idfy_request_id",
        "status",
        "error",
        "face_1_quality",
        "face_1_status",
        "face_2_quality",
        "face_2_status",
        "match_band",
        "match_score",
        "message",
        "response_data"
    ];


    public $guarded = [];

    protected $primaryKey = 'id';

    protected $connection = 'idfy';

    /**
     * IdfyFaceCompare constructor.
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
