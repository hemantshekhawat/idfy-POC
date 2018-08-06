<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use anlutro\cURL\cURL as cURL;


use App\idfyAadharOcr;
use App\IdfyFaceCompare;

class IdfyAadharOcrJob
{
    use Dispatchable, Queueable;

    private $cURL;

    const IDFY_URL = 'https://tasks.idfy.com/graphql';
    const USER_INFO_URL = 'https://admin.citruspay.com/service/um/principal/getUserInfo';
    const KYC_INFO_URL = 'https://admin.citruspay.com/service/um/principal/getUserInfo';
    const DOCSTORE_KYC_DOCS_URL = 'http://document-210543220.us-east-1.elb.amazonaws.com/docStore/getDocuments';

    const IDFY_API_KEY = '494cd992-5245-4501-a7f5-4c499ac3ac70';
    const USER_AUTH = 'eb5112a6-13bf-4b4f-9c33-2ed10d6fa2fe';

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->cURL = new cURL();
        $this->handle($data);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle($data): array 
    {
        foreach ($data as $key => $val) {
            $taskId = $val->request_id;
            try {
                $response = $this->getUserData($val->mobile);

                if (!empty($response->body)) {
                    $userData = json_decode($response->body, true);

                    $docs = $this->getUserDocuments($userData[$val->mobile][0]['uuid']);

                    dd($docs);
                    $aadharOcrResponse = $this->hitIdfyAadharOcrRequest($taskId, $taskId . "_aadhaar_ocr", $docs['aadhaar']['documents'][0]['s3Url']);
                    dd($aadharOcrResponse);
                    $faceCompareResponse = $this->hitIdfyFaceCompareRequest($taskId, $taskId . "_face_compare", $docs['selfie']['documents'][0]['s3Url'], $docs['aadhaar']['documents'][0]['s3Url']);

                    dd($aadharOcrResponse, $faceCompareResponse);
                }
            } catch (\Exception $e) {

            }
        }

    }

    private function getUserData($identity)
    {
        return $this->cURL->newJsonRequest('post', self::USER_INFO_URL, ["identity" => [$identity]])
            ->setHeader('Authorization', self::USER_AUTH)->send();

    }

    private function getUserDocuments($uuid)
    {
        $returnArray = [];

        $selfieUrl = $this->cURL->newJsonRequest('get', self::KYC_INFO_URL, ["uuid" => $uuid, 'documentType' => 'IPV_SELFIE'])->setHeader('Authorization', self::USER_AUTH)->send();
//        $selfieUrl = "{
//    \"responseStatus\": \"SUCCESS\",
//    \"failureCode\": null,
//    \"message\": null,
//    \"documents\": [
//        {
//            \"id\": 1845,
//            \"uuid\": \"5345292035923566921\",
//            \"documentType\": \"AADHAAR_FRONT\",
//            \"extension\": \"jpeg\",
//            \"source\": 1,
//            \"size\": 27,
//            \"s3Url\": \"https://s3.ap-south-1.amazonaws.com/document-pay-sbox/userDocuments/5345292035923566921_AADHAAR_FRONT_9f04bf67d4804772944dc9f2e142ed27.jpeg\",
//            \"dateCreated\": 1531487660000,
//            \"metadata\": null
//        }
//    ]
//}";

        if ($selfieUrl->statusCode == 200 || true) {
            $returnArray['selfie'] = json_decode($selfieUrl->body, true);
        }

        $aadharUrl = $this->cURL->newJsonRequest('get', self::KYC_INFO_URL, ["uuid" => $uuid, 'documentType' => 'AADHAAR_FRONT'])->setHeader('Authorization', self::USER_AUTH)->send();
//        $aadharUrl = "{
//    \"responseStatus\": \"SUCCESS\",
//    \"failureCode\": null,
//    \"message\": null,
//    \"documents\": [
//        {
//            \"id\": 1845,
//            \"uuid\": \"5345292035923566921\",
//            \"documentType\": \"AADHAAR_FRONT\",
//            \"extension\": \"jpeg\",
//            \"source\": 1,
//            \"size\": 27,
//            \"s3Url\": \"https://s3.ap-south-1.amazonaws.com/document-pay-sbox/userDocuments/5345292035923566921_AADHAAR_FRONT_9f04bf67d4804772944dc9f2e142ed27.jpeg\",
//            \"dateCreated\": 1531487660000,
//            \"metadata\": null
//        },
//        {
//            \"id\": 1849,
//            \"uuid\": \"5345292035923566921\",
//            \"documentType\": \"AADHAAR_FRONT\",
//            \"extension\": \"jpeg\",
//            \"source\": 1,
//            \"size\": 27,
//            \"s3Url\": \"https://s3.ap-south-1.amazonaws.com/document-pay-sbox/userDocuments/5345292035923566921_AADHAAR_FRONT_a333449e6fff42209029985fadc44e8d.jpeg\",
//            \"dateCreated\": 1531590727000,
//            \"metadata\": null
//        }
//    ]
//}";

        if ($aadharUrl->statusCode == 200) {
            $returnArray['aadhaar'] = json_decode($aadharUrl->body, true);
        }

        return $returnArray;

    }

    private function hitIdfyAadharOcrRequest($taskId, $groupId, $url_1)
    {

        $requestData = "
        mutation {
                      createAadhaarOcrTask(task: {
                        task_id:" . $taskId . ",
                        group_id:" . $groupId . ",
                        data:{
                          doc_url:" . $url_1 . ",
                          aadhaar_consent:'yes'
        }})
        {
        aadhaar_number, gender, group_id, is_scanned, name_on_card, raw_text, request_id, status, task_id, year_of_birth }
        }";

        $responseData = $this->cURL->newRawRequest('post', self::IDFY_URL, $requestData)
            ->setHeader('content-type', 'application/json')
            ->setHeader('apikey', self::IDFY_API_KEY)->send();

        dd($requestData,$responseData);

        $dbData = [
            "task_id" => $taskId,
            "group_id" => $groupId,
            "doc_url" => $url_1,
            "aadhaar_consent" => "yes",
        ];
        $idfyAadharOcr = idfyAadharOcr::firstOrNew($dbData);

        $idfyAadharOcr->fill(["request_data" => serialize($requestData), "status" => "initiated"]);
        $idfyAadharOcr->save();


        if ($responseData->statusCode == 200) {
            $response = json_decode($responseData->body, true);
            $idfyAadharOcr->fill([
                "idfy_request_id" => $response['data']['request_id'],
                "status" => $response['data']['status'],
                "aadhaar_number" => $response['data']['status'],
                "gender" => $response['data']['status'],
                "is_scanned" => $response['data']['status'],
                "name_on_card" => $response['data']['status'],
                "raw_text" => $response['data']['status'],
                "year_of_birth" => $response['data']['status'],
                "response_data" => $response['data']['status']
            ]);
            $idfyAadharOcr->save();
            return $response;
        }
        $idfyAadharOcr->update(['status' => 'failed']);
        return false;

    }

    private function hitIdfyFaceCompareRequest($taskId, $groupId, $selfie_url, $aadhar_url)
    {

        $requestData = "mutation {
              createFaceCompareTask(task: {
                task_id:" . $taskId . ",
                group_id:" . $groupId . ",
                data:{
                  url_1:" . $selfie_url . ",
                  url_2:" . $aadhar_url . "
                }
              })
              {
              error
              face_1 {quality, status}
              face_2 {quality, status}
              group_id
              match_band
              match_score
              message
              request_id
              status
              task_id
              }}";

        $responseData = $this->cURL->newJsonRequest('post', self::IDFY_URL, [$requestData])
            ->setHeader('apikey', self::IDFY_API_KEY)->send();

        $dbData = [
            "task_id" => $taskId,
            "group_id" => $groupId,
            "url_1" => $selfie_url,
            "url_2" => $aadhar_url,
        ];
        $idfyfaceCompare = IdfyFaceCompare::firstOrNew($dbData);
        $idfyfaceCompare->fill(["request_data" => serialize($requestData), "status" => "initiated"]);
        $idfyfaceCompare->save();

        if ($responseData->statusCode == 200) {
            $response = json_decode($responseData->body, true);
            $idfyfaceCompare->update([
                "idfy_request_id" => $response['data']['request_id'],
                "status" => $response['data']['status'],
                "error" => $response['data']['error'],
                "face_1_quality" => $response['data']['face_1']['quality'],
                "face_1_status" => $response['data']['face_1']['status'],
                "face_2_quality" => $response['data']['face_2']['quality'],
                "face_2_status" => $response['data']['face_2']['status'],
                "match_band" => $response['data']['match_band'],
                "match_score" => $response['data']['match_score'],
                "message" => $response['data']['message'],
                "response_data" => $response['data']['response_data']
            ]);
            return $response;
        }
        $idfyfaceCompare->update(['status' => 'failed']);

        return false;
    }

}
