<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IdfyFaceCompare extends Model
{
    public $fillable=[
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

    /**
     * IdfyFaceCompare constructor.
     */
    public function __construct()
    {
//        $this->setConnection('idfy');
    }


}
