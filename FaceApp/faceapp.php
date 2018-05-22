<?php
/**
 * Created by PhpStorm.
 * User: Nariman
 * Date: 17/03/2018
 * Time: 10:17 AM
 */

class FaceApp
{
    private
        $default_api_host = 'https://ph-x.faceapp.io/api/v2.11/photos',
        $default_user_agent = 'FaceApp/2.0.957 (Linux; Android 4.4)',
        $device_id_length = 8,
        $devide_id_letters = '',
        $header_options = [],
        $PhotoOptions = NULL,
        $Photo = NULL,
        $proxyHost = NULL,
        $proxyPort = NULL;

    public function __construct(string $photo_path, bool $turnOnProxy = false)
    {
        $this->devide_id_letters = substr(join('', range('a', 'z')),
            rand(0, 25 - $this->device_id_length),
            $this->device_id_length);
        $this->header_options = [
            'User-Agent:' . $this->default_user_agent,
            'X-FaceApp-DeviceID:' . $this->devide_id_letters
        ];
        if ($turnOnProxy == true) {
            $this->setProxy();
        }
        $this->PhotoOptions = $this->UploadPhoto($photo_path);
    }

    public function getPhotoCode()
    {
        return (!empty($this->PhotoOptions->code)) ? $this->PhotoOptions->code : null;
    }

    private function setProxy()
    {
        $proxys = [
            ["188.40.141.216", "3128"],
            ["5.101.99.155", "3128"],
            ["144.76.62.29", "3128"],
            ["194.88.105.156", "3128"],
            ["80.211.225.28", "8888"],
            ["80.211.201.9", "8888"],
            ["212.237.30.237", "8888"],
            ["80.211.141.177", "8888"],
            ["54.39.46.86", "3128"],
            ["65.94.93.3", "3128"],
            ["149.56.108.133", "3128"],
            ["66.70.255.195", "3128"]
        ];
        $selectedProxy = $proxys[array_rand($proxys, 1)];
        $this->proxyHost = $selectedProxy[0];
        $this->proxyPort = $selectedProxy[1];
    }

    public function getFilters()
    {
        if (!empty($this->PhotoOptions->filters)) {
            return array_map(function ($array) {
                return $array[0];
            }, $this->PhotoOptions->filters);
        } else {
            return null;
        }
    }

    public function savePhoto(string $photo_path)
    {

        if (!empty($this->Photo)) {
            file_put_contents($photo_path, $this->Photo);
            return true;
        } else {
            throw new \Exception("No Photo Has Been Created!\nFirst Run Apply_Filter() On Photo.");
        }
    }

    private function uploadPhoto($photo_path)
    {
        if (!file_exists($photo_path)) throw new \Exception("Photo Not Find!");
        if (!getimagesize($photo_path)) throw new \Exception("Input File Is NOT Photo!");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header_options);
        curl_setopt($ch, CURLOPT_URL, $this->default_api_host);
        if (!empty($this->proxyHost)) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxyPort);
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new \CURLFile($photo_path)]);
        $response = json_decode(curl_exec($ch));
        if (empty($response)) exit("no response...!");
        if (isset($response->err))
            throw new \Exception($response->err->desc);
        else {
            if(!function_exists("getFilter_id")) {
                function getFilter_id($array)
                {
                    $croped = (isset($array->only_cropped) && $array->only_cropped == 1) ? 1 : 0;
                    return [$array->id, $croped];
                }
            }

            $code = $response->code;
            $free_filters = array_map("getFilter_id", $response->objects[0]->children);
            $pro_filters = array_map("getFilter_id", $response->objects[1]->children);
            $filters = array_merge($free_filters, $pro_filters);
            return (object)['code' => $code, 'filters' => $filters];
        }
    }

    public function applyFilter(string $photoCode, string $filter, bool $crop = false)
    {
        $filters = array_map(function ($array) {
            return $array[0];
        }, $this->PhotoOptions->filters);
        if (!in_array($filter, $filters)) throw new Exception('Filter Not Found!');
        $arraykey = array_search($filter, $filters);

        if ($crop == false && $this->PhotoOptions->filters[$arraykey][1] == true)
            $crop = true;
        $crop = ($crop == true) ? 'true' : 'false';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header_options);
        if (!empty($this->proxyHost)) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxyPort);
        }
        curl_setopt($ch, CURLOPT_URL, $this->default_api_host . "/" . $photoCode . "/filters/" . $filter . "?cropped=" . $crop);
        $res = curl_exec($ch);
        if (curl_getinfo($ch)["http_code"] == 200) {
            $this->Photo = $res;
        } else {
            switch (curl_getinfo($ch)["http_code"]) {
                case 400 :
                    throw new Exception('Photo Must be Cropped', 400);
                    break;
                case 404 :
                    throw new Exception('Filter Not Found!', 404);
                    break;
                case 402 :
                    throw new Exception('Filter is not Free!', 402);
                    break;
                default :
                    throw new Exception('Unknown Error : ' . curl_getinfo($ch)["http_code"], curl_getinfo($ch)["http_code"]);
            }
        }

    }
} 