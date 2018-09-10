<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


use anlutro\cURL\cURL as cURL;
use App\idfyAadharOcr;
use App\IdfyFaceCompare;

class IdfyTaskResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $cURL;

    private $IdfyAadhaarOcr;
    private $IdfyFaceCompare;

    const IDFY_URL = 'https://tasks.idfy.com/graphql';
    const IDFY_API_KEY = '494cd992-5245-4501-a7f5-4c499ac3ac70';
    const USER_AUTH = 'eb5112a6-13bf-4b4f-9c33-2ed10d6fa2fe';


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->cURL = new cURL();
        $this->IdfyFaceCompare = new IdfyFaceCompare();
        $this->IdfyAadhaarOcr = new idfyAadharOcr();

        $this->handle();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $aadharOcrRequests = $this->IdfyAadhaarOcr->getIdfyInProgressJobs(1000);
        if (!empty($aadharOcrRequests)) {
            foreach ($aadharOcrRequests as $request) {
                $this->hitIdfyAadharOcrResult($request);

            }
        }
//        $faceCompareRequests = $this->IdfyFaceCompare->getIdfyInProgressJobs(1000);
//        if (!empty($faceCompareRequests)) {
//            foreach ($faceCompareRequests as $request) {
//                $this->hitIdfyFaceCompareResult($request);
//            }
//        }

    }

    private function hitIdfyAadharOcrResult(idfyAadharOcr $request)
    {
        $query = '{"query":" query { aadhaarOcrResult(request_id: \"' . $request->idfy_request_id . '\"){   aadhaar_number, address,    date_of_birth,  district,   error,  gender, group_id,   is_scanned, message,    name_on_card,   pincode,    request_id, state,  status, street_address, task_id,    year_of_birth,  fields_match_result{name}}}"}';

        $request->status = "result_initiated";
        $request->save();

        $responseData = $this->cURL->newRawRequest('post', self::IDFY_URL, $query)
            ->setHeader('content-type', 'application/json')
            ->setHeader('apikey', self::IDFY_API_KEY)->send();

        if ($responseData->statusCode == 200) {
            $response = json_decode($responseData->body, true);
            $request->update([
                "status" => $response['data']['aadhaarOcrResult']['status'],
                "aadhaar_number" => $response['data']['aadhaarOcrResult']['aadhaar_number'],
                "gender" => $response['data']['aadhaarOcrResult']['gender'],
                "is_scanned" => $response['data']['aadhaarOcrResult']['is_scanned'],
                "name_on_card" => $response['data']['aadhaarOcrResult']['name_on_card'],
                "raw_text" => $response['data']['aadhaarOcrResult']['raw_text'],
                "year_of_birth" => $response['data']['aadhaarOcrResult']['year_of_birth'],
                "response_data" => serialize($response),
                "aadhaar_number"=> "600533812296",

			    "address"=> $response['data']['aadhaarOcrResult']['address'],
                "date_of_birth"=> $response['data']['aadhaarOcrResult']['date_of_birth'],
                "district"=>$response['data']['aadhaarOcrResult']['district'],
                "error"=> $response['data']['aadhaarOcrResult']['error'],
                "message"=> $response['data']['aadhaarOcrResult']['message'],
                "pincode"=>$response['data']['aadhaarOcrResult']['pincode'],
                "state"=> $response['data']['aadhaarOcrResult']['state'],
                "street_address"=>$response['data']['aadhaarOcrResult']['street_address'],
                "fields_match_result_name"=>$response['data']['aadhaarOcrResult']['fields_match_result']["name"]

            ]);
            $request->save();
            return $response;
        }
        $request->update(['status' => 'result_failed', 'response_data' => serialize($responseData)]);
        $request->save();
    }

    private function hitIdfyFaceCompareResult(IdfyFaceCompare $request){

        $requestData = '{"query":"{ faceCompareResult(request_id: \"' . $request->idfy_request_id . '\") { error, face_1 {quality, status}, face_2 {quality,status}, group_id, match_band, match_score, message, request_id, status, task_id}}"}';

        $responseData = $this->cURL->newRawRequest('post', self::IDFY_URL, $requestData)
            ->setHeader('content-type', 'application/json')
            ->setHeader('apikey', self::IDFY_API_KEY)->send();


        if ($responseData->statusCode == 200) {
            $response = json_decode($responseData->body, true);
            $request->update([
                "status" => $response['data']['faceCompareResult']['status'],
                "error" => $response['data']['faceCompareResult']['error'],
                "face_1_quality" => $response['data']['faceCompareResult']['face_1']['quality'],
                "face_1_status" => $response['data']['faceCompareResult']['face_1']['status'],
                "face_2_quality" => $response['data']['faceCompareResult']['face_2']['quality'],
                "face_2_status" => $response['data']['faceCompareResult']['face_2']['status'],
                "match_band" => $response['data']['faceCompareResult']['match_band'],
                "match_score" => $response['data']['faceCompareResult']['match_score'],
                "message" => $response['data']['faceCompareResult']['message'],
                "response_data" => serialize($response)
            ]);
            $request->save();
            return $response;
        }
        $request->update(['status' => 'failed', 'response_data' => serialize($responseData)]);
        $request->save();

        return false;
    }
}
