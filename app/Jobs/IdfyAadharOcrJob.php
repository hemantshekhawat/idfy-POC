<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use anlutro\cURL\cURL as cURL;


use App\idfyAadharOcr;
use App\IdfyFaceCompare;
use App\LazyPayUsers;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class IdfyAadharOcrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private $cURL;

    const IDFY_URL = 'https://tasks.idfy.com/graphql';
    const USER_INFO_URL = 'https://admin.citruspay.com/service/um/principal/getUserInfo';
    const KYC_INFO_URL = 'http://kyc-elb-310738828.ap-south-1.elb.amazonaws.com/api/kycEngine/kycStatus';
    const DOCSTORE_KYC_DOCS_URL = 'http://kyc-elb-310738828.ap-south-1.elb.amazonaws.com/api/kycEngine/ops/userDocuments';

    const IDFY_API_KEY = '494cd992-5245-4501-a7f5-4c499ac3ac70';
    const USER_AUTH = 'eb5112a6-13bf-4b4f-9c33-2ed10d6fa2fe';

    const FILTER_DOCUMENTS_TYPES = ['IPV_SELFIE', 'AADHAAR_FRONT','AADHAAR_BACK','AADHAAR_FORM'];
    private $lazyPayUsersObj;

    private $uuid;
    private $kycCaseId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->cURL = new cURL();
        $this->lazyPayUsersObj = new LazyPayUsers();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->lazyPayUsersObj->getNotPickedUpData(1);

//        dd($data);
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $val->status = LazyPayUsers::JOB_IN_PROGRESS;
                $val->save();
                $taskId = $val->request_id;
                try {
                    $response = $this->getUserData($val->mobile);

                    print_r($response);
                    if (!empty($response->body)) {
                        $userData = json_decode($response->body, true);
                        if (!empty($userData) && isset($userData[$val->mobile][0]['uuid'])) {
                            $docs = $this->getUserDocuments($userData[$val->mobile][0]['uuid']);

                            print_r($docs);
                            $val->status = LazyPayUsers::JOB_COMPLETED;
                            if (!empty($docs['aadhaar_front']) && isset($docs['aadhaar_front']['url'])) {
                                $aadharOcrResponseFront = $this->hitIdfyAadharOcrRequest($taskId . "_front", $taskId . "_aadhaar_ocr", $docs['aadhaar_front']['url'],$docs['aadhaar_form']);
                                if (empty($aadharOcrResponseFront)) {
                                    $val->status = LazyPayUsers::JOB_IDFY_AADHAR_RESPONSE_EXCEPTION;
                                }

                            } else {
                                $val->status = LazyPayUsers::JOB_AADHAR_DOCUMENTS_NOT_FOUND;
                            }
                            if (!empty($docs['aadhaar_back']) && isset($docs['aadhaar_back']['url'])) {
                                $aadharOcrResponseBack = $this->hitIdfyAadharOcrRequest($taskId . "_back", $taskId . "_aadhaar_ocr", $docs['aadhaar_back']['url'],$docs['aadhaar_form']);
                                if (empty($aadharOcrResponseBack)) {
                                    $val->status = LazyPayUsers::JOB_IDFY_AADHAR_RESPONSE_EXCEPTION;
                                }

                            } else {
                                $val->status = LazyPayUsers::JOB_AADHAR_DOCUMENTS_NOT_FOUND;
                            }

//                            if (!empty($docs['aadhaar']) && !empty($docs['selfie']) && isset($docs['aadhaar']['url']) && isset($docs['selfie']['url'])) {
//                                $faceCompareResponse = $this->hitIdfyFaceCompareRequest($taskId, $taskId . "_face_compare", $docs['selfie']['url'], $docs['aadhaar']['url']);
//                                if (empty($faceCompareResponse)) {
//                                    $val->status = LazyPayUsers::JOB_IDFY_SELFIE_RESPONSE_EXCEPTION;
//                                }
//                            } else {
//                                $val->status = LazyPayUsers::JOB_SELFIE_DOCUMENTS_NOT_FOUND;
//                            }

                            if (empty($aadharOcrResponseFront) || empty($aadharOcrResponseBack)) {
                                $val->status = LazyPayUsers::JOB_IDFY_RESPONSE_EXCEPTION;
                            }
                        } else {
                            $val->status = LazyPayUsers::JOB_UUID_NOT_FOUND;
                        }

                        $val->save();
                    }
                } catch (\Exception $e) {
//                    dd($e);
                    $val->status = LazyPayUsers::JOB_EXCEPTION;
                    $val->save();
                }
            }
        }
        exit;

    }

    private function getUserData($identity)
    {
        return $this->cURL->newJsonRequest('post', self::USER_INFO_URL, ["identity" => [$identity]])
            ->setHeader('Authorization', self::USER_AUTH)->send();

    }

    private function getUserDocuments($uuid)
    {
        $returnArray = [];
        $userKycInfo = json_decode(file_get_contents(self::KYC_INFO_URL . "?uuid=" . $uuid), true);
//        dd($userKycInfo);
        $this->uuid = $uuid;
        if (!empty($userKycInfo) && isset($userKycInfo['kycCaseId'])) {
            $this->kycCaseId = $userKycInfo['kycCaseId'];
            $userDocuments = json_decode(file_get_contents(self::DOCSTORE_KYC_DOCS_URL . "?kycCaseId=" . $userKycInfo['kycCaseId']), true);
//            dd($userDocuments);
            if (!empty($userDocuments)) {
                $allowed = self::FILTER_DOCUMENTS_TYPES;
                $userDocumentsArr = array_values(array_filter($userDocuments['userDocumentResponseList'], function ($value) use ($allowed) {
                    return in_array($value['documentTypeId'], $allowed);
                }
                    , ARRAY_FILTER_USE_BOTH
                ));

//                print_r($userDocumentsArr);
                if (!empty($userDocumentsArr)) {
                    foreach ($userDocumentsArr as $key => $val) {
                        if ('IPV_SELFIE' == $val['documentTypeId']) {
                            $returnArray['selfie'] = $val;
                        } elseif ('AADHAAR_FRONT' == $val['documentTypeId']) {
                            $returnArray['aadhaar_front'] = $val;
                        } elseif ('AADHAAR_BACK' == $val['documentTypeId']) {
                            $returnArray['aadhaar_back'] = $val;
                        } elseif ('AADHAAR_FORM' == $val['documentTypeId']) {
                            $returnArray['aadhaar_form'] = json_decode($val['value'],true);
                        }
                    }
                }
            }
        }
        return $returnArray;
    }

    private function hitIdfyAadharOcrRequest($taskId, $groupId, $url_1, $meta = null)
    {
        $requestData = '{"query":"mutation {  createAadhaarOcrTask(task: {    task_id:\"' . $taskId . '\",    group_id:\"' . $groupId . '\",    data:{      doc_url:\"' . $url_1 . '\",      aadhaar_consent:\"yes\"    }  })  {    aadhaar_number,    gender,    group_id,    is_scanned,    name_on_card,    raw_text,    request_id,    status,    task_id,    year_of_birth  }}"}';

        $dbData = [
            "uuid"=>$this->uuid,
            "kycCaseId"=>$this->kycCaseId,
            "task_id" => $taskId,
            "group_id" => $groupId,
            "doc_url" => $url_1,
            "aadhaar_consent" => "yes",
            "request_data" => serialize($requestData),
            "status" => "initiated",
            "ADDRESSLINE1_" => $meta['ADDRESSLINE1'],
            "PINCODE_" =>  $meta['PINCODE'],
            "NAME_" =>  $meta['NAME'],
            "DOB_" =>  date("Y-m-d", substr($meta['DOB'], 0, 10)),
            "GENDER_" =>  $meta['GENDER'],
            "AADHAAR_NO_" =>  $meta['AADHAAR_NO'],

        ];
        $idfyAadharOcr = idfyAadharOcr::create($dbData);

        $responseData = $this->cURL->newRawRequest('post', self::IDFY_URL, $requestData)
            ->setHeader('content-type', 'application/json')
            ->setHeader('apikey', self::IDFY_API_KEY)->send();

        print_r($responseData);
        if ($responseData->statusCode == 200) {
            $response = json_decode($responseData->body, true);
            $idfyAadharOcr->update([
                "idfy_request_id" => $response['data']['createAadhaarOcrTask']['request_id'],
                "status" => $response['data']['createAadhaarOcrTask']['status'],
                "aadhaar_number" => $response['data']['createAadhaarOcrTask']['aadhaar_number'],
                "gender" => $response['data']['createAadhaarOcrTask']['gender'],
                "is_scanned" => $response['data']['createAadhaarOcrTask']['is_scanned'],
                "name_on_card" => $response['data']['createAadhaarOcrTask']['name_on_card'],
                "raw_text" => $response['data']['createAadhaarOcrTask']['raw_text'],
                "year_of_birth" => $response['data']['createAadhaarOcrTask']['year_of_birth'],
                "response_data" => serialize($response)
            ]);
            $idfyAadharOcr->save();
            return $response;
        }
        $idfyAadharOcr->update(['status' => 'failed', 'response_data' => serialize($responseData)]);
        $idfyAadharOcr->save();
    }

    private function hitIdfyFaceCompareRequest($taskId, $groupId, $selfie_url, $aadhar_url)
    {
        $requestData = '{"query":"mutation {  createFaceCompareTask(task: {    task_id:\"' . $taskId . '\",    group_id:\"' . $groupId . '\",    data:{      url_1:\"' . $selfie_url . '\",      url_2:\"' . $aadhar_url . '\"    }  })  {  error,  face_1 {quality, status},  face_2 {quality, status},  group_id,  match_band,  match_score,  message,  request_id,  status,  task_id }}"}';

        $responseData = $this->cURL->newRawRequest('post', self::IDFY_URL, $requestData)
            ->setHeader('content-type', 'application/json')
            ->setHeader('apikey', self::IDFY_API_KEY)->send();

        $dbData = [
            "uuid"=>$this->uuid,
            "kycCaseId"=>$this->kycCaseId,
            "task_id" => $taskId,
            "group_id" => $groupId,
            "url_1" => $selfie_url,
            "url_2" => $aadhar_url,
            "request_data" => serialize($requestData),
            "status" => "initiated"
        ];
        $idfyfaceCompare = IdfyFaceCompare::create($dbData);


        if ($responseData->statusCode == 200) {
            $response = json_decode($responseData->body, true);
            $idfyfaceCompare->update([
                "idfy_request_id" => $response['data']['createFaceCompareTask']['request_id'],
                "status" => $response['data']['createFaceCompareTask']['status'],
                "error" => $response['data']['createFaceCompareTask']['error'],
                "face_1_quality" => $response['data']['createFaceCompareTask']['face_1']['quality'],
                "face_1_status" => $response['data']['createFaceCompareTask']['face_1']['status'],
                "face_2_quality" => $response['data']['createFaceCompareTask']['face_2']['quality'],
                "face_2_status" => $response['data']['createFaceCompareTask']['face_2']['status'],
                "match_band" => $response['data']['createFaceCompareTask']['match_band'],
                "match_score" => $response['data']['createFaceCompareTask']['match_score'],
                "message" => $response['data']['createFaceCompareTask']['message'],
                "response_data" => serialize($response)
            ]);
            $idfyfaceCompare->save();
            return $response;
        }
        $idfyfaceCompare->update(['status' => 'failed', 'response_data' => serialize($responseData)]);
        $idfyfaceCompare->save();

        return false;
    }

}
